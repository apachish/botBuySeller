<?php

namespace App\Models;


use MongoDB\Laravel\Eloquent\Model;

class MessageWordAccounting extends Model
{
    protected $connection = "mongodb";
    protected $collection = 'messages_word_collection';

    protected $fillable = [
      "telegram_id",
      "bot_id",
      "status",
      "text",
      "message_id",
      "transfer_id",
      "word_id",
        "error_text"
    ];
    const STATUS_PENDING = "pending";
    const STATUS_RECEIVE = "receive";
    const STATUS_FAILED = "failed";

}
