<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepository
{
    public function paginate(array $filters): LengthAwarePaginator;
    public function paginateForPolling(array $filters): LengthAwarePaginator;
    public function create(array $data): Order;
    public function findForUpdate(int $id): Order;
    public function findWithRelations(int $id): Order;
    public function findActiveByTableForUpdate(int $tableId): ?Order;
    public function updateStatus(Order $order, string $status): Order;
    public function hasActiveByTable(int $tableId): bool;
    public function getQueueNumber(Order $order): ?int;
    public function getCurrentProcessingQueueNumber(): ?int;
}
