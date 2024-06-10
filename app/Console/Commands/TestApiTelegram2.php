<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\BotMenuUser;
use App\Models\CustomerUser;
use App\Models\DailyRequestTransfer;
use App\Models\Setting;
use App\Models\Transfer;
use App\Models\UserTelegram;
use App\Models\UserTradeAccess;
use App\Models\WordTelegram;
use App\Services\TelegramServices;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;

class TestApiTelegram2 extends Command
{

    private $list_type = [  "خفن","خفش","ففش","ففن", "فف","خف","خش","فش","خن","فن","خ","ف"];
    protected $list_type_buy = [ "خفش", "خش", "خفن", "خن","خف", "خ"];
    protected $list_type_sell = [ "ففش", "فش",  "ففن","فن","فف", "ف"];
    protected $list_type_sell_n_buy_tom = ["خفن", "ففن"];
    protected $list_type_sell_tommarow = ["فف", "ففش", "ففن"];
    protected $list_type_buy_tommarow = ["خف", "خفش", "خفن"];
    protected $list_type_tommarow = ["خف", "خفش", "خفن","فف", "ففش", "ففن"];

    private $type;
    private $price;

    private $number_order;
    private $description_test;

    private $type_message;

    protected $message;

    private $message_cache;

    public $data;

    protected $bot;

    protected $telegram;

    protected $message_menu = "خوش آمدید";

    private $pattern;

    protected $telegram_services;

    private $token;

    protected $update;

    private $user;

    private $user_id;

    private $key_cache;
    private $contact;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-api-telegram2';


    /**
     * @return TelegramServices
     */
    public function getTelegramServices(): TelegramServices
    {
        return $this->telegram_services;
    }

    /**
     * @return mixed
     */
    public function getKeyCache()
    {
        return $this->key_cache;
    }

    /**
     * @param mixed $key_cache
     */
    public function setKeyCache($key_cache): void
    {
        $this->key_cache = $key_cache;
    }

    /**
     * @return mixed
     *
     *  get type message
     *
     * // دریافت دستور ارسال شده توسط کار
     */
    public function getTypeMessage()
    {
        return $this->type_message;
    }

    /**
     * @param mixed $type_message
     */
    public function setTypeMessage(): void
    {
        if (isset($this->update["my_chat_member"]))
            $type = "my_chat_member";
        elseif (isset($this->update['callback_query']))
            $type = "callback_query";
        else
            $type = "message";

        $this->type_message = $type;
        logger("type_messager", [$this->type_message]);
    }


    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @param mixed $user_id
     */
    public function setUserId(): void
    {
        $this->user_id = data_get($this->update, $this->type_message . '.from.id');;
        logger("user_id", [$this->user_id]);
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }
    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param mixed $price
     */
    public function setPrice($price): void
    {
        $this->price = $price;
        logger("price".$this->price);

    }

    /**
     * @return mixed
     */
    public function getNumberOrder()
    {
        return $this->number_order;
    }

    /**
     * @param mixed $number_order
     */
    public function setNumberOrder($number_order): void
    {
        $this->number_order = $number_order;
        logger("number order".$this->number_order);
    }

    /**
     * @return mixed
     */
    public function getDescriptionTest()
    {
        return $this->description_test;
    }

    /**
     * @param mixed $description_test
     */
    public function setDescriptionTest($description_test): void
    {
        $this->description_test = $description_test;
        logger("description_test".$this->description_test);

    }

    /**
     * @param mixed $user
     */
    public function setUser(): void
    {
        $user_telegram = UserTelegram::where("id", $this->user_id)->withTrashed()->first();
        if ($user_telegram == null) {
            $update = $this->update;
            $type = $this->type_message;
            $data =  array_filter([
                "id" => $this->user_id,
                "is_bot" => data_get($update, $type . '.from.is_bot'),
                "first_name" => data_get($update, $type . '.from.first_name'),
                "last_name" => data_get($update, $type . '.from.last_name'),
                "mobile" => data_get($update, $type . '.mobile'),
                "username" => data_get($update, $type . '.from.username'),
                "language_code" => data_get($update, $type . '.from.language_code'),
                "role"=>null,
                "status"=>false,
            ]);
            if ($update && $data) {
                $user_telegram = UserTelegram::create($data);
                $this->sendMessageNewUser();
                CustomerUser::updateOrCreate(["user_id" => $this->getUserId(), "mobile" => data_get($update, $type . '.mobile')],
                    [
                        "fullName" => "خودم",
                        "status" => true,
                        "limit" => null
                    ]);
            }
            $this->user = $user_telegram;
        }elseif(data_get($user_telegram,"deleted_at"))
        {
            cache()->forget("keyword_menu" . $this->getKeyCache() . $user_telegram->id);
            $this->telegram_services->deleteKeyboard($user_telegram->id);
        }else
            $this->user = $user_telegram;
    }

    private $message_id = null;

    public function getMessageId()
    {
        return $this->message_id;
    }

