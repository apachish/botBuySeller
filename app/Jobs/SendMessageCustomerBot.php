<?php

namespace App\Jobs;

use App\Models\Bot;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Telegram\Bot\Api;

class SendMessageCustomerBot implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $title;
    private $number;
    private $type;
    private $description;
    private $parties;
    private $date;
    private $factor;
    private $user_id;
    private $customer;
    private $send;
    /**
     * Create a new job instance.
     */
    public function __construct($title,$number,$type,$description,$parties,$date,$factor,$user_id,$customer)
    {
        $this->title = $title;
        $this->number = $number;
        $this->type = $type;
        $this->description = $description;
        $this->parties = $parties;
        $this->date = $date;
        $this->factor = $factor;
        $this->user_id = $user_id;
        $this->customer = $customer;
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $bot_customer = Bot::where("title", "botCustomer")->first();
        if ($bot_customer) {
            try {
                logger("bot customer", [$bot_customer]);
                $telegram_customer = new Api($bot_customer->token);
                $message = "نام مشتری:";
                $message .= $this->customer;
                $message .= "\n\n";
                $message .= $this->title;
                $message .= "\n\n";
                $message .= "مقدار:" . $this->number . "کیلو";
                $message .= "\n\n";
                $message .= "نوع:" . getTypeTransfer($this->type);
                if ($this->description) {
                    $message .= "\n\n";
                    $message .= "توضیحات";
                    $message .= "\xE2\x9D\x97 : \n\n" . $this->description;
                }
                $message .= "\n\n";
                $message .= "طرف معامله:" . $this->parties;
                $message .= "\n\n";
                $message .= "برای:" . toJalali($this->date, "Y/m/d");
                $message .= "\n\n";
                $message .= "       شماره حواله:" . $this->factor ;

                $this->send = Message::create([
                    "telegram_id"=>$this->user_id,
                    "bot_id"=>$bot_customer->id,
                    "status"=>Message::STATUS_PENDING,
                    "text"=>$message,
                    "request_id"=>$this->factor

                ]);

                $message_telegram = $telegram_customer->sendMessage(
                    [
                        'chat_id' => $this->user_id,
                        'text' => $message,
                    ]);
                $this->send->message_id = data_get($message_telegram,"message_id");
                $this->send->status = Message::STATUS_RECEIVE;
                $this->send->update();
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
        }
    }
}
