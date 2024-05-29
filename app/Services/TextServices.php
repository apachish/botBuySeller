<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\Transfer;
use App\Models\UserTelegram;
use Telegram\Bot\Api;

class TextServices
{
    private static $list_type =  ["فف","خخ","خ","ف","خفش","خش","ففش","فش"];
    private static $type;
    private  $type_message;
    public $message;
    private $message_cache;

    public $data;

    private $bot;
    private $telegram;
    private $telegram_services;
    private $token;
    private $update;

    private  $user;

    private  $user_id;

    public function __construct($token)
    {
        $this->token = $token;
        $this->bot = cache()->remember("token_".$token,now()->addDay(),function() {
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
    }

    private  $message_id = null;

    public function getMessageId(): null
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
    public function setMessage($message): void
    {
        $this->message = isset($update['message']['text']) ? $update['message']['text'] : null;;
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
        $this->message_cache = cache()->get("text_cache_" . $this->user_id);
        // data_get($cache_data, "title")
    }

    /**
     * @return mixed
     */
    public static function getType()
    {
        return self::$type;
    }

    /**
     * @param mixed $type
     */
    public static function setType($type): void
    {
        self::$type = $type;
    }
    private static $pattern;

    /**
     * @return mixed
     */
    public static function getPattern()
    {
        return self::$pattern;
    }

    /**
     * @param mixed $pattern
     */
    public static function setPattern($pattern): void
    {
        self::$pattern = "/^\d{3,5}".self::$type."\d{1}$/";
    }

    public  function checkText()
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
        if(in_array($this->text,$accept))
            return true;
        collect(self::$list_type)->contains(function (int $value, int $key){
            if(str_contains($this->text,$value))
               self::setType($value);
        });
        if (preg_match(self::$pattern, $this->text))
            return true;

        return false;
    }
    public  function checkData()
    {
        if(str_contains($this->data, "request_transfer_") ||
            str_contains($this->data, "trade_limit_"))
            return true;
        return false;
    }

    public function actionByMessage()
    {
        /*
       * check message
       */
        $this->checkText();
        $this->telegram->sendMessage(['chat_id' => $this->user_id, 'text' => 'متن نا معتبر می باشد']);

    }

    public function actionByData()
    {
        /*
        * check data
        */
        if(!$this->checkData())
            $this->telegram->sendMessage(['chat_id' => $this->user_id, 'text' => 'متن نا معتبر می باشد']);

        if (str_contains($this->data, "request_transfer_"))
            ActionServices::requestTransfer();

    }
    private function sendMessageNewUser():void
    {
        if (isset($this->update["my_chat_member"])) {
            $chatId = $this->update['my_chat_member']['from']['id']; // چت‌آیدی کاربر

            $this->telegram->sendMessage([
                'chat_id' => $this->user_id,
                'text' => 'خوش آمدید! این یک پیام خودکار به کاربر جدید است.'
            ]);
        }
    }


    private function iranMobile($value)
    {

        if ((bool)preg_match('/^(((98)|(\+98)|(0098)|0)(9){1}[0-9]{9})+$/', $value) || (bool)preg_match('/^(9){1}[0-9]{9}+$/', $value))
            return true;

        return false;
    }

    private function convertNumber($value)
    {
        $western = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];
        $eastern = ['۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '۰'];
        return str_replace($eastern, $western, $value);
    }

}
