<?php

namespace App\Services;


use App\Models\Bot;
use App\Models\BotMenuUser;
use App\Models\CustomerUser;
use App\Models\Setting;
use App\Models\SupportTelegram;
use App\Models\Transfer;
use App\Models\UserTelegram;
use App\Services\Admin\CustomerServices;
use App\Services\Admin\SettingServices;
use App\Services\Admin\TimeServices;
use App\Services\Admin\TransactionServices;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Telegram\Bot\Api;

class ActionAdminServices extends TextServices
{
    public $service_user;
    public $bot_title;
    public $bot_user;
    public $key_cache_user = "text_user_";

    private $transaction;
    private $time;
    private $setting;
    private $custromer;

    public $keyboard_menu = [
        [
            ["text" => "\xF0\x9F\x93\x88معامله"],
            ['text' => "\xE2\x8C\x9Aزمان"]
        ],
        [
            ["text" => "\xF0\x9F\x9A\xBBکاربران"], ['text' => "\xF0\x9F\x94\xA7تنظیمات"]
        ],
        [
            ["text" => "کانال"],
            ['text' => "حسابداری"],
            ['text' => "لفظ"],
            ['text' => "دفترچه"],
        ],
    ];

    public function checkMessage()
    {

        $access_text = [
            "/start",
            "\xF0\x9F\x93\x88معامله",
            "\xE2\x8C\x9Aزمان",
            "\xF0\x9F\x9A\xBBکاربران",
            "\xF0\x9F\x94\xA7تنظیمات",
            "کانال",
            "حسابداری",
            "لفظ",
            "دفترچه",
            "\xE2\x86\xA9منو",
            "\xF0\x9F\x9A\xBBلیست کاربران",
            "جستجو کاربر\xF0\x9F\x94\x8D",
            "\xF0\x9F\x93\x88شروع مبلغ معامله",
            "\xF0\x9F\x93\x88سقف مبلغ معامله",
            "\xE2\x8C\x9Aساعت شروع",
            "\xE2\x8F\xB0ساعت پایان",
            "\xE2\x98\x81تعطیل/باز",
            "\xF0\x9F\x9A\xA9حذف پیام ها",
            "\xF0\x9F\x93\x9Aویرایش قوانین",
            "\xE2\x81\x89ویرایش راهنما",
            "\xF0\x9F\x92\xB3\xF0\x9F\x8C\xB3ویرایش حق اشتراک",
            "\xF0\x9F\x92\xB1کیف پول",
            "\xF0\x9F\x92\xACلیست پیام ها کاربران",
            "\xF0\x9F\x93\x81ارسال فایل"
        ];
        if (in_array($this->message, $access_text))
            return true;

        return false;
    }


    public function __construct($token)
    {
        parent::__construct($token);
        $this->transaction = new TransactionServices();
        $this->time = new TimeServices();
        $this->custromer = new CustomerServices();
        $this->setting = new SettingServices();
        $this->bot_user = Bot::where('title', "botUser")
            ->first();
//        $this->bot_user = cache()->remember("telegram_user", now()->addDay(), function () {
//            return Bot::where('title', "botUser")
//                ->first();
//        });
        if ($this->bot_user) {
            logger("bot user active",[$this->bot_user]);
//            $this->service_telgram_user = new TelegramServices($this->bot_user->token);
            $this->service_user = new ActionServices($this->bot_user->token);
            $this->bot_title = $this->bot_user->title;
        }
    }

