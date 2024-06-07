<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;

class RequestTransfer extends Model
{
    use SoftDeletes;
    protected $connection = "mongodb";
    protected $collection = 'request_transfer_collection';

    protected $fillable = [
        "request_id",
        "transfer_id",
        "number",
        "price",
        "status",
        "remittance_number"
    ];

    use HasFactory;

    public function transfer()
    {
        return $this->belongsTo(Transfer::class,"transfer_id","id");
    }

}
