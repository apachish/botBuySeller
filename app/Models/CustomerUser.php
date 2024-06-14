<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\HybridRelations;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;

class CustomerUser extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HybridRelations;

    protected $connection = "mongodb";
    protected $collection = 'customer_user_collection';

    protected $fillable = [
        "fullName",
        "mobile",
        "user_id",
        "limit",
        "status"
    ];

    public function user()
    {
        return $this->belongsTo(UserTelegram::class,"mobile","mobile");
    }

    public function headCustomer()
    {
        return $this->belongsTo(UserTelegram::class,"user_id","id");
    }

    public function parentCustomer()
    {
        return $this->belongsTo(UserTelegram::class,"id","user_id");
    }




}
