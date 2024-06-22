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
            logger("user", [$text_services->getUser()]);
            if ($text_services->getUser() == null) return false;

            if ($text_services->getMessage() == "/start") {
                $message = "با تشکر از ثبت نام در ربات مشتریان";
                $message .= "\n\n";
                $message .= "
از این پس فاکتور هر معامله ای که توسط زیر مجموعه هایتان انجام شود اینجا برایتان ارسال میگردد";
                $message .= "\n\n";
                $message .= "
در نظر داشته باشید اسم طرف معامله مشتریانتان فقط برای شما قابل روئیت میباشد";

                    $text_services->telegram_services->sendMessage($text_services->getUserId(), $message);
            } else {

            }


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
