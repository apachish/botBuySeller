<?php

namespace App\Services;


use App\Models\Bot;
use App\Services\Admin\CustomerServices;
use App\Services\Admin\SettingServices;
use App\Services\Admin\TimeServices;
use App\Services\Admin\TransactionServices;


class ActionAdminServices extends TextServices
{
    public $service_user;
    public $bot_title;
    public $bot_user;
    public $key_cache_user = "text_user_";

    public function checkMessage()
    {

        $access_text = [
            "/start",
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

    protected $keyword_colleague = [
        [
            ['text' => "\xF0\x9F\x91\xA5معرفی مشتری"],
            ['text' => "\xF0\x9F\x93\x8Bلیست همکاران"]
        ],
        [
            ['text' => "\xF0\x9F\x93\x88معاملات باز"]
        ],
        [
            ['text' => "\xF0\x9F\x93\x9Aقوانین"],
            ['text' => "راهنما\xE2\x81\x89"],
            ['text' => "\xF0\x9F\x92\xB3\xF0\x9F\x8C\xB3حق اشتراک"]

        ], [
            ['text' => "\xE2\x9C\x8Cفعال سازی دو مرحله ای"],
            ['text' => "\xE2\x9D\x8Cغیر فعال فوری"],

        ]];

    protected $keyword_customer = [
        [
            ['text' => "\xF0\x9F\x93\x9Aقوانین"],
            ['text' => "راهنما\xE2\x81\x89"],
            ['text' => "\xF0\x9F\x92\xB3\xF0\x9F\x8C\xB3حق اشتراک"]
        ], [
            ['text' => "\xE2\x9C\x8Cفعال سازی دو مرحله ای"],
            ['text' => "\xE2\x9D\x8Cغیر فعال فوری"],

        ]];

    public function __construct($token)
    {
        parent::__construct($token);

        $this->bot_user = cache()->remember("telegram_user", now()->addDay(), function () {
            return Bot::where('title', "botUser")
                ->first();
        });
        if ($this->bot_user) {
//            $this->service_telgram_user = new TelegramServices($this->bot_user->token);
            $this->service_user = new ActionServices($this->bot_user->token);
            $this->bot_title = $this->bot_user->title;
        }
    }

    public function actionData()
    {

    }

    public function actionTextCache()
    {

    }

    public function actionText()
    {
        logger("Message Admin:".$this->getMessage());
        switch ($this->getMessage()) {
            case "\xF0\x9F\x93\x88معامله":
                logger("inja");
                new TransactionServices($this->telegram);
                break;
            case "\xE2\x8C\x9Aزمان":
                new TimeServices();
                break;
            case "\xF0\x9F\x9A\xBBکاربران":
                new CustomerServices();
                break;
            case "\xF0\x9F\x94\xA7تنظیمات":
                new SettingServices();
                break;
            case "کانال":
                break;
            case "حسابداری":
                break;
            case "لفظ":
                break;
            case "دفترچه":
                break;
        }
    }
}
