<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Bot extends Model
{

    use HasFactory;


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
        'description'
    ];

    public function accessBot()
    {
        return $this->hasMany(AccessBot::class,"bot_id");
    }
}
