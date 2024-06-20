<?php

namespace App\Services\Admin;


use App\Services\TelegramServices;
use App\Services\TextServices;

class SettingServices extends TextServices
{

    private $keyword = [
                [
                    ['text' => "\xF0\x9F\x93\x9Aویرایش قوانین"],
                    ['text' => "\xE2\x81\x89ویرایش راهنما"],
                ],
                [
                    ['text' => "\xF0\x9F\x92\xB3\xF0\x9F\x8C\xB3ویرایش حق اشتراک"],
                    ['text' => "\xF0\x9F\x92\xB1کیف پول"]
                ],
                [
                    ['text' => "\xE2\x86\xA9منو"]
                ],
            ];
    public function __construct()
    {
        $text = "تغییر در تنظیمات انجام دهید";
        logger($text);

        TelegramServices::menu($this->telegram, $this->keyword, $this->getUser(), $text);
    }
}
