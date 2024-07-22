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
        $bot = Bot::whereIn("title",["botUser","botManage"])->get()->keyBy('title');
        if($bot->count()){
            $times = cache()->get("bot_chanel_edit_time",1);
            $key =  cache()->get("bot_chanel_edit") =="botUser" && $times >=3?"botManage":"botUser";
            if(cache()->get("bot_chanel_edit") != $key)
                $times = 1;
            logger("edit message chanel ",[$key.".token"]);
            $bot = data_get($bot,$key);
            $token = data_get($bot,"token");
            cache()->set("bot_chanel_edit",$key,now()->addMinutes(3));
            cache()->set("bot_chanel_edit_time",++$times);
            $text_services = new TextServices($token);

            try {
                $transfer = Transfer::where("number", ">", 0)
                    ->whereIn("status", [Transfer::STATUS_ACTIVE, Transfer::STATUS_ACTIVE_DO])
                    ->find($this->transfer_id);
                logger("transfer deactive", [$transfer, $this->transfer_id]);
                if ($transfer) {
                    $message = $transfer->message . "\xF0\x9F\x95\x9B	";
                    $edit_message = $text_services->getTelegramServices()->editMessageTextAndInlineKeyboard($bot->chanel_id, $transfer->message_id, $message);
                    logger("edit_message", [$edit_message]);
                    if (data_get($edit_message, "ok")) {
                        $transfer->delete();
                    } else {
                        logger("edit block message", [$transfer]);

                    }
                }
            }catch (\Exception $exception) {
                logger("get error", [
                    $exception->getMessage(),
                    $exception->getLine(),
                    $exception->getCode(),
                    $exception->getTrace(),
                    $exception->getFile()
                ]);
            }

        }
    }
}
