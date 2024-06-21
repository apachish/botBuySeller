<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserTelegram extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $table = 'user_telegram';
    protected $fillable = [
        "is_bot",
        "telegram_id",
        "first_name",
        "last_name",
        "fullName",
        "mobile",
        "username",
        "language_code",
        "is_premium",
        "can_join_groups",
        "can_read_all_group_messages",
        "supports_inline_queries",
        "status",
        "verify_two",
        "agent_id",
        "role",
        "change_menu",
        "link_invite",
        "accept_rule"
    ];



    public function userTradeAccess()
    {
        return $this->hasMany(UserTradeAccess::class,"user_id","id");
    }

    public function customerUser()
    {
        return $this->hasOne(UserTelegram::class,"agent_id","id");
    }

    public function customer()
    {
        return $this->hasOne(UserTelegram::class,"id","agent_id");
    }

    public function memberShip()
    {
        return $this->hasOne(Membership::class,"user_id");
    }

    public function customerUsers()
    {
        return $this->hasMany(UserTelegram::class,"user_id","id");
    }


}
