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

class SendMessageUserBot implements ShouldQueue
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
    private $send;
    /**
     * Create a new job instance.
     */
    public function __construct($title,$number,$type,$description,$parties,$date,$factor,$user_id)
    {
        $this->title = $title;
        $this->number = $number;
        $this->type = $type;
        $this->description = $description;
        $this->parties = $parties;
        $this->date = $date;
        $this->factor = $factor;
        $this->user_id = $user_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $bot_user = Bot::where("title", "botUser")->first();
        logger("user",[$bot_user,$this->title ,
$this->number ,
$this->type ,
$this->description ,
$this->parties ,
$this->date ,
$this->factor ,
$this->user_id ]);
        if ($bot_user) {
            try {
                logger("bot user", [$bot_user]);
                $telegram_user = new Api($bot_user->token);
                $this->parties = str_replace("(","\(",$this->parties);
                $this->parties = str_replace(")","\)",$this->parties);

                $message = $this->title;
                $message .= "\n";
                $message .= "مقدار: ";
                $message .= "[ **";
                $message .= $this->number;
                $message .= " کیلو ";
                $message .= " ** ]";
                $message .= "(https://example.com)";
                $message .= "\n";
                $message .= "نوع:" . getTypeTransfer($this->type);
                if ($this->description) {
                    $message .= "\n";
                    $message .= "توضیحات";
                    $message .= "\xE2\x9D\x97 : \n" . $this->description;
                }
                $message .= "\n";
                $message .= "طرف معامله:" ;
                $message .= "[" ;
                $message .= $this->parties ;
                $message .= "]" ;
                $message .= "(https://example.com)" ;
                $message .= "\n";
                $message .= "برای:" . toJalali($this->date, "Y/m/d");
                $message .= "\n";
                $message .= "   شماره حواله:"  ;
                $message .= "**$this->factor**"   ;

                $this->send = Message::create([
                    "telegram_id"=>$this->user_id,
                    "bot_id"=>$bot_user->id,
                    "status"=>Message::STATUS_PENDING,
                    "text"=>$message,
                    "request_id"=>$this->factor

                ]);
                $message_telegram = $telegram_user->sendMessage(
                    [
                        'chat_id' => $this->user_id,
                        'text' => $message,
                        'parse_mode' => 'MarkdownV2'
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
                $this->send->error_text = $exception->getMessage();
                $this->send->status = Message::STATUS_FAILED;
                $this->send->update();
            }
        }
    }
}
