<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class DailyRequestTransfer extends Model
{

    use HasFactory;


    protected $connection = "mongodb";
    protected $collection = 'daily_transfer_request_collection';


    protected $fillable = [
        "request_id",
        "transfer_id",
        "type",
        "use_day"
    ];
}
