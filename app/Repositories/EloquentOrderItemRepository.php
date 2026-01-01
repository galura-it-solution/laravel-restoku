<?php

namespace App\Repositories;

use App\Models\OrderItem;

class EloquentOrderItemRepository implements OrderItemRepository
{
    public function create(array $data): OrderItem
    {
        return OrderItem::create($data);
    }
}
