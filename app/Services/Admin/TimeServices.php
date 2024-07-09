<?php

namespace App\Services\Admin;


use App\Models\Setting;
use App\Services\TelegramServices;

class TimeServices
{

    public $keyword = [
        [
            ['text' => "\xE2\x8C\x9Aساعت شروع"],
        ],
        [
            ['text' => "\xE2\x8F\xB0ساعت پایان"],
        ],
        [
            ['text' => "\xE2\x98\x81تعطیل"],
        ],
        [
            ['text' => "🕰تاریخ فردا️"],
        ],
        [
            ['text' => "\xE2\x86\xA9منو"]
        ],
    ];

    public function getStartOperation($object)
    {
        $hours_of_operation = Setting::where("key", "start_hours_of_operation")->first();
        if ($hours_of_operation) {
            $response_text = "ساعت شروع تنظیم شد:";
            $response_text .= "\n\n";
            $response_text .= "\n\n";
            $response_text .= data_get($hours_of_operation, "value");
            $response_text .= "\n\n";
        } else {
            $response_text = " شروع  وارد شده باید به صورت \n\n";
            $response_text .= "09:00 \n\n";
        }
        cache()->set($object->getKeyCache() . $object->getUserId(), "start_hours_of_operation");

        $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
    }

    public function getEndOperation($object)
    {
        $hours_of_operation = Setting::where("key", "end_hours_of_operation")->first();
        if ($hours_of_operation) {
            $response_text = "ساعت پایان تنظیم شد:";
            $response_text .= "\n\n";
            $response_text .= "\n\n";
            $response_text .= data_get($hours_of_operation, "value");
            $response_text .= "\n\n";
        } else {
            $response_text = " پایان  وارد شده باید به صورت \n\n";
            $response_text .= "22:00 \n\n";
        }
        cache()->set($object->getKeyCache() . $object->getUserId(), "end_hours_of_operation");

        $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
    }

    public function open($object)
    {
        $date = now()->format("Y-m-d");
        $holiday = Setting::where("key", "vacation")->first();
        $response_text = toJalali($date, "Y/m/d");
        if ($holiday && $holiday->value) {
            $response_text .= " باز شد";
            $holiday->value = null;
            $holiday->update();
        } else {
            $response_text .= " تعطیل شد";
            $holiday = Setting::updateOrCreate(
                ["key" => "vacation"],
                ["value" => $date]
            );
        }
        $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
        cache()->forget("parameter_need");
    }

    public function close($object)
    {
        $date = now()->format("Y-m-d");
        $holiday = Setting::where("key", "vacation")->first();
        $response_text = toJalali($date, "Y/m/d");
        if ($holiday && $holiday->value) {
            $response_text .= " باز شد";
            $holiday->value = null;
            $holiday->update();
        } else {
            $response_text .= " تعطیل شد";
            $holiday = Setting::updateOrCreate(
                ["key" => "vacation"],
                ["value" => $date]
            );
        }
        $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
        cache()->forget("parameter_need");
    }

    public function setStartPriceTrade($object){
        $start_price_trade = $object->getMessage();
        if (is_numeric($start_price_trade) && $start_price_trade > 0) {
            $start_price_trade = Setting::updateOrCreate(
                ["key" => "start_price_trade"],
                ["value" => (int)$start_price_trade]
            );

            $response_text = "شروع معامله   بروزرسانی شد:";
            $response_text .= "\n\n";
            $response_text .= " مبلغ";
            $response_text .= "\n\n";
            $response_text .= number_format(data_get($start_price_trade, "value"), 0);
            $response_text .= "\n\n";
            $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
            cache()->forget($object->getKeyCache() . $object->getUserId());
            cache()->forget("start_price_trade");
        } else {
            $object->getTelegramServices()->sendMessage($object->getUserId(), "مبلغ وارد شده معامله  صحیخ نمی باشد");

        }
    }

    public function setEndPriceTrade($object)
    {
        $end_price_trade = $object->getMessage();
        if (is_numeric($end_price_trade) && $end_price_trade > 0) {
            $end_price_trade = Setting::updateOrCreate(
                ["key" => "end_price_trade"],
                ["value" => (int)$end_price_trade]
            );

            $response_text = "سقف مبلغ معامله بروزرسانی شد:";
            $response_text .= "\n\n";
            $response_text .= " مبلغ";
            $response_text .= "\n\n";
            $response_text .= number_format(data_get($end_price_trade, "value"), 0);
            $response_text .= "\n\n";
            $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
            cache()->forget($object->getKeyCache() . $object->getUserId());
            cache()->forget("end_price_trade");

        } else {
            $object->getTelegramServices()->sendMessage($object->getUserId(), "مبلغ وارد شده سقف مبلغ صحیخ نمی باشد");

        }
    }

    public function setStartHoursOfOperation($object){
        if (isValidTime($object->getMessage())) {

            $strat = Setting::updateOrCreate(
                ["key" => "start_hours_of_operation"],
                ["value" => $object->getMessage()]
            );


            $response_text = "زمان شروع معاملات:";
            $response_text .= "\n\n";
            $response_text .= $strat->value;
            $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
            cache()->forget($object->getKeyCache() . $object->getUserId());
            cache()->forget("parameter_need");
        } else {
            $object->getTelegramServices()->sendMessage($object->getUserId(), "ساختار وارد شده ساعت باید باشد 09:00");

        }
    }

    public function setEndHoursOfOperation($object)
    {
        if (isValidTime($object->getMessage())) {
            $strat = Setting::updateOrCreate(
                ["key" => "end_hours_of_operation"],
                ["value" => $object->getMessage()]
            );


            $response_text = "زمان پایان معاملات:";
            $response_text .= "\n\n";
            $response_text .= $strat->value;
            $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
            cache()->forget($object->getKeyCache() . $object->getUserId());
            cache()->forget("parameter_need");
        } else
            $object->getTelegramServices()->sendMessage($object->getUserId(), "ساختار وارد شده ساعت باید باشد 22:00");
    }

    public function getDataTomorrow($object)
    {
        $tomorrow = Setting::where("key", "tomorrow")->first();
        $response_text = "تاریخ فردا معاملات:";
        $response_text .= "\n\n";
        if($tomorrow)
            $response_text .= toJalali(data_get($tomorrow,"value"), "Y/m/d");
        else
            $response_text .= toJalali(now()->addDay(), "Y/m/d");
        $response_text .= "\n\n";
        $response_text .= "در صورت تاریخ دیگر به صورت ۱۴۰۳/۰۴/۰۱ وارد کنید";
        $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
        cache()->set($object->getKeyCache() . $object->getUserId(), "set_date_tomorrow");

    }
    public function setDataTomorrow($object)
    {
        if(isValidShamsiDate($object->getMessage()))
        {
            $date = toGregorian($object->getMessage(), "Y/m/d");
            Setting::updateOrCreate(["key"=>"tomorrow"],[
                "value"=>$date
            ]);
            $response_text = "تاریخ فردا معاملات:";
            $response_text .= "\n\n";
            $response_text .= toJalali($date, "Y/m/d");
            $response_text .= "\n\n";
            $response_text .= "تنظیم شد";
            $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
            cache()->forget($object->getKeyCache() . $object->getUserId());
            cache()->forget("set_tomorrow_date");
        }else{
            $response_text = "فرمت تاریخ وارد شده درست نمی باشد";
            $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);

        }


    }
}
