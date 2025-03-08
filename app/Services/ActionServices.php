<?php

namespace App\Services;


use App\Jobs\DeactivateTransfer;
use App\Jobs\DeactivateWord;
use App\Jobs\PartiesToTheTransaction;
use App\Jobs\SendAcceptWordAccounting;
use App\Jobs\SendAcceptWordPublicChannel;
use App\Jobs\SendMessageAccountingBot;
use App\Models\AccessBot;
use App\Models\Bot;
use App\Models\CustomerUser;
use App\Models\DailyRequestTransfer;
use App\Models\MessageTelegram;
use App\Models\MessageWordPublic;
use App\Models\RequestTransfer;
use App\Models\Setting;
use App\Models\Transfer;
use App\Models\UglyWord;
use App\Models\UserTelegram;
use App\Models\UserTradeAccess;
use App\Models\WordTelegram;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Keyboard\Keyboard;

class ActionServices extends TextServices
{

    protected $service_customer;


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
            $message .= "\n";
            $message .= "نام و نام خانوادگی:";
            $message .= $customer->fullName;
            $message .= "\n";
            $message .= "شماره همراه:";
            $message .= $customer->mobile;
            $message .= "\n";
            $message .= "حد مجاز معامله ";
            $message .= "\n";
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

        if ($fullName && $mobile) {
            CustomerUser::updateOrCreate(["user_id" => $this->getUserId(), "mobile" => $mobile],
                [
                    "fullName" => $fullName,
                ]);
            $message = $fullName;
            $message .= "\n";
            $message = "پس از تایید مدیریت به لیست مشتریان شما اضافه خواهد شد ";
            $message .= "\n";
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
            $check = CustomerUser::where("mobile", $this->getMessage())
                ->where("user_id", "!=", $this->getUserId())
                ->first();
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
        if ($mobile) {
            $this->getUser()->mobile = $mobile;
            $this->getUser()->update();
            $this->setBotAdmin();
            $keyboard[] = [
                ["text" => "تایید", "callback_data" => "ok_user_" . $this->getUserId()],
                ["text" => "رد", "callback_data" => "reject_user_" . $this->getUserId()]
            ];
            $reply_markup = Keyboard::make([
                'inline_keyboard' => $keyboard,
                'resize_keyboard' => true,
                'one_time_keyboard' => true
            ]);
            $text = " کاربر  ";
            $text .= $this->getUser()->fullName;
            $text .= " می خواهد وارد سیستم شود ";
            $admins = AccessBot::where("type", "admin")->get();
            foreach ($admins as $admin) {
                $message_admin = $this->getBotAdmin()->sendMessage(
                    [
                        'chat_id' => $admin->user_id,
                        'text' => $text,
                        'reply_markup' => $reply_markup,

                    ]
                );
                cache()->set("message_admin_" . $admin->user_id, $message_admin);
            }
            if (!$this->getUser()->fullName) {
                $text = "لطفا نام و نام خانوادگی خود را وارد نمایید";
                cache()->set($this->getKeyCache() . $this->getUserId(), "add_fullName");
                $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => $text]);

            }
            $text = "منتظر تایید مدیر سیستم باشید تا دسترسی به شما ارائه گردد";
            $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => $text]);
            cache()->forget($this->getKeyCache() . $this->getUserId());
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

    public function ruleAccept()
    {
        $this->getUser()->update(["accept_rule" => now()->format("Y-m-d H:i")]);
        $text = "لطفا راهنما را مطالعه فرمایید";
        $help = Setting::where("key", "help")->first();

        $text .= $help ? $help->value : "";
        $keyboard[0][0] = ['text' => "راهنما را خواندم و یاد گرفتم"];
        TelegramServices::menu($this->telegram, $keyboard, $this->getUser(), $text);
//        $message_id = cache()->get("rule_accept". $this->getUserId());
//        $this->telegram_services->deleteKeyboard($this->getUserId(), $text);
//        $this->getTelegramServices()->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $text, []);
//        cache()->forget("rule_accept". $this->getUserId());
    }

    public function helpAccept()
    {
//        $text = "اطلاعات شما برای مدیر سیستم ارسال شد پس از تایید شما در گروه اضافه می شوید";
        $this->getUser()->update(["accept_help" => now()->format("Y-m-d H:i")]);
//        cache()->set($this->getKeyCache() . $this->getUserId(), "pending_accept");
//        $message_id = cache()->get("rule_accept". $this->getUserId());
//        $this->telegram_services->deleteKeyboard($this->getUserId(), $text);
        $this->getUser()->change_menu = true;
        $this->getUser()->update();
        $keyboard_menu = $this->setMenu();
        $this->message_menu = "خوش آمدید، از این لحظه منو کاربری فعال شد";
        $this->menu($keyboard_menu, $this->getUser()->status, $this->getUser());

//        $this->getTelegramServices()->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $text, []);
//        cache()->forget("rule_accept". $this->getUserId());
    }

    public function requestTransfer()
    {
        if (!$this->getUser()->status) {
            $this->telegram_services->sendMessage($this->getUserId(), "اکانت کاربری شما غیر فعال می باشد");
            return true;
        }
        $array = str_replace('request_transfer_', '', $this->getData());
        $info = explode("_", $array);
        $id = data_get($info, 0);
        $num = (int)data_get($info, 1);

        if ($this->getUser()->verify_two && !cache()->get("double_click_" . $id . "_" . $this->getUserId())) {
            cache()->set("double_click_" . $id . "_" . $this->getUserId(), 1, now()->addSecond(5));
            return true;
        } elseif ($this->getUser()->verify_two && cache()->get("double_click_" . $id . "_" . $this->getUserId()))
            cache()->forget("double_click_" . $id . "_" . $this->getUserId());
        logger("transfer".$id."=>".time()." - ".$this->getUserId());

        DB::beginTransaction();
        try {
            logger("transfer".$id."=>".time()." - ".$this->getUserId());
            $transfer = Transfer::with(["user" => function ($query) {
                $query->with("customer.userTradeAccess");
                $query->with("customerUser");
            }])
                ->lockForUpdate()
                ->where("number", ">", 0)->find($id);

            logger("tr",[$transfer]);

            if ($transfer) {

                if ($transfer->status_transaction) {
                    $this->sendAlert("درحال ارائه به شخص دیگر می باشد  ... \xE2\x9A\xA0	");
                    DB::rollBack();
                    return true;
                }
                if ($transfer->user_id == $this->getUser()->id) {
                    $alert_text = "شما نمی توانید لفظ خود را دریافت کنید";
                    $this->sendAlert($alert_text);
//                $this->telegram_services->sendMessage($this->getUserId(), "شما نمی توانید لفظ خود را دریافت کنید");
                    DB::rollBack();
                    return true;
                }
                $transfer_type = getTypeOrder($transfer->type);
                $createdAt = $transfer->created_at;
                $transfer->status_transaction = true;
                $transfer->save();
                $now = Carbon::now();
                $diffInSeconds = $now->diffInSeconds($createdAt);
                if ($diffInSeconds > 60) {
//                $message = $transfer->message ;
//                $edit_message = $this->getTelegramServices()->editMessageTextAndInlineKeyboard($this->bot->chanel_id, $transfer->message_id, $message);
                    $transfer->delete();
//                $this->telegram_services->sendMessage($this->getUserId(), "زمان دریافت لفظ تمام شده");
                    $this->sendAlert("زمان دریافت لفظ تمام شده");
                    DB::commit();
                    return true;
                }
                $forbidden = Setting::where("key", "forbidden")->where("value", true)->first();

                $limit_day = null;
                $transaction_party = null;
                $transaction_party_s = null;
                $transaction_party_req = null;
                $transaction_party_req_s = null;
                $request_transfer = [];

                if ($this->getUser()->role == "customer") {
                    $head = data_get($this->getUser(), "customer");
                    if (!$head) {
                        $this->checkTransaction($transfer);
                        $this->telegram_services->sendMessage($this->getUserId(), "شما نمی توانید لفظ  دریافت کنید");
                        DB::rollBack();
                        return true;
                    }
                } else
                    $head = $this->getUser();

                if (data_get($transfer, "user.role") == "customer")
                    $colleague = data_get($transfer, "user.customer");
                else
                    $colleague = data_get($transfer, "user");


                $access_limit_head = data_get($head, 'userTradeAccess');
                $access_limit_transaction = data_get($colleague, "userTradeAccess");

                if ($access_limit_head)
                    $user_request = $access_limit_head->where("user_trade_id", $colleague->id)->first();
                if ($access_limit_transaction)
                    $user_transfer_limit = $access_limit_transaction->where("user_trade_id", $head->id)->first();

                if (($user_request && $user_request->limit_access >= 0) && ($user_transfer_limit && $user_transfer_limit->limit_access >= 0))
                    $limit_day = min($user_request->limit_access, $user_transfer_limit->limit_access);
                elseif (($user_transfer_limit && $user_transfer_limit->limit_access >= 0))
                    $limit_day = $user_transfer_limit->limit_access;
                elseif (($user_request && $user_request->limit_access >= 0))
                    $limit_day = $user_request->limit_access;

                if ($this->getUser()->role == "customer") {
                    if ($forbidden && data_get($transfer, "user.role") == "colleague") {
                        $list_worker = $head->customerUsers->pluck("id")->toArray();
                        $list_worker[] = data_get($head, "id");
                        if (in_array($this->getUser()->id, $list_worker)) {
                            $this->checkTransaction($transfer);
                            $this->telegram_services->sendMessage($this->getUserId(), "\xE2\x9D\x8C	استثنائا در این دقایق خاص بصورت موقت امکان گرفتن لفظ سرگروه و زیر مجموعه خودش امکان پذیر نمی باشد\xE2\x9D\x8C	");
                            DB::rollBack();
                            return true;
                        }
                    }
                } elseif ($this->getUser()->role == "colleague") {
                    if ($forbidden && data_get($transfer, "user.role") == "customer") {
                        if (in_array($this->getUserId(), $this->getUser()->customerUsers)) {
                            $this->checkTransaction($transfer);
                            $this->telegram_services->sendMessage($this->getUserId(), "\xE2\x9D\x8C	استثنائا در این دقایق خاص بصورت موقت امکان گرفتن لفظ سرگروه و زیر مجموعه خودش امکان پذیر نمی باشد\xE2\x9D\x8C	");
                            DB::rollBack();
                            return true;
                        }
                    }
                }
                $buyer = $transfer_type == "buy" ? $transfer->user : $this->getUser();
                $seller = $transfer_type == "sell" ? $transfer->user : $this->getUser();
                $buyer_id = $transfer_type == "buy" ? $transfer->user_id : $this->getUser()->id;
                $seller_id = $transfer_type == "sell" ? $transfer->user_id : $this->getUser()->id;

                if ($limit_day !== null) {
                    [$daily_transfer, $num] = $this->performTransaction($seller, $buyer, $num, $limit_day);
                    if (!$num) {
                        $this->sendAlert("امکان دریافت ندارید محدودیت توسط طرفین اعمال گردید است.\xE2\x9A\xA0	");
                        $this->checkTransaction($transfer);
                        DB::rollBack();
                        return true;
                    }
                    $transfer->number -= $num;
                    $request_transfer["number"] = $num;
                    $use_day = $num;
                    $request_transfer["status"] = $transfer->number == 0 ? "complete" : "half";
                } else {
                    if ($transfer->number >= $num) {
                        $transfer->number -= $num;
                        $request_transfer["number"] = $num;
                        $request_transfer["status"] = $transfer->number == 0 ? "complete" : "half";
                        $use_day = $num;
                        if (data_get($request_transfer, "number")) {
                            $daily_transfer = DailyRequestTransfer::updateOrCreate([
                                "seller_id" => $seller_id,
                                "buyer_id" => $buyer_id,
                            ], [
                                "use_day" => $use_day,
                            ]);
                        }
                    }
                }

                if (data_get($request_transfer, "number") > 0) {
                    $keyboard = self::getKeyboardRequest($transfer);

                    $trade_message = $transfer->message;
                    if ($transfer->number == 0) {
                        $trade_message .= "\xE2\x9C\x85	🤝🏼";
                        $transfer->status = Transfer::STATUS_ACTIVE_DONE;
                        $transfer->update();
                    } else {
                        $trade_message .= "(" . $transfer->number . " مانده )	🤝🏼";
                        $transfer->status = Transfer::STATUS_ACTIVE_DO;
                        $transfer->update();
                    }

                    $this->telegram_services->editMessageTextAndInlineKeyboard($this->bot->chanel_id, $transfer->message_id, $trade_message, $keyboard);
                    $transfer->update();

                    $request_transfer["request_id"] = $this->getUser()->id;
                    $request_transfer["transfer_id"] = $transfer->id;
                    $request_transfer["price"] = $transfer->price;
                    $request_transfer["type_title"] = getTypeTransfer($transfer->type);
                    $request_transfer["type"] = getTypeOrder(data_get($transfer, "type")) == "buy" ? "sell" : "buy";

                    $order_buy = RequestTransfer::create($request_transfer);
                    $this->checkTransaction($transfer);
                    $daily_transfer->request_id = $order_buy->id;
                    $daily_transfer->update();


                    dispatch(new PartiesToTheTransaction($order_buy->id));
                    dispatch(new SendMessageAccountingBot($order_buy->id));
                    DB::commit();

                } else {
//                    $this->telegram_services->sendMessage($this->getUserId(), "متأسفانه امکان دریافت حواله برای شما در این معامله نمی باشد");
                    return true;
                }

            } else {
                $transfer = Transfer::with(["user" => function ($query) {
                    $query->with("customer.userTradeAccess");
                    $query->with("customerUser");
                }])->where("number", ">", 0)->withTrashed()->find($id);
                if ($transfer) {
                    $this->sendAlert("زمان دریافت لفظ تمام شده");
                }else{
                    $this->sendAlert("درحال ارائه به شخص دیگر می باشد  ... \xE2\x9A\xA0	");
                }
                DB::rollBack();
                return true;
            }
        } catch (\Exception $exception) {
            DB::rollback();
            $this->sendAlert("امکان ارائه نمی باشد  ... \xE2\x9A\xA0	");

            logger("exp send request", [$exception->getMessage(),
                $exception->getLine(),
                $exception->getCode(),
                $exception->getTrace(),
                $exception->getFile()]);
        }
        return true;
    }


    private function performTransaction($seller, $buyer, $quantity, $max_trade_limit)
    {
        if (!$seller || !$buyer) {
            return 0;
        }

        $seller_head = $seller->customer;
        $seller_customer = $seller->customerUser ? $seller->customerUsers->pluck("id")->toArray() : [];
        $seller_ids[] = $seller->id;

        if ($seller_head) {
            $seller_ids[] = $seller_head->id;
            $seller_customer_head = $seller_head->customerUser ? $seller_head->customerUsers->pluck("id")->toArray() : [];
            $seller_customer = array_merge($seller_customer_head, $seller_customer);
        }
        if ($seller_customer)
            $seller_ids = array_merge($seller_ids, $seller_customer);

        $buyer_head = $buyer->customer;

        $buyer_customer = $buyer->customerUser ? $buyer->customerUsers->pluck("id")->toArray() : [];

        $buyer_ids[] = $buyer->id;
        if ($buyer_head) {
            $buyer_ids[] = $buyer_head->id;
            $buyer_customer_head = $buyer_head->customerUser ? $buyer_head->customerUsers->pluck("id")->toArray() : [];
            $buyer_customer = array_merge($buyer_customer_head, $buyer_customer);

        }
        if ($buyer_customer)
            $buyer_ids = array_merge($buyer_ids, $buyer_customer);
        $total_sold_by_seller = 0;
        $total_sold_by_buyer = 0;
        foreach (array_unique($seller_ids) as $seller_id) {
            foreach (array_unique($buyer_ids) as $buyer_id) {
                $total_sold_by_seller += DailyRequestTransfer::where('seller_id', $seller_id)
                    ->whereDate("created_at", now())
                    ->where('buyer_id', $buyer_id)->sum('use_day');

                $total_sold_by_buyer += DailyRequestTransfer::where('seller_id', $buyer_id)
                    ->whereDate("created_at", now())
                    ->where('buyer_id', $seller_id)->sum('use_day');
            }
        }

        $available_to_sell = $max_trade_limit - $total_sold_by_seller + $total_sold_by_buyer;

        $new_quantity = min($quantity, $available_to_sell);


        if ($new_quantity > 0) {
            $daily_transfer = DailyRequestTransfer::create([
                'seller_id' => $seller->id,
                'buyer_id' => $buyer->id,
                'use_day' => $new_quantity,
            ]);

            return [$daily_transfer, $new_quantity];
        } else {
            return false;
        }
    }

    public function transferBuy()
    {
        $data = str_replace('transfer_buy_', '', $this->getData());
        $array = explode("_", $data);
        $check = data_get($array, 0);
        $word_id = data_get($array, 1);
        if (!$check && !$word_id)
            return false;
        $word = WordTelegram::where("status", WordTelegram::STATUS_PENDING)->find($word_id);

        if ($word == null) return false;

        if ($check == "true") {
            /*
             * check not exist
             */
            $word->status = WordTelegram::STATUS_ACCEPT;
            $word->update();
            $type_transaction = in_array($word->type, $this->list_type_buy) ? "buy" : "sell";
            $check_transfer = Transfer::where("price", $type_transaction == "buy" ? ">" : "<", $word->price)
                ->where("status", Transfer::STATUS_ACTIVE)
                ->whereDate("created_at", now())
                ->whereIn("type", getTypeSimilar($word->type))
                ->first();
            if ($check_transfer && !Str::of($word->description)->contains(['شب حساب', 'تک فیش'])) {
                $message = "لفظ پیشنهادی بهتر در کانال : \n\n";
                $message .= " \n";
                $message .= number_format($check_transfer->price, 0);
                $this->telegram_services->sendMessage($this->getUserId(), $message);
                return true;
            }
            $transfer_olds = Transfer::where("user_id", $this->getUser()->id)
                ->whereIn("type", getTypeSimilar(data_get($word, "type")))
                ->whereIn("status", [Transfer::STATUS_ACTIVE, Transfer::STATUS_ACTIVE_DO])
                ->get();
            foreach ($transfer_olds as $row_delet) {
                $message = $row_delet->message . "\xE2\x9D\x8C	";
                $this->telegram_services->editMessageTextAndInlineKeyboard($this->bot->chanel_id, $row_delet->message_id, $message);
                $row_delet->delete();
            }

            $order = [
                "status" => Transfer::STATUS_ACTIVE,
                "user_id" => $this->getUser()->id,
                "type" => data_get($word, "type"),
                "number" => (int)data_get($word, "number"),
                "price" => data_get($word, "price"),
                "message" => data_get($word, "message"),
                "date" => data_get($word, "date"),
                "description" => data_get($word, "description"),
                "message_request" => data_get($word, "message_request"),
                "message_request_me" => data_get($word, "message_request_me"),
            ];
            $transfer_new = Transfer::create($order);
            $this->telegram_services->editMessageReplyMarkup($this->getUserId(), $this->getMessageId(), new \stdClass());
//            $this->telegram_services->sendMessage($this->getUserId(), "لفظ شما تایید شد\xE2\x9C\x85	");
            $message = $transfer_new->message;
            $keyboard = $this->getKeyboardRequest($transfer_new);

            $message_result = $this->telegram_services->MessageReplyMarkup($this->telegram, $this->bot->chanel_id, $message, $keyboard, false);
            $transfer_new->message_id = $message_result;
            $transfer_new->update();
            dispatch(new DeactivateTransfer($transfer_new->id))->delay(now()->addSecond(54));
            $keyboard = [];
            $copy = data_get($word, "word");
            if (data_get($word, "description"))
                $copy .= ":" . data_get($word, "description");
            $keyboard[0][0] = ['text' => $copy];
            $keyboard[1] = [
                ['text' => "نشد"],
                ['text' => "منو"],
            ];

            $response = TelegramServices::menu($this->telegram, $keyboard, $this->getUser(), "لفظ شما تایید شد\xE2\x9C\x85	");
//            $bot_accounting = Bot::where('title', "botAccounting")->first();
//            if($bot_accounting){
//                $telegram_accounting_services = new TelegramServices($bot_accounting->token);
//                $admins = $bot_accounting->accessBot;
//                foreach ($admins as $admin) {
//                    $message_accounting = $transfer_new->user->fullName;
//                    $message_accounting .= "\n";
//                    if(data_get($transfer_new,"user.customer"))
//                    {
//                        $message_accounting .= " مشتری :".data_get($transfer_new,"user.customer.fullName");
//                        $message_accounting .= "\n";
//
//                    }
//                    $message_accounting .= data_get($word, "message");
//                    $send_accounting = $telegram_accounting_services->sendMessage($admin->user_id,$message_accounting);
//                }
//            }
            dispatch(new SendAcceptWordAccounting($transfer_new->id, $word->id));
            dispatch(new SendAcceptWordPublicChannel($transfer_new->id, $word->id));


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

        $worker = UserTelegram::find($worker_id);
        if ($worker) {
            $limit_access = UserTradeAccess::where("user_id", $this->getUser()->id)
                ->where("user_trade_id", $worker->id)->first();
            if ($limit_access) {
                $name_worker = $worker->fullName ?: $worker->first_name . " " . $worker->last_name;


                $message_id = cache()->get("menu_List_worker_" . $this->getUserId());
                $limit_access->delete();
                $this->listWorker($page, $message_id);
                $this->telegram->sendMessage([
                    'chat_id' => $this->getUserId(),
                    'text' => "حد مجاز برای $name_worker نا محدود شد "
                ]);
            }
        }
    }

    public function tradeLimitOpen()
    {
        $array = explode("_", str_replace('trade_limit_open_', '', $this->getData()));
        $worker_id = (int)data_get($array, 0);
        $page = (int)data_get($array, 1);

        $worker = UserTelegram::find($worker_id);
        if ($worker) {
            $name_worker = $worker->fullName ?: $worker->first_name . " " . $worker->last_name;
            $message_id = cache()->get("menu_List_worker_" . $this->getUserId());
            UserTradeAccess::updateOrCreate([
                "user_id" => $this->getUser()->id,
                "user_trade_id" => $worker_id
            ],
                [
                    "limit_access" => 0
                ]);
            $this->telegram->sendMessage([
                'chat_id' => $this->getUserId(),
                'text' => "حد مجاز برای $name_worker  محدود شد "
            ]);
            $this->listWorker($page, $message_id);

        }
    }


    public function tradeLimit()
    {
        $data = str_replace('trade_limit_', '', $this->getData());
        $data = explode("_", $data);
        $worker_id = (int)data_get($data, 0);
        $page = (int)data_get($data, 1);
        $worker = UserTelegram::find($worker_id);
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
            $customer = UserTelegram::find($customer_id);
//            if ($customer_id != $this->getUserId() && $this->getUser()->role == "colleague")
//                $keyboard[0][] = ['text' => "\xF0\x9F\x94\x90	حد مجاز", 'callback_data' => "trade_open_limit_$customer_id"];
            $keyboard[0][] = ['text' => "\xF0\x9F\x93\x9C	گزارش", 'callback_data' => "trade_open_report_$customer_id"];

            $message = "یکی از گزینه های زیر برای مشتری ";
            $message .= "\n ";
            $message .= $customer ? $customer->fullName : $customer->first_name . " " . $customer->last_name;
            $this->telegram_services->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $message, $keyboard);
        }

    }

    public function tradeOpenLimit()
    {
        $customer_id = str_replace('trade_open_limit_', '', $this->getData());
        $message_id = cache()->get("trade_open_" . $this->getUserId());
        if ($customer_id && $message_id) {
            $customer = CustomerUser::find($customer_id);
            if ($customer) {
                $message = "حد مجاز برای مشتری ";
                $message .= "\n ";
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
            $customer = UserTelegram::find($customer_id);

            $today = now()->format("Y-m-d");
            $tomorrow = cache()->remember("set_tomorrow_date", now()->setTime(22, 59), function () {
                $tomorrow = Setting::where("key", "tomorrow")->first();
                if ($tomorrow)
                    return $tomorrow->value;
            });
            $tomorrow = $tomorrow ?: now()->addDay(1)->format("Y-m-d");
            $keyboard[0] = [
                ['text' => toJalali(now(), "Y/m/d"), 'callback_data' => "trade_open_report_date_" . $customer_id . "_" . $today],
                ['text' => toJalali($tomorrow, "Y/m/d"), 'callback_data' => "trade_open_report_date_" . $customer_id . "_" . $tomorrow],
            ];
            $message = ' گزارش ';
            $message .= $customer ? $customer->fullName : $customer->first_name . " " . $customer->last_name;
            $message .= "\n ";
            $message .= "تاریخ های زیر را انتخاب کنید";
            $message .= "\n ";
            $this->telegram_services->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $message, $keyboard);
        }

    }

    public function tradeOpenReportDate()
    {
        $data = str_replace('trade_open_report_date_', '', $this->getData());
        $array = explode("_", $data);
        $customer_id = (int)data_get($array, 0);
        $date = data_get($array, 1);
        $message_id = cache()->get("trade_open_" . $this->getUserId());
        if ($customer_id && $message_id) {
            $customer = UserTelegram::find($customer_id);

            $date_p = toJalali($date, "Y_m_d");
            $message = ' گزارش ';
            $message .= $customer ? $customer->fullName : $customer->first_name . " " . $customer->last_name;
            $message .= "  تاریخ   " . toJalali($date, "Y/m/d");
            $message .= "\n ";
            $this->telegram_services->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $message);
            $me = RequestTransfer::with(["transferReport" => function ($query) use ($customer_id, $date) {
                $query->where("user_id", $customer_id);
                $query->whereDate("date", $date);
                $query->with("user");
            }, "userRequest"])
                ->whereHas("transferReport", function ($query) use ($customer_id, $date) {
                    $query->where("user_id", $customer_id);
                    $query->whereDate("date", $date);
                })->get();
            $request = RequestTransfer::where("request_id", $customer_id)
                ->with(["transferReport" => function ($query) use ($customer_id, $date) {
                    $query->with("user");
                    $query->whereDate("date", $date);
                }, "userRequest"])
                ->whereHas("transferReport", function ($query) use ($date) {
                    $query->whereDate("date", $date);
                })->get();
            $request_transfer = [];
            foreach ($me as $req) {
                $request_transfer[] = $req;
                if (data_get($customer, 'id') == data_get($req, 'transferReport.user_id')) {
                    $req->type_label = data_get($req, "type") == "buy" ? "sell" : "buy";
                    if ($this->getUser()->role == "customer")
                        $req->said = "مشاهده فقط برای سرگروه";
                    else {
                        if (data_get($req, "userRequest.customer"))
                            $req->said = data_get($req, "userRequest.fullName") . "(" . data_get($req, "userRequest.customer.fullName") . ")";
                        else
                            $req->said = data_get($req, "userRequest.fullName");
                    }
                    $req->color = $req->type_label == "sell" ? "#ef4444" : "dodgerblue";
                } else {
                    $req->type_label = data_get($req, "type");
                    if ($this->getUser()->role == "customer")
                        $req->said = "مشاهده فقط برای سرگروه";
                    else {
                        if (data_get($req, "transferReport.user.customer"))
                            $req->said = data_get($req, "transferReport.user.fullName") . "(" . data_get($req, "transferReport.user.customer.fullName") . ")";
                        else
                            $req->said = data_get($req, "transferReport.user.fullName");
                    }
                    $req->color = $req->type_label == "sell" ? "#ef4444" : "dodgerblue";

                }
            }
            foreach ($request as $req) {
                $request_transfer[] = $req;
                if (data_get($customer, 'id') == data_get($req, 'transferReport.user_id')) {
                    $req->type_label = data_get($req, "type") == "buy" ? "sell" : "buy";
                    if ($this->getUser()->role == "customer")
                        $req->said = "مشاهده فقط برای سرگروه";
                    else {
                        if (data_get($req, "userRequest.customer"))
                            $req->said = data_get($req, "userRequest.fullName") . "(" . data_get($req, "userRequest.customer.fullName") . ")";
                        else
                            $req->said = data_get($req, "userRequest.fullName");
                    }
                    $req->color = $req->type_label == "sell" ? "#ef4444" : "dodgerblue";

                } else {

                    $req->type_label = data_get($req, "type");
                    if ($this->getUser()->role == "customer")
                        $req->said = "مشاهده فقط برای سرگروه";
                    else {
                        if (data_get($req, "transferReport.user.customer"))
                            $req->said = data_get($req, "transferReport.user.fullName") . "(" . data_get($req, "transferReport.user.customer.fullName") . ")";
                        else
                            $req->said = data_get($req, "transferReport.user.fullName");
                    }
                    $req->color = $req->type_label == "sell" ? "#ef4444" : "dodgerblue";
                }
            }

            if ($request_transfer) {
                $customer = $customer ?: $this->getUser();
                $mpdf = new \Mpdf\Mpdf(['tempDir' => public_path("tmp")]);
                $html = view('users.report_pdf', compact('date_p', 'request_transfer', 'customer'))->render();
                $mpdf->WriteHTML($html);
                $name_file = $this->getUserId() . "_" . $date_p . ".pdf";
                $path = "app/public/report/" . $this->getUserId() . "/";
                makeDirectoryStorage($path);
                $path_report = storage_path($path . $name_file);
                $document = $mpdf->Output($path_report, 'F');
                $response = $this->telegram->sendDocument([
                    'chat_id' => $this->getUserId(),
                    'document' => InputFile::create($path_report, "$date_p.pdf")
                ]);

            } else {
                $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => 'معامله ای در این تاریخ انجام نشده']);
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
                "user_id" => $this->getUser()->id,
                "user_trade_id" => $worker_id,],
                [
                    "limit_access" => $number
                ]);
            $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => 'حد ثابت شد']);
            $message_id = cache()->get("menu_List_worker_" . $this->getUserId());
            $this->listWorker($page, $message_id);
            cache()->forget($this->getKeyCache() . $this->getUserId());
        } else {
            $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => 'عدد وارد کنید']);
        }
    }

    public function rejectAll()
    {
        $result = false;
        DB::beginTransaction();
        try {
            $transfers = Transfer::where("user_id", $this->getUser()->id)
                ->whereIn("status", [Transfer::STATUS_ACTIVE, Transfer::STATUS_ACTIVE_DO])
                ->where("number", ">", 0)
                ->lockForUpdate()
                ->get();
            $i = 0;
            foreach ($transfers as $transfer) {
                $message = $transfer->message . "\xE2\x9D\x8C";
//            $message = $transfer->message . "\xF0\x9F\x9A\xAB";

                if ($transfer->status_transaction) {
                    $msg = $transfer->message . "[ امکان کنسلی نمی باشد چون کاربر در حال گرفتنش است  ]";
                    $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => $msg]);
                    continue;
                }

                $this->telegram_services->editMessageTextAndInlineKeyboard($this->bot->chanel_id, $transfer->message_id, $message);
                $transfer->status = Transfer::STATUS_DEACTIVATE;
                $transfer->update();
                $transfer->delete();
                $message_public = MessageWordPublic::where("transfer_id", $transfer->id)->first();
                if ($message_public && $message_public->message_id && env("CHANEL_ID_PUBLIC"))
                    $this->telegram_services->deleteMessage(env("CHANEL_ID_PUBLIC"), $message_public->message_id);
                $i++;
            }
            DB::commit();
            if ($transfers->count() && $i == $transfers->count())
                return true;
            return $result;
        }catch (\Exception $exception) {
            DB::rollback();
            $this->sendAlert("خطا در کنسلی ... \xE2\x9A\xA0	");

            logger("exp send request", [$exception->getMessage(),
                $exception->getLine(),
                $exception->getCode(),
                $exception->getTrace(),
                $exception->getFile()]);
        }
        return false;
    }

    public function checkWord()
    {

        if (!$this->getUser()->status) {
            $this->telegram_services->sendMessage($this->getUserId(), "اکانت کاربری شما غیر فعال می باشد");
            return true;
        }

        if ($this->getUser()->role == "customer") {
            $head = data_get($this->getUser(), "customer");
            if (!$head) {
                $this->telegram_services->sendMessage($this->getUserId(), "شما نمی توانید لفظ  بدهید");
                return true;
            }
        }
        if ($this->getUser()->set_word || ($this->getUser()->customer && $this->getUser()->customer->set_word)) {
            $this->telegram_services->sendMessage($this->getUserId(), "اکانت کاربری شما فقط می تواند لفظ بگیرد ");
            return true;
        }

        if(env("DISABLE_TOMORROW" ,false) && in_array($this->getType(), $this->list_type_tommarow)){
            $this->telegram_services->sendMessage($this->getUserId(), "فقط امکان لفظ روز می باشد");
            return true;
        }

        if(env("DISABLE_TOMORROW" ,false) && in_array($this->getType(), $this->list_type_floating)){
            $this->telegram_services->sendMessage($this->getUserId(), "امکان لفظ شنا نمی باشد");
            return true;
        }
        if(env("DISABLE_TOMORROW" ,false) && in_array($this->getType(), $this->list_type_reverse)){
            $this->telegram_services->sendMessage($this->getUserId(), "امکان لفظ معکوس نمی باشد");
            return true;
        }

        if ($this->getDescription()) {
            $ugly_words = UglyWord::get();
            foreach ($ugly_words as $ugly_word) {
                if (str_contains($this->getDescription(), $ugly_word->word)) {
                    $this->telegram_services->sendMessage($this->getUserId(), "لطفا کلمات نامتعارف در توضیحات به کار نبرید");

                    return true;
                }
            }
        }
        if ($this->getDescription() && !in_array($this->getType(), $this->list_type_reverse_floating_cache)) {
            $this->telegram_services->sendMessage($this->getUserId(), "توضیحات برای معاملات نقدی و شنا و معکوس می باشد");
            return true;
        }

        $suggest_price = $this->getPrice();

        $parameter = cache()->remember("parameter_need", now()->setTime(23, 59), function () {
            return Setting::whereIn("key", ["start_hours_of_operation", "end_hours_of_operation", "vacation"])->get()->keyBy("key");
        });
        if (data_get($parameter, "vacation.value")) {
            $this->telegram_services->sendMessage($this->getUserId(), "تعطیل می باشد");
            return false;
        }
        $word_old = WordTelegram::where("status", WordTelegram::STATUS_PENDING)->where("user_id", $this->getUserId())->get();

        if ($word_old->count()) {
            foreach ($word_old as $word) {
                try {
                    $word->status = WordTelegram::STATUS_REJECT;
                    $word->update();
                    $this->getTelegramServices()->editMessageReplyMarkup($word->user_id, $word->message_id, new \stdClass());
                } catch (\Exception $exception) {
                    logger("get error", [
                        $exception->getMessage(),
                        $exception->getLine(),
                        $exception->getCode(),
                        $exception->getTrace(),
                        $exception->getFile()
                    ]);
                }
            }
        }
        // گرفتن زمان فعلی
        $now = Carbon::now();

// تعریف زمان 09:00 امروز
        $array_time_s = explode(":", data_get($parameter, "start_hours_of_operation.value", "09:00"));
        $array_time_e = explode(":", data_get($parameter, "end_hours_of_operation.value", "22:00"));
        $start_time = Carbon::createFromTime(data_get($array_time_s, 0), data_get($array_time_s, 1), 0);
        $end_time = Carbon::createFromTime(data_get($array_time_e, 0), data_get($array_time_e, 1), 0);

// چک کردن اینکه آیا زمان فعلی قبل یا بعد از 09:00 است
        if ($now->lessThan($start_time)) {
            $message_s = " زمان شروع بازار ";
            $message_s .= data_get($parameter, "start_hours_of_operation.value", "09:00");
            $message_s .= "می باشد";
            $this->telegram_services->sendMessage($this->getUserId(), $message_s);
            return false;
        } elseif ($now->greaterThan($end_time)) {
            $this->telegram_services->sendMessage($this->getUserId(), "تعطیل می باشد");
            return false;
        }

        $last_transfer = Transfer::whereIn("type", getTypeSimilar($this->getType()))
            ->whereIn("status", [Transfer::STATUS_ACTIVE_DO, Transfer::STATUS_ACTIVE_DONE])
            ->whereDate("created_at", now())
            ->withTrashed()
            ->orderBy("updated_at", "DESC")->first();
        logger("last",[$last_transfer]);
        if (env("BETWEEN_TRADE", true) == true || !$last_transfer) {
            $start_trade_s = (int)cache()->remember("start_price_trade", now()->addDay(1), function () {
                $value = 14000000;
                $setting = Setting::where("key", "start_price_trade")->first();
                if ($setting)
                    $value = data_get($setting, "value");
                return $value;
            });
            $end_trade_s = (int)cache()->remember("end_price_trade", now()->addDay(1), function () {
                $value = 14200000;
                $setting = Setting::where("key", "end_price_trade")->first();
                if ($setting)
                    $value = data_get($setting, "value");
                return (int)$value;
            });
            $price = $this->getPriceTrade($suggest_price, $start_trade_s);


        } else {
            $last_trade = (int)$last_transfer->price;
            $price = $this->getPriceTrade($suggest_price, $last_trade);
            if ($last_trade) {
                // محاسبه قیمت جدید در محدوده ±2٪

                $start_trade_s = $last_trade - 500000;
                $end_trade_s = $last_trade + 500000;
            }
        }
        logger("price",[$start_trade_s,$end_trade_s,$price]);
        if ($price < $start_trade_s || $price > $end_trade_s) {
            $message = "مبلغ وارد شده باید در بازه";
            $message .= "\n";
            $message .= $start_trade_s;
            $message .= "\n";
            $message .= "تا";
            $message .= "\n";
            $message .= $end_trade_s;
            $message .=  "\n";
            $message .=  "اگر لفظ شما 3 رقمی درست نمی باشد ۵ رقمی لفظ دهید";


            $this->telegram_services->sendMessage($this->getUserId(), $message);
            return false;
        }

        $number = $this->getNumberOrder();

        $type_transaction = in_array($this->getType(), $this->list_type_buy) ? "buy" : "sell";
        $word_active = WordTelegram::where("user_id", $this->getUserId())
            ->where("status", WordTelegram::STATUS_PENDING)
            ->whereIn("type", getTypeSimilar($this->getType()))
            ->where("number", (int)$number)
            ->where("price", $price)
            ->where("word", $this->getWord())->first();
        if ($word_active) {
            $message = "لفظ مشابه فعال  برای ارسال به کانال وجود دارد ";
            $this->telegram_services->sendMessage($this->getUserId(), $message);
            return true;
        }

        $check_transfer = Transfer::where("price", $type_transaction == "buy" ? ">" : "<", $price)
            ->where("status", Transfer::STATUS_ACTIVE)
            ->whereDate("created_at", now())
            ->whereIn("type", getTypeSimilar($this->getType()))
            ->first();

        if ($check_transfer) {
//            $message = "قیمت پیشنهادی بهتری از لفظ شمادر کانال میباشد\n\n";
//            $message .= "لطف اگر پیشنهاد بهتری دارید مجددا لفظ دهید یا \n\n";
//            $message .= "حداکثر با تلرانس ۲۰۰۰ تومان لفظ دهید \n\n";
            $message = "لفظ پیشنهادی بهتر در کانال : \n\n";
            $message .= " \n";
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
            $morning = Carbon::create($time->year, $time->month, $time->day, data_get($array_time_s, 0), data_get($array_time_s, 1), 0); //set time to 08:00
            $none = Carbon::create($time->year, $time->month, $time->day, env("NONE_HOUR", "15"), env("NONE_MIN", "30"), 0); //set time to 18:00
            $none_13_30 = Carbon::create($time->year, $time->month, $time->day, env("NONE_M_HOUR", "13"), env("NONE_M_MIN", "30"), 0); //set time to 18:00

            cache()->forget("forbidden_day");
            $forbidden_day = cache()->remember("forbidden_day", now()->setTime(23, 59), function () {
                $item = Setting::where("key", "forbidden_day")->first();
                return data_get($item, "value");
            });
            if (!$forbidden_day)
                $forbidden_day = ($time->isThursday() || $time->isFriday()) && !env("DISABLE_TOMORROW" ,false)  ? true : false;
//            if ($forbidden_day && data_get($forbidden_day, "value") && in_array($this->getType(), $this->list_type_today)) {
//                $this->telegram_services->sendMessage($this->getUserId(), "تمام معاملات برای اولین روز کاری می باشد و امکان معامله روز در حال حاظر وجود ندارد");
//                return true;
//            }
//            if (!$time->between($morning, $none_13_30, true) && in_array($this->getType(), $this->list_type_today_r_f)) {
//                $this->telegram_services->sendMessage($this->getUserId(), "\xE2\x9D\x8C	زمان معامله شرایطی برای امروز به پایان رسیده است\xE2\x9D\x8C	");
//                return true;
//            } elseif (!$time->between($morning, $none, true) && in_array($this->getType(), $this->list_type_today_normal)) {
//                $this->telegram_services->sendMessage($this->getUserId(), "\xE2\x9D\x8C	زمان معامله روز به پایان رسیده است\xE2\x9D\x8C		");
//                return true;
//            } elseif (!$time->between($morning, $none, true) && in_array($this->getType(), $this->list_type_today_cache)) {
//                $this->telegram_services->sendMessage($this->getUserId(), "\xE2\x9D\x8C	زمان معامله نقدی حاضر به پایان رسیده است\xE2\x9D\x8C		");
//                return true;
//            } else
            $tomorrow = cache()->remember("set_tomorrow_date", now()->setTime(22, 59), function () {
                $tomorrow = Setting::where("key", "tomorrow")->first();
                if ($tomorrow)
                    return $tomorrow->value;
            });

            if (!$forbidden_day && $time->between($morning, $none_13_30, true) && in_array($this->getType(), $this->list_type_today_r_f)) {
                $message .= "\xE2\x98\x80";
                $message_request .= "\xE2\x98\x80";
                $message_request_me .= "\xE2\x98\x80";
                $date = now()->format("Y-m-d");
            } else if (!$forbidden_day && $time->between($morning, $none, true) && (in_array($this->getType(), $this->list_type_today_normal) ||
                    in_array($this->getType(), $this->list_type_today_cache))) {
                $message .= "\xE2\x98\x80";
                $message_request .= "\xE2\x98\x80";
                $message_request_me .= "\xE2\x98\x80";
                $date = now()->format("Y-m-d");
            } else {
                $message .= "\xE2\x8F\xB3️";
                $message_request .= "\xE2\x8F\xB3️";
                $message_request_me .= "\xE2\x8F\xB3️";
                if ($tomorrow)
                    $date = $tomorrow;
                else
                    $date = now()->addDay(1)->format("Y-m-d");

            }

            $message_request .= "\n";
            $message_request .= "فی:";
            $message_request .= number_format($price, 0);
            $message_request_me .= "\n";
            $message_request_me .= "فی:";
            $message_request_me .= number_format($price, 0);
            if (in_array($this->getType(), $this->list_type_cash)) {
                if (!$forbidden_day && $time->between($morning, $none_13_30, true) && in_array($this->getType(), $this->list_type_cash_n))
                    $message .= " نقدی حاضر ";
                else
                    $message .= "   بی حواله اولین روز کاری ";

                $message .= "\xF0\x9F\x92\xB0";

            } elseif (!$forbidden_day && $time->between($morning, $none, true) && (in_array($this->getType(), $this->list_type_today)))
                $message .= " روز   ";
            else
                $message .= "  با حواله  ";
            $message .= $number;
            $message .= " تا ";
            if (in_array($this->getType(), $this->list_type_floating))
                $message .= "\xE2\xAC\x86 شنا ";
            elseif (in_array($this->getType(), $this->list_type_reverse))
                $message .= "\xE2\xAC\x87 معکوس ";


            if ($this->getDescription()) {
                $message .= "\n";
                $message .= " توضیحات ";
                $message .= "\xE2\x9D\x97: ";
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
                "word" => $this->getWord(),
                "message_request" => $message_request,
                "message_request_me" => $message_request_me,
                "description" => $this->getDescription()
            ]);
            $keyboard[0] = [
                ['text' => "\xE2\x9C\x85	تایید", 'callback_data' => "transfer_buy_true_$word_telegram->id"],
                ['text' => "\xE2\x9D\x8C	رد", 'callback_data' => "transfer_buy_false_$word_telegram->id"],
            ];
            $result_word = $this->telegram_services->MessageReplyMarkup($this->telegram, $this->getUserId(), $message, $keyboard, false);
            if ($result_word) {
                $word_telegram->message_id = $result_word;
                $word_telegram->update();
                dispatch(new DeactivateWord($word_telegram->id))->delay(now()->addMinute(1));
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
        return $keyboard;
    }

    /**
     * @param mixed $suggest_price
     * @param mixed $start_trade_s
     * @return float|int
     */
    private function getPriceTrade(mixed $suggest_price, mixed $start_trade_s): int|float
    {
// طول رشته عدد را محاسبه کنید
        $length = strlen($suggest_price);

        // بررسی کنید که آیا طول عدد 3 یا 5 است
        if ($length === 3) {
            $start_price = (int)$start_trade_s;
            $unit = getUnitPrice($start_price);
        } elseif ($length === 5) {
            $start_price = (int)($suggest_price * 1000);
            $unit = getUnitPrice($start_price);
            $suggest_price = $suggest_price % 1000;

        }
        $start_trade = floor($start_price / $unit) * $unit;


        $price = $start_trade + ($suggest_price * 1000);
        return $price;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Builder|array $transfer
     * @return void
     */
    public function checkTransaction(Transfer $transfer): void
    {
        $transfer = $transfer->refresh();
        if ($transfer->number > 0) {
            $transfer->status_transaction = false;
            $transfer->update();
            $transfer = $transfer->refresh();
            DB::commit();


        }
    }

    /**
     * @param string $alert_text
     * @return void
     */
    public function sendAlert(string $alert_text): void
    {
        $callback_query = $this->update[$this->getTypeMessage()];
        $callback_id = data_get($callback_query, 'id');
        if ($callback_query) {
            $url = "https://api.telegram.org/bot" . $this->bot->token . "/answerCallbackQuery?callback_query_id=$callback_id&text=" . urlencode($alert_text) . "&show_alert=true";
            file_get_contents($url);
        }
    }

}
