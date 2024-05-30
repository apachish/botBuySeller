<?php

namespace App\Http\Controllers;

use App\Jobs\DeactivateTransfer;
use App\Models\Setting;
use App\Models\Transfer;
use App\Models\UserTradeAccess;
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
        $text_services = new TextServices($token);
        $text_services->setTypeMessage();
        $text_services->setUserId();
        $text_services->setMessageId();
        $text_services->setData();
        $text_services->setMessage();
        $text_services->setMessageCache();
        $text_services->setUser();

        $text_services->menu();
        if($text_services->getData())
            $text_services->actionByData();

        if($text_services->getMessage())
            $text_services->actionByMessage();


    }

}
