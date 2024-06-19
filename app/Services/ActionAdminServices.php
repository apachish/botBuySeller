<?php

namespace App\Services;


use App\Models\Bot;
use App\Models\BotMenuUser;
use App\Models\CustomerUser;
use App\Models\Setting;
use App\Models\SupportTelegram;
use App\Models\Transfer;
use App\Models\UserTelegram;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Telegram\Bot\Api;

class ActionAdminServices extends TextServices
{
    public $service_user;
    public $bot_title;
    public $bot_user;
    public $key_cache_user = "text_user_";

    public function checkMessage()
    {

        $access_text = [
            "/start",
            "\xF0\x9F\x9A\xBBلیست کاربران",
            "جستجو کاربر\xF0\x9F\x94\x8D",
            "\xF0\x9F\x93\x88شروع مبلغ معامله",
            "\xF0\x9F\x93\x88سقف مبلغ معامله",
            "\xE2\x8C\x9Aساعت شروع",
            "\xE2\x8F\xB0ساعت پایان",
            "\xE2\x98\x81تعطیل/باز",
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
            $data = str_replace('pre_', '', $this->getData());
            $array = explode("_",$data);
            $page = (int)data_get($array,0);
            $filter = data_get($array,1,null);
            $data_old = cache()->get("menu_List_user_" . $this->getUserId());
            $message_id = data_get($data_old, "id", null);
            if ($message_id)
                $this->listUser($page, $message_id,$filter);
        } elseif (str_contains($this->getData(), "next_")) {
            $data = str_replace('next_', '', $this->getData());
            $array = explode("_",$data);
            $page = (int)data_get($array,0);
            $filter = data_get($array,1,null);
            $data_old = cache()->get("menu_List_user_" . $this->getUserId());
            $message_id = data_get($data_old, "id", null);
            logger("aa", [$data_old, $message_id, $page]);
            if ($message_id)
                $this->listUser($page, $message_id,$filter);
        } elseif (str_contains($this->getData(), "forbidden_")) {
            $data = str_replace('forbidden_', '', $this->getData());
            $value = $data=="active"?true:false;
            $forbidden = Setting::where("key", "forbidden")->first();
            if($forbidden)
                $forbidden->update(["value"=>$value]);
            else{
                Setting::create([
                    "key"=>"forbidden",
                    "value"=>$value,
                ]);
            }
            $message_id = cache()->get("forbidden_".$this->getUserId());
            $text = $data=="active"?"فعال شد":"غیرفعال شد";
            $this->getTelegramServices()->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $text, []);
            cache()->forget("forbidden_".$this->getUserId());
        }

