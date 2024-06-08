<?php

namespace App\Services;


use App\Jobs\DeactivateTransfer;
use App\Models\CustomerUser;
use App\Models\DailyRequestTransfer;
use App\Models\MessageTelegram;
use App\Models\RequestTransfer;
use App\Models\Setting;
use App\Models\Transfer;
use App\Models\UserTelegram;
use App\Models\UserTradeAccess;
use App\Models\WordTelegram;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Telegram\Bot\FileUpload\InputFile;

class ActionServices extends TextServices
{

    public function __construct($token)
    {
        parent::__construct($token);
    }

    public function addCustomerLimit()
    {
        $customer_id = str_replace('trade_open_limit_', '', $this->getMessageCache());

        if ($customer_id) {
            $customer = CustomerUser::find($customer_id);
            $customer->limit = (int)$this->getMessage();
            $customer->update();
            $message = "اطلاعات مشتری ثبت شد";
            $message .= "\n\n";
            $message .= "نام و نام خانوادگی:";
            $message .= $customer->fullName;
            $message .= "\n\n";
            $message .= "شماره همراه:";
            $message .= $customer->mobile;
            $message .= "\n\n";
            $message .= "حد مجاز معامله ";
            $message .= "\n\n";
            $message .= $customer->limit;

            $this->telegram_services->sendMessage($this->getUserId(), $message);
            cache()->forget($this->getKeyCache() . $this->getUserId());
            cache()->forget("trade_open_" . $this->getUserId());


        } else {
            $this->telegram_services->sendMessage($this->getUserId(), "اطلاعات وارد شده مشکل دارد با ادمین سیستم تماس حاصل فرمایید یا مجددا معرفی مشتری بزنید");

        }
    }

    public function addCustomerName()
    {
        $mobile = str_replace('add_customer_name_', '', $this->getMessageCache());
        $fullName = $this->getMessage();

        logger("data add customer", [$mobile, $fullName]);
        if ($fullName && $mobile) {
            CustomerUser::updateOrCreate(["user_id" => $this->getUserId(), "mobile" => $mobile],
                [
                    "fullName" => $fullName,
                ]);
            $message = $fullName;
            $message .= "\n\n";
            $message = "پس از تایید مدیریت به لیست مشتریان شما اضافه خواهد شد ";
            $message .= "\n\n";
            $this->telegram_services->sendMessage($this->getUserId(), $message);
            cache()->forget($this->getKeyCache() . $this->getUserId());


        } else {
            $this->telegram_services->sendMessage($this->getUserId(), "اطلاعات وارد شده مشکل دارد با ادمین سیستم تماس حاصل فرمایید یا مجددا معرفی مشتری بزنید");

        }
    }

    public function addCustomer()
    {
        $pattern = '/^\+\d{1,3}\d{4,14}(?:x.+)?$/';
        $message = "شماره موبایل وارد شده نامعتبر می باشد ";
        // بررسی اینکه شماره موبایل با الگو مطابقت دارد یا خیر
        if (preg_match($pattern, $this->getMessage())) {
            $check = CustomerUser::where("mobile", $this->getMessage())->where("user_id", "!=", $this->getUserId())->first();
            // الگوی regex برای بررسی شماره موبایل با کد کشور


            if (!$check) {
                cache()->set($this->getKeyCache() . $this->getUserId(), "add_customer_name_" . $this->getMessage());
                $message = "نام مستعار مشتری خود را وارد کنید";
            } else {
                $message = "مشتری با این شماره تلفن امکان ثبت نمی باشد";
                cache()->forget($this->getKeyCache() . $this->getUserId());
            }
        }

        $this->telegram_services->sendMessage($this->getUserId(), $message);


    }

