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


    public function setWebhook($token)
    {
        $bot = Bot::where('token', $token)
            ->first();

        if (!$bot) return false;


        $access_token = $bot->token;

// لیست کاربران مجاز
        $allowed_users = array(
            70115829, // شناسه کاربر اول
            987654321, // شناسه کاربر دوم
            // شناسه‌های بیشتر را اینجا اضافه کنید
        );

        $content = file_get_contents("php://input");
        $update = json_decode($content, true);

        if (!$update) {
            exit;
        }

        $message = isset($update['message']) ? $update['message'] : "";
        $message_id = isset($message['message_id']) ? $message['message_id'] : "";
        $chat_id = isset($message['chat']['id']) ? $message['chat']['id'] : "";
        $text = isset($message['text']) ? $message['text'] : "";
        $user_id = isset($message['from']['id']) ? $message['from']['id'] : "";

// بررسی دسترسی کاربر
        if (!in_array($user_id, $allowed_users)) {
            $this->sendMessage($chat_id, "شما دسترسی لازم برای استفاده از این ربات را ندارید.", $message_id);
            exit;
        }
        $content = file_get_contents("php://input");
        $update = json_decode($content, true);

        if (!$update) {
            exit;
        }

        $message = isset($update['message']) ? $update['message'] : "";
        $message_id = isset($message['message_id']) ? $message['message_id'] : "";
        $chat_id = isset($message['chat']['id']) ? $message['chat']['id'] : "";
        $text = isset($message['text']) ? $message['text'] : "";
        $user_id = isset($message['from']['id']) ? $message['from']['id'] : "";



// پردازش دستورات کاربر
        if ($text == "/start") {
            $this->sendInlineKeyboard($chat_id, "سلام! به منوی اصلی خوش آمدید.", $message_id);
        } elseif ($text == "📞 ارسال شماره تلفن") {
            $this->sendMessage($chat_id, "لطفا شماره موبایل خود را ارسال کنید.", $message_id);
        } elseif ($text == "ℹ️ درباره ما") {
            $this->sendMessage($chat_id, "ما یک شرکت پیشرو در ارائه خدمات ... هستیم.", $message_id);
        } elseif ($text == "📧 تماس با ما") {
            $this->sendMessage($chat_id, "برای تماس با ما لطفا ایمیل بزنید به: example@example.com", $message_id);
        } elseif (preg_match("/^[0-9]{10,15}$/", $text)) {
            $this->sendMessage($chat_id, "شماره موبایل شما دریافت شد: $text", $message_id);
        } else {
            $this->sendMessage($chat_id, "لطفا یکی از گزینه‌های منو را انتخاب کنید.", $message_id);
        }
        if (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
        }

    }

    // تابع ارسال پیام
    private function sendMessage($chat_id, $message, $reply_to_message_id = null) {
        global $access_token;
        $url = "https://api.telegram.org/bot$access_token/sendMessage";
        $post_fields = array(
            'chat_id' => $chat_id,
            'text' => $message
        );
        if ($reply_to_message_id) {
            $post_fields['reply_to_message_id'] = $reply_to_message_id;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_exec($ch);
        curl_close($ch);
    }

// تابع ارسال منوی شیشه‌ای
    private function sendInlineKeyboard($chat_id, $message, $reply_to_message_id = null) {
        global $access_token;
        $url = "https://api.telegram.org/bot$access_token/sendMessage";

        // تعریف کیبورد شیشه‌ای
        $inline_keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📞 ارسال شماره تلفن', 'callback_data' => 'send_phone'],
                    ['text' => 'ℹ️ درباره ما', 'callback_data' => 'about_us']
                ],
                [
                    ['text' => '📧 تماس با ما', 'callback_data' => 'contact_us']
                ]
            ]
        ];

        $post_fields = array(
            'chat_id' => $chat_id,
            'text' => $message,
            'reply_markup' => json_encode($inline_keyboard)
        );

        if ($reply_to_message_id) {
            $post_fields['reply_to_message_id'] = $reply_to_message_id;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_exec($ch);
        curl_close($ch);
    }

// تابع پردازش callback queries
    private function handleCallbackQuery($callback_query) {
        $callback_id = $callback_query['id'];
        $chat_id = $callback_query['message']['chat']['id'];
        $data = $callback_query['data'];

        if ($data == 'send_phone') {
            $this->sendMessage($chat_id, "لطفا شماره موبایل خود را ارسال کنید.");
        } elseif ($data == 'about_us') {
            $this->sendMessage($chat_id, "ما یک شرکت پیشرو در ارائه خدمات ... هستیم.");
        } elseif ($data == 'contact_us') {
            $this->sendMessage($chat_id, "برای تماس با ما لطفا ایمیل بزنید به: example@example.com");
        }
    }
//    public function setWebhookw($token)
//    {
//        $bot = Bot::where('token', $token)
//            ->first();
//
//        if (!$bot) return false;
//
//
//        $telegram = new Api($token);
//
//
//        // دریافت پیام ارسال شده به بات
//        $input = file_get_contents("php://input");
//        $update = json_decode($input, true);
//        $update = $telegram->getWebhookUpdate();
//        logger('ss', [$update]);
//
//
//        // دریافت دستور ارسال شده توسط کار
//        if (isset($update["my_chat_member"]))
//            $type = "my_chat_member";
//        elseif (isset($update['callback_query']))
//            $type = "callback_query";
//        else
//            $type = "message";
//
//        $user_id = data_get($update, $type . '.from.id');
//
//        $access = $bot->accessBot->where("user_id", $user_id)->where("type", "admin");
//
//        if ($access == null) return false;
//        $user_telegram = UserTelegram::find($user_id);
//        if ($user_telegram == null) {
//            $user_telegram = UserTelegram::create([
//                "id" => $user_id,
//                "is_bot" => data_get($update, $type . '.from.is_bot'),
//                "first_name" => data_get($update, $type . '.from.first_name'),
//                "last_name" => data_get($update, $type . '.from.last_name'),
//                "mobile" => data_get($update, $type . '.mobile'),
//                "username" => data_get($update, $type . '.from.username'),
//                "language_code" => data_get($update, $type . '.from.language_code'),
//            ]);
//        }
//        $chatId = $update[$type]['from']['id']; // چت‌آیدی کاربر
//        logger("chatid" . $chatId);
//        if($type =="callback_query"){
//            $data=  data_get($update,$type.".data");
//            if($data == "list"){
//
//            }
//        }
//        $this->menu($telegram, $chatId);
//
//
//    }
//
//    public function menu($telegram, $chatId)
//    {
//        $keyboard = [
//            [
//                ["text" => '\xE2\x86\xA9	 لیست کاربران', "callback_data" => "list"]
//            ],
//            [
//                ["text" => '\xE2\x9D\x95	 لیست کاربران منتظر تایید', "callback_data" => "pending"]
//            ],
//            [
//                ['text' => "\xE2\x86\xA9	 بازگشت به ", "callback_data" => "return"]
//            ]
//        ];
//        $reply_markup = Keyboard::make([
//            'inline_keyboard' => $keyboard,
//            'resize_keyboard' => true,
//            'one_time_keyboard' => true
//        ]);
//
//        $response = $telegram->sendMessage([
//            'chat_id' => $chatId,
//            'text' => "",
//            'reply_markup' => $reply_markup
//        ]);
//    }

}