    public function actionData()
    {
        logger("actionText", [$this->getData()]);
        if (str_contains($this->getData(), "ok_user_"))
            $this->custromer->AcceptUser($this);
        if (str_contains($this->getData(), "reject_user_"))
            $this->custromer->rejectUser($this);
        elseif (str_contains($this->getData(), "pre_"))
            $this->custromer->pre($this);
        elseif (str_contains($this->getData(), "next_"))
            $this->custromer->next($this);
        elseif (str_contains($this->getData(), "forbidden_day"))
            $this->transaction->setForbiddenDay($this);
        elseif (str_contains($this->getData(), "forbidden_"))
            $this->transaction->setForbidden($this);
        elseif (str_contains($this->getData(), "set_worker_"))
            $this->custromer->setRole($this);
        elseif (str_contains($this->getData(), "set_head_done_"))
            $this->custromer->setHeadCustomer($this);
        elseif (str_contains($this->getData(), "get_membership_"))
            $this->custromer->getMemberShip($this);
        elseif (str_contains($this->getData(), "head_customer_"))
            $this->custromer->headCustomer($this);
        elseif (str_contains($this->getData(), "add_chanel_"))
            $this->custromer->addChanel($this);
        elseif (str_contains($this->getData(), "confirm_"))
            $this->custromer->confirm($this);
        elseif (str_contains($this->getData(), "active_"))
            $this->custromer->active($this);
        elseif (str_contains($this->getData(), "reject_"))
            $this->custromer->reject($this);
        elseif (str_contains($this->getData(), "delete_"))
            $this->custromer->delete($this);
        elseif (str_contains($this->getData(), "edit_name_"))
            $this->custromer->getEditName($this);
        elseif (str_contains($this->getData(), "say_mobile_action_"))
            $this->custromer->setMobileNew($this);
        elseif (str_contains($this->getData(), "sync_mobile_"))
            $this->custromer->syncMobile($this);
    }

    public function actionText()
    {
        cache()->forget($this->getKeyCache() . $this->getUserId());
        logger("actionText", [$this->getMessage()]);
        switch ($this->getMessage()) {
            case "\xE2\x86\xA9منو":

                logger("bargashti");
                TelegramServices::menu($this->telegram, $this->keyboard_menu, $this->getUser(), "بازگشت");
                break;
            case "\xF0\x9F\x93\x88معامله":
                $text = "تغییر در معاملات انجام دهید";
                TelegramServices::menu($this->telegram, $this->transaction->keyword, $this->getUser(), $text);
                break;
            case  "\xF0\x9F\x9A\xAB\xF0\x9F\x9A\xBBممنوع معامله":
                $this->transaction->getForbidden($this);
                break;
            case  "\xF0\x9F\x9A\xAB\xE2\x98\x80ممنوع معامله روز":
                $this->transaction->getForbiddenDay($this);
                break;
            case "\xF0\x9F\x93\x88شروع مبلغ معامله":
                $this->transaction->getStartPrice($this);
                break;
            case "\xF0\x9F\x93\x88سقف مبلغ معامله":
                $this->transaction->getEndPrice($this);
                break;
            case "\xF0\x9F\x9A\xBBکاربران":
                $text = "مدیریت کاربران سیستم";
                TelegramServices::menu($this->telegram, $this->custromer->keyword, $this->getUser(), $text);
                break;
            case "\xF0\x9F\x9A\xBBلیست مشتریان":
                $this->custromer->listCustomer($this);
                break;
            case "\xF0\x9F\x9A\xBBلیست همکاران":
                $this->custromer->listColleague($this);
                break;
            case "\xF0\x9F\x92\xACلیست پیام ها کاربران":
                $this->listMessageSupport();
                break;
            case "جستجو کاربر\xF0\x9F\x94\x8D":
                cache()->set($this->getKeyCache() . $this->getUserId(), "find_user");
                $this->getTelegramServices()->sendMessage($this->getUserId(), "شماره تلفن یا نام و نام خانوادگی کاربر مورد نظر وارد کنید");
                break;
            case "\xF0\x9F\x94\xA7تنظیمات":
                $text = "تنظیات سیستم";
                TelegramServices::menu($this->telegram, $this->setting->keyword, $this->getUser(), $text);
                break;
            case "\xF0\x9F\x93\x9Aویرایش قوانین":
                $this->setting->getRule($this);
                break;
            case  "\xE2\x81\x89ویرایش راهنما":
                $this->setting->getHelp($this);
                break;
            case  "\xF0\x9F\x92\xB3\xF0\x9F\x8C\xB3ویرایش حق اشتراک":
                $this->setting->getMembership($this);
                break;
            case  "\xF0\x9F\x92\xB1کیف پول":
                $this->setting->getWalletMembership($this);
                break;
            case "\xE2\x8C\x9Aزمان":
                $text = "تنظیات زمان معاملات";
                TelegramServices::menu($this->telegram, $this->time->keyword, $this->getUser(), $text);
                break;

            case "\xE2\x8C\x9Aساعت شروع":
                $this->time->getStartOperation($this);
                break;
            case "\xE2\x8F\xB0ساعت پایان":
                $this->time->getEndOperation($this);

                break;

            case "\xE2\x98\x81باز":
                $this->time->open($this);
                break;
            case "\xE2\x98\x81تعطیل":
                $this->time->open($this);

                break;
            case "کانال":
                $this->getTelegramServices()->sendMessage($this->getUserId(), $this->bot->chanel_link);

                break;
            case "حسابداری":
                $this->getTelegramServices()->sendMessage($this->getUserId(), $this->bot->accounting);

                break;
            case "لفظ":
                $this->getTelegramServices()->sendMessage($this->getUserId(), $this->bot->word);

                break;
            case "دفترچه":
                $this->getTelegramServices()->sendMessage($this->getUserId(), $this->bot->contact);

                break;

        }
    }

