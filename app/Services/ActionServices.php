<?php

namespace App\Services;


use App\Models\CustomerUser;
use App\Models\Setting;
use App\Models\Transfer;
use App\Models\UserTelegram;
use App\Models\UserTradeAccess;
use Carbon\Carbon;

class ActionServices extends TextServices
{

    public function __construct($token)
    {
        parent::__construct($token);
    }

    public function addCustomer()
    {
        $limit = null;
        $message = $this->convertNumber($this->getMessage());
        $array = explode(",", $message);
        $mobile = null;
        $fullName = null;
        foreach ($array as $item) {
            logger("item", [$item]);
            $item = str_replace(":", "", $item);
            if (str_contains($item, "موبایل")) {
                $mobile = str_replace("موبایل", "", $item);
            } elseif (str_contains($item, "نام و نام خانوادگی")) {
                $fullName = str_replace("نام و نام خانوادگی", "", $item);
            } elseif (str_contains($item, "حد")) {
                $limit = str_replace("حد", "", $item);
            }
        }
        logger("item", [$mobile, $fullName, $limit]);

        if ($mobile && $fullName) {
            CustomerUser::updateOrCreate(["user_id" => $this->getUserId(), "mobile" => $mobile],
                [
                    "fullName" => $fullName,
                    "limit" => $limit
                ]);
            $message = "اطلاعات مشتری وارد شد";
            $message .= "\n\n";
            $message .= "نام و نام خانوادگی:";
            $message .= $fullName;
            $message .= "\n\n";
            $message .= "شماره همراه:";
            $message .= $mobile;
            $message .= "\n\n";
            $message .= "حد مجاز:";
            $message .= $limit === null ? "آزاد" : $limit;
            $message .= "\n\n";
            $this->telegram_services->sendMessage($this->getUserId(), $message);
            $message_share = "لینک را برای مشتری خود فورواد کرد تا پس از تایید  مدیر دسترسی به معامله خواهد داشت";
            $message_share .= "\n\n";

            $message_share .= "https://t.me/sell_buy_goldbot";
            $this->telegram_services->sendMessage($this->getUserId(), $message_share);
            cache()->forget($this->getKeyCache() . $this->getUserId());


        } else {
            $this->telegram_services->sendMessage($this->getUserId(), "اطلاعات وارد شده مشابه مثال باید باشه");

        }
    }

    public function addMobile()
    {
        $mobile = $this->convertNumber($this->message);
        if ($this->iranMobile($mobile)) {
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
        if (!$this->getUser()->status) {
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
                $keyboard["inline_keyboard"] = self::getKeyboardRequest($transfer);


                $this->telegram_services->editMessageReplyMarkup($this->getUserId(), $transfer->message_id, $keyboard);
                $transfer->update();
            } catch (\Exception $e) {

                logger("exp", [$e->getMessage(), $e->getLine()]);
            }
        }
    }

    public function transferBuy()
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
                $keyboard["inline_keyboard"] = self::getKeyboardRequest($transfer);


                $this->telegram_services->editMessageReplyMarkup($this->getUserId(), $transfer->message_id, $keyboard);
                $transfer->update();
            } catch (\Exception $e) {

                logger("exp", [$e->getMessage(), $e->getLine()]);
            }
        }
    }

    public function tradeLimitClose()
    {
        $worker_id = (int)str_replace('trade_limit_close_', '', $this->getData());

        $worker = UserTelegram::where("id", $worker_id)->first();
        logger("worker", [$worker,$worker_id]);
        if ($worker) {
            $limit_access = UserTradeAccess::where("user_id",$this->getUserId())
                ->where("user_trade_id",$worker->id)->first();
            if($limit_access) {
                $name_worker = $worker->fullName ?: $worker->first_name . " " . $worker->last_name;

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

    public function tradeNumberLimit()
    {
        $data_cache = $this->getMessageCache();

        $number = (int)$this->getMessage();
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
        }else{
            $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => 'عدد وارد کنید']);
        }
    }

    public function checkWord()
    {
        $start_trade = cache()->remember("s_price_trade", now()->addDay(1), function () {
            $value = 14000000;
            $setting = Setting::where("key", "s_price_trade")->first();
            if ($setting)
                $value = (int)data_get($setting, "value");
            return $value;
        });
        logger("start_trade", [$start_trade]);


        $array = explode($this->getType(), $this->message);
        $array_desc = explode(":", $this->message);

        if (isset($array_desc[1]))
            $description = $array_desc[1];
        $price = $start_trade + ((int)data_get($array, 0) * 1000);

        $number = (int)data_get($array, 1, 1);
        logger("check_transfer", [$price, $number, $array]);

        $type_transaction = in_array($this->getType(), $this->list_type_buy) ? "buy" : "sell";
        $check_transfer = Transfer::where("price", $type_transaction == "buy" ? ">" : "<", $price)
            ->where("status", Transfer::STATUS_ACTIVE)
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
            cache()->set("transfer_cache_buy_" . $this->getUserId(), [
                "user_id" => $this->getUserId(),
                "type" => $this->getType(),
                "number" => $number,
                "price" => $price,
                "status" => 0
            ]);
            $price_format = number_format($price, 0);
            $message = $price_format;
            if (in_array($this->getType(), $this->list_type_buy))
                $message .= " \xF0\x9F\x94\xB5	خرید";
            elseif (in_array($this->getType(), $this->list_type_buy))
                $message .= " \xF0\x9F\x94\xB4	فروش";
            $time = Carbon::now();
            $morning = Carbon::create($time->year, $time->month, $time->day, 10, 0, 0); //set time to 08:00
            $none = Carbon::create($time->year, $time->month, $time->day, 13, 15, 0); //set time to 18:00
            if ($time->between($morning, $none, true) & (
                    !in_array($this->getType(), $this->list_type_buy_tommarow) ||
                    !in_array($this->getType(), $this->list_type_sell_tommarow)
                )) {
                $message .= " \xE2\x98\x80	";
            } else {
                $message .= " \xE2\x8F\xB3	";
            }

            if (str_contains("ن", $this->getType())) {
                $message .= "بی حواله";
                if (!$time->between($morning, $none, true) ||
                    in_array($this->getType(), $this->list_type_sell_n_buy_tom))
                    $message .= "فردا";
                $message .= "\xF0\x9F\x92\xB0	\xF0\x9F\x92\xB5	";

                $message .= "بی حواله";
            } else
                $message .= "با حواله";
            $message .= $number;
            $message .= "تا";
            $keyboard[0] = [
                ['text' => "\xE2\x9C\x85	تایید", 'callback_data' => "transfer_buy_true"],
                ['text' => "\xE2\x9D\x8C	رد", 'callback_data' => "transfer_buy_false"],
            ];
            logger("ke", [$this->getUserId(), $message, $keyboard]);
            $this->telegram_services->MessageReplyMarkup($this->telegram, $this->getUserId(), $message, $keyboard);
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
            $keyboard = new \stdClass();
        logger("key", [$keyboard]);
        return $keyboard;
    }
}
