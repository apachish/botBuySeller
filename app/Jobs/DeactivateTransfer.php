<?php

namespace App\Jobs;

use App\Models\Bot;
use App\Models\Transfer;
use App\Services\TelegramServices;
use App\Services\TextServices;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Telegram\Bot\Api;

class DeactivateTransfer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $transfer_id;
    /**
     * Create a new job instance.
     */
    public function __construct($transfer_id)
    {
        $this->transfer_id = $transfer_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $bot = Bot::where("title","botUser")->first();
        if($bot){
            $token = $bot->token;
            $text_services = new TextServices($token);

            $transfer = Transfer::where("number",">",0)
                ->whereIn("status",[Transfer::STATUS_ACTIVE,Transfer::STATUS_ACTIVE_DO])
                ->find($this->transfer_id);
            if($transfer) {
                $message = $transfer->message."\xF0\x9F\x95\x9B	";
                $text_services->getTelegramServices()->editMessageTextAndInlineKeyboard($bot->chanel_id, $transfer->message_id, $message);
                $transfer->delete();
            }

        }
    }
}
