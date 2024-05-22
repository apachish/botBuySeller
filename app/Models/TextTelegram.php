<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model as EloquentModel;

class TextTelegram extends EloquentModel
{
    use HasFactory;

    protected $connection = "mongodb";
    protected $collection = 'text_telegram_collection';


    protected $fillable = [
        "update_id",
        "message_id",
        "user_telegram_id",
        "text",
        "data"
    ];
}
