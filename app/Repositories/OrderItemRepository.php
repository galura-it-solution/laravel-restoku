<?php

namespace App\Repositories;

use App\Models\OrderItem;

interface OrderItemRepository
{
    public function create(array $data): OrderItem;
}
