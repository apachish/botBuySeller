<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\HybridRelations;
use MongoDB\Laravel\Eloquent\Model;

class DailyRequestTransfer extends Model
{

    use HasFactory;
    use HybridRelations;


    protected $connection = "mongodb";
    protected $collection = 'daily_transfer_request_collection';


    protected $fillable = [
        "request_id",
        "buyer_id",
        "seller_id",
        "use_day"
    ];
}
