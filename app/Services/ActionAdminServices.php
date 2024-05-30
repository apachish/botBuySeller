<?php

namespace App\Services;


use App\Models\Bot;
use App\Models\Setting;
use App\Models\Transfer;
use App\Models\UserTelegram;
use Carbon\Carbon;

class ActionAdminServices extends TextServices
{
    public $service_telgram_user;

    public function __construct($token)
    {
        parent::__construct($token);

        $bot = cache()->remember("telegram_user", now()->addDay(), function () {
            return Bot::where('title', "botUser")
                ->first();
        });
        if($bot)
            $this->service_telgram_user = new TelegramServices($bot->token);
    }
    public function actionData(){
        logger("actionText",[$this->getData()]);

        if (str_contains($this->getData(), "tel:")) {
            $tel = str_replace('tel:', '', $this->getData());
            $tel = "[$tel]";//(tel:$tel)
            $response_text = "برای تماس با شماره زیر کلیک کنید:\n\n$tel";
            $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
        } elseif (str_contains($this->getData(), "confirm_")) {
            $id = (int)str_replace('confirm_', '', $this->getData());
            $user_con = UserTelegram::where("id", $id)->first();
            logger("con", [$user_con, $id]);
            if ($user_con) {
                $fullName = $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
                $user_con->status = true;
                $user_con->update();
                $response_text = "$fullName اکانت کاربریش فعال شد\n\n ";
                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
                $response_text = "$fullName اکانت کاربریتان فعال شد\n\n ";
                $this->service_telgram_user->sendMessage($user_con->id, $response_text);
            }
        } elseif (str_contains($this->getData(), "reject_")) {
            $id = (int)str_replace('reject_', '', $this->getData());
            $user_con = UserTelegram::where("id", $id)->first();
            logger("rej", [$user_con, $id]);

            if ($user_con) {
                $fullName = $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
                $user_con->status = false;
                $user_con->update();
                $response_text = "$fullName\n\n اکانت کاربریش غیر فعال شد ";
                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
                $response_text = "$fullName اکانت کاربریتان غیر فعال شد \n\n ";
                $this->service_telgram_user->sendMessage($user_con->id, $response_text);
            }
        }
    }
    public function actionText()
    {
        logger("actionText",[$this->getMessage()]);
        switch ($this->getMessage()) {
            case "📞 دفترچه تلفن":
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

                $this->getTelegramServices()->MessageReplyMarkup($this->getTelegram(), $this->getUserId(), $text, $keyboard);
                break;
            case "📋 لیست کاربران":
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

                $this->getTelegramServices()->MessageReplyMarkup($this->getTelegram(), $this->getUserId(), $text, $keyboard);
                break;
            case "تعداد کاربران":
                $response_text = UserTelegram::count();
                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
                break;

            case "📚  ویرایش قوانین":
                $rule = Setting::where("key", "rule")->first();

                if ($rule) {
                    $response_text = $rule->value;
                    $response_text .= "\n\n";
                    $response_text .= "متن بالا متنن قبلی می  باشد ویرایش کنید";
                } else
                    $response_text = "متن قواتین وارد کنید";

                cache()->set("text_admin_" . $this->getUserId(), "rule");

                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);

                break;

            case  "\xE2\x81\x89 ویرایش راهنما":
                $rule = Setting::where("key", "help")->first();

                if ($rule) {
                    $response_text = $rule->value;

                    $response_text .= "\n\n";
                    $response_text .= "متن بالا متنن قبلی می  باشد ویرایش کنید";
                } else
                    $response_text = "متن راهنما وارد کنید";

                cache()->set("text_admin_" . $this->getUserId(), "help");

                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);


            case "📈محدود شروع مبلغ معاملات":
                $s_price_trade = Setting::where("key", "s_price_trade")->first();

                if ($s_price_trade) {
                    $response_text = "محدود معامله   قبلا شد:";
                    $response_text .= "\n\n";
                    $response_text .= "از مبلغ";
                    $response_text .= number_format(data_get($s_price_trade,"value.start"), 0);
                    $response_text .= "\n\n";

                    $response_text .= "تا مبلغ";
                    $response_text .= "\n\n";

                    $response_text .= number_format(data_get($s_price_trade,"value.end"),0);
                    $response_text .= "\n\n";
                }else {
                    $response_text = "محدود شروع مبلغ وارد شده باید به صورت \n\n";
                    $response_text .= "14000000:15000000 \n\n";
                }
                cache()->set("text_admin_" . $this->getUserId(), "s_price_trade");

                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);

                break;

        }
    }
    public function actionTextCache()
    {
        logger("cache",[$this->getMessageCache()]);
        switch ($this->getMessageCache()) {
            case "rule":
                $rule = Setting::updateOrCreate(
                    ["key" => "rule"],
                    ["value" => $this->getMessage()]
                );


                $response_text = "متن قواتین  بروزرسانی شد:";
                $response_text .= "\n\n";
                $response_text .= $rule->value;
                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
                cache()->forget("text_admin_" . $this->getUserId());
                break;
            case "help":
                $rule = Setting::updateOrCreate(
                    ["key" => "help"],
                    ["value" => $this->getMessage()]
                );


                $response_text = "متن راهنما  بروزرسانی شد:";
                $response_text .= "\n\n";
                $response_text .= $rule->value;
                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
                cache()->forget("text_admin_" . $this->getUserId());
                break;
            case "s_price_trade":
                $s_price_trade = $this->getMessage();
                if ($s_price_trade) {
                    $limit_price = explode(":",$this->convertNumber($s_price_trade));
                    if(data_get($limit_price,0) & data_get($limit_price,1)) {
                        $rule = Setting::updateOrCreate(
                            ["key" => "s_price_trade"],
                            ["value" =>
                                ["start"=>data_get($limit_price,0),
                                "end"=>data_get($limit_price,1)]
                            ]
                        );

                        $response_text = "محدوده معامله   بروزرسانی شد:";
                        $response_text .= "\n\n";
                        $response_text .= "از مبلغ";
                        $response_text .= number_format(data_get($limit_price,0), 0);
                        $response_text .= "\n\n";

                        $response_text .= "تا مبلغ";
                        $response_text .= "\n\n";

                        $response_text .= number_format(data_get($limit_price,1), 0);
                        $response_text .= "\n\n";
                        $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
                        cache()->forget("text_admin_" . $this->getUserId());
                    }else{
                        $this->getTelegramServices()->sendMessage($this->getUserId(), "محدوده وارد شده معامله  صحیخ نمی باشد");

                    }
                } else {
                    $this->getTelegramServices()->sendMessage($this->getUserId(), "محدوده وارد شده معامله  صحیخ نمی باشد");

                }
                break;
        }
    }
}
