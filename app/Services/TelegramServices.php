<?php

namespace App\Services;

use danog\MadelineProto\API;
use danog\MadelineProto\RPCErrorException;
use Telegram\Bot\Keyboard\Keyboard;
use function Symfony\Component\Translation\t;

class TelegramServices
{

    private $access_token;
    public $menu_key = "menu_";

    /**
     * @return mixed
     */
    public function getAccessToken()
    {
        return $this->access_token;
    }


    public function __construct($access_token)
    {
        $this->access_token = $access_token;
    }

    public static function menu($telegram, $keyboard, $user, $text)
    {
        $reply_markup = Keyboard::make([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ]);


        $response = $telegram->sendMessage([
            'chat_id' => $user->telegram_id,
            'text' => $text,
            'reply_markup' => $reply_markup
        ]);
        return $response;
    }

    function setKeyword($chat_id, $keyboard)
    {
        $url = "https://api.telegram.org/bot$this->access_token/sendMessage";

        // تنظیم کیبورد با درخواست به اشتراک‌گذاری مخاطب
        $keyboard = [
            'keyboard' => $keyboard,
            'one_time_keyboard' => true,
            'resize_keyboard' => true
        ];

        $text = "لطفاً مخاطب خود را به اشتراک بگذارید:";

        $post_fields = [
            'chat_id' => $chat_id,
            'text' => $text,
            'reply_markup' => json_encode($keyboard)
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

    public function MessageReplyMarkup($telegram, $chat_id, $text, $keyboard,$cache_use=true)
    {
//        $keyboard = [
//            'inline_keyboard' => [
//                [
//                    [
//                        'text' => "تماس بگیرید",
//                        'url' => "tel:+989120308527" // شماره تلفن مورد نظر را با فرمت صحیح وارد کنید
//                    ]
//                ]
//            ]
//        ];
        $reply_markup = Keyboard::make([
            'inline_keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        $response = $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $text,
            'reply_markup' => $reply_markup,

        ]);

        if (data_get($response, "message_id") )//&& $cache_use
        {
//            if(cache()->get($this->menu_key.$chat_id))
//            {
//                $result_delete = $this->deleteMessage($chat_id,cache()->get($this->menu_key.$chat_id));
//                logger("result_delete",[$result_delete]);
//            }
            cache()->set($this->menu_key.$chat_id,data_get($response, "message_id"));
            logger("message_id",[cache()->get($this->menu_key.$chat_id),$this->menu_key.$chat_id]);
        }
        else
            logger("exption", [$response]);
        return data_get($response, "message_id");

    }

// تابع برای حذف پیام
    function deleteMessage($chat_id, $message_id) {
        $url = "https://api.telegram.org/bot$this->access_token/deleteMessage";

        $post_fields = [
            'chat_id' => $chat_id,
            'message_id' => $message_id
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:application/json"));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }


    public function deleteKeyboard($chatId,$text='منو کاربری شما تغییر  یافت')
    {
        $keyboard = [
            'remove_keyboard' => true
        ];

        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode($keyboard)
        ];

        $url = "https://api.telegram.org/bot$this->access_token/sendMessage";

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type:application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($data),
            ],
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
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
        $r = curl_exec($ch);
        curl_close($ch);
    }

    function editCustomKeyboard($chat_id, $message_id, $text, $keyboard_menu)
    {

        $url = "https://api.telegram.org/bot$this->access_token/editMessageReplyMarkup";

        // تنظیم کیبورد سفارشی جدید
        $keyboard = [
            'keyboard' => $keyboard_menu,
            'resize_keyboard' => true,
            'one_time_keyboard' => false,
            'input_field_placeholder' => $text
        ];


        $post_fields = [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'reply_markup' => json_encode($keyboard)
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:application/json"));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }

    function editMessageTextAndInlineKeyboard($channel_chat_id, $message_id, $message, $keyboard = null)
    {
        $url = "https://api.telegram.org/bot$this->access_token/editMessageText";

        // تنظیم کیبورد شیشه‌ای جدید
        if ($keyboard)
            $keyboard = [
                'inline_keyboard' => $keyboard
            ];
        else
            $keyboard = new \stdClass();

        $post_fields = [
            'chat_id' => $channel_chat_id,
            'message_id' => (int)$message_id,
            'text' => $message,
            'reply_markup' => json_encode($keyboard)
        ];

        logger("post field",$post_fields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:application/json"));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
        $result = curl_exec($ch);
        curl_close($ch);

        logger("edit message request",[json_decode($result, true)]);
        return json_decode($result, true);
    }

    // تابع ارسال پیام با کیبورد شیشه‌ای
    public function sendMessage($chat_id, $message, $keyboard = null)
    {
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
        $result = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($result, true);
        return data_get($response, "result.message_id");
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
    public function setCommands($commands)
    {

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


    public function kickUserFromChannel($chat_id, $user_id)
    {
        global $access_token, $channel_username;
        $url = "https://api.telegram.org/bot$access_token/kickChatMember";
        $post_fields = [
            'chat_id' => "$channel_username",
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

    public function checkMember($chatId,$userId)
    {
        // ارسال درخواست به API تلگرام
        try {
            logger("https://api.telegram.org/bot$this->access_token/getChatMember?chat_id=$chatId&user_id=$userId");
            $response = file_get_contents("https://api.telegram.org/bot$this->access_token/getChatMember?chat_id=$chatId&user_id=$userId");

// تبدیل پاسخ از JSON به آرایه
            $result = json_decode($response, true);
            logger("response", [$response]);
// بررسی وضعیت عضویت کاربر در کانال
            if (data_get($result, 'ok') && in_array(data_get($result, 'result.status'), ['member', 'creator'])) {
                logger("کاربر عضو کانال است.");
                return true;
            } else {
                logger("کاربر عضو کانال نیست یا خطایی رخ داده است.");
                return false;
            }
        }catch (\Exception $exception){
            return false;
        }

    }


    public function addMemberChanel()
    {
        $settings = [
            'app_info' => [
                'api_id' => env('YOUR_API_ID', 37090), // API ID خود را وارد کنید
                'api_hash' => env('YOUR_API_HASH', '0fca2444e39d6d2eb7ad48c7cb302ae3') // API Hash خود را وارد کنید
            ],
        ];

        $MadelineProto = new API('session.madeline', $settings);

// Login and synchronize
        $MadelineProto->start();

// تابع برای اضافه کردن کاربر به کانال
        function addUserToChannel($MadelineProto, $channel, $user)
        {
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

    public function sendRequestContactButton($chat_id,$text) {
        $url = "https://api.telegram.org/bot$this->access_token/sendMessage";

        $keyboard = [
            'keyboard' => [
                [
                    [
                        'text' => 'ارسال شماره تلفن',
                        'request_contact' => true
                    ]
                ]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ];

        $post_fields = [
            'chat_id' => $chat_id,
            'text' => $text,
            'reply_markup' => json_encode($keyboard)
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:application/json"));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }

    public function kickChatMember( $channelId, $userId) {
        $url = "https://api.telegram.org/bot$this->access_token/kickChatMember";
        $data = [
            'chat_id' => $channelId,
            'user_id' => $userId
        ];

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type:application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($data),
            ],
        ];

        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        return json_decode($response, true);
    }

    public function banChatMember( $chatId, $userId) {
        $url = "https://api.telegram.org/bot$this->access_token/banChatMember";
        $data = [
            'chat_id' => $chatId,
            'user_id' => $userId
        ];

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type:application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($data),
            ],
        ];

        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        return json_decode($response, true);
    }

}
