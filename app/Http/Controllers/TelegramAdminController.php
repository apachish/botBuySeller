<?php

namespace App\Http\Controllers;

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
        logger("chatid" . $chatId);
        if($type =="callback_query"){
            $data=  data_get($update,$type.".data");
            if($data == "list"){
            }
        }
//        $this->menu($telegram, $chatId);
        $commands = [
            [
                "command" => "start",
                "description" => "Start the bot"
            ],
            [
                "command" => "help",
                "description" => "Get help"
            ],
            [
                "command" => "info",
                "description" => "Get info about the bot"
            ],
            [
                "command" => "contact",
                "description" => "Contact us"
            ],
        ];
        $this->setCommands($commands);

    }

    public function menu($telegram, $chatId)
    {
        $keyboard = [
            [
                ["text" => '\xE2\x86\xA9	 لیست کاربران', "callback_data" => "list"]
            ],
            [
                ["text" => '\xE2\x9D\x95	 لیست کاربران منتظر تایید', "callback_data" => "pending"]
            ],
            [
                ['text' => "\xE2\x86\xA9	 بازگشت به ", "callback_data" => "return"]
            ]
        ];
        $reply_markup = Keyboard::make([
            'inline_keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        $response = $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "",
            'reply_markup' => $reply_markup
        ]);

    }

    private function setCommands($commands) {

        $url = "https://api.telegram.org/bot$this->access_token/setMyCommands";

        $post_fields = [
            'commands' => json_encode($commands)
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:application/json"));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

}
