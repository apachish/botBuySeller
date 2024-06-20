<?php

namespace App\Services\Admin;


use App\Models\Setting;

class TransactionServices
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


    public function setForbidden($object)
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

        $menu = $object->getTelegramServices()->MessageReplyMarkup($object->getTelegram(), $object->getUserId(), $response_text, $keyboard);
        cache()->set("forbidden_" . $object->getUserId(), $menu);
    }


}
