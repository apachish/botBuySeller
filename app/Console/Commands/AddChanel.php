<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\UserTelegram;
use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class AddChanel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-chanel';

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
        $users = UserTelegram::get();
        $bot = Bot::where("title","botUser")->first(); // توکن بات
        $channelUsername = "@newsTabadol"; // نام کاربری کانال (مثلاً @MyChannel)

        if($bot)
        {
            $botToken = data_get($bot,"token");
            foreach ($users as $user) {

                $userId = data_get($user,'telegram_id'); // آیدی عددی کاربر

                $url = "https://api.telegram.org/bot$botToken/addChatMember";

                $data = [
                    "chat_id" => $channelUsername,
                    "user_id" => $userId
                ];
                sleep(1);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

                $response = curl_exec($ch);
                curl_close($ch);

                echo $response; // نمایش پاسخ API
            }
        }

    }
}
