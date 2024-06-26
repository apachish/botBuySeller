<?php

namespace App\Services\Admin;


use App\Models\Setting;
use App\Models\UserTelegram;
use App\Services\TelegramServices;
use App\Services\TextServices;
use Illuminate\Support\Str;

class CustomerServices
{

    public $keyword = [
        [
            ['text' => "جستجو کاربر\xF0\x9F\x94\x8D"],
        ],
        [
            ['text' => "\xF0\x9F\x9A\xBBلیست همکاران"],
        ],
        [
            ['text' => "\xF0\x9F\x9A\xBBلیست مشتریان"],
        ],
        [
            ['text' => "\xF0\x9F\x93\xA9ارسال پیام گروه"],
        ],
        [
            ['text' => "\xE2\x86\xA9منو"]
        ],
    ];

    protected $keyword_colleague = [
        [
            ['text' => "\xF0\x9F\x93\x8Bلیست همکاران"]
        ],
        [
            ['text' => "\xF0\x9F\x93\x88معاملات باز"]
        ],
        [
            ['text' => "\xF0\x9F\x93\x9Aقوانین"],
            ['text' => "راهنما\xE2\x81\x89"],
            ['text' => "\xE2\x8C\x9Bمدت اشتراک"],
            ['text' => "\xF0\x9F\x92\xB3حق اشتراک"]

        ], [
            ['text' => "\xE2\x9C\x8Cفعال سازی دو مرحله ای"],
            ['text' => "\xE2\x9D\x8Cغیر فعال فوری"],

        ]];

    protected $keyword_customer = [
        [
            ['text' => "\xF0\x9F\x93\x88معاملات باز"]
        ],
        [

            ['text' => "\xF0\x9F\x93\x9Aقوانین"],
            ['text' => "راهنما\xE2\x81\x89"],
            ['text' => "\xE2\x8C\x9Bمدت اشتراک"],
            ['text' => "\xF0\x9F\x92\xB3حق اشتراک"]
        ], [
            ['text' => "\xE2\x9C\x8Cفعال سازی دو مرحله ای"],
            ['text' => "\xE2\x9D\x8Cغیر فعال فوری"],

        ]];

    public function AcceptUser($object)
    {
        $id = (int)str_replace('ok_user_', '', $object->getData());
        $user = UserTelegram::where("telegram_id",$id)->first();
        if($user)
        {
            $text = "لطفا قوانین را مطالعه فرمایید";
            $rule = Setting::where("key", "rule")->first();

            $user->status = true;
            $user->update();
            $text .=  $rule?$rule->value:"";
            $keyboard[0][0] = ['text' => "قوانین را خواندم و آنها را پذیرفتم"];
            $object->service_user->telegram_services::menu($object->service_user->telegram, $keyboard, $user, $text);
            $message_admin = cache()->get("message_admin_".$object->getUserId());
            logger("message_admin",[$message_admin]);
            if($message_admin)
            {
                $object->telegram_services->deleteMessage($object->getUserId(),data_get($message_admin,"message_id"));
                $text_a = "کاربر";
                $text_a .= $user->fullName;
                $text_a .= " تایید شد ";
                $object->getTelegramServices()->sendMessage($object->getUserId(), $text_a);

            }

        }
    }

    public function rejectUser($object)
    {
        $id = str_replace('reject_user_', '', $object->getData());
        $user = UserTelegram::where("id",$id)->first();
        if($user)
            $user->deleted();

    }

    public function pre($object)
    {
        $data = str_replace('pre_', '', $object->getData());
        $array = explode("_", $data);
        $page = (int)data_get($array, 0);
        $role = data_get($array, 1, null);
        $filter = data_get($array, 2, null);
        $message_id = cache()->get("menu_List_user_" . $object->getUserId());
        if ($message_id)
            $this->listUser($role,$object,$page, $message_id, $filter);
    }

    public function next($object)
    {
        $data = str_replace('next_', '', $object->getData());
        $array = explode("_", $data);
        $page = (int)data_get($array, 0);
        $role = data_get($array, 1, null);
        $filter = data_get($array, 2, null);
        $message_id = cache()->get("menu_List_user_" . $object->getUserId());
        logger("aa", [ $message_id, $page]);
        if ($message_id)
            $this->listUser($role,$object,$page, $message_id, $filter);
    }

