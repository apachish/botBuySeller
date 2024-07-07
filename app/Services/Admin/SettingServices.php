<?php

namespace App\Services\Admin;


use App\Models\Setting;
use App\Models\UglyWord;

class SettingServices
{

    public $keyword = [
                [
                    ['text' => "\xF0\x9F\x93\x9Aویرایش قوانین"],
                    ['text' => "\xE2\x81\x89ویرایش راهنما"],
                ],
                    [
                    ['text' => "\xE2\x98\xBAلیست کلمات زشت"],
                    ['text' => "\xE2\x9E\x95افزودن کلمه زشت"],
                ],
                [
                    ['text' => "\xF0\x9F\x92\xB3\xF0\x9F\x8C\xB3ویرایش حق اشتراک"],
                    ['text' => "\xF0\x9F\x95\x92پیام ساعت"]
                ],
                [
                    ['text' => "\xE2\x86\xA9منو"]
                ],
            ];
    public function pre($object)
    {
        $data = str_replace('pre_ugly_word_', '', $object->getData());
        $array = explode("_", $data);
        $page = (int)data_get($array, 0);
        $message_id = cache()->get("menu_List_ugly_word_" . $object->getUserId());
        if ($message_id)
            $this->listUglyWord($object,$page, $message_id);
    }

    public function next($object)
    {
        $data = str_replace('next_ugly_word_', '', $object->getData());
        $array = explode("_", $data);
        $page = (int)data_get($array, 0);

        $message_id = cache()->get("menu_List_ugly_word_" . $object->getUserId());
        logger("aa", [ $message_id, $page]);
        if ($message_id)
            $this->listUglyWord($object,$page, $message_id);
    }
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

    public function getsUglyWord($object)
    {
        $this->listUglyWord($object,1);
    }

    public function getHelp($object)
    {
        $rule = Setting::where("key", "help")->first();

        if ($rule) {
            $response_text = $rule->value;

            $response_text .= "\n\n";
            $response_text .= "متن بالا متن قبلی می باشد ویرایش کنید";
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

    public function setUglyWord($object)
    {
        $ugly = UglyWord::updateOrCreate(
            ["word" => $object->getMessage()],
            ["word" => $object->getMessage()]
        );


        $response_text = "کلمه زیر افزوده شد:";
        $response_text .= "\n\n";
        $response_text .= $ugly->word;
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
    public function deleteUglyWord($object)
    {
        $data = str_replace('delete_ugly_word_', '', $object->getData());
        $data = explode("_",$data);
        $id = data_get($data,0);
        $page = data_get($data,1);
        logger("ss",[$data,$id,$page]);
        $ugly = UglyWord::find($id);

        if($ugly) {
            $response_text = "کلمه زیر حذف شد:";
            $response_text .= "\n\n";
            $response_text .= $ugly->word;
            $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
            $ugly->delete();
            $this->listUglyWord($object,$page);

        }else{
            $object->getTelegramServices()->sendMessage($object->getUserId(), "یافت نشد");

        }
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


    public function getMessage3($object)
    {
        $message = Setting::where("key", "message_3")->first();

        if ($message) {
            $response_text = $message->value;

            $response_text .= "\n\n";
            $response_text .= "متن بالا متن قبلی ساعت ۳ می باشد ویرایش کنید";
        } else
            $response_text = "متن ساعت ۳ وارد کنید";

        cache()->set($object->getKeyCache() . $object->getUserId(), "message_3");

        $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
    }

    public function setMessage3($object)
    {
        $message = Setting::updateOrCreate(
            ["key" => "message_3"],
            ["value" => $object->getMessage()]
        );


        $response_text = "متن پیام ۳  بروزرسانی شد:";
        $response_text .= "\n\n";
        $response_text .= $message->value;
        $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
        cache()->forget($object->getKeyCache() . $object->getUserId());
    }

    private function listUglyWord($object,$page = 1, $message_id = null)
    {

        $text = "\n\nلیست  کلمات زشت";
        $text .= "\n\n";
        $text .= "با کلیک بر روی \xE2\x9D\x8C	 روبروی کلمه می توانید حذف کنید";
        $ugly_words = UglyWord::query();

        $ugly_words = $ugly_words->simplePaginate(10, ['*'], 'page', $page);
        logger("ugly words",[$ugly_words]);
        $page = $ugly_words->currentPage();
        $next = $ugly_words->nextPageUrl() ? (int)str_replace("?page=", "", strstr($ugly_words->nextPageUrl(), "?page=")) : null;
        $pre = $ugly_words->previousPageUrl() ? (int)str_replace("?page=", "", strstr($ugly_words->previousPageUrl(), "?page=")) : null;
        $keyboard = [];
        $i = 0;

        $ugly_words->each(function ($ugly_word) use (&$keyboard, &$i, $page,$object) {

            $keyboard[$i++] = [
                ['text' => "$ugly_word->word", 'callback_data' => $ugly_word->id."_".$page],
                ['text' => "\xE2\x9D\x8C", 'callback_data' => "delete_ugly_word_".$ugly_word->id."_".$page],
            ];
        });
        if ($pre)
            $keyboard[$i][] = ['text' => "قبلی", "callback_data" => "pre_ugly_word_" . $pre ];
        if ($next)
            $keyboard[$i][] = ['text' => "بعدی", "callback_data" => "next_ugly_word" . $next ];

        logger("ugli key",$keyboard);
        if ($message_id)
            $object->getTelegramServices()->editMessageTextAndInlineKeyboard($object->getUserId(), $message_id, $text, $keyboard);
        else {
            $object->getTelegramServices()->menu_key = "menu_List_ugly_word_";
            $menu = $object->getTelegramServices()->MessageReplyMarkup($object->getTelegram(), $object->getUserId(), $text, $keyboard);
        }
    }
}
