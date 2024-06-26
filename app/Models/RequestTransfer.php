<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use MongoDB\Laravel\Eloquent\HybridRelations;


class RequestTransfer extends Model
{
    use SoftDeletes;
    use HasFactory;
    use HybridRelations;

    protected $table = 'request_transfer';

    protected $fillable = [
        "request_id",
        "transfer_id",
        "number",
        "price",
        "status",
        "type"
    ];


    public function transfer()
    {
        return $this->belongsTo(Transfer::class, 'transfer_id',"id");
    }
    public function userRequest()
    {
        return $this->belongsTo(UserTelegram::class,"request_id");
    }

    public function dailyRequest()
    {
        return $this->hasOne(DailyRequestTransfer::class,"request_id","id");

    }

}
