<?php

namespace App\Services;


use App\Models\Bot;
use App\Models\BotMenuUser;
use App\Models\Transfer;
use App\Models\UserTelegram;
use App\Services\Admin\CustomerServices;
use App\Services\Admin\SettingServices;
use App\Services\Admin\TimeServices;
use App\Services\Admin\TransactionServices;


class ActionAccountingServices extends TextServices
{

    public $keyboard_menu = [
        [
            ["text" => "\xF0\x9F\x94\x8Dجستجو"],
            ['text' => "\xF0\x9F\x94\x8D\xF0\x9F\x93\x83جستجو با حواله"]
        ],
    ];
    public function __construct($token)
    {
        parent::__construct($token);
    }


    public function checkMessage()
    {

        $access_text = [
            "/start",
            "\xF0\x9F\x94\x8Dجستجو",
            "\xF0\x9F\x94\x8D\xF0\x9F\x93\x83جستجو با حواله",
        ];
        if (in_array($this->message, $access_text))
            return true;

        return false;
    }

    public function actionData()
    {
//        logger("actionText", [$this->getData()]);
//        if (str_contains($this->getData(), "ok_user_"))
//            $this->custromer->AcceptUser($this);
    }

    public function actionText()
    {
        cache()->forget($this->getKeyCache() . $this->getUserId());
        logger("actionText", [$this->getMessage()]);
        switch ($this->getMessage()) {
            case "\xF0\x9F\x94\x8D\xF0\x9F\x93\x83جستجو با حواله":
                TelegramServices::menu($this->telegram, $this->keyboard_menu, $this->getUser(), "بازگشت");
                break;
            case "\xF0\x9F\x94\x8Dجستجو":
                $text = "تغییر در معاملات انجام دهید";
                TelegramServices::menu($this->telegram, $this->transaction->keyword, $this->getUser(), $text);
                break;
            case  "/start":
                $this->getTelegramServices()->sendMessage($this->getUserId(), "@" . $this->bot->contact);
                break;
        }
    }

    public function actionTextCache()
    {
        $key_case = $this->getMessageCache();
        logger("cache", [$this->getMessageCache()]);
//        if (str_contains($this->getMessageCache(), "edit_name_done_"))
//            $key_case = "edit_name_done_";


        logger($key_case);
        switch ($key_case) {

//            case "send_message_group":
//                $this->custromer->setMessageGroup($this);
//                break;

        }
    }

}