        elseif (str_contains($this->getData(), "active_sub_customer_")) {
            $data = str_replace('active_sub_customer_', '', $this->getData());
            $array = explode("_",$data);
            $customer = CustomerUser::with("headCustomer")->find(data_get($array,0));
            logger("customer",[$customer]);
            if($customer){
                $customer->status = true;
                $customer->update();
                $user_con = $customer->headCustomer;
                $data_menu = cache()->get("sub_customer".$customer->user_id);

                $this->subcustomer($user_con,$data_menu);
            }
        }elseif (str_contains($this->getData(), "reject_sub_customer_")) {
            $data = str_replace('reject_sub_customer_', '', $this->getData());
            $array = explode("_",$data);
            $customer = CustomerUser::with("headCustomer")->find(data_get($array,0));
            if($customer){
                $customer->status = false;
                $customer->update();
                $user_con = $customer->headCustomer;
                $data_menu = cache()->get("sub_customer".$customer->user_id);

                $this->subcustomer($user_con,$data_menu);
            }
        }
        elseif (str_contains($this->getData(), "sub_customer_")) {
            $data = str_replace('sub_customer_', '', $this->getData());
            logger("sub_customer_",[$data]);
            $array = explode("_",$data);
            $id = (int)data_get($array,0);
            $page = (int)data_get($array,1);
            $filter = data_get($array,2,null);
            $user_con = UserTelegram::where("id", $id)->with("customerUsers")->first();
            logger("con", [$user_con, $id]);
            if ($user_con) {
                $this->subcustomer($user_con, $data);
            }
        }
        elseif (str_contains($this->getData(), "customer_")) {
            $data = str_replace('customer_', '', $this->getData());
            $array = explode("_",$data);
            $id = (int)data_get($array,0);
            $page = (int)data_get($array,1);
            $filter = data_get($array,2,null);
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
                $data_old = cache()->get("menu_List_user_" . $this->getUserId());
                $message_id = data_get($data_old, "id", null);
                $this->listUser($page,$message_id,$filter);

            }
        } elseif (str_contains($this->getData(), "colleague_")) {
            $data = str_replace('colleague_', '', $this->getData());
            $array = explode("_",$data);
            $id = (int)data_get($array,0);
            $page = (int)data_get($array,1);
            $filter = data_get($array,2,null);
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
                $data_old = cache()->get("menu_List_user_" . $this->getUserId());
                $message_id = data_get($data_old, "id", null);
                $this->listUser($page,$message_id,$filter);
            }
        }
       elseif (str_contains($this->getData(), "return_menu_")) {
            $data = str_replace('return_menu_', '', $this->getData());
            $array = explode("_",$data);
            $id = (int)data_get($array,0);
            $page = (int)data_get($array,1);
            $filter = data_get($array,2,null);
            $data_old = cache()->get("menu_List_user_" . $this->getUserId());
            $message_id = data_get($data_old, "id", null);
            $this->listUser($page,$message_id,$filter);
        }elseif (str_contains($this->getData(), "add_chanel_")) {
            $data = str_replace('add_chanel_', '', $this->getData());
            $array = explode("_",$data);
            $id = (int)data_get($array,0);
            $page = (int)data_get($array,1);
            $filter = data_get($array,2,null);
            $user_con = UserTelegram::where("id", $id)->with("customerUsers")->first();
            logger("con", [$user_con, $id]);
            if ($user_con) {
                logger("link",[
                    $this->bot,
                    'chat_id' => $this->bot->chanel_id,
                    'expire_date' => time() + 3600, // لینک به مدت 24 ساعت معتبر است
                    'name' => Str::slug($user_con->fullName,"_"),
                    'member_limit' => 1, // تعداد اعضای جدیدی که با این لینک می‌توانند بپیوندند
                ]);
                $response = $this->telegram->createChatInviteLink([
                    'chat_id' => $this->bot->chanel_id,
                    'name' => Str::slug($user_con->fullName,"_"),
                    'expire_date' => time() + 3600, // لینک به مدت 24 ساعت معتبر است
                    'member_limit' => 1, // تعداد اعضای جدیدی که با این لینک می‌توانند بپیوندند
                ]);

                logger("r",[$response,data_get($response,"invite_link")]);
                $inviteLink = data_get($response,"invite_link");

                logger("link",[$inviteLink]);
                $this->telegram->sendMessage([
                    'chat_id' => $this->getUserId(),
                    'text' => "لینک دعوت کانال برای کاربر ارسال شد",
                ]);
                // ارسال لینک دعوت به کاربر
                $message_link  = "لطفا با استفاده از لینک دعوت[فقط یک ساعت معتبر می باشد] به کانال  ".env("APP_NAME")." بپیوندید: " . $inviteLink;
                $message_link  = "\n\n " . $inviteLink;
                $this->service_user->telegram_services->sendMessage($user_con->id,$message_link);
            }
        } elseif (str_contains($this->getData(), "confirm_")) {
            $data = str_replace('confirm_', '', $this->getData());
            $array = explode("_",$data);
            $id = (int)data_get($array,0);
            $page = (int)data_get($array,1);
            $filter = data_get($array,2,null);
            $user_con = UserTelegram::where("id", $id)->first();
            logger("con", [$user_con, $id]);
            if ($user_con) {
                $fullName = $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
                $user_con->status = true;
                $user_con->change_menu = true;
                $user_con->role = $user_con->role?:"customer";
                $user_con->update();
                cache()->forget("keyword_menu" . $this->key_cache_user . $user_con->id);
                $response_text = "$fullName اکانت کاربریش فعال شد\n\n ";
                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
//                $response_text = "$fullName اکانت کاربریتان فعال شد\n\n ";
//                $this->service_user->telegram_services->sendMessage($user_con->id, $response_text);
                $this->service_user->message_menu = "$fullName اکانت کاربریتان فعال شد\n\n ";
                $this->service_user->menu($this->keyword_customer, $user_con->status, $user_con);
                $data_old = cache()->get("menu_List_user_" . $this->getUserId());
                $message_id = data_get($data_old, "id", null);
                $this->listUser($page,$message_id,$filter);
            }
        }elseif (str_contains($this->getData(), "active_")) {
            $data = str_replace('active_', '', $this->getData());
            $array = explode("_",$data);
            $id = (int)data_get($array,0);
            $page = (int)data_get($array,1);
            $filter = data_get($array,2,null);
            $user_con = UserTelegram::withTrashed()->where("id", $id)->first();
            logger("con", [$user_con, $id,UserTelegram::withTrashed()->where("id", $id)->getQuery()]);
            if ($user_con) {
                $fullName = $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
                $user_con->status = true;
                $user_con->change_menu = true;
                $user_con->role = $user_con->role?:"customer";
                $user_con->deleted_at = null;
                $user_con->update();
                cache()->forget("keyword_menu" . $this->key_cache_user . $user_con->id);
                $response_text = "$fullName اکانت کاربریش فعال شد\n\n ";
                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
//                $response_text = "$fullName اکانت کاربریتان فعال شد\n\n ";
//                $this->service_user->telegram_services->sendMessage($user_con->id, $response_text);
                $this->service_user->message_menu = "$fullName اکانت کاربریتان فعال شد\n\n ";
                $this->service_user->menu($this->keyword_customer, $user_con->status, $user_con);
                $data_old = cache()->get("menu_List_user_" . $this->getUserId());
                $message_id = data_get($data_old, "id", null);
                $this->listUser($page,$message_id,$filter);
            }
        } elseif (str_contains($this->getData(), "reject_")) {
            $data = str_replace('reject_', '', $this->getData());
            $array = explode("_",$data);
            $id = (int)data_get($array,0);
            $page = (int)data_get($array,1);
            $filter = data_get($array,2,null);

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
                $data_old = cache()->get("menu_List_user_" . $this->getUserId());
                $message_id = data_get($data_old, "id", null);
                $this->listUser($page,$message_id,$filter);
            }
        } elseif (str_contains($this->getData(), "delete_")) {

            $data = str_replace('delete_', '', $this->getData());
            $array = explode("_",$data);
            $id = (int)data_get($array,0);
            $page = (int)data_get($array,1);
            $filter = data_get($array,2,null);

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

                try {
                    // خارج کردن کاربر از کانال
                    $response =  $this->telegram->kickChatMember(
                        [
                            'chat_id' => $this->bot->chanel_id,
                            'user_id' => $user_con->id,
                        ]);

                    if ($response) {
                        logger( "User has been successfully removed from the channel.");
                    } else {
                        logger( "Failed to remove user from the channel.");
                    }
                } catch (\Exception $e) {
                    logger( "Error: " . $e->getMessage());
                }
                $this->service_user->message_menu = "اکانت کاربریش حذف شد";
                $this->service_user->menu([], $user_con->status, $user_con);
                $this->listUser($page);
                $data_old = cache()->get("menu_List_user_" . $this->getUserId());
                $message_id = data_get($data_old, "id", null);
                $this->listUser($page,$message_id,$filter);

            }
        }elseif (str_contains($this->getData(), "edit_name_")) {

            $data = str_replace('edit_name_', '', $this->getData());
            $array = explode("_",$data);
            $id = (int)data_get($array,0);
            $page = (int)data_get($array,1);
            $filter = data_get($array,2,null);

            $user_con = UserTelegram::where("id", $id)->first();
            logger("rej", [$user_con, $id]);

            if ($user_con) {
                $fullName = $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
                $message  = $fullName;
                $message  .= "\n\n";
                $message  .= "می باشد لطفا نام و نام خانوادگی وارد کنید ";
                $this->getTelegramServices()->sendMessage($this->getUserId(), $message);
                cache()->set($this->getKeyCache() . $this->getUserId(), "edit_name_done_".$data);
            }
        }elseif (str_contains($this->getData(), "answer_message_")) {

            $data = str_replace('answer_message_', '', $this->getData());
            $array = explode("_",$data);
            $id = data_get($array,0);
            $page = (int)data_get($array,1);

            $support = SupportTelegram::with("user")->find($id);
            logger("answer_message_",[$id,$page,$support]);
            if($support) {
                $user = $support->user;
                if ($user) {
                    $fullName = $user->fullName ?: $user->first_name . " " . $user->last_name;
                    $message = "می توانید با ارسال متن پاسخ سوال کاربر ";
                    $message .= "\n\n";
                    $message .= $fullName;
                    $message .= "\n\n";
                    $message .= ":پیام";
                    $message .= "\n\n";
                    $message .= data_get($support,'text');
                    $message .= "\n\n";
                    $message .= "!!پاسخ دهید!!";
                    $this->getTelegramServices()->sendMessage($this->getUserId(), $message);
                    cache()->set($this->getKeyCache() . $this->getUserId(), "answer_message_" . $id."_".$page);
                }
            }
        }elseif (str_contains($this->getData(), "edit_mobile_")) {

            $data = str_replace('edit_mobile_', '', $this->getData());
            $array = explode("_",$data);
            $id = (int)data_get($array,0);
            $page = (int)data_get($array,1);
            $filter = data_get($array,2,null);

            $user_con = UserTelegram::where("id", $id)->first();
            logger("edit_mobile_done_", [$user_con, $id]);

            if ($user_con) {
                $message  = $user_con->mobile;
                $message  .= "\n\n";
                $message  .= "می باشد لطفا موبایل وارد کنید ";
                $this->getTelegramServices()->sendMessage($this->getUserId(), $message);
                cache()->set($this->getKeyCache() . $this->getUserId(), "edit_mobile_done_".$data);
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
                case "\xF0\x9F\x92\xACلیست پیام ها کاربران":
                $this->listMessageSupport();
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
                case  "\xF0\x9F\x9A\xAB\xF0\x9F\x9A\xBBممنوع معامله":
                $forbidden = Setting::where("key", "forbidden")->first();

                if ($forbidden) {
                    $response_text = $forbidden->value?"فعال":"غیرفعال";

                    $response_text .= "\n\n";
                    $response_text .= "معامله همکار و مشتری";
                } else
                    $response_text = "مشخص کنید معامله مشتری و همکار چگونه می باشد";

                    $keyboard[0][0] = ['text' => "فعال", "callback_data" => "forbidden_active"];
                    $keyboard[0][1] = ['text' => "غیرفعال", "callback_data" => "forbidden_deactivate"];

                    $menu = $this->getTelegramServices()->MessageReplyMarkup($this->getTelegram(), $this->getUserId(), $response_text, $keyboard);
                    cache()->set("forbidden_".$this->getUserId(), $menu);
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
            case "\xF0\x9F\x93\x88سقف مبلغ معامله":
                $s_price_trade = Setting::where("key", "end_price_trade")->first();
                if ($s_price_trade) {
                    $response_text = "سقف مبلغ معامله   تنظیم شد:";
                    $response_text .= "\n\n";
                    $response_text .= " مبلغ";
                    $response_text .= "\n\n";
                    $response_text .= number_format(data_get($s_price_trade, "value"), 0);
                    $response_text .= "\n\n";
                } else {
                    $response_text = " شروع مبلغ وارد شده باید به صورت \n\n";
                    $response_text .= "14000000 \n\n";
                }
                cache()->set($this->getKeyCache() . $this->getUserId(), "end_price_trade");

                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);

                break;
            case "\xE2\x8C\x9Aساعت شروع":
                $hours_of_operation = Setting::where("key", "start_hours_of_operation")->first();
                if ($hours_of_operation) {
                    $response_text = "ساعت شروع تنظیم شد:";
                    $response_text .= "\n\n";
                    $response_text .= "\n\n";
                    $response_text .= data_get($hours_of_operation, "value");
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
                    $response_text .= data_get($hours_of_operation, "value");
                    $response_text .= "\n\n";
                } else {
                    $response_text = " پایان  وارد شده باید به صورت \n\n";
                    $response_text .= "22:00 \n\n";
                }
                cache()->set($this->getKeyCache() . $this->getUserId(), "end_hours_of_operation");

                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);

                break;

            case "\xE2\x98\x81تعطیل/باز":
                $date = now()->format("Y-m-d");
                $holiday = Setting::where("key","vacation")->first();
                $response_text = toJalali($date,"Y/m/d");
                if($holiday && $holiday->value)
                {
                    $response_text .= " باز شد";
                    $holiday->value =  null;
                    $holiday->update();
                }else {
                    $response_text .= " تعطیل شد";
                    $holiday = Setting::updateOrCreate(
                        ["key" => "vacation"],
                        ["value" => $date]
                    );
                }
                $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
                cache()->forget("parameter_need");

                break;

        }
    }

    public function actionTextCache()
    {
        $key_case = $this->getMessageCache();
        logger("cache", [$this->getMessageCache()]);
        if(str_contains($this->getMessageCache(), "edit_name_done_"))
            $key_case = "edit_name_done_";
        elseif(str_contains($this->getMessageCache(), "edit_mobile_done_"))
            $key_case = "edit_mobile_done_";
        elseif(str_contains($this->getMessageCache(), "answer_message_"))
            $key_case = "answer_message_";
        logger($key_case);
        switch ($key_case) {

            case "edit_name_done_":
                $data = str_replace('edit_name_done_', '', $this->getMessageCache());
                $array = explode("_",$data);
                logger("array",[$array]);
                $id = (int)data_get($array,0);
                $page = (int)data_get($array,1);
                $filter = data_get($array,2,null);

                $user_con = UserTelegram::where("id", $id)->first();
                logger("rej", [$user_con, $id]);

                if ($user_con) {
                    $user_con->fullName = $this->getMessage();
                    $user_con->update();
                    $message  = $user_con->fullName;
                    $message  .= "\n\n";
                    $message  .= "نام و نام خانوادگی بروزرسانی شد ";
                    $this->getTelegramServices()->sendMessage($this->getUserId(), $message);
                }
                $data_old = cache()->get("menu_List_user_" . $this->getUserId());
                $message_id = data_get($data_old, "id", null);
                $this->listUser($page,$message_id,$filter);
                cache()->forget($this->getKeyCache() . $this->getUserId());
                break;
            case "edit_mobile_done_":
                $pattern = '/^\+\d{1,3}\d{4,14}(?:x.+)?$/';
                if (preg_match($pattern, $this->getMessage())) {
                    $data = str_replace('edit_mobile_done_', '', $this->getMessageCache());
                    $array = explode("_", $data);
                    $id = (int)data_get($array, 0);
                    $page = (int)data_get($array, 1);
                    $filter = data_get($array, 2, null);

                    $user_con = UserTelegram::where("id", $id)->first();
                    logger("rej", [$user_con, $id]);

                    if ($user_con) {
                        $user_con->mobile = $this->getMessage();
                        $user_con->update();
                        $message = $user_con->fullName;
                        $message .= "\n\n";
                        $message .= "نام و نام خانوادگی بروزرسانی شد ";
                        $this->getTelegramServices()->sendMessage($this->getUserId(), $message);
                    }
                    $data_old = cache()->get("menu_List_user_" . $this->getUserId());
                    $message_id = data_get($data_old, "id", null);
                    $this->listUser($page, $message_id, $filter);
                    cache()->forget($this->getKeyCache() . $this->getUserId());
                }else{
                    $text = "موبایل مشتری باید با کد کشور بدون صفر مثل ";
                    $text .= "\n\n";
                    $text .='+989120001122';
                    $text .= "\n\n";
                    $text .='+11234567890';

                    $this->telegram_services->sendMessage($this->getUserId(), $text);
                }
                break;
            case "find_user":
                $this->listUser(1,null,$this->getMessage());

                cache()->forget($this->getKeyCache() . $this->getUserId());
                break;
            case "answer_message_":
                $data = str_replace('answer_message_', '', $this->getMessageCache());
                $array = explode("_",$data);
                logger("array",[$array]);
                $id = (int)data_get($array,0);
                $page = (int)data_get($array,1);
                $message = SupportTelegram::with("user")->find($id);

                if($message)
                {
                    $message->replay = $this->getMessage();
                    $message->update();
                    $data_old = cache()->get("menu_List_message_" . $this->getUserId());
                    $message_id = data_get($data_old, "id", null);
                    $this->listMessageSupport($page,$message_id);
                    $bot = Bot::where("title","botSupport")-first();
                    logger("bot support",[$bot]);
                    if($bot) {
                        $telegram = new Api($bot->token);
                        $m = $telegram->sendMessage([
                            'chat_id' => $message->user_telegram_id,
                            'text' => $this->getMessage(),
                        ]);
                        logger("ss",[$m]);
                    }
                }


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
                    cache()->forget("start_price_trade");
                } else {
                    $this->getTelegramServices()->sendMessage($this->getUserId(), "مبلغ وارد شده معامله  صحیخ نمی باشد");

                }
                break;
                case "end_price_trade":
                $end_price_trade = $this->getMessage();
                if (is_numeric($end_price_trade) && $end_price_trade > 0) {
                    $end_price_trade = Setting::updateOrCreate(
                        ["key" => "end_price_trade"],
                        ["value" => (int)$end_price_trade]
                    );

                    $response_text = "سقف مبلغ معامله بروزرسانی شد:";
                    $response_text .= "\n\n";
                    $response_text .= " مبلغ";
                    $response_text .= "\n\n";
                    $response_text .= number_format(data_get($end_price_trade, "value"), 0);
                    $response_text .= "\n\n";
                    $this->getTelegramServices()->sendMessage($this->getUserId(), $response_text);
                    cache()->forget($this->getKeyCache() . $this->getUserId());
                    cache()->forget("end_price_trade");

                } else {
                    $this->getTelegramServices()->sendMessage($this->getUserId(), "مبلغ وارد شده سقف مبلغ صحیخ نمی باشد");

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
                    cache()->forget("parameter_need");
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
                    cache()->forget("parameter_need");
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

        $users = UserTelegram::withTrashed();
        if($filter){
            $users->where(function ($query) use ($filter){
               $query->where("fullName","like","%".$filter."%");
               $query->orWhere("mobile","like","%".$filter."%");
            });
        }
        $users = $users->simplePaginate(4, ['*'], 'page', $page);
        $page = $users->currentPage();
        $next = $users->nextPageUrl() ? (int)str_replace("?page=", "", strstr($users->nextPageUrl(), "?page=")) : null;
        $pre = $users->previousPageUrl() ? (int)str_replace("?page=", "", strstr($users->previousPageUrl(), "?page=")) : null;
        $keyboard = [];
        $i = 0;

        logger("users", [$users]);
        $users->each(function ($user) use (&$keyboard, &$i,$page,$filter){
            $text = $user->fullName ?: $user->first_name . " " . $user->last_name;
            $text .= $user->role == "colleague" ? "(همکار)" : "(مشتری)";
            $keyboard[$i++] = [
                ['text' => "  $text  ", 'callback_data' => ($user->role == "colleague" ? "colleague_" : "customer_") . $user->id."_".$page],
            ];
            $key_i =  $user->id."_".$page;
            if($filter)
                $key_i.="_".$filter;
            $array = [
                ['text' => "\xE2\x9C\x8F\xF0\x9F\x91\xA8", 'callback_data' => 'edit_name_' . $key_i],
                ['text' => "\xE2\x9C\x8F\xF0\x9F\x93\xB1", 'callback_data' => 'edit_mobile_' . $key_i],
                ['text' => "\xF0\x9F\x91\xA4", 'callback_data' => 'sub_customer_' . $key_i],
            ];
            if($user->deleted_at)
                $array[] =['text' => "\xF0\x9F\x86\x97", 'callback_data' => 'active_' . $key_i];
            else {
                $array[] = ['text' => "\xF0\x9F\x9A\xAF", 'callback_data' => 'delete_' . $key_i];
                if($user->status) {
                    $array[] = ['text' => "\xE2\x9D\x8C", 'callback_data' => 'reject_' . $key_i];
                    $array[] = ['text' => "\xE2\x9E\x95	\xF0\x9F\x8C\xB3", 'callback_data' => 'add_chanel_' . $key_i];
                }else
                    $array[]= ['text' => "\xE2\x9C\x85 ", 'callback_data' => 'confirm_' . $key_i];

            }

            $keyboard[$i++] = $array;
        });
        if ($pre)
            $keyboard[$i][] = ['text' => "قبلی", "callback_data" => "pre_" . $pre.($filter?"_".$filter:null)];
        if ($next)
            $keyboard[$i][] = ['text' => "بعدی", "callback_data" => "next_" . $next.($filter?"_".$filter:null)];

        if ($message_id)
            $this->getTelegramServices()->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $text, $keyboard);
        else {
            $this->getTelegramServices()->menu_key = "menu_List_user_";
            $menu = $this->getTelegramServices()->MessageReplyMarkup($this->getTelegram(), $this->getUserId(), $text, $keyboard);
        }
    }
    public function listMessageSupport($page = 1, $message_id = null)
    {
        $text = "\n\nلیست  پیام های کاربران";
        $text .= "\n\n";
        $text .= "با کلیک بر پیام امکان پاسخ پیام می باشد ";

        $messages = SupportTelegram::orderBy("created_at","DESC")->orderBy("replay");
        $messages = $messages->simplePaginate(5, ['*'], 'page', $page);
        $page = $messages->currentPage();
        $next = $messages->nextPageUrl() ? (int)str_replace("?page=", "", strstr($messages->nextPageUrl(), "?page=")) : null;
        $pre = $messages->previousPageUrl() ? (int)str_replace("?page=", "", strstr($messages->previousPageUrl(), "?page=")) : null;
        $keyboard = [];
        $i = 0;

        logger("messages", [$messages]);
        $messages->each(function ($message) use (&$keyboard, &$i,$page){
            $user = $message->user;
            $text = $user->fullName ?: $user->first_name . " " . $user->last_name;
            $text .= $user->role == "colleague" ? "(همکار)" : "(مشتری)";
            $text .=":\n\n";
            $text .= $message->text;
            $text .="\n\n";
            $text .= toJalali($message->created_at);
            if($message->replay)
                $text.=" \n\n \xE2\x9C\x85	";

            $keyboard[$i++] = [
                ['text' => "  $text  ", 'callback_data' =>  "answer_message_".$message->id."_".$page],
            ];
        });
        if ($pre)
            $keyboard[$i][] = ['text' => "قبلی", "callback_data" => "pre_" . $pre];
        if ($next)
            $keyboard[$i][] = ['text' => "بعدی", "callback_data" => "next_" . $next];

        if ($message_id)
            $this->getTelegramServices()->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $text, $keyboard);
        else {
            $this->getTelegramServices()->menu_key = "menu_List_message_";
            $menu = $this->getTelegramServices()->MessageReplyMarkup($this->getTelegram(), $this->getUserId(), $text, $keyboard);
        }
    }

    /**
     * @param $user_con
     * @param mixed $data
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function subcustomer($user_con, mixed $data): void
    {
        $data_old = cache()->get("menu_List_user_" . $this->getUserId());
        $message_id = data_get($data_old, "id", null);
        $text = "لیست مشتریان";
        $text .= "\n\n";
        $text .= $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
        $keyboard = [];
        $i = 0;
        cache()->set("sub_customer".$user_con->id,$data);
        foreach ($user_con->customerUsers as $j => $cus) {
            $title = $cus->fullName;
            $title .= $cus->user && $cus->user->fullName ? "(" . $cus->user->fullName . ")" : null;
            $act = $cus->status ? "\xE2\x9D\x8C" : "\xE2\x9C\x85 ";
            $keyboard[$i++] = [
                [
                    'text' => $title . $act,
                    'callback_data' => ($cus->status ?  "reject_sub_customer_":"active_sub_customer_") . $cus->id . "_" . $user_con->id
                ],
            ];
        }
        $keyboard[$i++] = [
            [
                'text' => "برگشت",
                'callback_data' => "return_menu_" . $data
            ],
        ];
        $this->getTelegramServices()->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $text, $keyboard);
    }
}
