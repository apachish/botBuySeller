<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;

class CustomerUser extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $connection = "mongodb";
    protected $collection = 'customer_user_collection';

    protected $fillable = [
        "fullName",
        "mobile",
        "user_id",
        "limit",
        "status"
    ];




}
