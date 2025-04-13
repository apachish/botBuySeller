<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\Setting;
use App\Models\UserTelegram;
use Illuminate\Console\Command;
use Telegram\Bot\Api;

class MessageStart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:message-start';

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
        $message = Setting::whereIn("key", ["message_start","vacation"])->get()->keyBy("key");
        if(data_get($message,"message_start.value") && !data_get($message, "vacation.value")){
            $bot_user = Bot::where("title", "botUser")->first();

            if($bot_user) {
                $users = UserTelegram::get();
                $telegram_user = new Api($bot_user->token);
                $telegram_user->sendMessage([
                    'chat_id' => data_get($bot_user,"chanel_id"),
                    'text' => data_get($message, "message_start.value"),
                ]);
                $this->sendSticker($bot_user->token,data_get($bot_user,"chanel_id"));
                foreach ($users as $user) {
                    try {
                        if(data_get($user,"telegram_id"))
                            $telegram_user->sendMessage([
                                'chat_id' => data_get($user,"telegram_id"),
                                'text' => data_get($message, "message_start.value"),
                            ]);
                    } catch (\Exception $exception) {
                        logger("user " . $user->telegram_id . ":" . $exception->getMessage());
                    }


                }
            }


        }
    }

    public function sendSticker($token,$chat_id)
    {


        $sticker_file = new \CURLFile(public_path('sticker.webp'), 'image/webp');

        $ch = curl_init("https://api.telegram.org/bot$token/sendSticker");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'chat_id' => $chat_id,
            'sticker' => $sticker_file
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
    }
}
