<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\Transfer;
use Illuminate\Console\Command;

class TestLimit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-limit';

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

        $suggest_price = 390;
        $start_trade_s = (int)cache()->remember("start_price_trade", now()->addDay(1), function () {
            $value = 14000000;
            $setting = Setting::where("key", "start_price_trade")->first();

            if ($setting)
                $value = data_get($setting, "value");
            return $value;
        });
        $end_trade_s = (int)cache()->remember("end_price_trade", now()->addDay(1), function () {
            $value = 14200000;
            $setting = Setting::where("key", "end_price_trade")->first();
            if ($setting)
                $value = data_get($setting, "value");
            return (int)$value;
        });
        $last_transfer = Transfer::withTrashed()->first();
        if ( !$last_transfer) {

            $price = $this->getPriceTrade($suggest_price, $start_trade_s,$end_trade_s);


        } else {
            $last_trade = (int)$last_transfer->price;
            if ($last_trade) {
                // محاسبه قیمت جدید در محدوده ±2٪

                $start_trade_s = $last_trade - 500000;
                $end_trade_s = $last_trade + 500000;
            }

            $price = $this->getPriceTrade($suggest_price, $start_trade_s,$end_trade_s);

        }
        if ($price < $start_trade_s || $price > $end_trade_s) {
            $message = "مبلغ وارد شده باید در بازه";
            $message .= "\n";
            $message .= $start_trade_s;
            $message .= "\n";
            $message .= "تا";
            $message .= "\n";
            $message .= $end_trade_s;
            $message .=  "\n";
            $message .=  "اگر لفظ شما 3 رقمی درست نمی باشد ۵ رقمی لفظ دهید";

            $this->info($message);
        }else{
            $this->info($price);

        }
    }

    function getUnitPrice($number){
        $base = null;
        $number = convertNumber($number);
        if ($number >= 99999999999)
            $base = 1000000000000;
        elseif ($number >= 999999999)
            $base = 1000000000;
        elseif ($number >= 99999)
            $base = 1000000;
        elseif ($number >= 999)
            $base = 1000;
        return $base;
    }

    function convertNumber($value)
    {
        $western = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];
        $eastern = ['۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '۰'];
        return str_replace($eastern, $western, $value);
    }

    private function getPriceTrade(mixed $suggest_price, mixed $start_trade_s, mixed $end_trade_s): int|float
    {
// طول رشته عدد را محاسبه کنید
        $length = strlen($suggest_price);

        // بررسی کنید که آیا طول عدد 3 یا 5 است
        if ($length === 3) {
            $start_price = (int)$start_trade_s;
            $unit = getUnitPrice($start_price);

        } elseif ($length === 5) {
            $start_price = (int)($suggest_price * 1000);
            $unit = getUnitPrice($start_price);
            $suggest_price = $suggest_price % 1000;

        }
        $start_trade = floor($start_price / $unit) * $unit;


        $price = $start_trade + ($suggest_price * 1000);
        if (!($price>= $start_price && $price <= $end_trade_s)){
            $price +=$unit;
        }
        return $price;
    }

}
