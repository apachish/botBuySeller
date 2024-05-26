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
        $query_id = $this->ask("query_id");
            $url = "https://api.telegram.org/bot$access_token/answerInlineQuery";

            $results = json_encode([[
                'type' => 'article',
                'id' => 'unique-id',
                'title' => 'Title',
                'input_message_content' => [
                    'message_text' => "این یک پیام تست برای دریافت شناسه کانال است."
                ]
            ]]);

            $post_fields = [
                'inline_query_id' => $query_id,
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
        if (isset($response['result']['id'])) {
            $channel_id = $response['result']['id'];
            echo "شناسه کانال: " . $channel_id;
        } else {
            echo "خطا در دریافت شناسه کانال.";
        }

    }
}