    public function setCustomer($object)
    {
        $data = str_replace('customer_', '', $object->getData());
        $array = explode("_", $data);
        $id = (int)data_get($array, 0);
        $page = (int)data_get($array, 1);
        $filter = data_get($array, 2, null);
        $user_con = UserTelegram::find($id);
        logger("con", [$user_con, $id]);
        if ($user_con) {
            $fullName = $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
            $user_con->role = "colleague";
            $user_con->change_menu = true;
            $user_con->update();
            $response_text = "$fullName نقش همکار فعال شد \n\n ";
            $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
            $object->service_user->message_menu = "$fullName همکار گرامی به سیستم ما خوش آمدید\n\n ";
            $object->service_user->menu($this->keyword_colleague, $user_con->status, $user_con);//->sendMessage($user_con->id, $response_text);
//                $object->service_user->telegram_services->sendMessage($user_con->id, $response_text);
            $message_id = cache()->get("menu_List_user_" . $object->getUserId());
            $this->listUser($object,$page, $message_id, $filter);

        }
    }

    public function setRole($object)
    {
        $data = str_replace('set_worker_', '', $object->getData());
        $array = explode("_", $data);
        $role = data_get($array, 0);
        $id = (int)data_get($array, 1);
        $page = (int)data_get($array, 2);
        $filter = data_get($array, 3, null);
        $user_con = UserTelegram::find($id);
        logger("con", [$user_con, $id]);
        if ($user_con) {
            $fullName = $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
            if($role ==  "colleague")
                $user_con->role = "customer";
            else
                $user_con->role = "colleague";
            $user_con->change_menu = true;
            $user_con->update();

            if($role ==  "customer") {
                $response_text = "$fullName نقش همکار فعال شد \n\n ";
                $object->service_user->message_menu = "$fullName همکار گرامی به سیستم ما خوش آمدید\n\n ";
                $object->service_user->message_menu .= "\n\n";
                $object->service_user->message_menu .= "برای دریافت حساب مشتریان خود بات مشتریان را شروع کنید";
                $object->service_user->message_menu .= "\n\n";
                $object->service_user->message_menu .= "  @".$object->bot->contact;
                $object->service_user->menu($this->keyword_colleague, $user_con->status, $user_con);

            }else
            {
                $response_text = "$fullName نقش همکاری این شخص به مشتری تغییر یافت \n\n ";
                $object->service_user->message_menu = "$fullName همکاری شما در سیستم به سطح مشتری انتقال یافت\n\n ";
                $object->service_user->menu($this->keyword_customer, $user_con->status, $user_con);

            }
            $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
            $message_id = cache()->get("menu_List_user_" . $object->getUserId());
            $this->listUser($role,$object,$page, $message_id, $filter);
        }
    }

    public function addChanel($object)
    {
        $data = str_replace('add_chanel_', '', $object->getData());
        $array = explode("_", $data);
        $role = data_get($array, 0);
        $id = (int)data_get($array, 1);
        $page = (int)data_get($array, 2);
        $filter = data_get($array, 3, null);
        logger("data",[$data]);
        $user_con = UserTelegram::with("customerUsers")->find($id);
        if ($user_con) {
            logger("link",[
                'chat_id' => data_get($object,"bot.chanel_id"),
                'name' => Str::slug($user_con->fullName, "_"),
                'expire_date' => time() + 150, // لینک به مدت 24 ساعت معتبر است
                'member_limit' => 1, // تعداد اعضای جدیدی که با این لینک می‌توانند بپیوندند
            ]);
            $response = $object->telegram->createChatInviteLink([
                'chat_id' => data_get($object,"bot.chanel_id"),
                'name' => Str::slug($user_con->fullName, "_"),
                'expire_date' => time() + 150, // لینک به مدت 24 ساعت معتبر است
                'member_limit' => 1, // تعداد اعضای جدیدی که با این لینک می‌توانند بپیوندند
            ]);

            $inviteLink = data_get($response, "invite_link");


            $object->telegram->sendMessage([
                'chat_id' => $object->getUserId(),
                'text' => "لینک دعوت کانال برای کاربر ارسال شد",
            ]);
            // ارسال لینک دعوت به کاربر
            $message_link = "لطفا با استفاده از لینک دعوت[فقط ۳ دقیقه معتبر می باشد] به کانال  " . env("APP_NAME") . " بپیوندید: " ;
            $message_link .= "\n\n " . $inviteLink;
            $object->service_user->telegram_services->sendMessage($user_con->telegram_id, $message_link);
            $message_id = cache()->get("menu_List_user_" . $object->getUserId());
            $this->listUser($role,$object,$page, $message_id, $filter);
        }
    }

