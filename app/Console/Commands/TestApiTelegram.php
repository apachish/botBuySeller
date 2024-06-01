<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\BotMenuUser;
use App\Models\UserTelegram;
use App\Services\TelegramServices;
use Illuminate\Console\Command;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;

class TestApiTelegram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-api-telegram';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = UserTelegram::where("id",70115829)->with("userTradeAccess")->first();
            logger("user",[$user]);exit;
        $token = $this->ask('What is  token bot?');
        $user_id = (int)$this->ask('What is  user_id?');
        $menu_id = (int)$this->ask('What is  menu_id?');
        $telegram = new Api($token);
        $user = UserTelegram::where("id", $user_id)->first();
        $bot = Bot::where("token", $token)->first();
        $this->info("user".$user->fullName);
        if(!$menu_id) {
            $keyword_customer = [
                [
                    ['text' => "\xF0\x9F\x93\x9A	قوانین"],
                    ['text' => "راهنما \xE2\x81\x89"]
                ], [
                    ['text' => "\xE2\x9C\x8C	فعال سازی دو مرحله ای"],
                    ['text' => "\xE2\x9D\x8C	غیر فعال فوری"],

                ]];

            $response = TelegramServices::menu($telegram, $keyword_customer, $user, "تست");
            logger("response", [$response]);
            if (isset($response['message_id'])) {
                $this->info($response['message_id']);
                BotMenuUser::updateOrCreate(["user_id" => $user->id, "bot_id" => $bot->id], ["menu_id" => $response['message_id']]);
                $user->update();
            }
        }else {
            $keyword_colleague = [
                [
                    ['text' => "\xF0\x9F\x91\xA5	معرفی مشتری"],
                    ['text' => "\xF0\x9F\x93\x8B	لیست همکاران"],
                ],
                [
                    ['text' => "\xF0\x9F\x93\x88	معاملات باز"]
                ],
                [
                    ['text' => "\xF0\x9F\x93\x9A	قوانین"],
                    ['text' => "راهنما \xE2\x81\x89"]
                ], [
                    ['text' => "\xE2\x9C\x8C	فعال سازی دو مرحله ای"],
                    ['text' => "\xE2\x9D\x8C	غیر فعال فوری"],

                ]];
//            $reply_markup = Keyboard::make([
//                'keyboard' => $keyword_colleague,
//                'resize_keyboard' => true,
//                'one_time_keyboard' => false
//            ]);
//            $telegram_services = new TelegramServices($token);
//             $params = [
//            'chat_id'            => $user_id,  // int|string - (Optional). Required if inline_message_id is not specified. Unique identifier for the target chat or username of the target channel (in the format "@channelusername")
//           'message_id'         => $menu_id,  // int        - (Optional). Required if inline_message_id is not specified. Identifier of the sent message
//            'inline_message_id'  => '',  // string     - (Optional). Required if chat_id and message_id are not specified. Identifier of the inline message
//            'reply_markup'       => $reply_markup,  // string     - (Optional). A JSON-serialized object for an inline keyboard.
//      ];
//            $d = $telegram->editMessageReplyMarkup($params);
////            $d = $telegram_services->editCustomKeyboard($user_id, $menu_id, "تغییر منو", $keyword_colleague);
//            dd($d);
//            $response = TelegramServices::menu($telegram, $keyword_colleague, $user, "تست");
//            logger("response", [$response]);
//            if (isset($response['message_id'])) {
//                $this->info($response['message_id']);
//                BotMenuUser::updateOrCreate(["user_id" => $user->id, "bot_id" => $bot->id], ["menu_id" => $response['message_id']]);
//                $user->update();
//            }

            $apiToken = $token;
            $chatId = $user_id;


            $keyboard = [
                'remove_keyboard' => true
            ];

            $data = [
                'chat_id' => $chatId,
                'text' => 'کیبورد حذف شد',
                'reply_markup' => json_encode($keyboard)
            ];

            $url = "https://api.telegram.org/bot$apiToken/sendMessage";

            $options = [
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type:application/x-www-form-urlencoded\r\n",
                    'content' => http_build_query($data),
                ],
            ];

            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            logger("re",[$result]);
            $keyboard = [
                'keyboard' => [
                    [['text' => 'دکمه 1'], ['text' => 'دکمه 2']],
                    [['text' => 'دکمه 3'], ['text' => 'دکمه 4']]
                ],
                'resize_keyboard' => true,
                'one_time_keyboard' => true
            ];

            $data = [
                'chat_id' => $chatId,
                'text' => 'لطفاً یک گزینه را انتخاب کنید:',
                'reply_markup' => json_encode($keyboard)
            ];

            $url = "https://api.telegram.org/bot$apiToken/sendMessage";

            $options = [
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type:application/x-www-form-urlencoded\r\n",
                    'content' => http_build_query($data),
                ],
            ];

            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            logger("re",[$result]);

        }
    }
}
