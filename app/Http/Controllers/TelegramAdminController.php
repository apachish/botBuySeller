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
    public function setWebhook($token, $replay = [])
    {
        $bot = Bot::where('token', $token)
            ->first();

        if (!$bot) return false;


        $telegram = new Api($token);


        // دریافت پیام ارسال شده به بات
        $input = file_get_contents("php://input");
        $update = json_decode($input, true);
        $update = $telegram->getWebhookUpdate();
        logger('replay', [$replay]);


        // دریافت دستور ارسال شده توسط کار
        if (isset($update["my_chat_member"]))
            $type = "my_chat_member";
        elseif (isset($update['callback_query']))
            $type = "callback_query";
        else
            $type = "message";

        $user_id = data_get($update, $type . '.from.id');

        $access = $bot->accessBot->where("user_id",$user_id)->where("type","admin");

        if($access ==  null) return false;
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

        $this->menu($telegram,$chatId);


    }

    public function menu($telegram,$chatId)
    {
                $keyboard = [
            ["text"=>'\ud83e\uddd1\u200d\ud83e\udd1d\u200d\ud83e\uddd1 لیست کاربران',"callback_data"=>"list"],
            ["text"=>'\ud83e\uddd1\u200d\ud83e\udd1d\u200d\ud83e\uddd1 لیست کاربران منتظر تایید',"callback_data"=>"pending"],
            ['text'=>"\u21a9\ufe0f بازگشت به ","callback_data"=>"return"]
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

}
