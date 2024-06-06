<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;

class Transfer extends Model
{
    use SoftDeletes;
    protected $connection = "mongodb";
    protected $collection = 'transfer_collection';

    const STATUS_PENDING = "pending";
    const STATUS_ACTIVE = "active";
    const STATUS_ACTIVE_DO = "active_do";
    const STATUS_ACTIVE_DONE = "active_done";
    const STATUS_DEACTIVATE = "deactivate";

    protected $fillable = [
        "user_id",
        "type",
        "number",
        "price",
        "status",
        "message",
        "message_id",
        "date"
    ];

    use HasFactory;


}
