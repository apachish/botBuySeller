<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GetChanelId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-chanel-id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pattern_buy = '/^\d{3}خ\d{1,2}$/';

        $pattern = '/^[\x{06F0}-\x{06F9}]{3}خ[\x{06F0}-\x{06F9}]{1,2}$/u';
        $subject = '۳۲۰خ۱'; // رشته‌ای که می‌خواهید بررسی کنید

        $subject = $this->convertNumber($subject);
        if (preg_match($pattern_buy, $subject)) {
            echo "مطابقت دارد!";
        } else {
            echo "مطابقت ندارد!";
        }
        dd(explode("خ",$subject));
    }

    private function convertNumber($value)
    {
        $western = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];
        $eastern = ['۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '۰'];
        return str_replace($eastern, $western, $value);
    }

}
