<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\BotMenuUser;
use App\Models\CustomerUser;
use App\Models\DailyRequestTransfer;
use App\Models\RequestTransfer;
use App\Models\UserTelegram;
use App\Models\UserTradeAccess;
use App\Services\TelegramServices;
use Barryvdh\DomPDF\Facade\Pdf;
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
        $customer_id = (int)$this->ask('What is  customer_id?');
        $user_con = UserTelegram::withTrashed()->where("id", $customer_id)->first();
        dd($user_con);
        $customer = CustomerUser::with("user")->find($customer_id);
        $date = now();
        $date_p = toJalali($date, "Y_m_d");
        $request_transfer = RequestTransfer::with("transfer.user")
            ->whereDate("created_at",$date)
            ->where("request_id", $customer_id)->get();
        logger("request_transfer",[$request_transfer,$customer_id,$request_transfer->count()]);
        if ($request_transfer->count()) {
            $mpdf = new \Mpdf\Mpdf(['tempDir' => public_path("tmp")]);
            $html = view('users.report_pdf',compact('date_p', 'request_transfer', 'customer'))->render();
            $mpdf->WriteHTML($html);
            $name_file = $customer_id . "_" . $date_p . ".pdf";
            $path = "app/public/report/" . $customer_id . "/" ;
            makeDirectoryStorage($path);
            $path_report = storage_path($path. $name_file);
            logger("path_re",[$path_report]);
            $document = $mpdf->Output($path_report, 'F');exit;
//            $name_file = $customer_id . "_" . $date_p . ".pdf";
//            $path = "app/public/report/" . $customer_id . "/" ;
////            dd($pdf->getFontMetrics());
//            makeDirectoryStorage($path);
//            $path_report = storage_path($path. $name_file);
//            logger("path_re",[$path_report]);
//
//            $pdf->save($path_report);

            $f = InputFile::create($path_report, "$date_p.pdf");
            logger("sendDocument",[$f]);

        }
        exit;
        $seller_id = (int)$this->ask('What is  seller_id?');
        $buyer_id= (int)$this->ask('What is  buyer_id?');
        $quantity = (int)$this->ask('What is  quantity ?');
        $this->performTransaction($seller_id,$buyer_id,$quantity);
    }

    public function performTransaction($seller_id, $buyer_id, $quantity)
    {
        $seller = UserTelegram::where("id",$seller_id)->first();
        $buyer = UserTelegram::where("id",$buyer_id)->first();

        if (!$seller || !$buyer) {
            dd(['error' => 'User not found'], 404);
        }

        $total_sold_by_seller = DailyRequestTransfer::where('seller_id', $seller_id)->where('buyer_id', $buyer_id)->sum('use_day');
        $total_sold_by_buyer = DailyRequestTransfer::where('seller_id', $buyer_id)->where('buyer_id', $seller_id)->sum('use_day');
        $customer = CustomerUser::where("mobile", $buyer->mobile)->first();
        dd($customer);
        $max_trade_limit = $customer->limit;
        dd($max_trade_limit);
        $available_to_sell = $max_trade_limit - $total_sold_by_seller + $total_sold_by_buyer;
        $new_quantity = min($quantity, $available_to_sell);

        if ($new_quantity > 0) {
            DailyRequestTransfer::create([
                'seller_id' => $seller_id,
                'buyer_id' => $buyer_id,
                'use_day' => $new_quantity,
            ]);

            return dd(['message' => "Transaction successful: $new_quantity items sold from user $seller_id to user $buyer_id."], 200);
        } else {
            return dd(['error' => 'Transaction limit reached. No items sold.'], 400);
        }
    }


}
