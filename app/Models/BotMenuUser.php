<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class BotMenuUser extends Model
{

    use HasFactory;


    protected $connection = "mongodb";
    protected $collection = 'bot_menu_user_collection';


    protected $fillable = [
        "bot_id",
        "user_id",
        "menu_id"
    ];

    public function user()
    {
        return $this->belongsTo(UserTelegram::class,"user_id");
    }

    public function bot()
    {
        return $this->belongsTo(Bot::class,"bot_id");
    }
}
