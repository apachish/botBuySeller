<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\MessageAdmin;
use App\Services\TelegramServices;
use Illuminate\Console\Command;

class DeleteMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-message';

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
        $message_id = (int)$this->ask('What is  message id?');
        $bot_user = Bot::where("title", "botUser")->first();
        if ($bot_user) {
            $telegram_services = new TelegramServices($bot_user->token);
            logger("$bot_user->token");
            $message = MessageAdmin::where("message_id", $message_id)->first();
            if ($message)
                $telegram_services->deleteMessage(data_get($message,"user_id"), $message_id);
        }

    }
}
