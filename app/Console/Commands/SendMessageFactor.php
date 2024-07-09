<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\Message;
use Illuminate\Console\Command;
use Telegram\Bot\Api;

class SendMessageFactor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-message-factor';

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
        Message::whereDate("created_at",now()->format("Y-m-d"))->where("status","failed")->get()->each(function ($message) {
            $bot = Bot::find($message->bot_id);
            if($bot){
                $telegram = new Api($bot->token);

                $text = str_replace("(https://example.com)","",$message->text);
                $send_accounting = $telegram->sendMessage(
                    [
                        'chat_id' => $message->telegram_id,
                        'text' => $text,
                        'parse_mode' => 'MarkdownV2'
                    ]);
                logger("message",[$message]);
                if($send_accounting){
                    $message->status = "receive";
                    $message->update();
                }
            }
        })();
    }
}
