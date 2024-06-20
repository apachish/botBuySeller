<?php

namespace App\Services\Admin;


use App\Services\TelegramServices;

class TimeServices
{
private $telegram;
private $user;
    private $keyword = [
        [
            ['text' => "\xE2\x8C\x9Aساعت شروع"],
        ],
        [
            ['text' => "\xE2\x8F\xB0ساعت پایان"],
        ],
        [
            ['text' => "\xE2\x98\x81تعطیل"],
        ],
        [
            ['text' => "🕰تاریخ فردا️"],
        ],
        [
            ['text' => "\xE2\x86\xA9منو"]
        ],
    ];

    public function __construct($telegram,$user)
    {
        $this->telegram = $telegram;
        $text = "تغییر در زمان معاملات انجام دهید";
        logger($text);

        TelegramServices::menu($this->telegram, $this->keyword, $user, $text);
    }
}
