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
        $channel_invite_link = $this->ask("channel_invite_link");



            // دریافت شناسه کانال از لینک دعوت
            $channel_chat_id = '@' . parse_url($channel_invite_link, PHP_URL_PATH);

            $url = "https://api.telegram.org/bot$access_token/sendMessage";

            $post_fields = [
                'chat_id' => $channel_chat_id,
                'text' => "این یک پیام تست برای دریافت شناسه کانال است."
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
            $result = curl_exec($ch);
            curl_close($ch);

        $response = json_decode($result, true);

// ارسال پیام به کانال خصوصی و دریافت شناسه کانال
        if (isset($response['result']['chat']['id'])) {
            $channel_id = $response['result']['chat']['id'];
            echo "شناسه کانال: " . $channel_id;
        } else {
            echo "خطا در دریافت شناسه کانال.";
        }

    }
}
