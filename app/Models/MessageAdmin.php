<?php

namespace App\Models;


use MongoDB\Laravel\Eloquent\Model;

class MessageAdmin extends Model
{
    protected $connection = "mongodb";
    protected $collection = 'messages_admin_collection';

    protected $fillable = [
        "user_id",
        "text",
        "message_id",
    ];
}
