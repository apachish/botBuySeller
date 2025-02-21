<?php

namespace App\Http\Controllers;

use App\Services\ActionServices;


class TelegramController extends Controller
{
    public function setWebhook($token, $replay = [])
    {
        try {
            if(!$token) return true;
            $text_services = new ActionServices($token);
            $text_services->setTypeMessage();
            $text_services->setUserId();
            $text_services->setMessageId();
            $key_cache = "text_user_";
            $text_services->setKeyCache($key_cache);
            $text_services->setData();
            $text_services->setMessage();
            $text_services->setContact();
            $text_services->setMessageCache();
            $text_services->setUser();
            if($text_services->getUser() == null) return false;
            if ($text_services->getData())
                $text_services->actionByData();

            elseif ($text_services->getContact())
                $text_services->addMobile();
            elseif ($text_services->getMessageCache() && !$text_services->checkText())
                $text_services->actionByCache();
            elseif ($text_services->getMessage() && $text_services->checkText())
                $text_services->actionByMessage();
            elseif($text_services->getMessage())
                $text_services->getTelegramServices()->sendMessage($text_services->getUserId(),"متن شما نامعتبر می باشد");


            if(!$text_services->getUser()->fullName || !$text_services->getUser()->mobile )
                return false;

            $keyboard_menu = $text_services->setMenu();

            $text_services->menu($keyboard_menu, $text_services->getUser()->status);
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
