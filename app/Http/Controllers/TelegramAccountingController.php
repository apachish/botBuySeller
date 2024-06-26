<?php

namespace App\Http\Controllers;


use App\Services\ActionAccountingServices;
use App\Services\SupportServices;

class TelegramAccountingController extends Controller
{
    public function setWebhook($token, $replay = [])
    {
        try {
            $text_services = new ActionAccountingServices($token);
            $access = $text_services->accessAdmin();
            if ($access == null) return false;
            $text_services->setTypeMessage();
            $text_services->setUserId();
            $text_services->setMessageId();
            $text_services->setData();
            $key_cache = "text_accounting_";
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
            $text_services->menu($text_services->keyboard_menu, $access);


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
