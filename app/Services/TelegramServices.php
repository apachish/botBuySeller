<?php

namespace App\Services;

use danog\MadelineProto\API;
use danog\MadelineProto\RPCErrorException;
use Telegram\Bot\Keyboard\Keyboard;

class TelegramServices
{

    public $access_token;


    public function MessageReplyMarkup($telegram,$chat_id,$text,$keyboard,$resize_keyboard=true,$one_time_keyboard=true)
    {
        $keyboard[] =[
            ["text"=>"Shahriar P","url"=>"tel:09120308527"]
        ];
        logger("keyword",$keyboard);
        $reply_markup = Keyboard::make([
            'keyboard' => $keyboard,
            'resize_keyboard' => $resize_keyboard,
            'one_time_keyboard' => $one_time_keyboard
        ]);

        $response = $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $text,
            'reply_markup' => $reply_markup
        ]);

    }
    // تابع ویرایش کیبورد شیشه‌ای
    public function editMessageReplyMarkup($chat_id, $message_id, $keyboard) {
        $url = "https://api.telegram.org/bot$this->access_token/editMessageReplyMarkup";
        $post_fields = [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'reply_markup' => json_encode($keyboard)
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_exec($ch);
        curl_close($ch);
    }

    // تابع ارسال پیام با کیبورد شیشه‌ای
    public function sendMessage($chat_id, $message, $keyboard = null) {
        $url = "https://api.telegram.org/bot$this->access_token/sendMessage";
        $post_fields = [
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];

        if ($keyboard) {
            $post_fields['reply_markup'] = json_encode($keyboard);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_exec($ch);
        curl_close($ch);
    }

    //برای ایجاد یک منوی دائمی در ربات تلگرام خود که تمام یا برخی از دستورات ربات را نشان می دهد
    /*
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
     */
    public function setCommands($commands) {

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


    public function kickUserFromChannel($chat_id, $user_id) {
        global $access_token, $channel_username;
        $url = "https://api.telegram.org/bot$access_token/kickChatMember";
        $post_fields = [
            'chat_id' => "@$channel_username",
            'user_id' => $user_id
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    public function checkMember($user,$bot)
    {
//        $telegram = new Api(data_get($bot,'token')); // توکن ربات تلگرام خود را جایگزین کنید
        $token = data_get($bot,'token'); // توکن ربات تلگرام خود را جایگزین کنید

        $chatId = '@apdadana'; // نام کاربری یا آیدی کانال مورد نظر
        $userId = data_get($user,'id'); // آیدی کاربری مورد نظر

// درخواست اطلاعات کاربر در کانال
//        $response = $telegram->getChatMember([
//            'chat_id' => $chatId,
//            'user_id' => $userId
//        ]);
//
//        logger("response",[$response,$response->isOk() , $response->getChatMember() ]);
//// بررسی وضعیت عضویت کاربر در کانال
//        if ($response->isOk() && $response->getChatMember()->getStatus() == 'member') {
//            logger( "کاربر عضو کانال است.");
//            return true;
//        } else {
//            logger( "کاربر عضو کانال نیست یا خطایی رخ داده است.");
//            return false;
//
//        }
        // ارسال درخواست به API تلگرام
        logger("https://api.telegram.org/bot$token/getChatMember?chat_id=$chatId&user_id=$userId");
        $response = file_get_contents("https://api.telegram.org/bot$token/getChatMember?chat_id=$chatId&user_id=$userId");

// تبدیل پاسخ از JSON به آرایه
        $result = json_decode($response, true);
        logger("response",[$response ]);
// بررسی وضعیت عضویت کاربر در کانال
        if (data_get($result,'ok') && in_array(data_get($result,'result.status'),['member','creator'])) {
            logger( "کاربر عضو کانال است.");
            return true;
        } else {
            logger( "کاربر عضو کانال نیست یا خطایی رخ داده است.");
            return false;
        }
    }


    public function addMemberChanel()
    {
        $settings = [
            'app_info' => [
                'api_id' => env('YOUR_API_ID',37090), // API ID خود را وارد کنید
                'api_hash' => env('YOUR_API_HASH','0fca2444e39d6d2eb7ad48c7cb302ae3') // API Hash خود را وارد کنید
            ],
        ];

        $MadelineProto = new API('session.madeline', $settings);

// Login and synchronize
        $MadelineProto->start();

// تابع برای اضافه کردن کاربر به کانال
        function addUserToChannel($MadelineProto, $channel, $user) {
            try {
                $MadelineProto->channels->inviteToChannel([
                    'channel' => $channel,
                    'users' => [$user]
                ]);
                echo "User added successfully!";
            } catch (RPCErrorException $e) {
                echo "Error: " . $e->getMessage();
            }
        }

// شناسه کاربری کانال و کاربر
        $channel = '@your_channel_username'; // نام کاربری کانال
        $user = 'user_id'; // شناسه کاربری فردی که می‌خواهید اضافه کنید

// اضافه کردن کاربر به کانال
        addUserToChannel($MadelineProto, $channel, $user);

    }

}
