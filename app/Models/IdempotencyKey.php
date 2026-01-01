<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'key',
        'request_hash',
        'order_id',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
