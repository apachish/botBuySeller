<?php

namespace App\Services\Admin;


use App\Models\Setting;

class TransactionServices
{
    public $keyword = [
        [
            ['text' => "\xF0\x9F\x9A\xAB\xE2\x98\x80ممنوع معامله روز"],
        ], [
            ['text' => "\xF0\x9F\x9A\xAB\xF0\x9F\x9A\xBBممنوع معامله"],
        ], [
            ['text' => "\xF0\x9F\x93\x88شروع مبلغ معامله"],
        ], [
            ['text' => "\xF0\x9F\x93\x88سقف مبلغ معامله"],
        ],
        [
            ['text' => "\xE2\x86\xA9منو"]
        ],
    ];

    public function getForbidden($object)
    {
        $forbidden = Setting::where("key", "forbidden")->first();

        if ($forbidden) {
            $response_text = $forbidden->value ? "فعال" : "غیرفعال";

            $response_text .= "\n\n";
            $response_text .= "معامله همکار و مشتری";
        } else
            $response_text = "مشخص کنید معامله مشتری و همکار چگونه می باشد";

        $keyboard[0][0] = ['text' => "فعال", "callback_data" => "forbidden_active"];
        $keyboard[0][1] = ['text' => "غیرفعال", "callback_data" => "forbidden_deactivate"];

        $menu = $object->getTelegramServices()->MessageReplyMarkup($object->getTelegram(), $object->getUserId(), $response_text, $keyboard);
        cache()->set("forbidden_" . $object->getUserId(), $menu);
    }

    public function getForbiddenDay($object)
    {
        $forbidden_day = Setting::where("key", "forbidden_day")->first();

        if ($forbidden_day) {
            $response_text = "معامله روز ";
            $response_text .= "\n\n";
            $response_text .= $forbidden_day->value ? "فعال" : "غیرفعال";
            $response_text .= "\n\n";
            $response_text .= "می باشد";
        } else
            $response_text = "مشخص کنید معامله در چه وضعیتی باشد";

        $keyboard[0][0] = ['text' => "فعال", "callback_data" => "forbidden_day_active"];
        $keyboard[0][1] = ['text' => "غیرفعال", "callback_data" => "forbidden_day_deactivate"];

        $menu = $object->getTelegramServices()->MessageReplyMarkup($object->getTelegram(), $object->getUserId(), $response_text, $keyboard);
        cache()->set("forbidden_day_" . $object->getUserId(), $menu);
    }

    public function getStartPrice($object)
    {
        $s_price_trade = Setting::where("key", "start_price_trade")->first();
        if ($s_price_trade) {
            $response_text = "شروع معامله   تنظیم شد:";
            $response_text .= "\n\n";
            $response_text .= " مبلغ";
            $response_text .= "\n\n";
            $response_text .= number_format(data_get($s_price_trade, "value"), 0);
            $response_text .= "\n\n";
        } else {
            $response_text = " شروع مبلغ وارد شده باید به صورت \n\n";
            $response_text .= "14000000 \n\n";
        }
        cache()->set($object->getKeyCache() . $object->getUserId(), "start_price_trade");

        $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);

    }

    public function getEndPrice($object)
    {
        $s_price_trade = Setting::where("key", "end_price_trade")->first();
        if ($s_price_trade) {
            $response_text = "سقف مبلغ معامله   تنظیم شد:";
            $response_text .= "\n\n";
            $response_text .= " مبلغ";
            $response_text .= "\n\n";
            $response_text .= number_format(data_get($s_price_trade, "value"), 0);
            $response_text .= "\n\n";
        } else {
            $response_text = " شروع مبلغ وارد شده باید به صورت \n\n";
            $response_text .= "14000000 \n\n";
        }
        cache()->set($object->getKeyCache() . $object->getUserId(), "end_price_trade");

        $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
    }

    public function setForbidden($object)
    {
        $data = str_replace('forbidden_', '', $object->getData());
        $value = $data == "active" ? true : false;
        $forbidden = Setting::where("key", "forbidden")->first();
        if ($forbidden)
            $forbidden->update(["value" => $value]);
        else {
            Setting::create([
                "key" => "forbidden",
                "value" => $value,
            ]);
        }
        $message_id = cache()->get("forbidden_" . $object->getUserId());
        $text = $data == "active" ? "فعال شد" : "غیرفعال شد";
        $object->getTelegramServices()->editMessageTextAndInlineKeyboard($object->getUserId(), $message_id, $text, []);
        cache()->forget("forbidden_" . $object->getUserId());
    }
    public function setForbiddenDay($object)
    {
        $data = str_replace('forbidden_day_', '', $object->getData());
        $value = $data == "active" ? true : false;
        $forbidden_day = Setting::where("key", "forbidden_day")->first();
        if ($forbidden_day)
            $forbidden_day->update(["value" => $value]);
        else {
            Setting::create([
                "key" => "forbidden_day",
                "value" => $value,
            ]);
        }
        $message_id = cache()->get("forbidden_day_" . $object->getUserId());
        $text = $data == "active" ? "فعال شد" : "غیرفعال شد";
        $object->getTelegramServices()->editMessageTextAndInlineKeyboard($object->getUserId(), $message_id, $text, []);
        cache()->forget("forbidden_day_" . $object->getUserId());
    }

}
