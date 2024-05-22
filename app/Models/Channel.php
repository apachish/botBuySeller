<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Channel extends Model
{

    use HasFactory;

    protected $connection = "mongodb";
    protected $collection = 'channel_collection';

    protected $fillable = [
        "channel_id",
        "title",
        "user_count",
        "created_by"
    ];
}
