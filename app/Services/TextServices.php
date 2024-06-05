<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotMenuUser;
use App\Models\CustomerUser;
use App\Models\Setting;
use App\Models\TextTelegram;
use App\Models\Transfer;
use App\Models\UserTelegram;
use App\Models\UserTradeAccess;
use Telegram\Bot\Api;

class TextServices
{
    private $list_type = [  "خفن","خفش","ففش","ففن", "فف","خف","خش","فش","خن","فن","خ","ف"];
    protected $list_type_buy = [ "خفش", "خش", "خفن", "خن","خف", "خ"];
    protected $list_type_sell = [ "ففش", "فش",  "ففن","فن","فف", "ف"];
    protected $list_type_sell_n_buy_tom = ["خفن", "ففن"];
    protected $list_type_sell_tommarow = ["فف", "ففش", "ففن"];
    protected $list_type_buy_tommarow = ["خخ", "خفش", "خفن"];

    private $type;
    private $price;

    private $number_order;
    private $description;

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
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
        logger("description".$this->description);

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
            $data =  array_filter([
                "id" => $this->user_id,
                "is_bot" => data_get($update, $type . '.from.is_bot'),
                "first_name" => data_get($update, $type . '.from.first_name'),
                "last_name" => data_get($update, $type . '.from.last_name'),
                "mobile" => data_get($update, $type . '.mobile'),
                "username" => data_get($update, $type . '.from.username'),
                "language_code" => data_get($update, $type . '.from.language_code'),
            ]);
            if ($update && $data) {
                $user_telegram = UserTelegram::create($data);
                $this->sendMessageNewUser();
                CustomerUser::updateOrCreate(["user_id" => $this->getUserId(), "mobile" => data_get($update, $type . '.mobile')],
                    [
                        "fullName" => "خودم",
                        "limit" => null
                    ]);
            }
        }
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
        $this->message = isset($this->update['message']['text']) ? $this->convertNumber($this->update['message']['text']) : null;
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
     * @return mixed
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @param mixed $contact
     */
    public function setContact(): void
    {
        if (isset($this->update['message']['contact']["phone_number"]))
            $this->contact = $this->convertNumber($this->update['message']['contact']["phone_number"]);
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
            "\xE2\x9A\xA0\xE2\x9D\x8Cغیرفعال سازی تایید دو مرحله ای",
            "\xE2\x9C\x8Cفعال سازی دو مرحله ای",
            "\xE2\x9D\x8Cغیر فعال فوری",
        ];
        if (in_array($this->message, $accept))
            return true;
        $im = implode("|",$this->list_type);
        $pattern = "/^([0-9]{3}|[0-9]{5})($im)([1-3]?)(:.*)?$/u";

        logger($pattern,[preg_match($pattern, $this->message, $matches),$this->message]);
        if (preg_match($pattern, $this->message, $matches)) {
            $this->setPrice($matches[1]);
            $this->setType($matches[2]);
            $optionalNumber = isset($matches[3]) ?$matches[3]: '1'; // اگر گروه سوم خالی بود، مقدار ۱ قرار داده شود
            $this->setNumberOrder($optionalNumber);
            $description = isset($matches[4]) ? substr($matches[4], 1) : ''; // حذف ":" از ابتدای توضیحات
            $this->setDescription($description);
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

    public function checkData()
    {
        if (str_contains($this->data, "request_transfer_") ||
            str_contains($this->data, "transfer_buy_") ||
            str_contains($this->data, "trade_limit_") ||
            str_contains($this->data, "trade_limit_close_"))
            return true;
        return false;
    }

    public function checkCache()
    {
        if (is_array($this->message_cache) && str_contains(data_get($this->message_cache, "title"), "trade_number_limit"))
            return true;
        elseif (str_contains($this->message_cache, "add_customer"))
            return true;
        elseif (str_contains($this->message_cache, "add_fullName"))
            return true;
        elseif (str_contains($this->message_cache, "add_mobile"))
            return true;
        elseif (str_contains($this->message_cache, "pending_accept"))
            return true;
        return false;
    }

    public function actionByMessage()
    {
        /*
       * check message
       */
        logger("check message", [$this->checkText()]);
        logger("cache", [$this->message_cache]);
        cache()->forget($this->key_cache . $this->user_id);
        logger("cache", [$this->message_cache]);

        if (!$this->checkText())
            return $this->telegram->sendMessage(['chat_id' => $this->user_id, 'text' => 'متن نا معتبر می باشد']);

        TextTelegram::create([
            "update_id" => data_get($this->update, 'update_id'),
            "message_id" => data_get($this->update, 'message.message_id'),
            "user_telegram_id" => $this->user->id,
            "text" => data_get($this->update, 'message.text'),
            "data" => json_encode($this->update)
        ]);
        logger("check word",[$this->pattern, $this->message,preg_match($this->pattern, $this->message)]);
        if ($this->pattern && preg_match($this->pattern, $this->message))
            return $this->checkWord();

        switch ($this->message) {
            case '/start':
            case 'start':
                $text = $this->user->status ? "خوش آمدید" : "منتظر تایید مدیر سیستم باشید تا دسترسی به شما ارائه گردد";
                $type_add = null;
                if (!$this->user->fullName) {
                    $text = "سلام به طبیعت گردی خوش آمدین لطفاٌ نام و نام خانوادگی وارد کنید";
                    cache()->set($this->key_cache . $this->user_id, "add_fullName");
                    $this->telegram->sendMessage(['chat_id' => $this->user_id, 'text' => $text]);

                } elseif (!$this->user->mobile) {
                    $text = "ممنون شماره خود را به اشتراک بگذارید";
                    $this->telegram_services->sendRequestContactButton($this->getUserId(), $text);
                }

                break;
            case '/help':
                $this->telegram->sendMessage(['chat_id' => $this->user_id, 'text' => 'سلام! چطور می‌توانم به شما کمک کنم؟']);
                break;
            case 'ن':
            case 'نشد':
                if($this->rejectAll())
                    $this->telegram->sendMessage(['chat_id' => $this->user_id, 'text' => 'لفظ های شما کنسل شد']);
            break;
            case "\xF0\x9F\x91\xA5معرفی مشتری":
            case "\xF0\x9F\x93\x88معاملات باز":
            case "\xF0\x9F\x93\x8Bلیست همکاران":
            case "\xF0\x9F\x93\x9Aقوانین":
            case "راهنما\xE2\x81\x89":
            case "\xE2\x9A\xA0\xE2\x9D\x8Cغیرفعال سازی تایید دو مرحله ای":
            case "\xE2\x9C\x8Cفعال سازی دو مرحله ای":
            case  "\xE2\x9D\x8Cغیر فعال فوری":
                $this->getAction();
                break;
            default:
                $this->telegram->sendMessage(['chat_id' => $this->user_id, 'text' => 'متن نا معتبر می باشد']);
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
        elseif (str_contains($this->data, "trade_limit_close_"))
            $this->tradeLimitClose();
        elseif (str_contains($this->data, "trade_limit_"))
            $this->tradeLimit();

    }

    public function actionByCache()
    {
        /*
        * check data
        */
        if (!$this->checkCache())
            $this->telegram->sendMessage(['chat_id' => $this->user_id, 'text' => 'متن نا معتبر می باشد']);
        elseif (is_array($this->message_cache) && str_contains(data_get($this->message_cache, "title"), "trade_number_limit"))
            $this->tradeNumberLimit();
        elseif (str_contains($this->message_cache, "add_customer"))
            $this->addCustomer();
        elseif (str_contains($this->message_cache, "add_mobile"))
            $this->addMobile();
        elseif (str_contains($this->message_cache, "add_fullName"))
            $this->addFullName();
        elseif (str_contains($this->message_cache, "pending_accept")) {
            if ($this->user->status)
                cache()->forget($this->getKeyCache() . $this->user->id);
            else
                $this->pendingAccept();

        }
    }

    private function getAction()
    {
        switch ($this->message) {
            case "\xF0\x9F\x91\xA5معرفی مشتری":
                $text = "مشتری خود را به صورت زیر وارد کنید\n\n";
                $text .= "موبایل:شماره موبایل,نام و نام خانوادگی :نام,حد:۳";
                $text .= "\n\n";
                $text .= "در صورت وارد نکردن حد مقدار حد آن آزاد می باشد";
                $this->telegram_services->sendMessage($this->user_id, $text);
                cache()->set($this->key_cache . $this->getUserId(), "add_customer");

                break;
            case "\xF0\x9F\x93\x88معاملات باز":
                $worker = UserTelegram::where("user_id", $this->user_id)->whereHas("customerUser")->get();
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

            case "\xF0\x9F\x93\x8Bلیست همکاران":
                $text = "      لیست  همکاران    ";
                $text .= "\n\n";
                $text .= "میزان حد معامله خود با همکاران خود مشخص کنید";
                $users = UserTelegram::where("id", "!=", $this->user_id)
                    ->where("role", "colleague")
                    ->simplePaginate(5);
                logger("users", [$users]);
                $page = $users->currentPage();
                $next = $users->nextPageUrl();
                $pre = $users->previousPageUrl();
                logger("page", [$next, $page, $pre]);
                $keyboard = [];
                $i = 0;
                $userTradeAccess = $this->user->userTradeAccess;
                logger("userTradeAccess", [$userTradeAccess]);
                $users->each(function ($user) use (&$keyboard, &$i, $userTradeAccess) {
                    $text = $user->fullName ?: $user->first_name . " " . $user->last_name;
                    $limit_trade = $userTradeAccess->where("user_trade_id", $user->id)->first();


                    logger('limit_trade', [$limit_trade, data_get($limit_trade, "limit_access")]);

                    if ($limit_trade)
                        $keyboard[$i] = [
                            [
                                'text' => "  مجاز تا " . data_get($limit_trade, "limit_access") . "  تا ",
                                'callback_data' => "trade_limit_" . $user->id
                            ],
                            [
                                'text' => "  $text " . "\xE2\x9D\x8C",
                                'callback_data' => "trade_limit_close_" . $user->id . "_" . $i
                            ]
                        ];
                    else
                        $keyboard[$i][] = [
                            'text' => "  $text " . "\xE2\x9C\x85",
                            'callback_data' => "trade_limit_" . $user->id
                        ];
                    $i++;
                });
                logger("keyboard", [$keyboard]);
                if ($pre)
                    $keyboard[$i][] = ['text' => "قبلی", "callback_data" => "pre"];
                if ($pre)
                    $keyboard[$i][] = ['text' => "بعدی", "callback_data" => "next"];

                $this->telegram_services->MessageReplyMarkup($this->telegram, $this->user_id, $text, $keyboard);
                break;

            case "\xF0\x9F\x93\x9Aقوانین":
                $help = Setting::where("key", "rule")->first();
                $this->telegram_services->sendMessage($this->user_id, $help->value);
                break;

            case "\xE2\x81\x89راهنما":
                $help = Setting::where("key", "help")->first();
                $this->telegram_services->sendMessage($this->user_id, $help->value);

                break;

            case "\xE2\x9A\xA0\xE2\x9D\x8Cغیرفعال سازی تایید دو مرحله ای":
                $this->user->verify_two = false;
                $this->user->update();
                $this->telegram_services->sendMessage($this->user_id, "تایید دو مرحله ای غیر فعال شد");
                break;

            case "\xE2\x9D\x8Cغیر فعال فوری":
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

    public function menu($keyboard, $show)
    {
        if ($show) {
            if (!cache()->get("keyword_menu" . $this->getKeyCache() . $this->user->id)) {

                $this->telegram_services->deleteKeyboard($this->user_id);
                $response = TelegramServices::menu($this->telegram, $keyboard, $this->user, $this->message_menu);
                cache()->set("keyword_menu" . $this->getKeyCache() . $this->user->id, true);
            }

        } else {
            cache()->forget("keyword_menu" . $this->getKeyCache() . $this->user->id);
        }

    }


    protected function iranMobile($value)
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
