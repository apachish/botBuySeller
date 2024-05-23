<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class UserTelegram extends Model
{
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
    ];

    use HasFactory;


}
