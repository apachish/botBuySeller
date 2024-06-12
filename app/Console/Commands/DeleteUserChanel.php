<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\Transfer;
use App\Models\UserTelegram;
use App\Services\TelegramServices;
use Illuminate\Console\Command;

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
            $telegram = new TelegramServices($bot->token);
            $users = UserTelegram::all();
            foreach ($users as $user)
            {
                $telegram->kickChatMember($bot->chanel_id, $user->id);
            }
            logger("end delete Message");
        }

    }

// تابع برای حذف پیام
}