    public function confirm($object)
    {
        $data = str_replace('confirm_', '', $object->getData());
        $array = explode("_", $data);
        $role = data_get($array, 0);
        $id = (int)data_get($array, 1);
        $page = (int)data_get($array, 2);
        $filter = data_get($array, 3, null);
        $user_con = UserTelegram::find($id);
        logger("con", [$user_con, $id,$data]);
        if ($user_con) {
            $fullName = $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
            $user_con->status = true;
            $user_con->change_menu = true;
            $user_con->role = $user_con->role ?: "customer";
            $user_con->update();
            cache()->forget("keyword_menu" . $object->key_cache_user . $user_con->id);
            $response_text = "$fullName اکانت کاربریش فعال شد\n\n ";
            $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
//                $response_text = "$fullName اکانت کاربریتان فعال شد\n\n ";
//                $object->service_user->telegram_services->sendMessage($user_con->id, $response_text);
            $object->service_user->message_menu = "$fullName اکانت کاربریتان فعال شد\n\n ";
            $object->service_user->menu($this->keyword_customer, $user_con->status, $user_con);
            $message_id = cache()->get("menu_List_user_" . $object->getUserId());
            $this->listUser($role,$object,$page, $message_id, $filter);
        }
    }

    public function active($object)
    {
        $data = str_replace('active_', '', $object->getData());
        $array = explode("_", $data);
        $role = data_get($array, 0);
        $id = (int)data_get($array, 1);
        $page = (int)data_get($array, 2);
        $filter = data_get($array, 3, null);
        $user_con = UserTelegram::withTrashed()->find($id);
        logger("con", [$user_con, $id, UserTelegram::withTrashed()->where("id", $id)->getQuery()]);
        if ($user_con) {
            $fullName = $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
            $user_con->status = true;
            $user_con->change_menu = true;
            $user_con->role = $user_con->role ?: "customer";
            $user_con->deleted_at = null;
            $user_con->update();
            cache()->forget("keyword_menu" . $object->key_cache_user . $user_con->id);
            $response_text = "$fullName اکانت کاربریش فعال شد\n\n ";
            $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
//                $response_text = "$fullName اکانت کاربریتان فعال شد\n\n ";
//                $object->service_user->telegram_services->sendMessage($user_con->id, $response_text);
            $object->service_user->message_menu = "$fullName اکانت کاربریتان فعال شد\n\n ";
            $object->service_user->menu($this->keyword_customer, $user_con->status, $user_con);
            $message_id = cache()->get("menu_List_user_" . $object->getUserId());
            $this->listUser($role,$object,$page, $message_id, $filter);
        }
    }

