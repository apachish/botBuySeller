<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\Transfer;
use Illuminate\Console\Command;

class DeleteMessageChanel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-message-chanel';

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
            logger($targetDate->format("Y-m-d H:i"));
            $updates = Transfer::withTrashed()->
            whereNotNull("message_id")
                ->where("created_at","<=",$targetDate)->get();

            logger($updates->count());


            foreach ($updates as $update) {

                        // حذف پیام
                        $result = $this->deleteMessage($bot->token, $bot->chanel_id, $update->message_id);
                        logger("result",[$update->message_id,$result]);
                        if (data_get($result,'ok')) {
                            echo "پیام با شناسه $update->message_id حذف شد.\n";
                        } else {
                            echo "خطا در حذف پیام با شناسه $update->message_id: " .data_get($result,'description') . "\n";
                        }

            }
            logger("end delete Message");
        }

    }

// تابع برای حذف پیام
    function deleteMessage($apiToken, $chatId, $messageId) {
        $url = "https://api.telegram.org/bot$apiToken/deleteMessage";

        $post_fields = [
            'chat_id' => $chatId,
            'message_id' => $messageId
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:application/json"));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }

}