    public function actionTextCache()
    {
        $key_case = $this->getMessageCache();
        logger("cache", [$this->getMessageCache()]);
        if (str_contains($this->getMessageCache(), "edit_name_done_"))
            $key_case = "edit_name_done_";
        elseif (str_contains($this->getMessageCache(), "edit_mobile_done_"))
            $key_case = "edit_mobile_done_";
        elseif (str_contains($this->getMessageCache(), "set_head_select_"))
            $key_case = "set_head_select_";
        elseif (str_contains($this->getMessageCache(), "set_membership_"))
            $key_case = "set_membership_";
        elseif (str_contains($this->getMessageCache(), "say_mobile_new_"))
            $key_case = "say_mobile_new_";

        logger($key_case);
        switch ($key_case) {

            case "say_mobile_new_":
                $this->custromer->getMobile($this);
                break;
                case "set_head_select_":
                $this->custromer->selectHeadCustomer($this);
                break;
            case "set_membership_":
                $this->custromer->setMemberShip($this);
                break;
            case "edit_name_done_":
                $this->custromer->setName($this);
                break;
            case "find_user":
                $this->custromer->findUser($this);
                break;

            case "rule":
                $this->setting->setRule($this);
                break;
            case "help":
                $this->setting->setHelp($this);
                break;
            case "membership":
                $this->setting->setMembership($this);
                break;
            case "wallet_membership":
                $this->setting->setWalletMembership($this);
                break;
            case "start_price_trade":
                $this->time->setStartPriceTrade($this);
                break;
            case "end_price_trade":
                $this->time->setEndPriceTrade($this);
                break;
            case "start_hours_of_operation":
                $this->time->setStartHoursOfOperation($this);
                break;
            case "end_hours_of_operation":
                $this->time->setEndHoursOfOperation($this);
                break;
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|object|UserTelegram $user_con
     * @return void
     */
    public function changeMenu($user_con): void
    {
        $menu_bot = BotMenuUser::where("user_id", $user_con->id)->where("bot_id", $this->bot_user->id)->first();
        logger("menu bot", [$menu_bot]);
        if ($menu_bot) {
            $key = $user_con->role == "colleague" ? $this->keyword_colleague : $this->keyword_customer;
            if (!$user_con->status)
                $key = new \stdClass();
            $this->service_user->telegram_services->editCustomKeyboard($user_con->id, $menu_bot->menu_id, "تغییر منو", $key);
            cache()->forget("keyword_menu" . $this->getKeyCache() . $user_con->id);
        }
    }

}
