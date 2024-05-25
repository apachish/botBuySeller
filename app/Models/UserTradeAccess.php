<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;

class UserTradeAccess extends Model
{
    use SoftDeletes;
    protected $connection = "mongodb";
    protected $collection = 'user_trade_access_collection';

    protected $fillable = [
        "user_id",
        "user_trade_id",
        "limit"
    ];

    use HasFactory;


}
