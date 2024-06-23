<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transfer extends Model
{
    use SoftDeletes;
    protected $table = 'transfer';

    const STATUS_PENDING = "pending";
    const STATUS_ACTIVE = "active";
    const STATUS_ACTIVE_DO = "active_do";
    const STATUS_ACTIVE_DONE = "active_done";
    const STATUS_DEACTIVATE = "deactivate";

    public static function getStatus(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_ACTIVE,
            self::STATUS_ACTIVE_DO,
            self::STATUS_ACTIVE_DONE,
            self::STATUS_DEACTIVATE,
        ];
    }

    protected $fillable = [
        "user_id",
        "type",
        "number",
        "price",
        "status",
        "message",
        "description",
        "message_id",
        "date",
        "message_request",
        "message_request_me"
    ];

    use HasFactory;


    public function user()
    {
        return $this->belongsTo(UserTelegram::class,"user_id","id");
    }

    public function requestTransfer()
    {
        return $this->belongsTo(RequestTransfer::class,"transfer_id");
    }
}
