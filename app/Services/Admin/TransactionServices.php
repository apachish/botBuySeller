<?php

namespace App\Services\Admin;




use App\Models\Setting;
use App\Services\TextServices;

class TransactionServices extends TextServices
{
    public $keyword = [
        [
            ['text' => "\xF0\x9F\x9A\xAB\xE2\x98\x80ممنوع معامله روز"],
        ], [
            ['text' => "\xF0\x9F\x9A\xAB\xF0\x9F\x9A\xBBممنوع معامله"],
        ], [
            ['text' => "\xF0\x9F\x93\x88شروع مبلغ معامله"],
        ], [
            ['text' => "\xF0\x9F\x93\x88سقف مبلغ معامله"],
        ],
        [
            ['text' => "\xE2\x86\xA9منو"]
        ],
    ];

    public function __construct($token)
    {
        parent::__construct($token);
    }

    /*
     * "\xF0\x9F\x9A\xAB\xF0\x9F\x9A\xBBممنوع معامله"
     */
    public function setForbidden()
    {
        $forbidden = Setting::where("key", "forbidden")->first();

        if ($forbidden) {
            $response_text = $forbidden->value ? "فعال" : "غیرفعال";

            $response_text .= "\n\n";
            $response_text .= "معامله همکار و مشتری";
        } else
            $response_text = "مشخص کنید معامله مشتری و همکار چگونه می باشد";

        $keyboard[0][0] = ['text' => "فعال", "callback_data" => "forbidden_active"];
        $keyboard[0][1] = ['text' => "غیرفعال", "callback_data" => "forbidden_deactivate"];


        logger("forbiden",[$this->getUserId()]);

        //$menu = $this->getTelegramServices()->MessageReplyMarkup($this->getTelegram(), $this->getUserId(), $response_text, $keyboard);
        //cache()->set("forbidden_" . $this->getUserId(), $menu);
    }
}
