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

    public function setWebhooks($token)
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
        $this->menu($telegram, $chatId);
//        $commands = [
//            [
//                "command" => "start",
//                "description" => "Start the bot"
//            ],
//            [
//                "command" => "help",
//                "description" => "Get help"
//            ],
//            [
//                "command" => "info",
//                "description" => "Get info about the bot"
//            ],
//            [
//                "command" => "contact",
//                "description" => "Contact us"
//            ],
//        ];
//        $this->setCommands($commands);

    }

    public function setWebhook($token)
    {
        // دریافت داده‌ها از وب‌هوک تلگرام
        $content = file_get_contents("php://input");
        $update = json_decode($content, true);

        if (!$update) {
            exit;
        }
        $this->access_token = $token;

        $message = isset($update['message']) ? $update['message'] : "";
        $callback_query = isset($update['callback_query']) ? $update['callback_query'] : "";
        $chat_id = isset($message['chat']['id']) ? $message['chat']['id'] : "";
        $text = isset($message['text']) ? $message['text'] : "";
        $message_id = isset($message['message_id']) ? $message['message_id'] : "";
        $callback_data = isset($callback_query['data']) ? $callback_query['data'] : "";
        $callback_chat_id = isset($callback_query['message']['chat']['id']) ? $callback_query['message']['chat']['id'] : "";
        $callback_message_id = isset($callback_query['message']['message_id']) ? $callback_query['message']['message_id'] : "";

// پردازش دستورات کاربر
        if ($text == "/start") {
            $this->sendMessage($chat_id, "شخص مورد نظر را انتخاب کنید:", [
                'inline_keyboard' => [
                    [
                        ['text' => "پویا ✅", 'callback_data' => 'confirm_pouya'],
                        ['text' => "پویا ❌", 'callback_data' => 'reject_pouya']
                    ],
                    [
                        ['text' => "محمد ✅", 'callback_data' => 'confirm_mohammad'],
                        ['text' => "محمد ❌", 'callback_data' => 'reject_mohammad']
                    ],
                    [
                        ['text' => "صفحه بعد", 'callback_data' => 'next_page']
                    ]
                ]
            ]);
        } elseif ($callback_data) {
            if ($callback_data == 'next_page') {
                $this->sendPage2($callback_chat_id, $callback_message_id);
            } elseif ($callback_data == 'prev_page') {
                $this->sendPage1($callback_chat_id, $callback_message_id);
            } elseif (strpos($callback_data, 'confirm_') === 0) {
                $name = str_replace('confirm_', '', $callback_data);
                $this->editMessageReplyMarkup($callback_chat_id, $callback_message_id, null);
                $this->sendMessage($callback_chat_id, "شما $name را تایید کردید.");
            } elseif (strpos($callback_data, 'reject_') === 0) {
                $name = str_replace('reject_', '', $callback_data);
                $this->editMessageReplyMarkup($callback_chat_id, $callback_message_id, null);
                $this->sendMessage($callback_chat_id, "شما $name را رد کردید.");
            }
        }
    }

    public function menu($telegram, $chatId)
    {
        $keyboard =  [
            [['text' => "پویا"], ['text' => "محمد"]],
            [['text' => "وحید"], ['text' => "خودم"]],
            [['text' => "📞 دفترچه تلفن"], ['text' => "📈 معاملات باز"]],
            [['text' => "ظرفیت"], ['text' => "📋 لیست همکاران"]],
            [['text' => "📚 قوانین"], ['text' => "مانده کمیسیون"]],
            [['text' => "🔍 راهنما"], ['text' => "فعال سازی تایید دو مرحله‌ای"]],
            [['text' => "🛑 غیر فعال فوری"], ['text' => "⚠️ غیر فعال فوری"]]
        ];
        $reply_markup = Keyboard::make([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ]);

        $response = $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "سلام",
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



// تابع ارسال پیام با کیبورد شیشه‌ای
    private function sendMessage($chat_id, $message, $keyboard = null) {
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

// تنظیم کیبورد شیشه‌ای برای صفحه اول
    private function sendPage1($chat_id, $message_id) {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => "پویا ✅", 'callback_data' => 'confirm_pouya'],
                    ['text' => "پویا ❌", 'callback_data' => 'reject_pouya']
                ],
                [
                    ['text' => "محمد ✅", 'callback_data' => 'confirm_mohammad'],
                    ['text' => "محمد ❌", 'callback_data' => 'reject_mohammad']
                ],
                [
                    ['text' => "صفحه بعد", 'callback_data' => 'next_page']
                ]
            ]
        ];

        $this->editMessageReplyMarkup($chat_id, $message_id, $keyboard);
    }

// تنظیم کیبورد شیشه‌ای برای صفحه دوم
    private function sendPage2($chat_id, $message_id) {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => "وحید ✅", 'callback_data' => 'confirm_vahid'],
                    ['text' => "وحید ❌", 'callback_data' => 'reject_vahid']
                ],
                [
                    ['text' => "خودم ✅", 'callback_data' => 'confirm_self'],
                    ['text' => "خودم ❌", 'callback_data' => 'reject_self']
                ],
                [
                    ['text' => "صفحه قبل", 'callback_data' => 'prev_page']
                ]
            ]
        ];

        $this->editMessageReplyMarkup($chat_id, $message_id, $keyboard);
    }

// تابع ویرایش کیبورد شیشه‌ای
    function editMessageReplyMarkup($chat_id, $message_id, $keyboard) {
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



}
