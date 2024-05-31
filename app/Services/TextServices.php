<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\Setting;
use App\Models\TextTelegram;
use App\Models\Transfer;
use App\Models\UserTelegram;
use App\Models\UserTradeAccess;
use Telegram\Bot\Api;

class TextServices
{
    private static $list_type = ["فف", "خخ", "خ", "ف", "خفش", "خش", "ففش", "فش","خن","خفن","فن","ففن"];
    protected $list_type_buy = [ "خخ", "خ", "خفش", "خش","خن","خفن"];
    protected $list_type_sell = ["فف", "ف", "ففش", "فش","فن","ففن"];
    protected $list_type_sell_n_buy_tom = ["خفن","ففن"];
    protected $list_type_sell_tommarow = ["فف",  "ففش","ففن"];
    protected $list_type_buy_tommarow = [ "خخ", "خفش","خفن"];

    private  $type;

    private $type_message;

    protected $message;

    private $message_cache;

    public $data;

    private $bot;

    protected $telegram;

    protected $message_menu = "خوش آمدید";

    private $pattern;

    protected $telegram_services;

    private $token;

    private $update;

    private $user;

    private $user_id;

    private $key_cache;


    public function __construct($token)
    {
        $this->token = $token;
        $this->bot = cache()->remember("token_" . $token, now()->addDay(), function () {
            return Bot::where('token', $this->token)
                ->first();
        });
        $this->telegram = new Api($this->token);

        $this->telegram_services = new TelegramServices($this->token);
        /*
         *         // دریافت پیام ارسال شده به بات
        //        $input = file_get_contents("php://input");
        //        $update = json_decode($input, true);
         */
        $this->update = $this->telegram->getWebhookUpdate();
        logger("bot user", [$this->update]);
    }

    /**
     * @return Api
     */
    public function getTelegram(): Api
    {
        return $this->telegram;
    }
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
        logger("type_messager",[$this->type_message]);
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
        logger("user_id",[$this->user_id]);
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser(): void
    {
        $user_telegram = UserTelegram::where("id", $this->user_id)->first();
        if ($user_telegram == null) {
            $update = $this->update;
            $type = $this->type_message;
            $user_telegram = UserTelegram::create([
                "id" => $this->user_id,
                "is_bot" => data_get($update, $type . '.from.is_bot'),
                "first_name" => data_get($update, $type . '.from.first_name'),
                "last_name" => data_get($update, $type . '.from.last_name'),
                "mobile" => data_get($update, $type . '.mobile'),
                "username" => data_get($update, $type . '.from.username'),
                "language_code" => data_get($update, $type . '.from.language_code'),
            ]);
            $this->sendMessageNewUser();
        }
        $this->user = $user_telegram;
        logger("user",[$this->user]);
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

        logger("messgae_id",[$this->message_id]);
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
        $this->message = isset($this->update['message']['text']) ? $this->update['message']['text'] : null;
        logger("message",[$this->message]);
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
        logger("message_cache",[ $this->message_cache,$this->key_cache . $this->user_id]);

        // data_get($cache_data, "title")
    }

