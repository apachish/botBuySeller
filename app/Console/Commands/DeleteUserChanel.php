<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\Transfer;
use App\Models\UserTelegram;
use App\Services\TelegramServices;
use Illuminate\Console\Command;
use Telegram\Bot\Api;

class DeleteUserChanel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-user-chanel';

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
        $bot = Bot::where("title","botUser")->first();

        if($bot) {
            logger("delete Message");
            // تعیین تاریخ مورد نظر
            $targetDate = now()->subDay(1); // تاریخ مورد نظر برای حذف پیام‌ها (فرمت YYYY-MM-DD)
            $users = UserTelegram::all();
            $telegram = new Api($bot->token);
            foreach ($users as $user)
            {

                try {
                    // خارج کردن کاربر از کانال
                    $response = $telegram->kickChatMember([
                        'chat_id' => $bot->chanel_id,
                        'user_id' => $user->id,
                    ]);

                    if ($response) {
                        echo "User has been successfully removed from the channel.";
                    } else {
                        echo "Failed to remove user from the channel.";
                    }
                } catch (\Exception $e) {
                    echo "Error: " . $e->getMessage();
                }
            }
            logger("end delete Message");
        }

    }

// تابع برای حذف پیام
}
