<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model as EloquentModel;

class SupportTelegram extends EloquentModel
{
    use HasFactory;

    protected $connection = "mongodb";
    protected $collection = 'support_telegram_collection';


    protected $fillable = [
        "update_id",
        "message_id",
        "user_telegram_id",
        "text",
        "data",
        "replay"
    ];

    public function user()
    {
        return $this->belongsTo(UserTelegram::class,"user_telegram_id","id");
    }
}
