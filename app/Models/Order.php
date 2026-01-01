<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE = 'done';
    public const ACTIVE_STATUSES = [self::STATUS_PENDING, self::STATUS_PROCESSING];
    public const STATUS_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_PROCESSING],
        self::STATUS_PROCESSING => [self::STATUS_DONE],
        self::STATUS_DONE => [],
    ];
    public const STATUS_UPDATE_OPTIONS = [self::STATUS_PROCESSING, self::STATUS_DONE];

    protected $fillable = [
        'user_id',
        'restaurant_table_id',
        'status',
        'note',
        'assigned_to',
        'assigned_to_user_id',
        'subtotal',
        'service_charge',
        'tax',
        'total_price',
        'notified_at',
    ];

    protected $casts = [
        'total_price' => 'integer',
        'subtotal' => 'integer',
        'service_charge' => 'integer',
        'tax' => 'integer',
        'notified_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function table()
    {
        return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }
}
