<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantTable extends Model
{
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_OCCUPIED = 'occupied';
    public const STATUSES = [self::STATUS_AVAILABLE, self::STATUS_OCCUPIED];

    protected $table = 'restaurant_tables';

    protected $fillable = [
        'name',
        'status',
    ];
}
