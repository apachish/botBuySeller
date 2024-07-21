<?php

namespace App\Jobs;

use App\Models\Bot;
use App\Models\Transfer;
use App\Models\WordTelegram;
use App\Services\TelegramServices;
use App\Services\TextServices;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Telegram\Bot\Api;

class DeactivateWord implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $word_id;
    /**
     * Create a new job instance.
     */
    public function __construct($word_id)
    {
        $this->word_id = $word_id;
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
            $word = WordTelegram::where("status",WordTelegram::STATUS_PENDING)->find($this->word_id);

            if ($word) {
                try {
                    $word->status = WordTelegram::STATUS_REJECT;
                    $word->update();
                    $text_services->getTelegramServices()->editMessageReplyMarkup($word->user_id, $word->message_id, new \stdClass());

                } catch (\Exception $exception) {
                    logger("get error", [
                        $exception->getMessage(),
                        $exception->getLine(),
                        $exception->getCode(),
                        $exception->getTrace(),
                        $exception->getFile()
                    ]);
                }
            }else{
                logger("not exist word ".$this->word_id);
            }

        }
    }
}
