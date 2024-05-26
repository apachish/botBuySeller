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
        $access_token = $this->ask("access_token");
//        $query_id = $this->ask("query_id");



// دریافت داده‌های وب‌هوک تلگرام
        $content = file_get_contents("php://input");
        $update = json_decode($content, true);

        if (isset($update['inline_query'])) {
            $query = $update['inline_query'];
            $url = "https://api.telegram.org/bot$access_token/answerInlineQuery";

            $results = json_encode([[
                'type' => 'article',
                'id' => 'unique-id',
                'title' => 'Test Message',
                'input_message_content' => [
                    'message_text' => "این یک پیام تست برای دریافت شناسه کانال است."
                ]
            ]]);

            $post_fields = [
                'inline_query_id' => $query['id'],
                'results' => $results,
                'cache_time' => 0
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:application/json"));
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
            $result = curl_exec($ch);
            curl_close($ch);

            $response = json_decode($result, true);

            // بررسی پاسخ
            if ($response['ok']) {
                echo "درخواست inline با موفقیت ارسال شد.";
            } else {
                echo "خطا در ارسال درخواست inline.";
            }
        } else {
            echo "درخواست inline دریافت نشد.";
        }

    }
}
