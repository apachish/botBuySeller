<?php

namespace App\Services;


use App\Models\Bot;
use App\Models\BotMenuUser;
use App\Models\Setting;
use App\Models\Transfer;
use App\Models\UserTelegram;
use Carbon\Carbon;

class ActionAdminServices extends TextServices
{
    public $service_telgram_user;
    public $bot_title;
    public $bot_user;
    public $key_cache_user = "text_user_";

    protected $keyword_colleague = [
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
    protected $keyword_customer = [
        [
            ['text' => "\xF0\x9F\x93\x9A	قوانین"],
            ['text' => "راهنما \xE2\x81\x89"]
        ], [
            ['text' => "\xE2\x9C\x8C	فعال سازی دو مرحله ای"],
            ['text' => "\xE2\x9D\x8C	غیر فعال فوری"],

        ]];

    public function __construct($token)
    {
        parent::__construct($token);

        $this->bot_user = cache()->remember("telegram_user", now()->addDay(), function () {
            return Bot::where('title', "botUser")
                ->first();
        });
        if ($this->bot_user) {
            $this->service_telgram_user = new TelegramServices($this->bot_user->token);
            $this->bot_title = $this->bot_user->title;
        }
    }

    public function actionData()
    {
        logger("actionText", [$this->getData()]);

        if (str_contains($this->getData(), "tel:")) {
            $tel = str_replace('tel:', '', $this->getData());
            $tel = "[$tel]";//(tel:$tel)
            $response_text = "برای تماس با شماره زیر کلیک کنید:\n\n$tel";
            $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
        } elseif (str_contains($this->getData(), "customer_")) {
            $id = (int)str_replace('customer_', '', $this->getData());
            $user_con = UserTelegram::where("id", $id)->first();
            logger("con", [$user_con, $id]);
            if ($user_con) {
                $fullName = $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
                $user_con->role = "colleague";
                $user_con->update();
                $response_text = "$fullName نقش همکار فعال شد \n\n ";
                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
                $response_text = "$fullName همکار گرامی به سیستم ما خوش آمدید\n\n ";
                $this->service_telgram_user->sendMessage($user_con->id, $response_text);

                cache()->forget("keyword_menu".$this->key_cache_user.$user_con->id);

            }
        } elseif (str_contains($this->getData(), "colleague_")) {
            $id = (int)str_replace('colleague_', '', $this->getData());
            $user_con = UserTelegram::where("id", $id)->first();
            logger("con", [$user_con, $id]);
            if ($user_con) {
                $fullName = $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
                $user_con->role = "customer";
                $user_con->update();
                $response_text = "$fullName نقش همکاری این شخص به مشتری تغییر یافت \n\n ";
                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
                $response_text = "$fullName همکاری شما در سیستم به سطح مشتری انتقال یافت\n\n ";
                $this->service_telgram_user->sendMessage($user_con->id, $response_text);
                cache()->forget("keyword_menu".$this->key_cache_user.$user_con->id);

            }
        } elseif (str_contains($this->getData(), "confirm_")) {
            $id = (int)str_replace('confirm_', '', $this->getData());
            $user_con = UserTelegram::where("id", $id)->first();
            logger("con", [$user_con, $id]);
            if ($user_con) {
                $fullName = $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
                $user_con->status = true;
                $user_con->update();
                cache()->forget("keyword_menu" . $this->key_cache_user . $user_con->id);
                $response_text = "$fullName اکانت کاربریش فعال شد\n\n ";
                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
                $response_text = "$fullName اکانت کاربریتان فعال شد\n\n ";
                $this->service_telgram_user->sendMessage($user_con->id, $response_text);
                cache()->forget("keyword_menu".$this->key_cache_user.$user_con->id);
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
                cache()->forget("keyword_menu".$this->getKeyCache().$user_con->id);

            }
        }
    }

    public function actionText()
    {
        logger("actionText", [$this->getMessage()]);
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
            case "📋 لیست همکاران":
                $text = "لیست  همکاران";
                $users = UserTelegram::where("role", "colleague")->simplePaginate(5);
                $page = $users->currentPage();
                $next = $users->nextPageUrl();
                $pre = $users->previousPageUrl();
                logger("page", [$users,$next, $page, $pre]);
                $keyboard = [];
                $i = 0;

                $users->each(function ($user) use (&$keyboard, &$i) {
                    $text = $user->fullName ?: $user->first_name . " " . $user->last_name;
                    $keyboard[$i++] = [
                        ['text' => "  $text ", 'callback_data' => $user->id],
                    ];
//                    $keyboard[$i++] = [
//                        ['text' => "\xE2\x9C\x85 ", 'callback_data' => 'confirm_' . $user->id],
//                        ['text' => "\xE2\x9D\x8C", 'callback_data' => 'reject_' . $user->id],
//                    ];
                });
                logger("keyboard", [$keyboard]);
                if ($pre)
                    $keyboard[$i][] = ['text' => "قبلی", "callback_data" => "pre"];
                if ($pre)
                    $keyboard[$i][] = ['text' => "بعدی", "callback_data" => "next"];

                $this->getTelegramServices()->MessageReplyMarkup($this->getTelegram(), $this->getUserId(), $text, $keyboard);
                break;
            case "📋 لیست کاربران":
                $text = "\n\nلیست  کاربران";
                $text .= "\n\n";
                $text .= "با کلیک بر\xE2\x9D\x8C کاربر غیر فعال شده و با کلیک بر \xE2\x9C\x85 کاربرفعال گردید در صورت کلیک بر روی اسم شخص نوع کاربر از مشتری به همکار و همکار به مشتری تغییر می کنند ";
                $users = UserTelegram::simplePaginate(5);
                $page = $users->currentPage();
                $next = $users->nextPageUrl();
                $pre = $users->previousPageUrl();
                logger("page", [$next, $page, $pre,$users]);
                $keyboard = [];
                $i = 0;

                $users->each(function ($user) use (&$keyboard, &$i) {
                    $text = $user->fullName ?: $user->first_name . " " . $user->last_name;
                    $text .= $user->role == "colleague" ? "(همکار)" : "(مشتری)";
                    $keyboard[$i++] = [
                        ['text' => "  $text  ", 'callback_data' => ($user->role == "colleague" ? "colleague_" : "customer_") . $user->id],
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
                    $response_text .= "\n\n";

                    $response_text .= number_format(data_get($s_price_trade, "value.start"), 0);
                    $response_text .= "\n\n";

                    $response_text .= "تا مبلغ";
                    $response_text .= "\n\n";

                    $response_text .= number_format(data_get($s_price_trade, "value.end"), 0);
                    $response_text .= "\n\n";
                } else {
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
        logger("cache", [$this->getMessageCache()]);
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
                    $limit_price = explode(":", $this->convertNumber($s_price_trade));
                    if (data_get($limit_price, 0) && data_get($limit_price, 1)) {
                        $rule = Setting::updateOrCreate(
                            ["key" => "s_price_trade"],
                            ["value" =>
                                ["start" => data_get($limit_price, 0),
                                    "end" => data_get($limit_price, 1)]
                            ]
                        );

                        $response_text = "محدوده معامله   بروزرسانی شد:";
                        $response_text .= "\n\n";
                        $response_text .= "از مبلغ";
                        $response_text .= "\n\n";
                        $response_text .= number_format(data_get($limit_price, 0), 0);
                        $response_text .= "\n\n";
                        $response_text .= "تا مبلغ";
                        $response_text .= "\n\n";

                        $response_text .= number_format(data_get($limit_price, 1), 0);
                        $response_text .= "\n\n";
                        $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
                        cache()->forget("text_admin_" . $this->getUserId());
                    } else {
                        $this->getTelegramServices()->sendMessage($this->getUserId(), "محدوده وارد شده معامله  صحیخ نمی باشد");

                    }
                } else {
                    $this->getTelegramServices()->sendMessage($this->getUserId(), "محدوده وارد شده معامله  صحیخ نمی باشد");

                }
                break;
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|object|UserTelegram $user_con
     * @return void
     */
    public function changeMenu($user_con): void
    {
        $menu_bot = BotMenuUser::where("user_id", $user_con->id)->where("bot_id", $this->bot_user->id)->first();
        logger("menu bot",[$menu_bot]);
        if ($menu_bot) {
            $key = $user_con->role == "colleague" ? $this->keyword_colleague : $this->keyword_customer;
            if(!$user_con->status)
                $key = new \stdClass();
            $this->service_telgram_user->editCustomKeyboard($user_con->id, $menu_bot->menu_id, "تغییر منو", $key);
            cache()->forget("keyword_menu".$this->getKeyCache().$user_con->id);
        }
    }
}