    public function reject($object)
    {
        $data = str_replace('reject_', '', $object->getData());
        $array = explode("_", $data);
        $role = data_get($array, 0);
        $id = (int)data_get($array, 1);
        $page = (int)data_get($array, 2);
        $filter = data_get($array, 3, null);

        $user_con = UserTelegram::find($id);
        logger("rej", [$user_con, $id]);

        if ($user_con) {
            $fullName = $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
            $user_con->status = false;
            $user_con->change_menu = true;

            $user_con->update();
            $response_text = "$fullName\n\n اکانت کاربریش غیر فعال شد ";
            $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);
//                $response_text = "$fullName اکانت کاربریتان غیر فعال شد \n\n ";
//                $object->service_user->telegram_services->sendMessage($user_con->id, $response_text);
            $object->service_user->message_menu = "$fullName اکانت کاربریتان غیر فعال شد \n\n ";
            $object->service_user->menu([], $user_con->status, $user_con);
            $message_id = cache()->get("menu_List_user_" . $object->getUserId());
            $this->listUser($role,$object,$page, $message_id, $filter);
        }
    }

    public function delete($object)
    {
        $data = str_replace('delete_', '', $object->getData());
        $array = explode("_", $data);
        $role = data_get($array, 0);
        $id = (int)data_get($array, 1);
        $page = (int)data_get($array, 2);
        $filter = data_get($array, 3, null);

        $user_con = UserTelegram::find($id);
        logger("rej", [$user_con, $id]);

        if ($user_con) {
            $fullName = $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
            $user_con->status = false;
            $user_con->change_menu = true;
            $user_con->update();
            $user_con->delete();
            $response_text = "$fullName\n\n اکانت کاربریش حذف شد ";
            $object->getTelegramServices()->sendMessage($object->getUserId(), $response_text);

            try {
                // خارج کردن کاربر از کانال
                $response = $object->telegram->kickChatMember(
                    [
                        'chat_id' => $object->bot->chanel_id,
                        'user_id' => $user_con->telegram_id,
                    ]);

                if ($response) {
                    logger("User has been successfully removed from the channel.");
                } else {
                    logger("Failed to remove user from the channel.");
                }
            } catch (\Exception $e) {
                logger("Error: " . $e->getMessage());
            }
            $object->service_user->message_menu = "اکانت کاربریش حذف شد";
            $object->service_user->menu([], $user_con->status, $user_con);
            $message_id = cache()->get("menu_List_user_" . $object->getUserId());
            $this->listUser($role,$object,$page, $message_id, $filter);

        }
    }

    public function getEditName($object)
    {
        $data = str_replace('edit_name_', '', $object->getData());
        $array = explode("_", $data);
        $role = data_get($array, 0);
        $id = (int)data_get($array, 1);
        $page = (int)data_get($array, 2);
        $filter = data_get($array, 3, null);

        $user_con = UserTelegram::find($id);
        logger("rej", [$user_con, $id]);

        if ($user_con) {
            $fullName = $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
            $message = " نام و نام خانوادگی :";
            $message .= $fullName;
            $message .= "\n\n";
            $message .= " نام و نام خانوادگی جدید وارد کنید ";
            $object->getTelegramServices()->sendMessage($object->getUserId(), $message);
            cache()->set($object->getKeyCache() . $object->getUserId(), "edit_name_done_" . $data);
        }
    }

    public function headCustomer($object)
    {
        $data = str_replace('head_customer_', '', $object->getData());
        $array = explode("_", $data);
        $id = (int)data_get($array, 1);

        $user_con = UserTelegram::find($id);
        logger("edit_mobile_done_", [$user_con, $id]);

        if ($user_con) {
            $message = "";
            if($user_con->customerUser) {
                $message .= "سرگروه فعلی:";
                $message .= $user_con->customerUser->fullName;
            }
            $message .= "\n\n";
            $message .= "شماره یا نام سرگروه را وارد کنید";
            $object->getTelegramServices()->sendMessage($object->getUserId(), $message);
            cache()->set($object->getKeyCache() . $object->getUserId(), "set_head_select_" . $data);
        }
    }

    public function selectHeadCustomer($object)
    {
        $data = str_replace('set_head_select_', '', $object->getMessageCache());

        $users = UserTelegram::query();
        $users->where(function ($query) use ($object) {
            $query->where("fullName", "like", "%" . $object->getMessage() . "%");
            $query->orWhere("mobile", "like", "%" . $object->getMessage() . "%");
        });
        $users = $users->get();
        $i =0;
        if($users->count()) {
            $text = "از میان همکاران زیر سرگروه مشتری را انتخاب کنید";
            foreach ($users as $user)
                $keyboard[$i++][] = ['text' => $user->fullName, "callback_data" => "set_head_done_" . $user->id . "_" . $data];

            $menu = $object->getTelegramServices()->MessageReplyMarkup($object->getTelegram(), $object->getUserId(), $text, $keyboard);
            cache()->set("set_head_done_" . $object->getUserId(), $menu);
            cache()->forget($object->getKeyCache() . $object->getUserId());

        }else{
            $object->getTelegramServices()->sendMessage($object->getUserId(), "کاربری یافت نشد ");

        }
    }

    public function setHeadCustomer($object)
    {
        $data = str_replace('set_head_done_', '', $object->getData());

        $array = explode("_", $data);
        $parent = (int)data_get($array, 0);
        $role = data_get($array, 1);
        $id = (int)data_get($array, 2);
        $page = (int)data_get($array, 3);
        $filter = data_get($array, 4, null);
        logger("data",[$array]);
        $user_con = UserTelegram::find($id);
        logger("data",[$user_con]);

        if($user_con)
        {
            $user_con["agent_id"] = $parent;
            $user_con->update();
            $message_id = cache()->get("menu_List_user_" . $object->getUserId());
            $this->listUser($role,$object,$page, $message_id, $filter);
            $action_id = cache()->get("set_head_done_".$object->getUserId());
            $object->getTelegramServices()->deleteMessage($object->getUserId(),$action_id);
            $message = "همکار";
            $message .= "\n\n";
            $user_parent = UserTelegram::find($parent);
            $message .= $user_parent->fullName;
            $message .= " برای مشتری ";
            $message .= $user_con->fullName;
            $message .= " انتخاب شد ";
            $object->getTelegramServices()->sendMessage($object->getUserId(), $message);

        }
    }
    public function getMemberShip($object)
    {
        $data = str_replace('get_membership_', '', $object->getData());

        $array = explode("_", $data);
        $id = (int)data_get($array, 1);
        $user_con = UserTelegram::find($id);
        if($user_con)
        {
            $message = "\n\n";
            if($user_con->memberShip) {
                $message .= "تاریخ اشتراک";
                $message .= "\n\n";
                $message .= toJalali($user_con->memberShip->expiration_date,"Y/m/d");
                $message .= " می باشد ";
                $message .= "\n\n";
            }
            $message .= "تاریخ اشتراک کاربر را وارد کنید";
            $object->getTelegramServices()->sendMessage($object->getUserId(), $message);
            cache()->set($object->getKeyCache() . $object->getUserId(), "set_membership_" . $data);

        }
    }
    public function setMemberShip($object)
    {
        $data = str_replace('set_membership_', '', $object->getMessageCache());

        $array = explode("_", $data);
        logger("data",[$array]);
        $role = data_get($array, 0);
        $id = (int)data_get($array, 1);
        $page = (int)data_get($array, 2);
        $filter = data_get($array, 3, null);
        $user_con = UserTelegram::find($id);
        if($user_con && isValidShamsiDate($object->getMessage()))
        {
            $user_con->memberShip()->delete();
            $date = toGregorian($object->getMessage(), "Y/m/d");

            $user_con->memberShip()->create([
                "user_id"=>$user_con->id,
                "expiration_date"=>$date,
            ]);
            $message = "تاریخ اشتراک";
            $message .= " ".$user_con->fullName;
            $message .= "\n\n";
            $message .= convertNumber(toJalali($date,"Y\m\d"));
            $message .= "\n\n";
            $message .= "تنظیم شد";

            $object->getTelegramServices()->sendMessage($object->getUserId(), $message);
            cache()->forget($object->getKeyCache() . $object->getUserId());

        }else{
            $message = "فرمت تاریخ اشتراک 1403/04/01";
            $object->getTelegramServices()->sendMessage($object->getUserId(), $message);
        }
    }

    public function setGetWordOnly($object)
    {
        $data = str_replace('set_word_only_', '', $object->getData());
        $array = explode("_", $data);
        logger("data",[$array]);
        $role = data_get($array, 0);
        $id = (int)data_get($array, 1);
        $page = (int)data_get($array, 2);
        $filter = data_get($array, 3, null);
        $user_con = UserTelegram::find($id);
        if($user_con)
        {
            $message = "\n\n";
            $user_con->set_word = false;
           $user_con->update();
            $message .= " کاربر ";
                $message .= "\n\n";
                $message .= $user_con->fullName;
            $message .= " از حالت مسدود لفظ آزد شد ";
            $message .= "\n\n";
            $object->getTelegramServices()->sendMessage($object->getUserId(), $message);
            $message_id = cache()->get("menu_List_user_" . $object->getUserId());
            $this->listUser($role,$object,$page, $message_id, $filter);
        }
    }
    public function setFreeActivity($object)
    {
        $data = str_replace('free_activity_', '', $object->getData());
        $array = explode("_", $data);
        logger("data",[$array]);
        $role = data_get($array, 0);
        $id = (int)data_get($array, 1);
        $page = (int)data_get($array, 2);
        $filter = data_get($array, 3, null);
        $user_con = UserTelegram::find($id);
        if($user_con)
        {
            $message = "\n\n";
            $user_con->set_word = true;
           $user_con->update();

                $message .= " کاربر ";
                $message .= $user_con->fullName;
            $message .= " نمی تواند لفظ بدهد ";
                $message .= "\n\n";
            $object->getTelegramServices()->sendMessage($object->getUserId(), $message);
            $message_id = cache()->get("menu_List_user_" . $object->getUserId());
            $this->listUser($role,$object,$page, $message_id, $filter);
        }
    }

    public function syncMobile($object)
    {
        $data = str_replace('sync_mobile_', '', $object->getData());
        $array = explode("_", $data);
        $id = (int)data_get($array, 1);

        $user_con = UserTelegram::find($id);
        logger("edit_mobile_done_", [$user_con, $id]);

        if ($user_con) {
            $message = "شماره تلفن فعلی شما";
            $message .= $user_con->mobile;
            $message .= "\n\n";
            $message .= "می باشد لطفا موبایل  جدید وارد کنید ";
            $object->getTelegramServices()->sendMessage($object->getUserId(), $message);
            cache()->set($object->getKeyCache() . $object->getUserId(), "say_mobile_new_" . $data);
        }
    }
    public function getMobile($object)
    {
        $data = str_replace('say_mobile_new_', '', $object->getMessageCache());
        $array = explode("_", $data);
        $id = (int)data_get($array, 1);
        $user_con = UserTelegram::find($id);
        $user_search = UserTelegram::where("mobile","like","%".$object->getMessage()."%")->first();
        logger("edit_mobile_done_", [$user_search, $id]);

        if ($user_search && $user_con) {
            $message = "می خواهید شما شماره تلفن";
            $message .= "\n\n";
            $message .= $user_con->mobile;
            $message .= "\n\n";
            $message .= " با شماره تلفن  ";
            $message .= $user_search->mobile;
            $message .= "\n\n";
            $message .= " جایگزین کنید؟  ";

            $keyboard[0] = [
                ['text' => "  تایید  ", 'callback_data' => "say_mobile_action_accept_".$user_search->id."_".$user_con->id],
                ['text' => "  رد  ", 'callback_data' => "say_mobile_action_reject_".$user_search->id."_".$user_con->id],
            ];
            $menu = $object->getTelegramServices()->MessageReplyMarkup($object->getTelegram(), $object->getUserId(), $message, $keyboard);

            cache()->set("say_mobile_new_".$object->getUserId(),$menu);
            cache()->set($object->getKeyCache() . $object->getUserId(), "say_mobile_new_" . $data);
        }else{
            $object->getTelegramServices()->sendMessage($object->getUserId(), "شماره تلفن ارسال شده در لیست کاربران ما نیست");

        }
    }

    public function setMobileNew($object)
    {
        $data = str_replace('say_mobile_action_', '', $object->getData());

        $array = explode("_", $data);
        $status = data_get($array, 0);
        $new_user = (int)data_get($array, 1);
        $old_user = (int)data_get($array, 2);
        $action_id = cache()->get("say_mobile_new_".$object->getUserId());
        if($status == "reject"){
            $object->getTelegramServices()->deleteMessage($object->getUserId(),$action_id);
            cache()->forget("say_mobile_new_".$object->getUserId());
            cache()->forget($object->getKeyCache() . $object->getUserId());
            return true;
        }
        $user_new = UserTelegram::find($new_user);
        $user_old = UserTelegram::find($old_user);
        if($user_new && $user_old)
        {
            $message = "کاربر";
            $message .= "\n\n";
            $message .= $user_new->mobile;
            $message .= "جایگزین ";
            $message .= "\n\n";

            $message .= $user_old->mobile;
            $message .= "\n\n";

            $message .= "  شد ";
            $user_old->telegram_id = $user_new->telgram_id;
            $user_old->mobile = $user_new->mobile;


            $user_new->forceDelete();
            $user_old->update();
            $object->getTelegramServices()->sendMessage($object->getUserId(), $message);
            $object->getTelegramServices()->deleteMessage($object->getUserId(),$action_id);
            $message_id = cache()->get("menu_List_user_" . $object->getUserId());
            $object->getTelegramServices()->deleteMessage($object->getUserId(),$message_id);
            cache()->forget("say_mobile_new_".$object->getUserId());
            cache()->forget($object->getKeyCache() . $object->getUserId());
        }
    }


    public function setName($object)
    {
        $data = str_replace('edit_name_done_', '', $object->getMessageCache());
        $array = explode("_", $data);
        logger("array", [$array]);
        $role = data_get($array, 0);
        $id = (int)data_get($array, 1);
        $page = (int)data_get($array, 2);
        $filter = data_get($array, 3, null);

        $user_con = UserTelegram::find($id);
        logger("rej", [$user_con, $id]);

        if ($user_con) {
            $user_con->fullName = $object->getMessage();
            $user_con->update();
            $message = $user_con->fullName;
            $message .= "\n\n";
            $message .= "نام و نام خانوادگی بروزرسانی شد ";
            $object->getTelegramServices()->sendMessage($object->getUserId(), $message);
        }
        $message_id = cache()->get("menu_List_user_" . $object->getUserId());
        $this->listUser($role,$object,$page, $message_id, $filter);
        cache()->forget($object->getKeyCache() . $object->getUserId());
    }

    public function findUser($object)
    {
        $this->listUser(null,$object,1, null, $object->getMessage());

        cache()->forget($object->getKeyCache() . $object->getUserId());
    }

    public function listColleague($object)
    {
        $this->listUser("colleague",$object,1,);

    }
    public function listCustomer($object)
    {
        $this->listUser("customer",$object,1,);

    }

    public function getMessageGroup($object)
    {
        $message ="پیامی که می خواهید برای کاربان سیستم ارسال کنید وارد کنید";
            $object->getTelegramServices()->sendMessage($object->getUserId(), $message);
            cache()->set($object->getKeyCache() . $object->getUserId(), "send_message_group");

    }

    public function setMessageGroup($object)
    {
        $users = UserTelegram::get();
        foreach ($users as $user) {
            $object->service_user->telegram_services->sendMessage($user->telegram_id, $object->getMessage());

        }
        cache()->forget($object->getKeyCache() . $object->getUserId());

    }

    /**
     * @return void
     */
    private function listUser($type=null,$object,$page = 1, $message_id = null, $filter = null)
    {
        if($type == "colleague") {
            $text = "\n\nلیست  همکاران";
            $text .= "\n\n";
            $text .= "با کلیک بر\xE2\x9D\x8C کاربر غیر فعال شده و با کلیک بر \xE2\x9C\x85 کاربرفعال گردید در صورت کلیک بر روی اسم شخص نوع کاربر از مشتری به همکار و به لیست مشتری انتقال می یابد ";
            $users = UserTelegram::with("customerUser")->withTrashed()->where("role", "colleague");
        }elseif($type == "customer") {
            $text = "\n\nلیست  مشتریان";
            $text .= "\n\n";
            $text .= "با کلیک بر\xE2\x9D\x8C کاربر غیر فعال شده و با کلیک بر \xE2\x9C\x85 کاربرفعال گردید در صورت کلیک بر روی اسم شخص نوع کاربر از همکار به مشتری و به لیست همکار انتقال می یابد ";
            $users = UserTelegram::with("customer")->withTrashed()->where("role", "customer");
        }else{
            $text = "\n\nلیست  کاربران";
            $text .= "\n\n";
            $text .= "با کلیک بر\xE2\x9D\x8C کاربر غیر فعال شده و با کلیک بر \xE2\x9C\x85 کاربرفعال گردید در صورت کلیک بر روی اسم شخص نوع کاربر از همکار به مشتری و  از مشتری به همکار انتقال می یابد ";
            $users = UserTelegram::withTrashed();
        }
        if ($filter) {
            $users->where(function ($query) use ($filter) {
                $query->where("fullName", "like", "%" . $filter . "%");
                $query->orWhere("mobile", "like", "%" . $filter . "%");
            });
        }
        $users = $users->simplePaginate(4, ['*'], 'page', $page);
        logger("users",[$users]);
        $page = $users->currentPage();
        $next = $users->nextPageUrl() ? (int)str_replace("?page=", "", strstr($users->nextPageUrl(), "?page=")) : null;
        $pre = $users->previousPageUrl() ? (int)str_replace("?page=", "", strstr($users->previousPageUrl(), "?page=")) : null;
        $keyboard = [];
        $i = 0;

        $users->each(function ($user) use (&$keyboard, &$i, $page, $filter,$object) {
            $text = $user->fullName ?: $user->first_name . " " . $user->last_name;
            $key_i = $user->role ."_".$user->id . "_" . $page;
            if ($filter)
                $key_i .= "_" . $filter;
            if($user->role == "customer" && $user->customer)
                $text .= " - مشتری ".$user->customer->fullName." ";
            elseif($user->role == "colleague")
                $text .= "(همکار)";
            $keyboard[$i++] = [
                ['text' => "  $text  ", 'callback_data' => "set_worker_".$key_i],
            ];

            $array = [
                ['text' => "\xE2\x9C\x8F\xF0\x9F\x91\xA8", 'callback_data' => 'edit_name_' . $key_i],
                ['text' => "\xE2\x86\x94", 'callback_data' => 'sync_mobile_' . $key_i],
                ['text' => "\xF0\x9F\x93\x9D", 'callback_data' => 'get_membership_' . $key_i],
            ];

            if($user->role == "customer")
                $array[] =  ['text' => "\xF0\x9F\x91\xA4", 'callback_data' => 'head_customer_' . $key_i];


            if($user->set_word)
                $array[] = ['text' => "\xE2\x9B\x94", 'callback_data' => 'set_word_only_' . $key_i];
            else
                $array[] = ['text' => "\xE2\x98\x91", 'callback_data' => 'free_activity_' . $key_i];

            if ($user->deleted_at)
                $array[] = ['text' => "\xF0\x9F\x86\x97", 'callback_data' => 'active_' . $key_i];
            else {
                $array[] = ['text' => "\xF0\x9F\x9A\xAF", 'callback_data' => 'delete_' . $key_i];
                if ($user->status) {
                    $array[] = ['text' => "\xE2\x9D\x8C", 'callback_data' => 'reject_' . $key_i];
                    if(!$object->getTelegramServices()->checkMember(data_get($object,"bot.chanel_id"),$user->telegram_id))
                        $array[] = ['text' => "\xE2\x9E\x95	\xF0\x9F\x8C\xB3", 'callback_data' => 'add_chanel_' . $key_i];
                } else
                    $array[] = ['text' => "\xE2\x9C\x85 ", 'callback_data' => 'confirm_' . $key_i];

            }

            $keyboard[$i++] = $array;
        });
        if ($pre)
            $keyboard[$i][] = ['text' => "قبلی", "callback_data" => "pre_" . $pre . ($type ? "_" . $type :  "_" ). ($filter ? "_" . $filter : null)];
        if ($next)
            $keyboard[$i][] = ['text' => "بعدی", "callback_data" => "next_" . $next . ($type ? "_" . $type :  "_" ). ($filter ? "_" . $filter : null)];

        if ($message_id)
            $object->getTelegramServices()->editMessageTextAndInlineKeyboard($object->getUserId(), $message_id, $text, $keyboard);
        else {
            $object->getTelegramServices()->menu_key = "menu_List_user_";
            $menu = $object->getTelegramServices()->MessageReplyMarkup($object->getTelegram(), $object->getUserId(), $text, $keyboard);
        }
    }
}
