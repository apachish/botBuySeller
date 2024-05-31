<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;

class UserTelegram extends Model
{
    use SoftDeletes;
    protected $connection = "mongodb";
    protected $collection = 'user_telegram_collection';

    protected $fillable = [
        "is_bot",
        "id",
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
        "role"
    ];

    use HasFactory;


    public function userTradeAccess()
    {
        return $this->hasMany(UserTradeAccess::class,"user_id");
    }



}
