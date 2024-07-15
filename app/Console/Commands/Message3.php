<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\Setting;
use App\Models\UserTelegram;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Telegram\Bot\Api;

class Message3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:message3';

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
        $time = Carbon::now();

        $message = Setting::whereIn("key", ["message_3", "forbidden_day","vacation"])->get()->keyBy('key');

        $forbidden_day = data_get($message, "forbidden_day.value");
        if(!$forbidden_day)
            $forbidden_day = $time->isThursday() || $time->isFriday()?true:false;
        if (data_get($message, "message_3.value") && !$forbidden_day && !data_get($message, "vacation.value")) {

            $bot_user = Bot::where("title", "botUser")->first();

            if ($bot_user) {
                $users = UserTelegram::get();
                $telegram_user = new Api($bot_user->token);
                $telegram_user->sendMessage([
                    'chat_id' => data_get($bot_user, "chanel_id"),
                    'text' => data_get($message, "value"),
                ]);
                foreach ($users as $user) {
                    try {
                        if (data_get($user, "telegram_id"))
                            $telegram_user->sendMessage([
                                'chat_id' => data_get($user, "telegram_id"),
                                'text' => data_get($message, "value"),
                            ]);
                    } catch (\Exception $exception) {
                        logger("user " . $user->telegram_id . ":" . $exception->getMessage());
                    }


                }
            }


        }
    }
}
