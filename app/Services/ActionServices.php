<?php

namespace App\Services;


use App\Jobs\DeactivateTransfer;
use App\Models\CustomerUser;
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
            $customer->limit = $this->getMessage();
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
            cache()->forget("trade_open_".$this->getUserId());


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
            $message = "اطلاعات مشتری وارد شد";
            $message .= "\n\n";
            $message .= "نام و نام خانوادگی:";
            $message .= $fullName;
            $message .= "\n\n";
            $message .= "شماره همراه:";
            $message .= $mobile;
            $message .= "\n\n";
            $message = "پس از تایید مدیریت مشتری شما خواهد گشت ";
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
                $message = "نام و نام خانوادگی مشتری خود را وارد کنید";
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
        if (!$this->getUser()->mobile) {
            $text = "ممنون شماره خود را به اشتراک بگذارید";
            $this->telegram_services->sendRequestContactButton($this->getUserId(), $text);
        } elseif (!$this->getUser()->status) {
            cache()->set($this->getKeyCache() . $this->getUserId(), "pending_accept");
            $text = "منتظر تایید مدیر سیستم باشید تا دسترسی به شما ارائه گردد";
            $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => $text]);
        } else {
            cache()->forget($this->getKeyCache() . $this->getUserId());
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
        $num = data_get($info, 1);
        $transfer = Transfer::find($id);
        if ($transfer) {
            try {
                if ($transfer->number >= $num)
                    $transfer->number -= $num;
                $keyboard = self::getKeyboardRequest($transfer);


                $this->telegram_services->editMessageTextAndInlineKeyboard($this->bot->chanel_id, $transfer->message_id, $transfer->message, $keyboard);
                $transfer->update();
            } catch (\Exception $e) {

                logger("exp", [$e->getMessage(), $e->getLine()]);
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
                "number" => data_get($word, "number"),
                "price" => data_get($word, "price"),
                "message" => data_get($word, "message"),
                "date" => data_get($word, "date"),
            ];
            $transfer_new = Transfer::create($order);
            logger("a", [$this->getUserId(), $this->getMessageId(), []]);
            $this->telegram_services->editMessageReplyMarkup($this->getUserId(), $this->getMessageId(), new \stdClass());
            $this->telegram_services->sendMessage($this->getUserId(), "لفظ شما تایید شد\xE2\x9C\x85	");
            $message = $transfer_new->message;
            $keyboard = $this->getKeyboardRequest($transfer_new);

            logger("test", [$this->bot->chanel_id, $message, $keyboard]);
            $message_result = $this->telegram_services->MessageReplyMarkup($this->telegram, $this->bot->chanel_id, $message, $keyboard);
            $transfer_new->message_id = data_get($message_result, 'message_id');
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
        $worker_i = (int)data_get($array, 1);

        $worker = UserTelegram::where("id", $worker_id)->first();
        logger("worker", [$worker, $worker_id]);
        if ($worker) {
            $limit_access = UserTradeAccess::where("user_id", $this->getUserId())
                ->where("user_trade_id", $worker->id)->first();
            if ($limit_access) {
                $name_worker = $worker->fullName ?: $worker->first_name . " " . $worker->last_name;

                $message_menu = cache()->get("menu_" . $this->getUserId());
                if ($message_menu) {
                    $keyboard = data_get($message_menu, "keyboard");
                    $text = $worker->fullName ?: $worker->first_name . " " . $worker->last_name;
                    $keyboard[$worker_i] = [[
                        'text' => "  $text " . "\xE2\x9C\x85",
                        'callback_data' => "trade_limit_" . $worker->id
                    ]];
                    logger("close", [$this->getUserId(), data_get($message_menu, "id"), "لیست همکاران", $keyboard]);
                    $this->telegram_services->editMessageTextAndInlineKeyboard($this->getUserId(), data_get($message_menu, "id"), "لیست همکاران", $keyboard);
                }
                $limit_access->delete();
                $this->telegram->sendMessage([
                    'chat_id' => $this->getUserId(),
                    'text' => "حد مجاز برای $name_worker نا محدود شد "
                ]);
            }
        }
    }

    public function tradeLimit()
    {
        $worker_id = (int)str_replace('trade_limit_', '', $this->getData());

        $worker = UserTelegram::where("id", $worker_id)->first();
        logger("worker", [$worker]);
        if ($worker) {
            $name_worker = $worker->fullName ?: $worker->first_name . " " . $worker->last_name;

            $this->telegram->sendMessage([
                'chat_id' => $this->getUserId(),
                'text' => "حد مجازی که می خواهید با $name_worker داشته باشید را وارد کنید "
            ]);
            cache()->set($this->getKeyCache() . $this->getUserId(), ["title" => "trade_number_limit", "value" => $worker->id]);
        }
    }

    public function tradeOpen()
    {
        $customer_id = str_replace('trade_open_', '', $this->getData());
        $message_id =  cache()->get("trade_open_".$this->getUserId());
        if($customer_id && $message_id) {
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
        $message_id =  cache()->get("trade_open_".$this->getUserId());
        if($customer_id && $message_id) {
            $customer = CustomerUser::find($customer_id);
            logger("customer",[$customer,$customer_id,$message_id]);
            if($customer) {
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
        $message_id =  cache()->get("trade_open_".$this->getUserId());
        if($customer_id && $message_id) {
            $customer = CustomerUser::find($customer_id);

            $today = now()->format("Y-m-d");
            $tomorrow = now()->addDay(1)->format("Y-m-d");
            $keyboard[0] = [
                ['text' => toJalali(now(),"Y/m/d"), 'callback_data' => "trade_open_report_date_".$customer_id."_".$today],
                ['text' => toJalali(now()->addDay(1),"Y/m/d"), 'callback_data' => "trade_open_report_date_".$customer_id."_".$tomorrow],
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
        $array = explode("_",$data);
        $customer_id = data_get($array,0);
        $date = data_get($array,1);
        $message_id =  cache()->get("trade_open_".$this->getUserId());
        if($customer_id && $message_id) {
            $customer = CustomerUser::with("user")->find($customer_id);

            $date_p = toJalali($date,"Y_m_d");
            $message = ' گزارش ';
            $message .= $customer->fullName;
            $message .= "تاریخ ".toJalali($date,"Y/m/d");
            $message .= "\n\n ";
            $this->telegram_services->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $message);
            $request_transfer = RequestTransfer::with("transfer.user")->where("request_id",data_get($customer,"user.id"))->get();
            if($request_transfer){
                $pdf = Pdf::loadView('users.report_pdf', compact('date_p','request_transfer', 'customer'));
                $name_file = $customer_id."_".$date_p.".pdf";
                $path_report =  storage_path("app/public/report/" .$this->getUserId()."/".$name_file);
                $pdf->save($path_report);

                $response = $this->telegram->sendDocument([
                    'chat_id' => $this->getUserId(),
                    'document' => InputFile::create($path_report, "$date_p.pdf")
                ]);
            }else{
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
            UserTradeAccess::updateOrCreate([
                "user_id" => $this->getUserId(),
                "user_trade_id" => $worker_id,],
                [
                    "limit_access" => $number
                ]);
            $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => 'حد ثابت شد']);
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
            ->orWhere(function ($query) {
                $query->whereIn("status", [
                    Transfer::STATUS_ACTIVE_DO,
                    Transfer::STATUS_ACTIVE_DONE
                ])->where("updated", ">", now()->subMinute(1));
            })
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
            if (in_array($this->getType(), $this->list_type_buy))
                $message .= " \xF0\x9F\x94\xB5	خرید";
            elseif (in_array($this->getType(), $this->list_type_sell))
                $message .= " \xF0\x9F\x94\xB4	فروش";
            $time = Carbon::now();
            $morning = Carbon::create($time->year, $time->month, $time->day, 10, 0, 0); //set time to 08:00
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
                $date = now();
            } else {
                $message .= " \xE2\x8F\xB3	";
                $date = now()->addDay(1);
            }

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
                "number" => $number,
                "price" => $price,
                "date" => $date,
            ]);
            $keyboard[0] = [
                ['text' => "\xE2\x9C\x85	تایید", 'callback_data' => "transfer_buy_true_$word_telegram->id"],
                ['text' => "\xE2\x9D\x8C	رد", 'callback_data' => "transfer_buy_false_$word_telegram->id"],
            ];
            logger("ke", [$this->getUserId(), $message, $keyboard]);
            $result_word = $this->telegram_services->MessageReplyMarkup($this->telegram, $this->getUserId(), $message, $keyboard);
            if (data_get($result_word, "message_id")) {
                $word_telegram->message_id = data_get($result_word, "message_id");
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