    public function addMobile()
    {

        $mobile = $this->getContact();
        logger("mobile", [$mobile]);
        if ($mobile) {
            $this->getUser()->mobile = $mobile;
            $this->getUser()->update();
            if (!$this->getUser()->fullName) {
                $text = "نام و نام خانوادگی خود را وارد کنید";
                cache()->set($this->getKeyCache() . $this->getUserId(), "add_fullName");
                $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => $text]);
            } elseif (!$this->getUser()->status) {
                cache()->set($this->getKeyCache() . $this->getUserId(), "pending_accept");
                $text = "منتظر تایید مدیر سیستم باشید تا دسترسی به شما ارائه گردد";
                $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => $text]);
            } else {
                cache()->forget($this->getKeyCache() . $this->getUserId());
            }
        } else
            $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => "شماره همراه وارد شده نامعتبر می باشد دوباره وارد کنید"]);
    }

    public function addFullName()
    {
        $this->getUser()->fullName = $this->message;
        $this->getUser()->update();
        cache()->forget($this->getKeyCache() . $this->getUserId());
        if (!$this->getUser()->mobile) {
            $text = "ممنون شماره خود را به اشتراک بگذارید";
            $this->telegram_services->sendRequestContactButton($this->getUserId(), $text);
            cache()->set($this->getKeyCache() . $this->getUserId(), "add_mobile");

        } elseif (!$this->getUser()->status) {
            cache()->set($this->getKeyCache() . $this->getUserId(), "pending_accept");
            $text = "منتظر تایید مدیر سیستم باشید تا دسترسی به شما ارائه گردد";
            $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => $text]);
        }
    }

    public function pendingAccept()
    {
        $text = "منتظر تایید مدیر سیستم باشید تا دسترسی به شما ارائه گردد";
        cache()->set($this->getKeyCache() . $this->getUserId(), "pending_accept");
        $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => $text]);
    }

    public function requestTransfer()
    {
        $array = str_replace('request_transfer_', '', $this->getData());
        $info = explode("_", $array);
        $id = data_get($info, 0);
        $num = (int)data_get($info, 1);
        logger("request", [$num, $id]);
        $transfer = Transfer::find($id);
        logger("Transfer", [$transfer]);
        $transfer_type = getTypeOrder($transfer->type);
        if ($transfer) {
            try {

                $limit_day = null;
                $use_day = null;
                $transaction_party = null;
                $request_transfer = [];
                if ($transfer->user->role == "customer")
                    $transaction_party = data_get($transfer, 'user.customerUser.headCustomer.fullName') . "(" . data_get($transfer, 'user.customerUser.fullName') . ")";
                if ($transfer->user->role == "colleague")
                    $transaction_party = data_get($transfer, 'user.fullName');

                if ($this->getUser()->role == "customer") {
                    $customer = CustomerUser::where("mobile", $this->getUser())->first();
                    if ($customer && $customer->limit)
                        $limit_day = $customer->limit;

                } elseif ($this->getUser()->role == "colleague") {
                    $user_request = UserTradeAccess::where("user_id", $this->getUserId())
                        ->where("user_trade_id", $transfer->user_id)->first();
                    $user_transfer = UserTradeAccess::where("user_id", $transfer->user_id)
                        ->where("user_trade_id", $this->getUserId())->first();

                    logger("www", [$user_request, $user_transfer]);
                    if (($user_request && $user_request->limit_access) && ($user_transfer && $user_transfer->limit_access))
                        $limit_day = min($user_request->limit_access, $user_transfer->limit_access);
                    elseif (($user_transfer && $user_transfer->limit_access))
                        $limit_day = $user_transfer->limit_access;
                    elseif (($user_request && $user_request->limit_access))
                        $limit_day = $user_request->limit_access;

                }
                logger("type_t".$transfer_type,[$transfer->user_id,$this->getUserId()]);
                $buyer_id = $transfer_type == "buy"?$transfer->user_id:$this->getUserId();
                $seller_id = $transfer_type == "sell"?$transfer->user_id:$this->getUserId();
                logger("limit_day", [$limit_day]);
                logger("limit_day", [$buyer_id,$seller_id]);
                if ($limit_day) {
                    $quantity = 0;
                    // بررسی حد معاملات
                    if($transfer_type == "buy"){
                        $query_sell = DailyRequestTransfer::where("seller_id",$seller_id)->where("buyer_id",$buyer_id)->get();
                        logger("buyyyyyy",[$query_sell]);

                        $quantity = $query_sell->sum("use_day");
                    }elseif($transfer_type == "sell") {
                        $query_buy = DailyRequestTransfer::where("seller_id", $buyer_id)->where("buyer_id", $seller_id)->get();
                        logger("seeeeeelll",[$query_buy]);
                        $quantity = $query_buy->sum("use_day");
                    }
                    logger("quantity".$quantity);
                    if($quantity)
                        $a = $limit_day + $quantity;
                        if($a <= $num)
                            $num = $a;
                    else
                        $num = $limit_day;


                    $transfer->number -= $num;
                    $request_transfer["number"] = $num;
                    $use_day = $num;
                    logger("use_day",[$use_day]);

                    $request_transfer["status"] =  $transfer->number==0 ?"complete":"half";
                } else {
                    logger("check", [$transfer->number, $num, $transfer->number >= $num]);
                    if ($transfer->number >= $num) {
                        $transfer->number -= $num;
                        $request_transfer["number"] = $num;

                        $request_transfer["status"] = $num== $transfer->number ?"complete":"half";
                        logger("request", [$request_transfer]);
                        $use_day = $num;
                    }
                }


                if (data_get($request_transfer, "number")) {
                    logger("number" . data_get($request_transfer, "number"));
                    DailyRequestTransfer::updateOrCreate([
                        "seller_id" => $seller_id,
                        "buyer_id" => $buyer_id,
                    ], [
                        "use_day" => $use_day,
                    ]);

                    $keyboard = self::getKeyboardRequest($transfer);

                    $trade_message = $transfer->message;
                    if ($transfer->number == 0) {
                        $trade_message .= "\xE2\x9C\x85	🤝🏼";
                        $transfer->status = Transfer::STATUS_ACTIVE_DONE;
                        $transfer->update();
                    }

                    $this->telegram_services->editMessageTextAndInlineKeyboard($this->bot->chanel_id, $transfer->message_id, $trade_message, $keyboard);
                    $transfer->update();

                    $request_transfer["remittance_number"] = generateUniqueSixDigitCode();
                    $request_transfer["request_id"] = $this->getUserId();
                    $request_transfer["transfer_id"] = $transfer->user_id;
                    $request_transfer["price"] = $transfer->price;

                    RequestTransfer::create($request_transfer);

                    $message = $transfer->message_request_me;
                    $message .= "\n\n";
                    $message .= "مقدار:" . data_get($request_transfer, "number") . "کیلو";
                    $message .= "\n\n";
                    $message .= "نوع:" . getTypeTransfer($transfer->type);
                    $message .= "\n\n";
                    $message .= "طرف معامله:" . $transaction_party;
                    $message .= "\n\n";
                    $message .= "برای:" . toJalali($transfer->date, "Y/m/d");
                    $message .= "\n\n";
                    $message .= "       شماره حواله:" . data_get($request_transfer, 'remittance_number');
                    logger("message", [$message]);
                    $this->telegram_services->sendMessage($this->getUserId(), $message);


                    $message = $transfer->message_request;
                    $message .= "\n\n";
                    $message .= "مقدار:" . data_get($request_transfer, "number") . "کیلو";
                    $message .= "\n\n";
                    $message .= "نوع:" . getTypeTransfer($transfer->type);
                    $message .= "\n\n";
                    $message .= "طرف معامله:" . $this->getUser()->fullName;
                    $message .= "\n\n";
                    $message .= "برای:" . toJalali($transfer->date, "Y/m/d");
                    $message .= "\n\n";
                    $message .= "       شماره حواله:" . data_get($request_transfer, 'remittance_number');
                    $this->telegram_services->sendMessage($transfer->user_id, $message);

                } else {
                    $this->telegram_services->sendMessage($this->getUserId(), "متأسفانه امکان دریافت حواله برای شما در این معامله نمی باشد");

                }
            } catch (\Exception $exception) {

                logger("exp", [$exception->getMessage(),
                    $exception->getLine(),
                    $exception->getCode(),
                    $exception->getTrace(),
                    $exception->getFile()]);
            }
        }
    }

    public function transferBuy()
    {
        $data = str_replace('transfer_buy_', '', $this->getData());
        $array = explode("_", $data);
        $check = data_get($array, 0);
        $word_id = data_get($array, 1);
        logger("data", [$check, $word_id]);
        if (!$check && !$word_id)
            return false;
        $word = WordTelegram::find($word_id);
        logger("word", [$word]);

        if ($word == null) return false;

        if ($check == "true") {
            Transfer::where("user_id", $this->getUserId())
                ->where("type", data_get($word, "type"))
                ->delete();
            $word->status = WordTelegram::STATUS_ACCEPT;
            $word->update();
            $order = [
                "status" => Transfer::STATUS_ACTIVE,
                "user_id" => $this->getUserId(),
                "type" => data_get($word, "type"),
                "number" => (int)data_get($word, "number"),
                "price" => data_get($word, "price"),
                "message" => data_get($word, "message"),
                "date" => data_get($word, "date"),
                "message_request" => data_get($word, "message_request"),
                "message_request_me" => data_get($word, "message_request_me"),
            ];

            logger("order", [
                "status" => Transfer::STATUS_ACTIVE,
                "user_id" => $this->getUserId(),
                "type" => data_get($word, "type"),
                "number" => (int)data_get($word, "number"),
                "price" => data_get($word, "price"),
                "message" => data_get($word, "message"),
                "date" => data_get($word, "date"),
                "message_request" => data_get($word, "message_request"),
            ]);
            $transfer_new = Transfer::create($order);
            logger("a", [$this->getUserId(), $this->getMessageId(), []]);
            $this->telegram_services->editMessageReplyMarkup($this->getUserId(), $this->getMessageId(), new \stdClass());
            $this->telegram_services->sendMessage($this->getUserId(), "لفظ شما تایید شد\xE2\x9C\x85	");
            $message = $transfer_new->message;
            $keyboard = $this->getKeyboardRequest($transfer_new);

            logger("test", [$this->bot->chanel_id, $message, $keyboard]);
            $message_result = $this->telegram_services->MessageReplyMarkup($this->telegram, $this->bot->chanel_id, $message, $keyboard);
            $transfer_new->message_id = $message_result;
            $transfer_new->update();
            dispatch(new DeactivateTransfer($transfer_new->id))->delay(now()->addMinute(1));
        } elseif ($check == "false") {
            $word->status = WordTelegram::STATUS_REJECT;
            $word->update();
            $this->telegram_services->editMessageReplyMarkup($this->getUserId(), $this->getMessageId(), new \stdClass());
            $this->telegram_services->sendMessage($this->getUserId(), "لفظ شما رد شد\xE2\x9D\x8C	");

        }
    }

    public function tradeLimitClose()
    {
        $array = explode("_", str_replace('trade_limit_close_', '', $this->getData()));
        $worker_id = (int)data_get($array, 0);
        $page = (int)data_get($array, 1);

        $worker = UserTelegram::where("id", $worker_id)->first();
        logger("worker", [$worker_id, $worker, $page]);
        if ($worker) {
            $limit_access = UserTradeAccess::where("user_id", $this->getUserId())
                ->where("user_trade_id", $worker->id)->first();
            if ($limit_access) {
                $name_worker = $worker->fullName ?: $worker->first_name . " " . $worker->last_name;

//                $message_menu = cache()->get("menu_" . $this->getUserId());
//                if ($message_menu) {
//                    $keyboard = data_get($message_menu, "keyboard");
//                    $text = $worker->fullName ?: $worker->first_name . " " . $worker->last_name;
//                    $keyboard[$worker_i] = [[
//                        'text' => "  $text " . "\xE2\x9C\x85",
//                        'callback_data' => "trade_limit_" . $worker->id
//                    ]];
//                    logger("close", [$this->getUserId(), data_get($message_menu, "id"), "لیست همکاران", $keyboard]);
//                    $this->telegram_services->editMessageTextAndInlineKeyboard($this->getUserId(), data_get($message_menu, "id"), "لیست همکاران", $keyboard);
//                }
                $data_old = cache()->get("menu_List_worker_" . $this->getUserId());
                logger("menu_List_worker_", [$data_old]);
                $message_id = data_get($data_old, "id", null);
                $limit_access->delete();
                $this->listWorker($page, $message_id);
                $this->telegram->sendMessage([
                    'chat_id' => $this->getUserId(),
                    'text' => "حد مجاز برای $name_worker نا محدود شد "
                ]);
            }
        }
    }

    public function tradeLimit()
    {
        $data = str_replace('trade_limit_', '', $this->getData());
        $data = explode("_", $data);
        $worker_id = (int)data_get($data, 0);
        $page = (int)data_get($data, 1);
        $worker = UserTelegram::where("id", $worker_id)->first();
        logger("worker", [$worker]);
        if ($worker) {
            $name_worker = $worker->fullName ?: $worker->first_name . " " . $worker->last_name;

            $this->telegram->sendMessage([
                'chat_id' => $this->getUserId(),
                'text' => "حد مجازی که می خواهید با $name_worker داشته باشید را وارد کنید "
            ]);
            cache()->set($this->getKeyCache() . $this->getUserId(), ["title" => "trade_number_limit", "value" => $worker->id, "page" => $page]);
        }
    }

    public function tradeOpen()
    {
        $customer_id = str_replace('trade_open_', '', $this->getData());
        $message_id = cache()->get("trade_open_" . $this->getUserId());
        if ($customer_id && $message_id) {
            $customer = CustomerUser::find($customer_id);
            $keyboard[0] = [
                ['text' => "\xF0\x9F\x94\x90	حد مجاز", 'callback_data' => "trade_open_limit_$customer_id"],
                ['text' => "\xF0\x9F\x93\x9C	گزارش", 'callback_data' => "trade_open_report_$customer_id"],
            ];
            $message = "یکی از گزینه های زیر برای مشتری ";
            $message .= "\n\n ";
            $message .= $customer->fullName;
            $this->telegram_services->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $message, $keyboard);
        }

    }

    public function tradeOpenLimit()
    {
        $customer_id = str_replace('trade_open_limit_', '', $this->getData());
        $message_id = cache()->get("trade_open_" . $this->getUserId());
        if ($customer_id && $message_id) {
            $customer = CustomerUser::find($customer_id);
            logger("customer", [$customer, $customer_id, $message_id]);
            if ($customer) {
                $message = "حد مجاز برای مشتری ";
                $message .= "\n\n ";
                $message .= $customer->fullName;
                $this->telegram_services->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $message);
                cache()->set($this->getKeyCache() . $this->getUserId(), $this->getData());
            }
        }

    }

    public function tradeOpenReport()
    {
        $customer_id = str_replace('trade_open_report_', '', $this->getData());
        $message_id = cache()->get("trade_open_" . $this->getUserId());
        if ($customer_id && $message_id) {
            $customer = CustomerUser::find($customer_id);

            $today = now()->format("Y-m-d");
            $tomorrow = now()->addDay(1)->format("Y-m-d");
            $keyboard[0] = [
                ['text' => toJalali(now(), "Y/m/d"), 'callback_data' => "trade_open_report_date_" . $customer_id . "_" . $today],
                ['text' => toJalali(now()->addDay(1), "Y/m/d"), 'callback_data' => "trade_open_report_date_" . $customer_id . "_" . $tomorrow],
            ];
            $message = ' گزارش ';
            $message .= $customer->fullName;
            $message .= "تاریخ های زیر را انتخاب کنید";
            $message .= "\n\n ";
            $this->telegram_services->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $message, $keyboard);
        }

    }

    public function tradeOpenReportDate()
    {
        $data = str_replace('trade_open_report_date_', '', $this->getData());
        $array = explode("_", $data);
        $customer_id = data_get($array, 0);
        $date = data_get($array, 1);
        $message_id = cache()->get("trade_open_" . $this->getUserId());
        if ($customer_id && $message_id) {
            $customer = CustomerUser::with("user")->find($customer_id);

            $date_p = toJalali($date, "Y_m_d");
            $message = ' گزارش ';
            $message .= $customer->fullName;
            $message .= "  تاریخ   " . toJalali($date, "Y/m/d");
            $message .= "\n\n ";
            $this->telegram_services->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $message);
            $request_transfer = RequestTransfer::with("transfer.user")->where("request_id", data_get($customer, "user.id"))->get();
            if ($request_transfer->count()) {
                $pdf = Pdf::loadView('users.report_pdf', compact('date_p', 'request_transfer', 'customer'));
                $name_file = $customer_id . "_" . $date_p . ".pdf";
                $path_report = storage_path("app/public/report/" . $this->getUserId() . "/" . $name_file);
                $pdf->save($path_report);

                $response = $this->telegram->sendDocument([
                    'chat_id' => $this->getUserId(),
                    'document' => InputFile::create($path_report, "$date_p.pdf")
                ]);
            } else {
                $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => 'معاله ای در این تاریخ انجام نشده']);
            }

        }

    }

    public function tradeNumberLimit()
    {
        $data_cache = $this->getMessageCache();

        $number = (int)$this->convertNumber($this->getMessage());
        if (is_numeric($number)) {
            $worker_id = (int)data_get($data_cache, "value");
            $page = (int)data_get($data_cache, "page");
            UserTradeAccess::updateOrCreate([
                "user_id" => $this->getUserId(),
                "user_trade_id" => $worker_id,],
                [
                    "limit_access" => $number
                ]);
            $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => 'حد ثابت شد']);
            $data_old = cache()->get("menu_List_worker_" . $this->getUserId());
            $message_id = data_get($data_old, "id", null);
            $this->listWorker($page, $message_id);
            cache()->forget($this->getKeyCache() . $this->getUserId());
        } else {
            $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => 'عدد وارد کنید']);
        }
    }

    public function rejectAll()
    {
        $result = false;
        $transfers = Transfer::where("user_id", $this->getUserId())
            ->whereIn("status", [Transfer::STATUS_ACTIVE, Transfer::STATUS_ACTIVE_DO])
            ->get();
        $i = 0;
        foreach ($transfers as $transfer) {
            $message = $transfer->message . "\xF0\x9F\x9A\xAB";

            logger("tran" . $message);
            $this->telegram_services->editMessageTextAndInlineKeyboard($this->bot->chanel_id, $transfer->message_id, $message);
            $transfer->status = Transfer::STATUS_DEACTIVATE;
            $transfer->update();
            $transfer->delete();
            $i++;
        }
        logger("w", [$transfers->count(), $i == $transfers->count()]);
        if ($transfers->count() && $i == $transfers->count())
            return true;
        return $result;
    }

    public function checkWord()
    {
        $limit_trade = cache()->remember("s_price_trade", now()->addDay(1), function () {
            $value = ["start" => 14000000, "end" => 15000000];
            $setting = Setting::where("key", "s_price_trade")->first();
            if ($setting)
                $value = data_get($setting, "value");
            return $value;
        });
        logger("limit_trade", [$limit_trade]);


        $suggest_price = $this->getPrice();

        // طول رشته عدد را محاسبه کنید
        $length = strlen($suggest_price);

        // بررسی کنید که آیا طول عدد 3 یا 5 است
        if ($length === 3)
            $start_trade = ((int)data_get($limit_trade, "start") / 1000000) * 1000000;
        elseif ($length === 5)
            $start_trade = ((int)($suggest_price * 1000) / 1000000) * 1000000;


        $price = $start_trade + ($suggest_price * 1000);

        $number = $this->getNumberOrder();

        $type_transaction = in_array($this->getType(), $this->list_type_buy) ? "buy" : "sell";
        $check_transfer = Transfer::where("price", $type_transaction == "buy" ? ">" : "<", $price)
            ->where("status", Transfer::STATUS_ACTIVE)
            ->where("type", $this->getType())
//            ->orWhere(function ($query) {
//                $query->whereIn("status", [
//                    Transfer::STATUS_ACTIVE_DO,
//                    Transfer::STATUS_ACTIVE_DONE
//                ])->where("updated", ">", now()->subMinute(1));
//            })
            ->first();
        if ($check_transfer) {
//            $message = "قیمت پیشنهادی بهتری از لفظ شمادر کانال میباشد\n\n";
//            $message .= "لطف اگر پیشنهاد بهتری دارید مجددا لفظ دهید یا \n\n";
//            $message .= "حداکثر با تلرانس ۲۰۰۰ تومان لفظ دهید \n\n";
            $message = "لفظ پیشنهادی بهتر در کانال : \n\n";
            $message .= " \n\n";
            $message .= number_format($check_transfer->price, 0);
            $this->telegram_services->sendMessage($this->getUserId(), $message);
        } else {

            $price_format = number_format($price, 0);
            $message = $price_format;
            $message_request = null;
            if (in_array($this->getType(), $this->list_type_buy)) {
                $message .= " \xF0\x9F\x94\xB5	خرید";
                $message_request = " \xF0\x9F\x94\xB5	خرید";
                $message_request_me = " \xF0\x9F\x94\xB4	فروش";
            } elseif (in_array($this->getType(), $this->list_type_sell)) {
                $message .= " \xF0\x9F\x94\xB4	فروش";
                $message_request = " \xF0\x9F\x94\xB4	فروش";
                $message_request_me = " \xF0\x9F\x94\xB5	خرید";

            }
            $time = Carbon::now();
            $morning = Carbon::create($time->year, $time->month, $time->day, 9, 0, 0); //set time to 08:00
            $none = Carbon::create($time->year, $time->month, $time->day, 15, 00, 0); //set time to 18:00
            logger("check day", [
                $time->between($morning, $none, true),
                (
                    !in_array($this->getType(), $this->list_type_buy_tommarow) ||
                    !in_array($this->getType(), $this->list_type_sell_tommarow)
                )
            ]);
            if ($time->between($morning, $none, true) && !in_array($this->getType(), $this->list_type_tommarow)) {
                $message .= " \xE2\x98\x80	";
                $message_request .= " \xE2\x98\x80	";
                $message_request_me .= " \xE2\x98\x80	";
                $date = now()->format("Y-m-d");
            } else {
                $message .= " \xE2\x8F\xB3	";
                $message_request .= " \xE2\x8F\xB3	";
                $message_request_me .= " \xE2\x8F\xB3	";
                $date = now()->addDay(1)->format("Y-m-d");
            }

            $message_request .= "\n\n";
            $message_request .= "فی:";
            $message_request .= number_format($price, 0);
            $message_request_me .= "\n\n";
            $message_request_me .= "فی:";
            $message_request_me .= number_format($price, 0);
            if (str_contains($this->getType(), "ن")) {
                $message .= " بی حواله ";
                if (!$time->between($morning, $none, true) ||
                    in_array($this->getType(), $this->list_type_sell_n_buy_tom))
                    $message .= " فردا ";
                $message .= "\xF0\x9F\x92\xB0	\xF0\x9F\x92\xB5	";

            } else
                $message .= "با حواله";
            $message .= $number;
            $message .= " تا ";
            if (str_contains($this->getType(), "ش"))
                $message .= " شنا ";


            if ($this->getDescription()) {
                $message .= "\n\n ";
                $message .= "توضیحات ";
                $message .= "\xE2\x9D\x97 : ";
                $message .= $this->getDescription();
            }
            $word_telegram = WordTelegram::create([
                "user_id" => $this->getUserId(),
                "message" => $message,
                "status" => WordTelegram::STATUS_PENDING,
                "type" => $this->getType(),
                "number" => (int)$number,
                "price" => $price,
                "date" => $date,
                "message_request" => $message_request,
                "message_request_me" => $message_request_me
            ]);
            logger("word", [$word_telegram]);
            $keyboard[0] = [
                ['text' => "\xE2\x9C\x85	تایید", 'callback_data' => "transfer_buy_true_$word_telegram->id"],
                ['text' => "\xE2\x9D\x8C	رد", 'callback_data' => "transfer_buy_false_$word_telegram->id"],
            ];
            logger("ke", [$this->getUserId(), $message, $keyboard]);
            $result_word = $this->telegram_services->MessageReplyMarkup($this->telegram, $this->getUserId(), $message, $keyboard, false);
            if ($result_word) {
                $word_telegram->message_id = $result_word;
                $word_telegram->update();
            } else {
                $word_telegram->delete();
            }
            return true;
        }
    }

    /**
     * @param mixed $number
     * @param \Illuminate\Database\Eloquent\Model|Transfer $transfer_new
     * @return mixed
     */
    public static function getKeyboardRequest(Transfer $transfer_new): mixed
    {
        $m = 0;
        $k = 0;
        $number = $transfer_new->number;
        $keyboard = [];
        for ($i = 1; $i <= $number; $i++) {
            $keyboard[$k][$m++] = [
                'text' => $i,
                'callback_data' => "request_transfer_" . $transfer_new->id . "_" . $i,
            ];
            if ($m == 3) {
                $m = 0;
                $k++;
            }
        }
        if (!$keyboard)
            $keyboard = null;
        logger("key", [$keyboard]);
        return $keyboard;
    }
}
