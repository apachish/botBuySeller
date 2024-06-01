<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Services\TelegramServices;
use Illuminate\Console\Command;

class TestApiTelegram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-api-telegram';

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
        $token = $this->ask('What is  token bot?');
        $user_id = $this->ask('What is  user_id?');
        $menu_id = $this->ask('What is  menu_id?');
        $keyword_colleague = [
            [
                ['text' => "\xF0\x9F\x91\xA5	معرفی مشتری"],
                ['text' => "\xF0\x9F\x93\x8B	لیست همکاران"],
            ],
            [
                ['text' => "\xF0\x9F\x93\x88	معاملات باز"]
            ],
            [
                ['text' => "\xF0\x9F\x93\x9A	قوانین"],
                ['text' => "راهنما \xE2\x81\x89"]
            ], [
                ['text' => "\xE2\x9C\x8C	فعال سازی دو مرحله ای"],
                ['text' => "\xE2\x9D\x8C	غیر فعال فوری"],

            ]];
        $telegram_services = new TelegramServices($token);
        $d = $telegram_services->editCustomKeyboard($user_id, $menu_id, "تغییر منو", $keyword_colleague);
        dd($d);
    }
}
