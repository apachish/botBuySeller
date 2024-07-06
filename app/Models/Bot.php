<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\HybridRelations;
use MongoDB\Laravel\Eloquent\Model;

class Bot extends Model
{

    use HasFactory;
    use HybridRelations;

    protected $connection = "mongodb";
    protected $collection = 'bot_collection';


    protected $fillable = [
        'title',
        'token',
        'user_count',
        'pending_update_count',
        'max_connections',
        'last_error_date',
        'last_error_message',
        'allowed_updates',
        'url',
        'category_id',
        'created_by',
        'description',
        'chanel_id',
        "chanel_link",
        "accounting",
        "word",
        "contact",
    ];

    public function accessBot()
    {
        return $this->hasMany(AccessBot::class,"id","bot_id");
    }
}
