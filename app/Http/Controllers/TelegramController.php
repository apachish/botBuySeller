<?php

namespace App\Http\Controllers;

use App\Jobs\DeactivateTransfer;
use App\Models\Setting;
use App\Models\Transfer;
use App\Models\UserTradeAccess;
use App\Services\ActionServices;
use App\Services\TelegramServices;
use App\Services\TextServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Api;
use App\Models\Bot;
use App\Models\TextTelegram;
use App\Models\UserTelegram;
use Telegram\Bot\Keyboard\Keyboard;

class TelegramController extends Controller
{
    public function setWebhook($token, $replay = [])
    {
        $text_services = new ActionServices($token);
        $text_services->setTypeMessage();
        $text_services->setUserId();
        $text_services->setMessageId();
        $key_cache = "text_user_";
        $text_services->setKeyCache($key_cache);
        $text_services->setData();
        $text_services->setMessage();
        $text_services->setMessageCache();
        $text_services->setUser();

        if($text_services->getData())
            $text_services->actionByData();

        if($text_services->getMessage() && $text_services->checkText())
            $text_services->actionByMessage();
        elseif($text_services->getMessageCache())
            $text_services->actionByCache();


        $i = 0;
        if($text_services->getUser()->role == "colleague") {
            $keyboard_menu[$i++] = [
                ['text' => "\xF0\x9F\x91\xA5معرفی مشتری"],
                ['text' => "\xF0\x9F\x93\x8Bلیست همکاران"],
            ];
            $keyboard_menu[$i++] = [
                ['text' => "\xF0\x9F\x93\x88معاملات باز"]
            ];
        }
        $keyboard_menu[$i++] = [
                ['text' => "\xF0\x9F\x93\x9Aقوانین"],
                ['text' => "راهنما\xE2\x81\x89"]
            ];
        $keyboard_menu[$i++] = [
                ['text' => $text_services->getUser()->verify_two ? "\xE2\x9A\xA0\xE2\x9D\x8Cغیرفعال سازی تایید دو مرحله ای" : "\xE2\x9C\x8Cفعال سازی دو مرحله ای"],
                ['text' => "\xE2\x9D\x8Cغیر فعال فوری"],

            ];

        $text_services->menu($keyboard_menu,$text_services->getUser()->status);



    }

}
