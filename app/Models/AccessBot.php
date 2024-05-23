<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class AccessBot extends Model
{

    use HasFactory;


    protected $connection = "mongodb";
    protected $collection = 'access_bot_collection';


    protected $fillable = [
        "bot_id",
        "user_id",
        "type"
    ];
}
