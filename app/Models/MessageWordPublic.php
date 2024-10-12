<?php

namespace App\Models;


use MongoDB\Laravel\Eloquent\Model;

class MessageWordPublic extends Model
{
    protected $connection = "mongodb";
    protected $collection = 'messages_word_public_collection';

    protected $fillable = [
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
