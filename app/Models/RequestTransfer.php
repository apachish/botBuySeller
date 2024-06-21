<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class RequestTransfer extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $collection = 'request_transfer';

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
        return $this->belongsTo(Transfer::class);
    }

}
