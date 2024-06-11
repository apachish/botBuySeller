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
    public $service_user;
    public $bot_title;
    public $bot_user;
    public $key_cache_user = "text_user_";

    public function checkMessage()
    {

        $access_text = [
            "\xF0\x9F\x9A\xBBلیست کاربران",
            "جستجو کاربر\xF0\x9F\x94\x8D",
            "\xF0\x9F\x93\x88شروع مبلغ معامله",
            "\xE2\x8C\x9Aساعت شروع",
            "\xE2\x8F\xB0ساعت پایان",
            "\xE2\x98\x81تعطیل",
            "\xF0\x9F\x9A\xA9حذف پیام ها",
            "\xF0\x9F\x93\x9Aویرایش قوانین",
            "\xE2\x81\x89ویرایش راهنما",
            "\xF0\x9F\x92\xB3\xF0\x9F\x8C\xB3ویرایش حق اشتراک",
            "\xF0\x9F\x92\xB1کیف پول",
            "\xF0\x9F\x92\xACلیست پیام ها کاربران",
            "\xF0\x9F\x93\x81ارسال فایل"
        ];
        if (in_array($this->message, $access_text))
            return true;

        return false;
    }
    protected $keyword_colleague = [
        [
            ['text' => "\xF0\x9F\x91\xA5معرفی مشتری"],
            ['text' => "\xF0\x9F\x93\x8Bلیست همکاران"]
        ],
        [
            ['text' => "\xF0\x9F\x93\x88معاملات باز"]
        ],
        [
            ['text' => "\xF0\x9F\x93\x9Aقوانین"],
            ['text' => "راهنما\xE2\x81\x89"],
            ['text' => "\xF0\x9F\x92\xB3\xF0\x9F\x8C\xB3حق اشتراک"]

        ], [
            ['text' => "\xE2\x9C\x8Cفعال سازی دو مرحله ای"],
            ['text' => "\xE2\x9D\x8Cغیر فعال فوری"],

        ]];
    protected $keyword_customer = [
        [
            ['text' => "\xF0\x9F\x93\x9Aقوانین"],
            ['text' => "راهنما\xE2\x81\x89"],
            ['text' => "\xF0\x9F\x92\xB3\xF0\x9F\x8C\xB3حق اشتراک"]
        ], [
            ['text' => "\xE2\x9C\x8Cفعال سازی دو مرحله ای"],
            ['text' => "\xE2\x9D\x8Cغیر فعال فوری"],

        ]];

    public function __construct($token)
    {
        parent::__construct($token);

        $this->bot_user = cache()->remember("telegram_user", now()->addDay(), function () {
            return Bot::where('title', "botUser")
                ->first();
        });
        if ($this->bot_user) {
//            $this->service_telgram_user = new TelegramServices($this->bot_user->token);
            $this->service_user = new ActionServices($this->bot_user->token);
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
        } elseif (str_contains($this->getData(), "pre_")) {
            $page = str_replace('pre_', '', $this->getData());
            $data_old = cache()->get("menu_List_user_" . $this->getUserId());
            $message_id = data_get($data_old, "id", null);
            if ($message_id)
                $this->listUser($page, $message_id);
        } elseif (str_contains($this->getData(), "next_")) {
            $page = str_replace('next_', '', $this->getData());
            $data_old = cache()->get("menu_List_user_" . $this->getUserId());
            $message_id = data_get($data_old, "id", null);
            logger("aa", [$data_old, $message_id, $page]);
            if ($message_id)
                $this->listUser($page, $message_id);
        } elseif (str_contains($this->getData(), "customer_")) {
            $id = (int)str_replace('customer_', '', $this->getData());
            $user_con = UserTelegram::where("id", $id)->first();
            logger("con", [$user_con, $id]);
            if ($user_con) {
                $fullName = $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
                $user_con->role = "colleague";
                $user_con->change_menu = true;
                $user_con->update();
                $response_text = "$fullName نقش همکار فعال شد \n\n ";
                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
                $this->service_user->message_menu = "$fullName همکار گرامی به سیستم ما خوش آمدید\n\n ";
                $this->service_user->menu($this->keyword_colleague, $user_con->status, $user_con);//->sendMessage($user_con->id, $response_text);
//                $this->service_user->telegram_services->sendMessage($user_con->id, $response_text);


            }
        } elseif (str_contains($this->getData(), "colleague_")) {
            $id = (int)str_replace('colleague_', '', $this->getData());
            $user_con = UserTelegram::where("id", $id)->first();
            logger("con", [$user_con, $id]);
            if ($user_con) {
                $fullName = $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
                $user_con->role = "customer";
                $user_con->change_menu = true;
                $user_con->update();
                $response_text = "$fullName نقش همکاری این شخص به مشتری تغییر یافت \n\n ";
                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
//                $response_text = "$fullName همکاری شما در سیستم به سطح مشتری انتقال یافت\n\n ";
//                $this->service_user->telegram_services->sendMessage($user_con->id, $response_text);
                $this->service_user->message_menu = "$fullName همکاری شما در سیستم به سطح مشتری انتقال یافت\n\n ";
                $this->service_user->menu($this->keyword_customer, $user_con->status, $user_con);

            }
        } elseif (str_contains($this->getData(), "confirm_")) {
            $id = (int)str_replace('confirm_', '', $this->getData());
            $user_con = UserTelegram::where("id", $id)->first();
            logger("con", [$user_con, $id]);
            if ($user_con) {
                $fullName = $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
                $user_con->status = true;
                $user_con->change_menu = true;
                $user_con->update();
                cache()->forget("keyword_menu" . $this->key_cache_user . $user_con->id);
                $response_text = "$fullName اکانت کاربریش فعال شد\n\n ";
                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
//                $response_text = "$fullName اکانت کاربریتان فعال شد\n\n ";
//                $this->service_user->telegram_services->sendMessage($user_con->id, $response_text);
                $this->service_user->message_menu = "$fullName اکانت کاربریتان فعال شد\n\n ";
                $this->service_user->menu($this->keyword_customer, $user_con->status, $user_con);
            }
        } elseif (str_contains($this->getData(), "reject_")) {
            $id = (int)str_replace('reject_', '', $this->getData());
            $user_con = UserTelegram::where("id", $id)->first();
            logger("rej", [$user_con, $id]);

            if ($user_con) {
                $fullName = $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
                $user_con->status = false;
                $user_con->role = null;
                $user_con->change_menu = true;

                $user_con->update();
                $response_text = "$fullName\n\n اکانت کاربریش غیر فعال شد ";
                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
//                $response_text = "$fullName اکانت کاربریتان غیر فعال شد \n\n ";
//                $this->service_user->telegram_services->sendMessage($user_con->id, $response_text);
                $this->service_user->message_menu = "$fullName اکانت کاربریتان غیر فعال شد \n\n ";
                $this->service_user->menu([], $user_con->status, $user_con);
            }
        } elseif (str_contains($this->getData(), "delete_")) {
            $id = (int)str_replace('delete_', '', $this->getData());
            $user_con = UserTelegram::where("id", $id)->first();
            logger("rej", [$user_con, $id]);

            if ($user_con) {
                $fullName = $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
                $user_con->status = false;
                $user_con->role = null;
                $user_con->change_menu = true;
                $user_con->update();
                $user_con->delete();
                $response_text = "$fullName\n\n اکانت کاربریش حذف شد ";
                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
                $this->telegram_services->kickChatMember($this->bot->chanel_id, $user_con->id);

                $this->service_user->message_menu = "اکانت کاربریش حذف شد";
                $this->service_user->menu([], $user_con->status, $user_con);

            }
        }
    }

    public function actionText()
    {
        cache()->forget($this->getKeyCache() . $this->getUserId());
        logger("actionText", [$this->getMessage()]);
        switch ($this->getMessage()) {
            case "\xF0\x9F\x9A\xBBلیست کاربران":
                $this->listUser();
                break;
            case "جستجو کاربر\xF0\x9F\x94\x8D":
                cache()->set($this->getKeyCache() . $this->getUserId(), "find_user");
                $this->getTelegramServices()->sendMessage($this->getUserId(), "شماره تلفن یا نام و نام خانوادگی کاربر مورد نظر وارد کنید");

                break;
            case "\xF0\x9F\x93\x9Aویرایش قوانین":
                $rule = Setting::where("key", "rule")->first();

                if ($rule) {
                    $response_text = $rule->value;
                    $response_text .= "\n\n";
                    $response_text .= "متن بالا متنن قبلی می  باشد ویرایش کنید";
                } else
                    $response_text = "متن قواتین وارد کنید";

                cache()->set($this->getKeyCache() . $this->getUserId(), "rule");

                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);

                break;
            case  "\xE2\x81\x89ویرایش راهنما":
                $rule = Setting::where("key", "help")->first();

                if ($rule) {
                    $response_text = $rule->value;

                    $response_text .= "\n\n";
                    $response_text .= "متن بالا متنن قبلی می  باشد ویرایش کنید";
                } else
                    $response_text = "متن راهنما وارد کنید";

                cache()->set($this->getKeyCache() . $this->getUserId(), "help");

                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
                break;
            case  "\xF0\x9F\x92\xB3\xF0\x9F\x8C\xB3ویرایش حق اشتراک":
                $rule = Setting::where("key", "membership")->first();

                if ($rule) {
                    $response_text = "متن حق اشتراک وارد کنید";
                    $response_text .= $rule->value;
                    $response_text .= "\n\n";
                    $response_text .= "می توانید ویرایش کنید";
                } else
                    $response_text = "متن حق اشتراک وارد کنید";

                cache()->set($this->getKeyCache() . $this->getUserId(), "membership");

                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
                break;
            case  "\xF0\x9F\x92\xB1کیف پول":
                $wallet_membership = Setting::where("key", "wallet_membership")->first();

                if ($wallet_membership) {
                    $response_text = "کیف پول حق اشتراک وارد شده";

                    $response_text .= $wallet_membership->value;

                    $response_text .= "\n\n";
                    $response_text .= "می توانید ویرایش کنید";
                } else
                    $response_text = "کیف پول حق اشتراک وارد کنید";

                cache()->set($this->getKeyCache() . $this->getUserId(), "wallet_membership");

                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
                break;
            case "\xF0\x9F\x93\x88شروع مبلغ معامله":
                $s_price_trade = Setting::where("key", "start_price_trade")->first();
                if ($s_price_trade) {
                    $response_text = "شروع معامله   تنظیم شد:";
                    $response_text .= "\n\n";
                    $response_text .= " مبلغ";
                    $response_text .= "\n\n";
                    $response_text .= number_format(data_get($s_price_trade, "value"), 0);
                    $response_text .= "\n\n";
                } else {
                    $response_text = " شروع مبلغ وارد شده باید به صورت \n\n";
                    $response_text .= "14000000 \n\n";
                }
                cache()->set($this->getKeyCache() . $this->getUserId(), "start_price_trade");

                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);

                break;
            case "\xE2\x8C\x9Aساعت شروع":
                $hours_of_operation = Setting::where("key", "start_hours_of_operation")->first();
                if ($hours_of_operation) {
                    $response_text = "ساعت شروع تنظیم شد:";
                    $response_text .= "\n\n";
                    $response_text .= "\n\n";
                    $response_text .= number_format(data_get($hours_of_operation, "value"), 0);
                    $response_text .= "\n\n";
                } else {
                    $response_text = " شروع  وارد شده باید به صورت \n\n";
                    $response_text .= "09:00 \n\n";
                }
                cache()->set($this->getKeyCache() . $this->getUserId(), "start_hours_of_operation");

                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);

                break;
            case "\xE2\x8F\xB0ساعت پایان":
                $hours_of_operation = Setting::where("key", "end_hours_of_operation")->first();
                if ($hours_of_operation) {
                    $response_text = "ساعت پایان تنظیم شد:";
                    $response_text .= "\n\n";
                    $response_text .= "\n\n";
                    $response_text .= number_format(data_get($hours_of_operation, "value"), 0);
                    $response_text .= "\n\n";
                } else {
                    $response_text = " پایان  وارد شده باید به صورت \n\n";
                    $response_text .= "22:00 \n\n";
                }
                cache()->set($this->getKeyCache() . $this->getUserId(), "end_hours_of_operation");

                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);

                break;

            case "\xE2\x98\x81تعطیل":
                $date = now()->format("Y-m-d");
                $start_price_trade = Setting::updateOrCreate(
                    ["key" => "vacation"],
                    ["value" => $date]
                );
                $response_text = toJalali($date);
                $response_text .= " تعطیل شد";
                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);

                break;

        }
    }

    public function actionTextCache()
    {
        logger("cache", [$this->getMessageCache()]);
        switch ($this->getMessageCache()) {
            case "find_user":
                $this->listUser(1,null,$this->getMessage());

                cache()->forget($this->getKeyCache() . $this->getUserId());
                break;
            case "rule":
                $rule = Setting::updateOrCreate(
                    ["key" => "rule"],
                    ["value" => $this->getMessage()]
                );


                $response_text = "متن قواتین  بروزرسانی شد:";
                $response_text .= "\n\n";
                $response_text .= $rule->value;
                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
                cache()->forget($this->getKeyCache() . $this->getUserId());
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
                cache()->forget($this->getKeyCache() . $this->getUserId());
                break;
            case "membership":
                $membership = Setting::updateOrCreate(
                    ["key" => "membership"],
                    ["value" => $this->getMessage()]
                );


                $response_text = "متن حق اشتراک  بروزرسانی شد:";
                $response_text .= "\n\n";
                $response_text .= $membership->value;
                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
                cache()->forget($this->getKeyCache() . $this->getUserId());
                break;
            case "wallet_membership":
                $membership = Setting::updateOrCreate(
                    ["key" => "wallet_membership"],
                    ["value" => $this->getMessage()]
                );


                $response_text = "کیف پول حق اشتراک  بروزرسانی شد:";
                $response_text .= "\n\n";
                $response_text .= $membership->value;
                $response_text .= "\n\n";

                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
                cache()->forget($this->getKeyCache() . $this->getUserId());
                break;
            case "start_price_trade":
                $start_price_trade = $this->getMessage();
                if (is_numeric($start_price_trade) && $start_price_trade > 0) {
                    $start_price_trade = Setting::updateOrCreate(
                        ["key" => "start_price_trade"],
                        ["value" => (int)$start_price_trade]
                    );

                    $response_text = "شروع معامله   بروزرسانی شد:";
                    $response_text .= "\n\n";
                    $response_text .= " مبلغ";
                    $response_text .= "\n\n";
                    $response_text .= number_format(data_get($start_price_trade, "value"), 0);
                    $response_text .= "\n\n";
                    $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
                    cache()->forget($this->getKeyCache() . $this->getUserId());
                } else {
                    $this->getTelegramServices()->sendMessage($this->getUserId(), "مبلغ وارد شده معامله  صحیخ نمی باشد");

                }
                break;
            case "start_hours_of_operation":
                if(isValidTime($this->getMessage())) {

                    $strat = Setting::updateOrCreate(
                        ["key" => "start_hours_of_operation"],
                        ["value" => $this->getMessage()]
                    );


                    $response_text = "زمان شروع معاملات:";
                    $response_text .= "\n\n";
                    $response_text .= $strat->value;
                    $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
                    cache()->forget($this->getKeyCache() . $this->getUserId());
                }else{
                    $this->getTelegramServices()->sendMessage($this->getUserId(), "ساختار وارد شده ساعت باید باشد 09:00");

                }
                break;
            case "end_hours_of_operation":
                if(isValidTime($this->getMessage())) {
                    $strat = Setting::updateOrCreate(
                        ["key" => "end_hours_of_operation"],
                        ["value" => $this->getMessage()]
                    );


                    $response_text = "زمان پایان معاملات:";
                    $response_text .= "\n\n";
                    $response_text .= $strat->value;
                    $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
                    cache()->forget($this->getKeyCache() . $this->getUserId());
                }else
                    $this->getTelegramServices()->sendMessage($this->getUserId(), "ساختار وارد شده ساعت باید باشد 22:00");
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
        logger("menu bot", [$menu_bot]);
        if ($menu_bot) {
            $key = $user_con->role == "colleague" ? $this->keyword_colleague : $this->keyword_customer;
            if (!$user_con->status)
                $key = new \stdClass();
            $this->service_user->telegram_services->editCustomKeyboard($user_con->id, $menu_bot->menu_id, "تغییر منو", $key);
            cache()->forget("keyword_menu" . $this->getKeyCache() . $user_con->id);
        }
    }

    /**
     * @return void
     */
    public function listUser($page = 1, $message_id = null,$filter=null)
    {
        $text = "\n\nلیست  کاربران";
        $text .= "\n\n";
        $text .= "با کلیک بر\xE2\x9D\x8C کاربر غیر فعال شده و با کلیک بر \xE2\x9C\x85 کاربرفعال گردید در صورت کلیک بر روی اسم شخص نوع کاربر از مشتری به همکار و همکار به مشتری تغییر می کنند ";

        $users = UserTelegram::query();
        if($filter){
            $users->where(function ($query) use ($filter){
               $query->where("fullName","like","%".$filter."%");
               $query->orWhere("mobile","like","%".$filter."%");
            });
        }
        $users = simplePaginate(4, ['*'], 'page', $page);
        $page = $users->currentPage();
        $next = $users->nextPageUrl() ? (int)str_replace("?page=", "", strstr($users->nextPageUrl(), "?page=")) : null;
        $pre = $users->previousPageUrl() ? (int)str_replace("?page=", "", strstr($users->previousPageUrl(), "?page=")) : null;
        $keyboard = [];
        $i = 0;

        logger("users", [$users]);
        $users->each(function ($user) use (&$keyboard, &$i) {
            $text = $user->fullName ?: $user->first_name . " " . $user->last_name;
            $text .= $user->role == "colleague" ? "(همکار)" : "(مشتری)";
            $keyboard[$i++] = [
                ['text' => "  $text  ", 'callback_data' => ($user->role == "colleague" ? "colleague_" : "customer_") . $user->id],
            ];
            $keyboard[$i++] = [
                ['text' => "\xE2\x9C\x85 ", 'callback_data' => 'confirm_' . $user->id],
                ['text' => "\xE2\x9D\x8C", 'callback_data' => 'reject_' . $user->id],
                ['text' => "\xF0\x9F\x9A\xAF", 'callback_data' => 'delete_' . $user->id],
            ];
        });
        if ($pre)
            $keyboard[$i][] = ['text' => "قبلی", "callback_data" => "pre_" . $pre];
        if ($next)
            $keyboard[$i][] = ['text' => "بعدی", "callback_data" => "next_" . $next];

        if ($message_id)
            $this->getTelegramServices()->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $text, $keyboard);
        else {
            $this->getTelegramServices()->menu_key = "menu_List_user_";
            $menu = $this->getTelegramServices()->MessageReplyMarkup($this->getTelegram(), $this->getUserId(), $text, $keyboard);
        }
    }
}
