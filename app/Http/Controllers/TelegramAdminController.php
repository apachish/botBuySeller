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
            if ($text_services->getMessageCache()  && !$text_services->checkMessage())
                $text_services->actionTextCache();
            elseif ($text_services->getMessage())
                $text_services->actionText();



            $text = "سلام! به منوی اصلی خوش آمدید.";

            $keyboard_menu = [
                [
                    ['text' => "\xF0\x9F\x93\x88شروع مبلغ معامله"],
                ],
                [
                    ['text' => "\xF0\x9F\x9A\xBBلیست کاربران"],
                    ['text' => "جستجو کاربر\xF0\x9F\x94\x8D"],
                ],
                [
                    ['text' => "\xE2\x8C\x9Aساعت شروع"],
                    ['text' => "\xE2\x8F\xB0ساعت پایان"],
                    ['text' => "\xE2\x98\x81تعطیل/باز"],
                ],
                [
                    ['text' => "\xF0\x9F\x93\x9Aویرایش قوانین"],
                    ['text' => "\xE2\x81\x89ویرایش راهنما"],
                ],
                [
                    ['text' => "\xF0\x9F\x92\xB3\xF0\x9F\x8C\xB3ویرایش حق اشتراک"],
                    ['text' => "\xF0\x9F\x92\xB1کیف پول"]
                ],
                [
                    ['text' => "\xF0\x9F\x92\xACلیست پیام ها کاربران"],
                    ['text' => "\xF0\x9F\x93\x81ارسال فایل"],
                    ['text' => "\xF0\x9F\x9A\xA9حذف پیام ها"],
                ],
            ];
            $text_services->menu($keyboard_menu, $access);
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
