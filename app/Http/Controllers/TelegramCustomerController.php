<?php

namespace App\Http\Controllers;



use App\Services\SupportServices;

class TelegramCustomerController extends Controller
{
    public function setWebhook($token, $replay = [])
    {
        try {
            $text_services = new SupportServices($token);
            $text_services->setTypeMessage();
            $text_services->setUserId();
            $text_services->setMessageId();
            $key_cache = "text_user_customer";
            $text_services->setMessage();
            $text_services->setUser();
            logger("user",[$text_services->getUser()]);
            if($text_services->getUser() == null) return false;



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
