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
            $updates = Transfer::where("created_at",$targetDate)->get();



            foreach ($updates as $update) {

                        // حذف پیام
                        logger("aaaaa",[$bot,$update,$bot->token, ]);
                        $result = $this->deleteMessage($bot->token, $bot->chanel_id, $update->message_id);
                        if ($result['ok']) {
                            echo "پیام با شناسه $update->message_id حذف شد.\n";
                        } else {
                            echo "خطا در حذف پیام با شناسه $update->message_id: " . $result['description'] . "\n";
                        }

            }
            logger("end delete Message");
        }

    }

// تابع برای حذف پیام
    function deleteMessage($apiToken, $chatId, $messageId) {
        $url = "https://api.telegram.org/bot$apiToken/deleteMessage";
        $data = [
            'chat_id' => $chatId,
            'message_id' => $messageId
        ];

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type:application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($data),
            ],
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return json_decode($result, true);
    }

}
