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

    protected $fillable = [
        "user_id",
        "type",
        "number",
        "price",
        "status"
    ];

    use HasFactory;


}
