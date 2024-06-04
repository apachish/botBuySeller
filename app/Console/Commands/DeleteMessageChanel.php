<?php

namespace App\Console\Commands;

use App\Models\Bot;
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

        logger("delete Message");
        $updates = $this->getUpdates($bot->token);

// تعیین تاریخ مورد نظر
        $targetDate = now()->subDay(1); // تاریخ مورد نظر برای حذف پیام‌ها (فرمت YYYY-MM-DD)

        foreach ($updates['result'] as $update) {
            if (isset($update['channel_post'])) {
                $message = $update['channel_post'];
                $messageId = $message['message_id'];
                $messageDate = date('Y-m-d', $message['date']);

                // چک کردن تاریخ پیام
                if ($messageDate <= $targetDate) {
                    // حذف پیام
                    $result = $this->deleteMessage($bot->token, $bot->chanel_id, $messageId);
                    if ($result['ok']) {
                        echo "پیام با شناسه $messageId حذف شد.\n";
                    } else {
                        echo "خطا در حذف پیام با شناسه $messageId: " . $result['description'] . "\n";
                    }
                }
            }
        }
        logger("end delete Message");

    }

    function getUpdates($apiToken) {
        $url = "https://api.telegram.org/bot$apiToken/getUpdates";
        $response = file_get_contents($url);
        return json_decode($response, true);
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
