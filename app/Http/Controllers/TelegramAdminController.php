<?php

namespace App\Http\Controllers;

use App\Http\Resources\ContactResourceCollection;
use App\Models\Setting;
use App\Services\ActionAdminServices;
use App\Services\TelegramServices;
use App\Services\TextServices;
use Illuminate\Http\Request;
use Telegram\Bot\Api;
use App\Models\Bot;
use App\Models\TextTelegram;
use App\Models\UserTelegram;
use Telegram\Bot\Keyboard\Keyboard;

class TelegramAdminController extends Controller
{
    private $access_token;

    public function setWebhook($token)
    {

        try {


            $text_services = new ActionAdminServices($token);

            $access = $text_services->accessAdmin();
            if ($access == null) return false;

            $text_services->setTypeMessage();
            $text_services->setUserId();
            $text_services->setMessageId();
            $text_services->setData();
            $key_cache = "text_admin_";
            $text_services->setKeyCache($key_cache);
            $text_services->setMessage();
            $text_services->setMessageCache();
            $text_services->setUser();
            if ($text_services->getData())
                $text_services->actionData();
            if ($text_services->getMessageCache())
                $text_services->actionTextCache();
            elseif ($text_services->getMessage())
                $text_services->actionText();

            $access_text = [
                "📞 دفترچه تلفن",
                "📋 لیست کاربران",
                "📋 لیست همکاران",
                "📚  ویرایش قوانین",
                "\xF0\x9F\x92\xB3\xF0\x9F\x8C\xB3ویرایش حق اشتراک",
                "\xE2\x81\x89 ویرایش راهنما",
                "📈محدود شروع مبلغ معاملات",
            ];

            $text = "سلام! به منوی اصلی خوش آمدید.";

            $keyboard_menu = [
                [
                    ['text' => "📞 دفترچه تلفن"],
                    ['text' => "📈محدود شروع مبلغ معاملات"]
                ],
                [
                    ['text' => "📋 لیست همکاران"],
                    ['text' => "📋 لیست کاربران"]
                ],
//            [
//                ['text' => "🔍 جستجو کاربر"]
//            ],
                [
                    ['text' => "📚  ویرایش قوانین"]
                ],
                [
                    ['text' => "\xE2\x81\x89 ویرایش راهنما"]
                ],
                [
                    ['text' =>  "\xF0\x9F\x92\xB3\xF0\x9F\x8C\xB3ویرایش حق اشتراک"]
                ],
            ];
            $text_services->menu($keyboard_menu, $access);
        }catch (\Exception $exception){
            logger("get error",[
                $exception->getMessage(),
                $exception->getLine(),
                $exception->getCode(),
                $exception->getTrace(),
                $exception->getFile()
            ]);
        }

    }


}
