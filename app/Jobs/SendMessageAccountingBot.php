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
        logger("ghazal", [$bot_accounting,$this->order_id]);

        if ($bot_accounting) {
            try {
                logger("bot accounting job", [$bot_accounting]);
                $telegram_accounting = new Api($bot_accounting->token);
                $order_buy = RequestTransfer::with(["userRequest.customer", "transferReport"])->find($this->order_id);
                logger("order buy",[$order_buy]);
                $message = $this->getfactor($order_buy);
                $admins = $bot_accounting->accessBot;
                logger("aaa",[$admins]);
                foreach ($admins as $admin) {
                    logger("send job", [$admin]);
                    $this->send = Message::create([
                        "telegram_id"=>$admin->user_id,
                        "bot_id"=>$bot_accounting->id,
                        "status"=>Message::STATUS_PENDING,
                        "text"=>$message,
                        "request_id"=>$this->order_id
                    ]);
                    $send_accounting = $telegram_accounting->sendMessage(
                        [
                            'chat_id' => $admin->user_id,
                            'text' => $message,
                            'parse_mode' => 'MarkdownV2'
                        ]);
                    $this->send->message_id = data_get($send_accounting,"message_id");
                    $this->send->status = Message::STATUS_RECEIVE;
                    $this->send->update();
                    logger("aco job", [$send_accounting]);
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
        $message = "   شماره حواله:"  ;
        $message .= "*\*".data_get($order_buy, 'id')."\**"   ;
        $message .= "\n\n";
        $message .= "فی:";
        $message .= number_format(data_get($order_buy, 'price'), 0);
        $message .= "\n\n";
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
            $message .= "  $title_request: " . data_get($order_buy, "userRequest.fullName") . "(" . data_get($order_buy, "userRequest.customer.fullName") . ")";
        else
            $message .= "  $title_request: " . data_get($order_buy, "userRequest.fullName");
        $message .= "\n\n";
        if (data_get($order_buy, "transferReport.user.role") == "customer")
            $message .= "  $title_mal: " . data_get($order_buy, "transferReport.user.fullName") . "(" . data_get($order_buy, "transferReport.user.customer.fullName") . ")";
        else
            $message .= "  $title_mal: " . data_get($order_buy, "transferReport.user.fullName");
        $message .= "\n\n";
        $message .= "برای:" . toJalali($transfer->date, "Y/m/d");
        $message .= "\n\n";
        $message .= "ساعت:" . toJalali($order_buy->created_at, "H:i:s");
        $message .= "\n\n";
        $message .= "مقدار:" . data_get($order_buy, "number") . "کیلو";
        $message .= "\n\n";
        $message .= "نوع:" . getTypeTransfer($transfer->type);
        logger("mesage acco",[$message]);
        return $message;
    }
}
