<?php

namespace App\Jobs;

use App\Models\Bot;
use App\Models\Transfer;
use App\Services\TelegramServices;
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
            $telegram = new Api($token);
            $telegram_services = new TelegramServices();
            $telegram_services->access_token = $token;

            $transfer = Transfer::find($this->transfer_id);
            if($transfer) {
                $telegram_services->editMessageReplyMarkup($bot->chanel_id, $transfer->message_id, new \stdClass());
                $transfer->delete();
            }

        }
    }
}
