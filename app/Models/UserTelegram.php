<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class UserTelegram extends Model
{
    protected $fillable = [
        "is_bot",
        "id",
        "first_name",
        "last_name",
        "mobile",
        "username",
        "language_code",
        "is_premium",
        "can_join_groups",
        "can_read_all_group_messages",
        "supports_inline_queries",
    ];

    use HasFactory;

    public function questions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Question::class,'answer_question_users')->withTimestamps()->withPivot(["answer_id","question_id"]);
    }

    public function answers()
    {
        return $this->belongsToMany(Answer::class,'answer_question_users','user_id','id')->withTimestamps()->withPivot(["answer_id","question_id"]);
    }
}
