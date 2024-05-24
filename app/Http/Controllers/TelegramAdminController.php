<?php

namespace App\Http\Controllers;

use App\Http\Resources\ContactResourceCollection;
use App\Services\TelegramServices;
use Illuminate\Http\Request;
use Telegram\Bot\Api;
use App\Models\Bot;
use App\Models\TextTelegram;
use App\Models\UserTelegram;
use Telegram\Bot\Keyboard\Keyboard;

class TelegramAdminController extends Controller
{
    private $access_token;

    public function setWebhook($token)
    {
        $bot = Bot::where('token', $token)
            ->first();

        if (!$bot) return false;

        $this->access_token = $bot->token;

        $telegram = new Api($token);
        $service_telgram = new TelegramServices();
        $service_telgram->access_token = $token;


        // دریافت پیام ارسال شده به بات
        $input = file_get_contents("php://input");
        $update = json_decode($input, true);
        $update = $telegram->getWebhookUpdate();
        logger('ss', [$update]);


        // دریافت دستور ارسال شده توسط کار
        if (isset($update["my_chat_member"]))
            $type = "my_chat_member";
        elseif (isset($update['callback_query']))
            $type = "callback_query";
        else
            $type = "message";

        $user_id = data_get($update, $type . '.from.id');

        $access = $bot->accessBot->where("user_id", $user_id)->where("type", "admin");

        if ($access == null) return false;
        $user_telegram = UserTelegram::find($user_id);
        if ($user_telegram == null) {
            $user_telegram = UserTelegram::create([
                "id" => $user_id,
                "is_bot" => data_get($update, $type . '.from.is_bot'),
                "first_name" => data_get($update, $type . '.from.first_name'),
                "last_name" => data_get($update, $type . '.from.last_name'),
                "mobile" => data_get($update, $type . '.mobile'),
                "username" => data_get($update, $type . '.from.username'),
                "language_code" => data_get($update, $type . '.from.language_code'),
            ]);
        }
        $chatId = $update[$type]['from']['id']; // چت‌آیدی کاربر
        $message_id = null;
        if (isset($update[$type]['message_id']))
            $message_id = $update[$type]['message_id']; // چت‌آیدی کاربر
        if (isset($update[$type]['message']['message_id']))
            $message_id = $update[$type]['message']['message_id']; // چت‌آیدی کاربر
        logger("chatid" . $chatId);
        $text = "سلام! به منوی اصلی خوش آمدید.";
            $data = data_get($update, $type . ".text");
            logger("data",[$data]);
            switch ($data) {
                case "📞 دفترچه تلفن":
                    $text = "لیست شماره تلفن کاربران";
                    $contacts = UserTelegram::whereNotNull("mobile")->simplePaginate(5);
                    $page = $contacts->currentPage();
                    $next = $contacts->nextPageUrl();
                    $pre = $contacts->previousPageUrl();
                    logger("page",[$next,$page,$pre]);
                    $contacts = (new ContactResourceCollection($contacts))->toArray();
                    $contacts[] = [
                        ['text' => "بعدی", "callback_data" => "next"],
                        ['text' => "قبلی", "callback_data" => "pre"],
                    ];
                    $service_telgram->MessageReplyMarkup($telegram,$chatId,$text,$contacts);
                    break;
            }

        $this->menu($telegram, $chatId, $text);

    }


    public function menu($telegram, $chatId, $text)
    {
        $keyboard = [
            [
                ['text' => "📞 دفترچه تلفن"],
                ['text' => "📈 معاملات باز"]
            ],
            [
                ['text' => "ظرفیت"],
                ['text' => "📋 لیست کاربران"]
            ],
            [
                ['text' => "ظرفیت"],
                ['text' => "📋 لیست کاربران در انتظار"]
            ],
            [
                ['text' => "🔍 جستجو کاربر"]
            ],
            [
                ['text' => "📚  ویرایش قوانین"]
            ],
            [
                ['text' => "\xE2\x81\x89	  ویرایش راهنما"]
            ],
        ];
        $reply_markup = Keyboard::make([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ]);

        $response = $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => $reply_markup
        ]);

    }


}
