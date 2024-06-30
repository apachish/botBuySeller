<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model as EloquentModel;

class WordTelegram extends EloquentModel
{
    use HasFactory;

    protected $connection = "mongodb";
    protected $collection = 'word_telegram_collection';

    const STATUS_PENDING = "pending";
    const STATUS_ACCEPT = "accept";
    const STATUS_REJECT = "reject";


    protected $fillable = [
        "message_id",
        "user_id",
        "message",
        "status",
        "type",
        "number",
        "message_request",
        "message_request_me",
        "price",
        "word",
        "date",
        "description"
    ];
}
