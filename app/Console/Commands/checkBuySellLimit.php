<?php

namespace App\Console\Commands;

use App\Models\DailyRequestTransfer;
use App\Models\UserTelegram;
use Illuminate\Console\Command;

class checkBuySellLimit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-buy-sell-limit';

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

        $limit_day = null;

        $user = UserTelegram::with("userTradeAccess")->find(200);
        $user_transaction = UserTelegram::with("userTradeAccess")->find(228);
        $transfer_type = "buy";//sell
        $quantity = 1;

        if ($user->role == "customer")
        {
            $head = data_get($user, "customer");
            if(!$head) {
               $this->info("شما نمی توانید لفظ  دریافت کنید");
                return true;
            }
        }
        else
            $head = $user;

        if (data_get($user_transaction, "role") == "customer")
            $colleague = data_get($user_transaction, "customer");
        else
            $colleague = $user_transaction;
        $access_limit_head = data_get($head, 'userTradeAccess');
        $access_limit_transaction = data_get($colleague, "userTradeAccess");

        if ($access_limit_head)
            $user_request = $access_limit_head->where("user_trade_id", $colleague->id)->first();

        if ($access_limit_transaction)
            $user_transfer_limit = $access_limit_transaction->where("user_trade_id", $head->id)->first();

        if (($user_request && $user_request->limit_access >=0) && ($user_transfer_limit && $user_transfer_limit->limit_access >=0))
            $limit_day = min($user_request->limit_access, $user_transfer_limit->limit_access);
        elseif (($user_transfer_limit && $user_transfer_limit->limit_access >=0))
            $limit_day = $user_transfer_limit->limit_access;
        elseif (($user_request && $user_request->limit_access >=0))
            $limit_day = $user_request->limit_access;

        $buyer = $transfer_type == "buy" ? $user_transaction : $user;
        $seller = $transfer_type == "sell" ? $user_transaction : $user;
        $buyer_id = $transfer_type == "buy" ? $user_transaction->id : $user->id;
        $seller_id = $transfer_type == "sell" ? $user_transaction->id : $user->id;
        [$daily_transfer, $num] = $this->performTransaction($seller,$buyer,$quantity,$limit_day);
        if(!$num)
            $this->info("can not transaction");
        dd($daily_transfer, $num);
    }

    private function performTransaction($seller, $buyer, $quantity, $max_trade_limit)
    {
        if (!$seller || !$buyer) {
            return 0;
        }

        $seller_head = $seller->customer;
        $seller_customer = $seller->customerUser ? $seller->customerUsers->pluck("id")->toArray() : [];
        $seller_ids[] = $seller->id;

        if ($seller_head)
        {
            $seller_ids[] = $seller_head->id;
            $seller_customer_head = $seller_head->customerUser ? $seller_head->customerUsers->pluck("id")->toArray() : [];
            $seller_customer = array_merge($seller_customer_head, $seller_customer);

        }
        if ($seller_customer)
            $seller_ids = array_merge($seller_ids, $seller_customer);

        $buyer_head = $buyer->customer;

        $buyer_customer = $buyer->customerUser ? $buyer->customerUsers->pluck("id")->toArray() : [];

        $buyer_ids[] = $buyer->id;
        if ($buyer_head)
        {
            $buyer_ids[] = $buyer_head->id;
            $buyer_customer_head = $buyer_head->customerUser ? $buyer_head->customerUsers->pluck("id")->toArray() : [];
            $buyer_customer = array_merge($buyer_customer_head, $buyer_customer);

        }
        if ($buyer_customer)
            $buyer_ids = array_merge($buyer_ids, $buyer_customer);
        $total_sold_by_seller = 0;
        $total_sold_by_buyer = 0;
        foreach (array_unique($seller_ids) as $seller_id) {
            $this->info($seller_id);
            foreach (array_unique($buyer_ids) as $buyer_id) {
                $this->info($buyer_id);
                $total_sold_by_seller += DailyRequestTransfer::where('seller_id', $seller_id)
                    ->whereDate("created_at", now()->subDay(1))
                    ->where('buyer_id', $buyer_id)->sum('use_day');

                $total_sold_by_buyer += DailyRequestTransfer::where('seller_id', $buyer_id)
                    ->whereDate("created_at", now()->subDay(1))
                    ->where('buyer_id', $seller_id)->sum('use_day');
            }
        }

        $this->info( "sell:".$total_sold_by_seller."  buy:".$total_sold_by_buyer);
        $available_to_sell = $max_trade_limit - $total_sold_by_seller + $total_sold_by_buyer;

        $this->info("availbale:".$available_to_sell);
        $new_quantity = min($quantity, $available_to_sell);


        if ($new_quantity > 0) {
//            $daily_transfer = DailyRequestTransfer::create([
//                'seller_id' => $seller->id,
//                'buyer_id' => $buyer->id,
//                'use_day' => $new_quantity,
//            ]);

            return  [[],$new_quantity];
        } else {
            return false;
        }
    }

}
