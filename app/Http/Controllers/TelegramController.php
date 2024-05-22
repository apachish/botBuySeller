<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Api;
use App\Models\Bot;
use App\Models\TextTelegram;
use App\Models\UserTelegram;
use Telegram\Bot\Keyboard\Keyboard;

class TelegramController extends Controller
{
    public function setWebhook($token, $replay = [])
    {
        $bot = Bot::where('token', $token)->first();

        if(!$bot)
            $bot = Bot::create(["token"=>$token,"title"=>"bot".time()]);

        if (!$bot) return false;


        $telegram = new Api($token);


// دریافت پیام ارسال شده به بات
        $input = file_get_contents("php://input");
        $update = json_decode($input, true);

        logger('getWebhookUpdate', [$telegram->getWebhookUpdate()]);
        logger('update', [$update]);
        logger('replay', [$replay]);
        $update = $telegram->getWebhookUpdate();
        logger('donya', [$update]);



// دریافت دستور ارسال شده توسط کار
        if(isset($update["my_chat_member"]))
            $type = "my_chat_member";
        elseif(isset($update['callback_query']))
            $type = "callback_query";
        else
            $type = "message";

        $user_id = data_get($update, $type . '.from.id');

        $user_telegram = UserTelegram::updateOrCreate([
            "id" => $user_id,
        ], [
            "is_bot" => data_get($update, $type . '.from.is_bot'),
            "first_name" => data_get($update, $type . '.from.first_name'),
            "last_name" => data_get($update, $type . '.from.last_name'),
            "mobile" => data_get($update, $type . '.mobile'),
            "username" => data_get($update, $type . '.from.username'),
            "language_code" => data_get($update, $type . '.from.language_code'),
        ]);
        if(isset($update["my_chat_member"]))
        {
            $chatId = $update['my_chat_member']['from']['id']; // چت‌آیدی کاربر

            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'خوش آمدید! این یک پیام خودکار به کاربر جدید است.'
            ]);
        }
         if (isset($update['message']['text'])) {
            $message = $update['message']['text'];


            if (isset($update['message']['text'])) {
                TextTelegram::create([
                    "update_id" => data_get($update, 'update_id'),
                    "message_id" => data_get($update, 'message.message_id'),
                    "user_telegram_id" => $user_telegram->id,
                    "text" => data_get($update, 'message.text'),
                    "data" => json_encode($update)
                ]);
                logger($message);
                switch ($message) {
                    case '/start':
                        $telegram->sendMessage(['chat_id' => $update['message']['chat']['id'], 'text' => data_get($bot, 'description', 'بات شروع شد.')]);
                        break;
                    case '/help':
                        $telegram->sendMessage(['chat_id' => $update['message']['chat']['id'], 'text' => 'سلام! چطور می‌توانم به شما کمک کنم؟']);
                        break;
                    default:
                        $telegram->sendMessage(['chat_id' => $update['message']['chat']['id'], 'text' => 'متن نا معتبر می باشد']);
                        break;
                }
            }
        }
        logger('bot', [$bot]);
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

}
