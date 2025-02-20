<?php

namespace App\Jobs;

use App\Models\Bot;
use App\Models\Message;
use App\Models\RequestTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Telegram\Bot\Api;

class SendMessageAccountingBot implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $order_id;
    private $send;

    /**
     * Create a new job instance.
     */
    public function __construct($order_id)
    {
        $this->order_id = $order_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $bot_accounting = Bot::where("title", "botAccounting")->with("accessBot")->first();
        logger("ghazal", [$bot_accounting, $this->order_id]);

        if ($bot_accounting) {
            try {
                logger("bot accounting job", [$bot_accounting]);
                $telegram_accounting = new Api($bot_accounting->token);
                $order_buy = RequestTransfer::with(["userRequest.customer", "transferReport"])->find($this->order_id);
                if($order_buy) {
                    logger("order buy", [$order_buy]);
                    $message = $this->getfactor($order_buy);
                    $admins = $bot_accounting->accessBot;
                    logger("aaa", [$admins]);
                    foreach ($admins as $admin) {
                        logger("send job", [$admin]);
                        $this->send = Message::create([
                            "telegram_id" => $admin->user_id,
                            "bot_id" => $bot_accounting->id,
                            "status" => Message::STATUS_PENDING,
                            "text" => $message,
                            "request_id" => $this->order_id
                        ]);
                        $send_accounting = $telegram_accounting->sendMessage(
                            [
                                'chat_id' => $admin->user_id,
                                'text' => $message,
                                'parse_mode' => 'MarkdownV2'
                            ]);
                        $this->send->message_id = data_get($send_accounting, "message_id");
                        $this->send->status = Message::STATUS_RECEIVE;
                        $this->send->update();
                        logger("aco job", [$send_accounting]);
                    }
                }else{
                    logger("order buy can not found send accounting", [$this->order_id]);
                }
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

    private function getfactor(\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Builder|array $order_buy): string
    {
        $message = "   شماره حواله:";

        $message .= "**". data_get($order_buy, 'id')."**";
        $message .= "\n";
        $message .= "فی:";
        $message .= number_format(data_get($order_buy, 'price'), 0);
        $message .= "\n";
        $type = data_get($order_buy, "type");
        if ($type == "sell") {
            $title_request = "فروشنده";
            $title_mal = "خریدار";
        } else {
            $title_request = "خریدار";
            $title_mal = "فروشنده";

        }
        $transfer = $order_buy->transferReport;
        if (data_get($order_buy, "userRequest.role") == "customer")
        {
            $message .= "$title_request:";
            $message .= $this->getBlue(data_get($order_buy, "userRequest.fullName") . "\(" . data_get($order_buy, "userRequest.customer.fullName") . "\)");
        }
        else
        {
            $message .= "$title_request:";
            $message .= $this->getBlue(data_get($order_buy, "userRequest.fullName"));
        }
        $message .= "\n";

        if (data_get($order_buy, "transferReport.user.role") == "customer")
        {
            $message .= "$title_mal:";
            $message .= $this->getBlue(data_get($order_buy, "transferReport.user.fullName") . "\(" . data_get($order_buy, "transferReport.user.customer.fullName") . "\)");
        }
        else
        {
            $message .= "$title_mal:";
            $message .= $this->getBlue(data_get($order_buy, "transferReport.user.fullName"));
        }
        $message .= "\n";
        $message .= "برای:" . toJalali($transfer->date, "Y/m/d");
        $message .= "\n";
        $message .= "ساعت:" . toJalali($order_buy->created_at, "H:i:s");
        $message .= "\n";
        $message .= "مقدار:";
        $message .= "[**";
        $message .= data_get($order_buy, "number");
        $message .= " کیلو ";
        $message .= "**]";
        $message .= "(https://example.com)";
        $message .= "\n";
        $message .= "نوع:" . $order_buy->type_title?:getTypeTransfer($transfer->type);
        if (data_get($transfer,'description')) {
            $message .= "\n";
            $message .= "توضیحات";
            $message .= "\xE2\x9D\x97 : \n" . data_get($transfer,'description');
        }
        logger("mesage acco", [$message]);
        $message = str_replace(".","\\.",$message);
        $message = str_replace("\(","(",$message);
        $message = str_replace("\)",")",$message);
        $message = str_replace("(","\(",$message);
        $message = str_replace(")","\)",$message);
        $message = str_replace("-","\-",$message);
        $message = str_replace("\(https://example.com\)","(https://example.com)",$message);
        return $message;
    }

    private function getBlue($text)
    {
        $message = " [**";
        $message .= $text;
        $message .= "**]";
        $message .= "(https://example.com)";
        return $message;
    }
}
