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
    private $type_title;
    private $created_at;
    private $send;
    /**
     * Create a new job instance.
     */
    public function __construct($title,$number,$type,$description,$parties,$date,$factor,$user_id,$type_title,$created_at)
    {
        $this->title = $title;
        $this->number = $number;
        $this->type = $type;
        $this->description = $description;
        $this->parties = $parties;
        $this->date = $date;
        $this->factor = $factor;
        $this->user_id = $user_id;
        $this->type_title = $type_title;
        $this->created_at = $created_at;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $bot_user = Bot::where("title", "botUser")->first();
        if ($bot_user) {
            try {
                $telegram_user = new Api($bot_user->token);
                /*
                 * خرید 🔵⏳
معامله🤝
فی : 20,350,000
مقدار :  1 کیلو
نوع : عادی
طرف معامله : وحید سیاوشی ۱(وحید سیاوشی)
برای : 1403/08/19
شماره حواله : 3160223
زمان معامله :
1403/08/16   11:18:38
                 */
                $message = $this->title;
                $message .= "\n";
                $message .= "معامله🤝";
                $message .= "\n";
                $message .= "مقدار:";
                $message .= "[**";
                $message .= $this->number;
                $message .= " کیلو ";
                $message .= " **]";
                $message .= "(https://example.com)";
                $message .= "\n";
                $message .= "نوع:" . $this->type_title?:getTypeTransfer($this->type);
                if ($this->description) {
                    $this->description = str_replace(".","\\.",$this->description);
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
                $message .= "شماره حواله:";
                $message .= "**$this->factor**"   ;
                $message .= "\n";
                $message .= "زمان معامله : ";
                $message .= "\n";
                $message .=  toJalali($this->created_at, "Y/m/d  H:i:s");
                $message = str_replace("\(","(",$message);
                $message = str_replace("\)",")",$message);
                $message = str_replace("(","\(",$message);
                $message = str_replace(")","\)",$message);
                $message = str_replace("-","\-",$message);
                $message = str_replace("_","\_",$message);
                $message = str_replace("\(https://example.com\)","(https://example.com)",$message);
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
