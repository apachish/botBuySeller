<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\BotMenuUser;
use App\Models\CustomerUser;
use App\Models\DailyRequestTransfer;
use App\Models\UserTelegram;
use App\Models\UserTradeAccess;
use App\Services\TelegramServices;
use Illuminate\Console\Command;
use Telegram\Bot\Api;
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
        $max_trade_limit = $customer->limit;
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
