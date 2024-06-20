<?php

namespace App\Services\Admin;



use App\Services\TextServices;

class TransactionServices extends TextServices
{

    private $telegram;
    private $keyword = [
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

    public function __construct($telegram)
    {
        $this->telegram = $telegram;
        $text = "تغییر در معاملات انجام دهید";
        logger($text,[$this->getTelegram()]);
        $this->getTelegramServices()->menu($this->telegram, $this->keyword, $this->getUser(), $text);

    }
}
