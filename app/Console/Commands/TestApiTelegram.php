<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\BotMenuUser;
use App\Models\CustomerUser;
use App\Models\DailyRequestTransfer;
use App\Models\RequestTransfer;
use App\Models\Transfer;
use App\Models\UserTelegram;
use App\Models\UserTradeAccess;
use App\Services\TelegramServices;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Keyboard\Keyboard;

class TestApiTelegram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-api-telegram';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        echo cleanInput("430خ فش1: توضیحات متنی");exit;
//        $customer_id = (int)$this->ask('What is  customer_id?');
//        $customer = CustomerUser::with("user")->find($customer_id);
//        $date = now();
//        $date_p = toJalali($date, "Y_m_d");
//        $request_transfer = RequestTransfer::with("transfer.user")
//            ->whereDate("created_at",$date)
//            ->where("request_id", $customer_id)->get();
//        logger("request_transfer",[$request_transfer,$customer_id,$request_transfer->count()]);
//        if ($request_transfer->count()) {
//            $mpdf = new \Mpdf\Mpdf(['tempDir' => public_path("tmp")]);
//            $html = view('users.report_pdf',compact('date_p', 'request_transfer', 'customer'))->render();
//            $mpdf->WriteHTML($html);
//            $name_file = $customer_id . "_" . $date_p . ".pdf";
//            $path = "app/public/report/" . $customer_id . "/" ;
//            makeDirectoryStorage($path);
//            $path_report = storage_path($path. $name_file);
//            logger("path_re",[$path_report]);
//            $document = $mpdf->Output($path_report, 'F');exit;
////            $name_file = $customer_id . "_" . $date_p . ".pdf";
////            $path = "app/public/report/" . $customer_id . "/" ;
//////            dd($pdf->getFontMetrics());
////            makeDirectoryStorage($path);
////            $path_report = storage_path($path. $name_file);
////            logger("path_re",[$path_report]);
////
////            $pdf->save($path_report);
//
//            $f = InputFile::create($path_report, "$date_p.pdf");
//            logger("sendDocument",[$f]);
//
//        }
//        exit;
        //request_transfer_6669a852c5fe3148ea023df6
        $data = $this->ask('What is  data?');
        $request_id = (int)$this->ask('What is  request_id?');
        $this->performTransaction($data, $request_id);
    }

    public function performTransaction($data, $request_id)
    {

        $array = str_replace('request_transfer_', '', $data);
        $info = explode("_", $array);
        $id = data_get($info, 0);
        $num = (int)data_get($info, 1);
        logger("request", [$num, $id]);
        $transfer = Transfer::whereIn("status", [Transfer::STATUS_ACTIVE, Transfer::STATUS_ACTIVE_DO])->find($id);
        if ($transfer == null) {
            $this->info("متأسفانه امکان دریافت حواله  در این معامله نمی باشد");
            return true;
        }
        if ($transfer && $transfer->user_id == $request_id) {
            $this->info("متأسفانه امکان دریافت حواله برای شما در این معامله نمی باشد");
            return true;
        }
        logger("Transfer", [$transfer]);
        $transfer_type = getTypeOrder($transfer->type);
        $user_request = UserTelegram::where("id",$request_id)->first();
        logger("user",[$user_request]);
        try {

            $limit_day = null;
            $use_day = null;
            $transaction_party = null;
            $transaction_party_req = null;
            $request_transfer = [];


            if ($user_request->role == "customer") {
                logger("customer", [$user_request->role]);
                $customer = CustomerUser::where("mobile", $user_request->mobile)->first();
                logger("limit customer", [$customer]);
                if ($customer && $customer->limit)
                    $limit_day = $customer->limit;

            } elseif ($user_request->role == "colleague") {
                $user_request = UserTradeAccess::where("user_id", $request_id)
                    ->where("user_trade_id", $transfer->user_id)->first();
                $user_transfer = UserTradeAccess::where("user_id", $transfer->user_id)
                    ->where("user_trade_id", $request_id)->first();

                logger("www", [$user_request, $user_transfer]);
                if (($user_request && $user_request->limit_access) && ($user_transfer && $user_transfer->limit_access))
                    $limit_day = min($user_request->limit_access, $user_transfer->limit_access);
                elseif (($user_transfer && $user_transfer->limit_access))
                    $limit_day = $user_transfer->limit_access;
                elseif (($user_request && $user_request->limit_access))
                    $limit_day = $user_request->limit_access;

            }
            logger("type_t" . $transfer_type, [$transfer->user_id, $request_id]);
            $buyer_id = $transfer_type == "buy" ? $transfer->user_id : $request_id;
            $seller_id = $transfer_type == "sell" ? $transfer->user_id : $request_id;

            logger("limit_day", [$limit_day]);
            logger("limit_day", [$buyer_id, $seller_id]);
            exit;
            if ($limit_day) {
                $num = $this->performTransaction($seller_id, $buyer_id, $num, $limit_day);
                $transfer->number -= $num;
                $request_transfer["number"] = $num;
                $use_day = $num;
                logger("use_day", [$use_day]);

                $request_transfer["status"] = $transfer->number == 0 ? "complete" : "half";
            } else {
                logger("check", [$transfer->number, $num, $transfer->number >= $num]);
                if ($transfer->number >= $num) {
                    $transfer->number -= $num;
                    $request_transfer["number"] = $num;

                    $request_transfer["status"] = $num == $transfer->number ? "complete" : "half";
                    logger("request", [$request_transfer]);
                    $use_day = $num;
                }
            }


            if (data_get($request_transfer, "number")) {
                logger("number" . data_get($request_transfer, "number"));
                DailyRequestTransfer::updateOrCreate([
                    "seller_id" => $seller_id,
                    "buyer_id" => $buyer_id,
                ], [
                    "use_day" => $use_day,
                ]);

                $keyboard = self::getKeyboardRequest($transfer);

                $trade_message = $transfer->message;
                if ($transfer->number == 0) {
                    $trade_message .= "\xE2\x9C\x85	🤝🏼";
                    $transfer->status = Transfer::STATUS_ACTIVE_DONE;
                    $transfer->update();
                }

                $this->telegram_services->editMessageTextAndInlineKeyboard($this->bot->chanel_id, $transfer->message_id, $trade_message, $keyboard);
                $transfer->update();

                $request_transfer["remittance_number"] = generateUniqueSixDigitCode();
                $request_transfer["request_id"] = $request_id;
                $request_transfer["transfer_id"] = $transfer->id;
                $request_transfer["price"] = $transfer->price;
                $request_transfer["type"] = getTypeOrder(data_get($transfer, "type")) == "buy" ? "sell" : "buy";

                RequestTransfer::create($request_transfer);
                if (cache()->get("transfer_accept_" . $transfer_type)) {
// گرفتن تاریخ و زمان فعلی
                    $now = Carbon::now();

// تنظیم زمان به ۲۳:۵۹:۰۰ امروزی
                    $endOfDay = $now->copy()->setTime(23, 59, 0);
                    cache()->set("transfer_accept_" . $transfer_type, $transfer->price, $endOfDay);
                }
                if ($transfer->user->role == "customer" && $user_request->role == "customer") {
                    $transaction_party_req = "مشاهده فقط برای سرگروه";
                    $transaction_party_req_s = data_get($transfer, 'user.customerUser.headCustomer.fullName') . "(" . data_get($transfer, 'user.customerUser.fullName') . ")";
                    $transaction_party = "مشاهده فقط برای سرگروه";
                    $transaction_party_s = data_get($user_request, 'customerUser.headCustomer.fullName') . "(" . data_get($user_request, 'customerUser.fullName') . ")";

                } elseif ($transfer->user->role == "colleague" && $this->getUser()->role == "customer") {
                    $transaction_party_req = "مشاهده فقط برای سرگروه";
                    $transaction_party_req_s = data_get($transfer, 'user.customerUser.headCustomer.fullName') . "(" . data_get($transfer, 'user.customerUser.fullName') . ")";
                    $transaction_party = data_get($transfer, 'user.customerUser.headCustomer.fullName') . "(" . data_get($transfer, 'user.customerUser.fullName') . ")";
                } elseif ($transfer->user->role == "customer" && $this->getUser()->role == "colleague") {
                    $transaction_party_req = data_get($transfer, 'user.customerUser.headCustomer.fullName') . "(" . data_get($transfer, 'user.customerUser.fullName') . ")";
                    $transaction_party = "مشاهده فقط برای سرگروه";
                    $transaction_party_s = data_get($this->getUser(), 'customerUser.headCustomer.fullName') . "(" . data_get($this->getUser(), 'customerUser.fullName') . ")";
                } elseif ($transfer->user->role == "colleague" && $this->getUser()->role == "colleague") {
                    $transaction_party_req = data_get($transfer, 'user.fullName');
                    $transaction_party = $this->getUser()->fullName;
                }

                /*
                 * send for request
                 */
                $message = $transfer->message_request_me;
                $message .= "\n\n";
                $message .= "مقدار:" . data_get($request_transfer, "number") . "کیلو";
                $message .= "\n\n";
                $message .= "نوع:" . getTypeTransfer($transfer->type);
                $message .= "\n\n";
                $message .= "طرف معامله:" . $transaction_party_req;
                $message .= "\n\n";
                $message .= "برای:" . toJalali($transfer->date, "Y/m/d");
                $message .= "\n\n";
                $message .= "       شماره حواله:" . data_get($request_transfer, 'remittance_number');
                $this->telegram_services->sendMessage($request_id, $message);
                if ($this->getUser()->role == "customer") {
                    $message = str_replace($transaction_party_req, $transaction_party_req_s, $message);
                    $this->telegram_customer->sendMessage(
                        [
                            'chat_id' => data_get($this->getUser(), 'customerUser.headCustomer.id'),
                            'text' => $message,
                        ]
                    );
                }

                /*
                * send for  owner
                */
                $message = $transfer->message_request;
                $message .= "\n\n";
                $message .= "مقدار:" . data_get($request_transfer, "number") . "کیلو";
                $message .= "\n\n";
                $message .= "نوع:" . getTypeTransfer($transfer->type);
                $message .= "\n\n";
                $message .= "طرف معامله:" . $transaction_party;
                $message .= "\n\n";
                $message .= "برای:" . toJalali($transfer->date, "Y/m/d");
                $message .= "\n\n";
                $message .= "       شماره حواله:" . data_get($request_transfer, 'remittance_number');
                $this->telegram_services->sendMessage($transfer->user_id, $message);
                if ($transfer->user->role == "customer") {
                    $message = str_replace($transaction_party, $transaction_party_s, $message);
                    $this->telegram_customer->sendMessage(
                        [
                            'chat_id' => data_get($transfer->user, 'customerUser.headCustomer.id'),
                            'text' => $message,
                        ]
                    );
                }


            } else {
                $this->telegram_services->sendMessage($request_id, "متأسفانه امکان دریافت حواله برای شما در این معامله نمی باشد");

            }
        } catch (\Exception $exception) {

            logger("exp", [$exception->getMessage(),
                $exception->getLine(),
                $exception->getCode(),
                $exception->getTrace(),
                $exception->getFile()]);
        }


    }


}