    /*
  * set message id
  */
    public function setMessageId(): void
    {
        if (isset($this->update[$this->type_message]['message_id']))
            $this->message_id = $this->update[$this->type_message]['message_id']; // چت‌آیدی کاربر
        if (isset($this->update[$this->type_message]['message']['message_id']))
            $this->message_id = $this->update[$this->type_message]['message']['message_id']; // چت‌آیدی کاربر

        logger("messgae_id", [$this->message_id]);
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     */
    public function setMessage(): void
    {
        $this->message = isset($this->update['message']['text']) ? convertNumber(cleanInput($this->update['message']['text'])) : null;
        logger("message", [$this->message]);
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData(): void
    {
        $this->data = data_get($this->update, $this->type_message . ".data");
        logger('data', [$this->data]);
    }

    /**
     * @return mixed
     */
    public function getMessageCache()
    {
        return $this->message_cache;
    }

    /**
     * @param mixed $message_cache
     */
    public function setMessageCache(): void
    {
        $this->message_cache = cache()->get($this->key_cache . $this->user_id);
        logger("message_cache", [$this->message_cache, $this->key_cache . $this->user_id]);

        // data_get($cache_data, "title")
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @param mixed $pattern
     */
    public function setPattern(): void
    {
//        $this->pattern = "/^\d{3,5}" . $this->type . "\d{1}$/";
//        $this->pattern = "/^([0-9]{3}|[0-9]{5})$this->type([1-3]?)$/";
        $this->pattern = "/^([0-9]{3}|[0-9]{5})$this->type([1-3]?)(:.*)?$/u";
        logger("pattern", [$this->pattern, $this->type]);
    }



    /**
     * Execute the console command.
     */
    public function handle()
    {
        $word = (int)$this->ask('word?');
       // $this->update='{"update_id":744458867,"message":{"message_id":798,"from":{"id":70115829,"is_bot":false,"first_name":"Shahriar","last_name":"P","username":"apachish","language_code":"en"},"chat":{"id":70115829,"first_name":"Shahriar","last_name":"P","username":"apachish","type":"private"},"date":1717999548,"text":"430\u062e\u06f1"}}';
        $this->update='{"update_id":744458866,"message":{"message_id":796,"from":{"id":70115829,"is_bot":false,"first_name":"Shahriar","last_name":"P","username":"apachish","language_code":"en"},"chat":{"id":70115829,"first_name":"Shahriar","last_name":"P","username":"apachish","type":"private"},"date":1717999316,"text":"15430\u062e\u06f1"}}';
        $this->update =  json_decode($this->update,true);
        $this->setTypeMessage();
        $this->setUserId();
        $this->setMessageId();
        $key_cache = "text_user_";
        $this->setKeyCache($key_cache);
        $this->setData();
        $this->setMessage();
        $this->setMessageCache();
        $this->setUser();
        $this->checkText();
        $this->checkWord();
    }

    public function checkText()
    {


        $accept = [
            "/start",
            "start",
            "/help",
            "نشد",
            "ن",
            "\xF0\x9F\x91\xA5معرفی مشتری",
            "\xF0\x9F\x93\x88معاملات باز",
            "\xF0\x9F\x93\x8Bلیست همکاران",
            "\xF0\x9F\x93\x9Aقوانین",
            "راهنما\xE2\x81\x89",
            "\xF0\x9F\x92\xB3\xF0\x9F\x8C\xB3حق اشتراک",
            "\xE2\x9A\xA0\xE2\x9D\x8Cغیرفعال سازی تایید دو مرحله ای",
            "\xE2\x9C\x8Cفعال سازی دو مرحله ای",
            "\xE2\x9D\x8Cغیر فعال فوری",
        ];
        if (in_array($this->message, $accept))
            return true;
        $im = implode("|",$this->list_type);
        $pattern_un = "/^([0-9]{3}|[0-9]{5})($im)([4-9]?)(:.*)?$/u";
        if (preg_match($pattern_un, $this->message, $matches)) {
            $optionalNumber = isset($matches[3]) && $matches[3]?$matches[3]: '1'; // اگر گروه سوم خالی بود، مقدار ۱ قرار داده شود
            logger("aa",[ $optionalNumber, $optionalNumber < 1 ,  $optionalNumber > 3]);

            if ($optionalNumber < 1 || $optionalNumber > 3)
            {
                $this->telegram_services->sendMessage($this->getUserId(), "❌ حداکثر تعداد برای هر لفظ ۳ تا میباشد ❌");
                return false;
            }
        }
        $pattern = "/^([0-9]{3}|[0-9]{5})($im)([1-3]?)(:.*)?$/u";

        logger($pattern,[preg_match($pattern, $this->message, $matches),$this->message]);
        if (preg_match($pattern, $this->message, $matches)) {
            logger("matches",[$matches]);
            $this->setPrice($matches[1]);
            $this->setType($matches[2]);
            $optionalNumber = isset($matches[3]) && $matches[3]?$matches[3]: '1'; // اگر گروه سوم خالی بود، مقدار ۱ قرار داده شود
            $this->setNumberOrder($optionalNumber);
            $description_test = isset($matches[4]) ? substr($matches[4], 1) : ''; // حذف ":" از ابتدای توضیحات
            $this->setDescriptionTest($description_test);
            $this->setPattern();
            logger("aa",[$this->pattern]);
            logger("aa",[preg_match($this->pattern, $this->message)]);
            if ($this->pattern && preg_match($this->pattern, $this->message))
                return true;
        }
        if($this->contact)
            return true;

        return false;
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
        {
            $start_price = (int)data_get($limit_trade, "start");
            $unit = getUnitPrice($start_price);
        }
        elseif ($length === 5)
        {
            $start_price = (int)($suggest_price * 1000);
            $unit = getUnitPrice($start_price);
            $suggest_price = $suggest_price % 1000;

        }
        $start_trade = floor($start_price/$unit)*$unit;



        $price = $start_trade + ($suggest_price *1000);



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




}
