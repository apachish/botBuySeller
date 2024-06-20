<?php

namespace App\Services\Admin;


use App\Services\TelegramServices;

class CustomerServices extends TextServices
{

    private $keyword = [
        [
            ['text' => "جستجو کاربر\xF0\x9F\x94\x8D"],
        ],
        [
            ['text' => "\xF0\x9F\x9A\xBBلیست همکاران"],
        ],
        [
            ['text' => "\xF0\x9F\x9A\xBBلیست مشتریان"],
        ],
        [
            ['text' => "\xF0\x9F\x9A\xBBلیست کاربران غیر فعال"],
        ],
        [
            ['text' => "\xF0\x9F\x9A\xBBلیست کاربران مسدود شده"],
        ],
        [
            ['text' => "\xE2\x86\xA9منو"]
        ],
    ];

    public function __construct()
    {
        $text = "کاربران سیستم";
        logger($text);

        TelegramServices::menu($this->telegram, $this->keyword, $this->getUser(), $text);
    }
}
