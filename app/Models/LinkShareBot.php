<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class LinkShareBot extends Model
{

    use HasFactory;


    protected $connection = "mongodb";
    protected $collection = 'link_share_bot_collection';


    protected $fillable = [
        "bot_id",
        "user_id",
        "url",
        "status",
        "click"
    ];
}
