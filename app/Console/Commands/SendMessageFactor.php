<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\Message;
use Illuminate\Console\Command;

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

            }
        })();
    }
}
