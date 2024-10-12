<?php

namespace App\Jobs;

use App\Models\Bot;
use App\Models\Message;
use App\Models\MessageWordAccounting;
use App\Models\Transfer;
use App\Models\WordTelegram;
use App\Services\TelegramServices;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAcceptWordPublicChannel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $transfer_id;
    private $word_id;
    private $send;
    /**
     * Create a new job instance.
     */
    public function __construct($transfer_id,$word_id)
    {
        $this->transfer_id = $transfer_id;
        $this->word_id = $word_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $channel = env("CHANEL_ID_PUBLIC");
        if($channel) {

            $bot_user = Bot::where('title', "botUser")->first();
            if ($bot_user) {
                $transfer_new = Transfer::with('user')->where('id', $this->transfer_id)->first();
                $word = WordTelegram::find($this->word_id);
                if ($transfer_new && $word) {
                    try {
                        $telegram_accounting_services = new TelegramServices($bot_user->token);

                        $message_accounting = data_get($word, "message");
                        $this->send = [
                            "bot_id" => $bot_user->id,
                            "status" => Message::STATUS_PENDING,
                            "text" => $message_accounting,
                            "transfer_id" => $this->transfer_id,
                            "word_id" => $this->word_id

                        ];
                        $send_accounting = $telegram_accounting_services->sendMessage($channel, $message_accounting);
                        $this->send->message_id = $send_accounting;
                        if ($this->send->message_id)
                            $this->send->status = Message::STATUS_RECEIVE;
                        else
                            $this->send->status = Message::STATUS_FAILED;

                    } catch (\Exception $exception) {

                        logger("get error", [
                            $exception->getMessage(),
                            $exception->getLine(),
                            $exception->getCode(),
                            $exception->getTrace(),
                            $exception->getFile()
                        ]);
                        $this->send->status = Message::STATUS_FAILED;
                        $this->send->error_text = $exception->getMessage();
                        $this->send->update();
                    }
                } else {
                    logger("can not found transfer $this->transfer_id or word $this->word_id for send Accounting");
                }
            }
        }
    }
}
