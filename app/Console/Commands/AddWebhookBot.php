<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AddWebhookBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-webhook-bot';

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
        $apiToken = $this->ask('What is  token bot?');//"YOUR_TELEGRAM_BOT_API_TOKEN";
        $webhookUrl = $this->ask('What is  url?');//"https://your-domain.com/path-to-your-script.php"; // آدرس اسکریپت شما

        $url = "https://api.telegram.org/bot$apiToken/setWebhook?url=" . urlencode($webhookUrl);
        $response = file_get_contents($url);
        $result = json_decode($response, true);

        if ($result['ok']) {
            echo "Webhook با موفقیت تنظیم شد.\n";
        } else {
            echo "خطا در تنظیم Webhook: " . $result['description'] . "\n";
        }
    }
}
