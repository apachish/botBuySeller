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

        if (!$bot)
            $bot = Bot::create(["token" => $token, "title" => "bot" . time()]);

        if (!$bot) return false;


        $telegram = new Api($token);


        // دریافت پیام ارسال شده به بات
        $input = file_get_contents("php://input");
        $update = json_decode($input, true);
        $update = $telegram->getWebhookUpdate();


        // دریافت دستور ارسال شده توسط کار
        if (isset($update["my_chat_member"]))
            $type = "my_chat_member";
        elseif (isset($update['callback_query']))
            $type = "callback_query";
        else
            $type = "message";

        $user_id = data_get($update, $type . '.from.id');

        $user_telegram = UserTelegram::find($user_id);
        if (!$user_telegram) {
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
        if (isset($update["my_chat_member"])) {
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
                $chatId = $update['message']['chat']['id'];
                switch ($message) {
                    case '/start':
                        $text = "منتظر تایید مدیر سیستم باشید تا دسترسی به شما ارائه گردد";
                        if (!$user_telegram->mobile)
                            $text = "شماره موبایل خود را وارد کنید";
                        elseif (!$user_telegram->fullName)
                            $text = "نام و نام خانوادگی خود را وارد کنید";
                        cache()->remember("text_" . $chatId, now()->addDay(1), function () use ($text) {
                            return $text;
                        });
                        $telegram->sendMessage(['chat_id' => $chatId, 'text' => $text]);
                        break;
                    case '/help':
                        $telegram->sendMessage(['chat_id' => $chatId, 'text' => 'سلام! چطور می‌توانم به شما کمک کنم؟']);
                        break;
                    default:
                        $text_send = cache()->get("text_" . $chatId);
                        if ($text_send == "شماره موبایل خود را وارد کنید") {
                            $mobile = $this->convertNumber($message);
                            if ($this->iranMobile($message)) {
                                $user_telegram->mobile = $mobile;
                                $user_telegram->update();
                                if (!$user_telegram->fullName) {
                                    $text = cache()->remember("text_" . $chatId, now()->addDay(1), function () {
                                        return "نام و نام خانوادگی خود را وارد کنید";
                                    });
                                    $telegram->sendMessage(['chat_id' => $chatId, 'text' => $text]);
                                }
                            } else
                                $telegram->sendMessage(['chat_id' => $chatId, 'text' => "شماره همراه وارد شده نامعتبر می باشد دوباره وارد کنید"]);

                        } elseif ($text_send == "نام و نام خانوادگی خود را وارد کنید") {
                            $user_telegram->fullName = $message;
                            $user_telegram->update();
                            $text = "منتظر تایید مدیر سیستم باشید تا دسترسی به شما ارائه گردد";
                            cache()->set("text_" . $chatId, $text);
                            $telegram->sendMessage(['chat_id' => $chatId, 'text' => $text]);

                        } else
                            $telegram->sendMessage(['chat_id' => $chatId, 'text' => 'متن نا معتبر می باشد']);
                        break;
                }
            }
        }
    }

    private function iranMobile($value)
    {

        if ((bool)preg_match('/^(((98)|(\+98)|(0098)|0)(9){1}[0-9]{9})+$/', $value) || (bool)preg_match('/^(9){1}[0-9]{9}+$/', $value))
            return true;

        return false;
    }

    private function convertNumber($value)
    {
        $western = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];
        $eastern = ['۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '۰'];
        return str_replace($eastern, $western, $value);
    }


    public function checkMember($user, $bot)
    {
        //        $telegram = new Api(data_get($bot,'token')); // توکن ربات تلگرام خود را جایگزین کنید
        $token = data_get($bot, 'token'); // توکن ربات تلگرام خود را جایگزین کنید

        $chatId = '@apdadana'; // نام کاربری یا آیدی کانال مورد نظر
        $userId = data_get($user, 'id'); // آیدی کاربری مورد نظر

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
        logger("response", [$response]);
        // بررسی وضعیت عضویت کاربر در کانال
        if (data_get($result, 'ok') && in_array(data_get($result, 'result.status'), ['member', 'creator'])) {
            logger("کاربر عضو کانال است.");
            return true;
        } else {
            logger("کاربر عضو کانال نیست یا خطایی رخ داده است.");
            return false;
        }
    }
}
