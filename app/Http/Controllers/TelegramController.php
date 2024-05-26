<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\UserTradeAccess;
use App\Services\TelegramServices;
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
        $bot = Bot::where('token', $token)
            ->first();


        if (!$bot) return false;


        $telegram = new Api($token);
        $telegram_services = new TelegramServices();
        $telegram_services->access_token= $token;


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

        $user_telegram = UserTelegram::where("id",$user_id)->first();
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
        if (isset($update["my_chat_member"])) {
            $chatId = $update['my_chat_member']['from']['id']; // چت‌آیدی کاربر

            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'خوش آمدید! این یک پیام خودکار به کاربر جدید است.'
            ]);
        }
        logger("bot user",[$update]);
        $data = data_get($update, $type . ".data");
        logger('data',[$data]);
        if($data){
            if (str_contains($data, "trade_limit_")) {
                $worker_id = (int)str_replace('trade_limit_', '', $data);

                $worker = UserTelegram::where("id",$worker_id)->first();
                logger("worker",[$worker]);
                if($worker){
                    $name_worker = $worker->fullName ?: $worker->first_name . " " . $worker->last_name;

                    $telegram->sendMessage([
                        'chat_id' => $user_id,
                        'text' => "حد مجازی که می خواهید با $name_worker داشته باشید را وارد کنید "
                    ]);
                    cache()->set("text_cache_".$user_id,["title"=>"trade_number_limit","value"=>$worker->id]);
                }

            }
        }
        $message = isset($update['message']['text'])?$update['message']['text']:null;
        $cache_data = cache()->get("text_cache_".$user_id);
        if ($cache_data)
        {
            $message = data_get($cache_data,"title");
            $input_data = $message;
        }
        if ($message) {


            if (isset($update['message']['text'])) {
                TextTelegram::create([
                    "update_id" => data_get($update, 'update_id'),
                    "message_id" => data_get($update, 'message.message_id'),
                    "user_telegram_id" => $user_telegram->id,
                    "text" => data_get($update, 'message.text'),
                    "data" => json_encode($update)
                ]);
                $chatId = $update['message']['chat']['id'];
                if(!cache()->get("keyword_menu".$user_id))
                $this->menu($telegram,$user_telegram,"خوش آمدید");
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
                    case "trade_number_limit":
                        $worker_id =data_get($cache_data,"value");
                        $limit_assess =$input_data;
                        UserTradeAccess::updateOrCreate([
                            "user_id"=>$user_id,
                            "user_trade_id"=>$worker_id,
                            "limit_access"=>$limit_assess
                        ]);
                        $telegram->sendMessage(['chat_id' => $chatId, 'text' => 'حد ثابت شد']);

                        break;
                    case "\xE2\x98\x8E	دفترچه تلفن":
                    case "📈 معاملات باز":
                    case "📋 لیست همکاران":
                    case "📚   قوانین":
                    case "\xE2\x81\x89  راهنما":
                    case "\xE2\x9A\xA0	\xE2\x9D\x8C	غیرفعال سازی تایید دو مرحله ای ":
                    case "\xE2\x9C\x8C	فعال سازی دو مرحله ای":
                    case  "\xE2\x9D\x8C	غیر فعال فوری":
                        $this->getAction($message,$user_telegram,$telegram_services,$telegram);
                        break;
                    default:
                        $text_send = cache()->get("text_" . $chatId);
                        if ($text_send == "شماره موبایل خود را وارد کنید") {
                            $mobile = $this->convertNumber($message);
                            if ($this->iranMobile($mobile)) {
                                $user_telegram->mobile = $mobile;
                                $user_telegram->update();
                                if (!$user_telegram->fullName) {
                                    $text = "نام و نام خانوادگی خود را وارد کنید";
                                    cache()->set("text_" . $chatId, $text);
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

    public function getAction($data_text,$user,$telegram_services,$telegram)
    {

        if ($data_text){
            switch ($data_text) {
                case "\xE2\x98\x8E	دفترچه تلفن":
                    $keyboard = [
                        [
                            [
                                'text' => "ارسال مخاطب",
                                'request_contact' => true
                            ]
                        ]
                    ];
                    $telegram_services->setKeyword($user->id,$keyboard);
                    break;
                case "📈 معاملات باز":
                    $worker = UserTelegram::where("user_id",$user->id)->get();
                    $keyboard = [];
                    $i =0;
                    $worker->each(function ($row) use (&$i,&$keyboard){
                        $text = $row->fullName ?: $row->first_name . " " . $row->last_name;

                        $keyboard[$i++] = [
                            ['text' =>  $text, 'callback_data' => "trade_open_".$row->id],
                        ];
                    });
                    $telegram_services->sendMessage($user->id, "شخص مورد نظر را انتخاب کنید",$keyboard);
                    break;

                case "📋 لیست همکاران":
                    $text = "لیست  همکاران";
                    $users = UserTelegram::where("id","!=",$user->id)
                        ->with("userTradeAccess")->simplePaginate(5);
                    logger("users",[$users]);
                    $page = $users->currentPage();
                    $next = $users->nextPageUrl();
                    $pre = $users->previousPageUrl();
                    logger("page", [$next, $page, $pre]);
                    $keyboard = [];
                    $i = 0;

                    $users->each(function ($user) use (&$keyboard, &$i) {
                        $text = $user->fullName ?: $user->first_name . " " . $user->last_name;
                        $limit_trade =  $user->userTradeAccess->where("user_trade_id",$user->id);
                        logger('limit_trade',[$limit_trade]);

                        $keyboard[$i][0] = [
                            'text' => "  $text ".($limit_trade->count()?"\xE2\x9D\x8C":"\xE2\x9C\x85"),
                            'callback_data' => "trade_limit_".$user->id
                        ];
                        if($limit_trade->count())
                            $keyboard[$i][1] = [
                                'text' => "مجاز تا".$limit_trade->limit_access." تا",
                                'callback_data' => "trade_limit_".$user->id];


                        $i++;
                    });
                    logger("keyboard", [$keyboard]);
                    if ($pre)
                        $keyboard[$i][] = ['text' => "قبلی", "callback_data" => "pre"];
                    if ($pre)
                        $keyboard[$i][] = ['text' => "بعدی", "callback_data" => "next"];

                    $telegram_services->MessageReplyMarkup($telegram, $user->id, $text, $keyboard);
                    break;

                case "📚   قوانین":
                    $help = Setting::where("key", "rule")->first();
                    $telegram_services->sendMessage($user->id, $help->value);
                    break;

                case "\xE2\x81\x89  راهنما":
                    $help = Setting::where("key", "help")->first();
                    $telegram_services->sendMessage($user->id, $help->value);

                    break;

                case "\xE2\x9A\xA0	\xE2\x9D\x8C	غیرفعال سازی تایید دو مرحله ای ":
                    $user->verify_two = false;
                    $user->update();
                    $telegram_services->sendMessage($user->id,"تایید دو مرحله ای غیر فعال شد");
                    break;

                case "\xE2\x9C\x8C	فعال سازی دو مرحله ای":
                    $user->verify_two = true;
                    $user->update();
                    $telegram_services->sendMessage($user->id,"تایید دو مرحله ای  فعال شد");

                    break;

                case  "\xE2\x9D\x8C	غیر فعال فوری":
                    $user->delete();
                    $this->menu();
                    break;
                default:
                    return false;
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

    public function menu($telegram, $user, $text)
    {
        if($user->status) {
            $keyboard = [
                [
                    ['text' => "\xE2\x98\x8E	 دفترچه تلفن"],
                    ['text' => "📈 معاملات باز"]
                ],
                [
                    ['text' => "📋 لیست همکاران"]
                ],
                [
                    ['text' => "📚   قوانین"],
                    ['text' => "\xE2\x81\x89  راهنما"]
                ],
                [
                    ['text' => $user->verify_two ? "\xE2\x9A\xA0	\xE2\x9D\x8C	غیرفعال سازی تایید دو مرحله ای " : "\xE2\x9C\x8C	 فعال سازی دو مرحله ای"],
                    ['text' => "\xE2\x9D\x8C	   غیر فعال فوری"],

                ],
            ];
            $reply_markup = Keyboard::make([
                'keyboard' => $keyboard,
                'resize_keyboard' => true,
                'one_time_keyboard' => false
            ]);

            $response = $telegram->sendMessage([
                'chat_id' => $user->id,
                'text' => $text,
                'reply_markup' => $reply_markup
            ]);
            cache()->set("keyword_menu".$user->id,true);
        }else{
            cache()->forget("keyword_menu".$user->id);
        }

    }

}