    /**
     * @return mixed
     */
    public  function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public  function setType($type): void
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
        $this->pattern = "/^\d{3,5}" . self::$type . "\d{1}$/";
    }

    public function checkText()
    {

        $accept = [
            "/start",
            "start",
            "/help",
            "transfer_buy_true",
            "trade_number_limit",
            "\xE2\x98\x8E	دفترچه تلفن",
            "📈 معاملات باز",
            "📋 لیست همکاران",
            "📚   قوانین",
            "\xE2\x81\x89  راهنما",
            "\xE2\x9A\xA0	\xE2\x9D\x8C	غیرفعال سازی تایید دو مرحله ای ",
            "\xE2\x9C\x8C	فعال سازی دو مرحله ای",
            "\xE2\x9D\x8C	غیر فعال فوری",
        ];
        if (in_array($this->message, $accept))
            return true;
        collect(self::$list_type)->contains(function (int $value, int $key) {
            if (str_contains($this->message, $value))
            {
                $this->setType($value);
                $this->setPattern();
            }
        });
        if ($this->pattern &&   preg_match($this->pattern, $this->message))
            return true;

        return false;
    }

    public function checkData()
    {
        if (str_contains($this->data, "request_transfer_") ||
            str_contains($this->data, "trade_limit_"))
            return true;
        return false;
    }

    public function actionByMessage()
    {
        /*
       * check message
       */
        if (!$this->checkText())
            $this->telegram->sendMessage(['chat_id' => $this->user_id, 'text' => 'متن نا معتبر می باشد']);
        TextTelegram::create([
            "update_id" => data_get($this->update, 'update_id'),
            "message_id" => data_get($this->update, 'message.message_id'),
            "user_telegram_id" => $this->user->id,
            "text" => data_get($this->update, 'message.text'),
            "data" => json_encode($this->update)
        ]);
        if (preg_match($this->pattern, $this->message))
            $this->checkWord();
        switch ($this->message) {
            case '/start':
            case 'start':
                $text = "منتظر تایید مدیر سیستم باشید تا دسترسی به شما ارائه گردد";
                if (!$this->user->mobile)
                    $text = "شماره موبایل خود را وارد کنید";
                elseif (!$this->user->fullName)
                    $text = "نام و نام خانوادگی خود را وارد کنید";
                cache()->remember("text_" . $this->user_id, now()->addDay(1), function () use ($text) {
                    return $text;
                });
                $this->telegram->sendMessage(['chat_id' => $this->user_id, 'text' => $text]);
                break;
            case '/help':
                $this->telegram->sendMessage(['chat_id' => $this->user_id, 'text' => 'سلام! چطور می‌توانم به شما کمک کنم؟']);
                break;
            case "trade_number_limit":
                $worker_id = (int)data_get($this->message_cache, "value");
                UserTradeAccess::updateOrCreate([
                    "user_id" => $this->user_id,
                    "user_trade_id" => $worker_id,
                    "limit_access" => $this->message
                ]);
                $this->telegram->sendMessage(['chat_id' => $this->user_id, 'text' => 'حد ثابت شد']);
                cache()->forget($this->key_cache . $this->user_id);
                break;
            case "\xF0\x9F\x91\xA5	معرفی مشتری":
            case "\xF0\x9F\x93\x88	معاملات باز":
            case "\xF0\x9F\x93\x8B	لیست همکاران":
            case "\xF0\x9F\x93\x9A	قوانین":
            case "راهنما \xE2\x81\x89":
            case "\xE2\x9A\xA0	\xE2\x9D\x8C	غیرفعال سازی تایید دو مرحله ای ":
            case "\xE2\x9C\x8C	فعال سازی دو مرحله ای":
            case  "\xE2\x9D\x8C	غیر فعال فوری":
                $this->getAction();
                break;
            default:
                $text_send = cache()->get("text_" . $this->user_id);
                if ($text_send == "شماره موبایل خود را وارد کنید") {
                    $mobile = $this->convertNumber($this->message);
                    if ($this->iranMobile($mobile)) {
                        $this->user->mobile = $mobile;
                        $this->user->update();
                        if (!$this->user->fullName) {
                            $text = "نام و نام خانوادگی خود را وارد کنید";
                            cache()->set("text_" . $this->user_id, $text);
                            $this->telegram->sendMessage(['chat_id' => $this->user_id, 'text' => $text]);
                        }
                    } else
                        $this->telegram->sendMessage(['chat_id' => $this->user_id, 'text' => "شماره همراه وارد شده نامعتبر می باشد دوباره وارد کنید"]);

                } elseif ($text_send == "نام و نام خانوادگی خود را وارد کنید") {
                    $this->user->fullName = $this->message;
                    $this->user->update();
                    $text = "منتظر تایید مدیر سیستم باشید تا دسترسی به شما ارائه گردد";
                    cache()->set("text_" .  $this->user_id, $text);
                    $this->telegram->sendMessage(['chat_id' =>  $this->user_id, 'text' => $text]);

                } else
                    $this->telegram->sendMessage(['chat_id' =>  $this->user_id, 'text' => 'متن نا معتبر می باشد']);
                break;
        }


    }

    public function actionByData()
    {
        /*
        * check data
        */
        if (!$this->checkData())
            $this->telegram->sendMessage(['chat_id' => $this->user_id, 'text' => 'متن نا معتبر می باشد']);
        elseif (str_contains($this->data, "request_transfer_"))
            $this->requestTransfer();
        elseif (str_contains($this->data, "transfer_buy_"))
            $this->transferBuy();
        elseif (str_contains($this->data, "trade_limit_"))
            $this->tradeLimit();
    }

    private function getAction()
    {
            switch ($this->message) {
                case "\xF0\x9F\x91\xA5	معرفی مشتری":
                    break;
                case "\xF0\x9F\x93\x88	معاملات باز":
                    $worker = UserTelegram::where("user_id", $this->user_id)->get();
                    $keyboard = [];
                    $i = 0;
                    $worker->each(function ($row) use (&$i, &$keyboard) {
                        $text = $row->fullName ?: $row->first_name . " " . $row->last_name;

                        $keyboard[$i++] = [
                            ['text' => $text, 'callback_data' => "trade_open_" . $row->id],
                        ];
                    });
                    $this->telegram_services->sendMessage($this->user_id, "شخص مورد نظر را انتخاب کنید", $keyboard);
                    break;

                case "📋 لیست همکاران":
                    $text = "لیست  همکاران";
                    $users = UserTelegram::where("id", "!=", $this->user_id)
                        ->with("userTradeAccess")->simplePaginate(5);
                    logger("users", [$users]);
                    $page = $users->currentPage();
                    $next = $users->nextPageUrl();
                    $pre = $users->previousPageUrl();
                    logger("page", [$next, $page, $pre]);
                    $keyboard = [];
                    $i = 0;

                    $users->each(function ($user) use (&$keyboard, &$i) {
                        $text = $user->fullName ?: $user->first_name . " " . $user->last_name;
                        $limit_trade = $user->userTradeAccess->where("user_trade_id", $this->user_id);
                        logger('limit_trade', [$limit_trade]);

                        $keyboard[$i][0] = [
                            'text' => "  $text " . ($limit_trade->count() ? "\xE2\x9D\x8C" : "\xE2\x9C\x85"),
                            'callback_data' => "trade_limit_" . $this->user_id
                        ];
                        if ($limit_trade->count())
                            $keyboard[$i][1] = [
                                'text' => "مجاز تا" . $limit_trade->limit_access . " تا",
                                'callback_data' => "trade_limit_" . $this->user_id];


                        $i++;
                    });
                    logger("keyboard", [$keyboard]);
                    if ($pre)
                        $keyboard[$i][] = ['text' => "قبلی", "callback_data" => "pre"];
                    if ($pre)
                        $keyboard[$i][] = ['text' => "بعدی", "callback_data" => "next"];

                    $this->telegram_services->MessageReplyMarkup($this->telegram, $this->user_id, $text, $keyboard);
                    break;

                case "📚   قوانین":
                    $help = Setting::where("key", "rule")->first();
                    $this->telegram_services->sendMessage($this->user_id, $help->value);
                    break;

                case "\xE2\x81\x89  راهنما":
                    $help = Setting::where("key", "help")->first();
                    $this->telegram_services->sendMessage($this->user_id, $help->value);

                    break;

                case "\xE2\x9A\xA0	\xE2\x9D\x8C	غیرفعال سازی تایید دو مرحله ای ":
                    $this->user->verify_two = false;
                    $this->user->update();
                    $this->telegram_services->sendMessage($this->user_id, "تایید دو مرحله ای غیر فعال شد");
                    break;

                case "\xE2\x9C\x8C	فعال سازی دو مرحله ای":
                    $this->user->verify_two = true;
                    $this->user->update();
                    $this->telegram_services->sendMessage($this->user_id, "تایید دو مرحله ای  فعال شد");

                    break;

                case  "\xE2\x9D\x8C	غیر فعال فوری":
                    $this->user->delete();
                    $this->user->menu();
                    break;
                default:
                    return false;
            }

    }


    private function sendMessageNewUser(): void
    {
        if (isset($this->update["my_chat_member"])) {
            $chatId = $this->update['my_chat_member']['from']['id']; // چت‌آیدی کاربر

            $this->telegram->sendMessage([
                'chat_id' => $this->user_id,
                'text' => 'خوش آمدید! این یک پیام خودکار به کاربر جدید است.'
            ]);
        }
    }

    public function accessAdmin()
    {
       return $this->bot->accessBot->where("user_id", $this->user_id)->where("type", "admin");
    }

    public function menu($keyboard,$show)
    {
        logger("menu",[$this->user,$show]);
        if ($show ) {
            if(cache()->get("keyword_menu".$this->getKeyCache().$this->user->id)) {

                TelegramServices::menu($this->telegram, $keyboard, $this->user, $this->message_menu);
                cache()->set("keyword_menu" . $this->getKeyCache() . $this->user->id, true);
            }

        } else {
            cache()->forget("keyword_menu".$this->getKeyCache().$this->user->id);
        }

    }



    private function iranMobile($value)
    {

        if ((bool)preg_match('/^(((98)|(\+98)|(0098)|0)(9){1}[0-9]{9})+$/', $value) || (bool)preg_match('/^(9){1}[0-9]{9}+$/', $value))
            return true;

        return false;
    }

    protected function convertNumber($value)
    {
        $western = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];
        $eastern = ['۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '۰'];
        return str_replace($eastern, $western, $value);
    }

}
