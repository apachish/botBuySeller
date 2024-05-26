<?php

namespace App\Http\Controllers;

use App\Http\Resources\ContactResourceCollection;
use App\Models\Setting;
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

        $service_telgram_user = new TelegramServices();
        $bot_user = Bot::where("title", "botUser")->first();
        if ($bot_user)
            $service_telgram_user->access_token = $bot_user->token;


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
        $user_telegram = UserTelegram::where("id", $user_id)->first();
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
        $data_text = data_get($update, $type . ".text");
        logger("data_text", [$data_text]);

        $data = data_get($update, $type . ".data");
        logger("data_text", [$data_text]);
        if (in_array($data_text, [
            "📞 دفترچه تلفن",
            "📋 لیست کاربران",
            "تعداد کاربران",
            "📚  ویرایش قوانین",
            "\xE2\x81\x89 ویرایش راهنما",
            "📈شروع مبلغ معاملات",
        ]))
            cache()->forget("text_admin_" . $chatId);

        $cache_data = cache()->get("text_admin_" . $chatId);
        if ($cache_data)
            $data_text = $cache_data;

        if ($data_text && !str_contains($data_text, "start")) {
            if ($data_text == "📞 دفترچه تلفن") {
                $text = "لیست شماره تلفن کاربران";
                $contacts = UserTelegram::whereNotNull("mobile")->simplePaginate(5);
                $page = $contacts->currentPage();
                $next = $contacts->nextPageUrl();
                $pre = $contacts->previousPageUrl();
                logger("page", [$next, $page, $pre]);
//                    $contacts = new ContactResourceCollection($contacts);
                $keyboard = [];
                $i = 0;

                $contacts->each(function ($contact) use (&$keyboard, &$i) {
                    $keyboard[$i++][] = [
                        "text" => $contact->fullName ?: $contact->first_name . " " . $contact->last_name,
                        'callback_data' => "tel:" . $contact->mobile,

                    ];
                });
                logger("keyboard", [$keyboard]);
                if ($pre)
                    $keyboard[$i][] = ['text' => "قبلی", "callback_data" => "pre"];
                if ($pre)
                    $keyboard[$i][] = ['text' => "بعدی", "callback_data" => "next"];

                $service_telgram->MessageReplyMarkup($telegram, $chatId, $text, $keyboard);
            } elseif ($data_text == "📋 لیست کاربران") {
                $text = "لیست  کاربران";
                $users = UserTelegram::simplePaginate(5);
                $page = $users->currentPage();
                $next = $users->nextPageUrl();
                $pre = $users->previousPageUrl();
                logger("page", [$next, $page, $pre]);
                $keyboard = [];
                $i = 0;

                $users->each(function ($user) use (&$keyboard, &$i) {
                    $text = $user->fullName ?: $user->first_name . " " . $user->last_name;
                    $keyboard[$i++] = [
                        ['text' => "  $text ", 'callback_data' => $user->id],
                    ];
                    $keyboard[$i++] = [
                        ['text' => "\xE2\x9C\x85 ", 'callback_data' => 'confirm_' . $user->id],
                        ['text' => "\xE2\x9D\x8C", 'callback_data' => 'reject_' . $user->id],
                    ];
                });
                logger("keyboard", [$keyboard]);
                if ($pre)
                    $keyboard[$i][] = ['text' => "قبلی", "callback_data" => "pre"];
                if ($pre)
                    $keyboard[$i][] = ['text' => "بعدی", "callback_data" => "next"];

                $service_telgram->MessageReplyMarkup($telegram, $chatId, $text, $keyboard);
            } elseif ($data_text == "تعداد کاربران") {
                $response_text = UserTelegram::count();
                $service_telgram->sendMessage($chatId, $response_text);

            } elseif ($data_text == "📚  ویرایش قوانین") {
                $rule = Setting::where("key", "rule")->first();

                if ($rule) {
                    $response_text = $rule->value;
                    $response_text .= "\n\n";
                    $response_text .= "متن بالا متنن قبلی می  باشد ویرایش کنید";
                }else
                    $response_text = "متن قواتین وارد کنید";

                cache()->set("text_admin_" . $chatId, "rule");

                $service_telgram->sendMessage($chatId, $response_text);

            } elseif ($data_text == "rule") {
                $rule = Setting::updateOrCreate(
                    ["key" => "rule"],
                    ["value" => data_get($update, $type . ".text")]
                );


                $response_text = "متن قواتین  بروزرسانی شد:";
                $response_text .= "\n\n";
                $response_text .= $rule->value;
                $service_telgram->sendMessage($chatId, $response_text);
                cache()->forget("text_admin_" . $chatId);

            } elseif ($data_text ==  "\xE2\x81\x89 ویرایش راهنما") {
                $rule = Setting::where("key", "help")->first();

                if ($rule) {
                    $response_text = $rule->value;

                    $response_text .= "\n\n";
                    $response_text .= "متن بالا متنن قبلی می  باشد ویرایش کنید";
                }
                else
                    $response_text = "متن راهنما وارد کنید";

                cache()->set("text_admin_" . $chatId, "help");

                $service_telgram->sendMessage($chatId, $response_text);

            } elseif ($data_text == "help") {
                $rule = Setting::updateOrCreate(
                    ["key" => "help"],
                    ["value" => data_get($update, $type . ".text")]
                );


                $response_text = "متن راهنما  بروزرسانی شد:";
                $response_text .= "\n\n";
                $response_text .= $rule->value;
                $service_telgram->sendMessage($chatId, $response_text);
                cache()->forget("text_admin_" . $chatId);

            } elseif ($data_text == "📈شروع مبلغ معاملات") {
                $start_trade = Setting::where("key", "start_trade")->first();

                if ($start_trade)
                    $response_text = $start_trade->value;
                else
                    $response_text = " شروع مبلغ معاملات را وارد کنید \n\n مثل 14000000";

                cache()->set("text_admin_" . $chatId, "start_trade");

                $service_telgram->sendMessage($chatId, $response_text);

            } elseif ($data_text == "start_trade") {
                $start_trade = data_get($update, $type . ".text");
                if ($start_trade ) {
                    $rule = Setting::updateOrCreate(
                        ["key" => "start_trade"],
                        ["value" => $start_trade]
                    );
                    $response_text = "شروع معامله   بروزرسانی شد:";
                    $response_text .= "\n\n";
                    $response_text .= "از مبلغ";
                    $response_text .= number_format($start_trade,0);
                    $response_text .= "\n\n";
                    $service_telgram->sendMessage($chatId, $response_text);
                    cache()->forget("text_admin_" . $chatId);
                } else {
                    $service_telgram->sendMessage($chatId, "شروع معامله وارد شده صحیخ نمی باشد");

                }

            }
            return true;
        }
        logger("data", [$data]);
        if ($data) {
            if (str_contains($data, "tel:")) {
                $tel = str_replace('tel:', '', $data);
                $tel = "[$tel]";//(tel:$tel)
                $response_text = "برای تماس با شماره زیر کلیک کنید:\n\n$tel";
                $service_telgram->sendMessage($chatId, $response_text);
            } elseif (str_contains($data, "confirm_")) {
                $id = (int)str_replace('confirm_', '', $data);
                $user_con = UserTelegram::where("id", $id)->first();
                logger("con", [$user_con, $id]);
                if ($user_con) {
                    $fullName = $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
                    $user_con->status = true;
                    $user_con->update();
                    $response_text = "$fullName اکانت کاربریش فعال شد\n\n ";
                    $service_telgram->sendMessage($chatId, $response_text);
                    $response_text = "$fullName اکانت کاربریتان فعال شد\n\n ";
                    $service_telgram_user->sendMessage($user_con->id, $response_text);
                }
            } elseif (str_contains($data, "reject_")) {
                $id = (int)str_replace('reject_', '', $data);
                $user_con = UserTelegram::where("id", $id)->first();
                logger("rej", [$user_con, $id]);

                if ($user_con) {
                    $fullName = $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
                    $user_con->status = false;
                    $user_con->update();
                    $response_text = "$fullName\n\n اکانت کاربریش غیر فعال شد ";
                    $service_telgram->sendMessage($chatId, $response_text);
                    $response_text = "$fullName اکانت کاربریتان غیر فعال شد \n\n ";
                    $service_telgram_user->sendMessage($user_con->id, $response_text);
                }
            }
            return true;
        }

        $this->menu($telegram, $chatId, $text);

    }


    public function menu($telegram, $chatId, $text)
    {
        $keyboard = [
            [
                ['text' => "📞 دفترچه تلفن"],
                ['text' => "📈شروع مبلغ معاملات"]
            ],
            [
                ['text' => "تعداد کاربران"],
                ['text' => "📋 لیست کاربران"]
            ],
//            [
//                ['text' => "🔍 جستجو کاربر"]
//            ],
            [
                ['text' => "📚  ویرایش قوانین"]
            ],
            [
                ['text' => "\xE2\x81\x89 ویرایش راهنما"]
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
