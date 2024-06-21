<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTradeAccess extends Model
{
    protected $table = 'user_trade_access';

    protected $fillable = [
        "user_id",
        "user_trade_id",
        "limit_access"
    ];

    use HasFactory;


}
