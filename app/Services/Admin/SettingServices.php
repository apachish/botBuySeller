<?php

namespace App\Services\Admin;


use App\Models\Setting;
use App\Services\TelegramServices;
use App\Services\TextServices;

class SettingServices extends TextServices
{

    public $keyword = [
                [
                    ['text' => "\xF0\x9F\x93\x9Aویرایش قوانین"],
                    ['text' => "\xE2\x81\x89ویرایش راهنما"],
                ],
                [
                    ['text' => "\xF0\x9F\x92\xB3\xF0\x9F\x8C\xB3ویرایش حق اشتراک"],
                    ['text' => "\xF0\x9F\x92\xB1کیف پول"]
                ],
                [
                    ['text' => "\xE2\x86\xA9منو"]
                ],
            ];

    public function getRule($object)
    {
        $rule = Setting::where("key", "rule")->first();

        if ($rule) {
            $response_text = $rule->value;
            $response_text .= "\n\n";
            $response_text .= "متن بالا متنن قبلی می  باشد ویرایش کنید";
        } else
            $response_text = "متن قواتین وارد کنید";

        cache()->set($object->getKeyCache() . $object->getUserId(), "rule");

        $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
    }

    public function getHelp($object)
    {
        $rule = Setting::where("key", "help")->first();

        if ($rule) {
            $response_text = $rule->value;

            $response_text .= "\n\n";
            $response_text .= "متن بالا متنن قبلی می  باشد ویرایش کنید";
        } else
            $response_text = "متن راهنما وارد کنید";

        cache()->set($object->getKeyCache() . $object->getUserId(), "help");

        $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
    }

    public function getMembership($object)
    {
        $rule = Setting::where("key", "membership")->first();

        if ($rule) {
            $response_text = "متن حق اشتراک وارد کنید";
            $response_text .= $rule->value;
            $response_text .= "\n\n";
            $response_text .= "می توانید ویرایش کنید";
        } else
            $response_text = "متن حق اشتراک وارد کنید";

        cache()->set($object->getKeyCache() . $object->getUserId(), "membership");

        $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
    }

    public function getWalletMembership($object)
    {
        $wallet_membership = Setting::where("key", "wallet_membership")->first();

        if ($wallet_membership) {
            $response_text = "کیف پول حق اشتراک وارد شده";

            $response_text .= $wallet_membership->value;

            $response_text .= "\n\n";
            $response_text .= "می توانید ویرایش کنید";
        } else
            $response_text = "کیف پول حق اشتراک وارد کنید";

        cache()->set($object->getKeyCache() . $object->getUserId(), "wallet_membership");

        $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
    }

    public function setRule($object)
    {
        $rule = Setting::updateOrCreate(
            ["key" => "rule"],
            ["value" => $object->getMessage()]
        );


        $response_text = "متن قواتین  بروزرسانی شد:";
        $response_text .= "\n\n";
        $response_text .= $rule->value;
        $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
        cache()->forget($object->getKeyCache() . $object->getUserId());
    }

    public function setHelp($object)
    {
        $rule = Setting::updateOrCreate(
            ["key" => "help"],
            ["value" => $object->getMessage()]
        );


        $response_text = "متن راهنما  بروزرسانی شد:";
        $response_text .= "\n\n";
        $response_text .= $rule->value;
        $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
        cache()->forget($object->getKeyCache() . $object->getUserId());
    }

    public function setMembership($object)
    {
        $membership = Setting::updateOrCreate(
            ["key" => "membership"],
            ["value" => $object->getMessage()]
        );


        $response_text = "متن حق اشتراک  بروزرسانی شد:";
        $response_text .= "\n\n";
        $response_text .= $membership->value;
        $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
        cache()->forget($object->getKeyCache() . $object->getUserId());
    }

    public function setWalletMembership($object)
    {
        $membership = Setting::updateOrCreate(
            ["key" => "wallet_membership"],
            ["value" => $object->getMessage()]
        );


        $response_text = "کیف پول حق اشتراک  بروزرسانی شد:";
        $response_text .= "\n\n";
        $response_text .= $membership->value;
        $response_text .= "\n\n";

        $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
        cache()->forget($object->getKeyCache() . $object->getUserId());
    }
}
