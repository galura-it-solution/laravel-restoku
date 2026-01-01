<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentOrderRepository implements OrderRepository
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Order::query()->with(['items.menu', 'table', 'assignedUser']);

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            if ($search !== '') {
                $query->where(function ($builder) use ($search) {
                    if (ctype_digit($search)) {
                        $builder->orWhere('id', (int) $search);
                    }
                    $builder->orWhereHas('table', function ($tableQuery) use ($search) {
                        $tableQuery->where('name', 'like', '%' . $search . '%');
                    });
                });
            }
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['restaurant_table_id'])) {
            $query->where('restaurant_table_id', $filters['restaurant_table_id']);
        }

        if (!empty($filters['updated_after'])) {
            $query->where('updated_at', '>', $filters['updated_after']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function paginateForPolling(array $filters): LengthAwarePaginator
    {
        $query = Order::query()->select([
            'id',
            'restaurant_table_id',
            'status',
            'notified_at',
            'updated_at',
        ]);

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            if ($search !== '') {
                $query->where(function ($builder) use ($search) {
                    if (ctype_digit($search)) {
                        $builder->orWhere('id', (int) $search);
                    }
                    $builder->orWhereHas('table', function ($tableQuery) use ($search) {
                        $tableQuery->where('name', 'like', '%' . $search . '%');
                    });
                });
            }
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['restaurant_table_id'])) {
            $query->where('restaurant_table_id', $filters['restaurant_table_id']);
        }

        if (!empty($filters['updated_after'])) {
            $query->where('updated_at', '>', $filters['updated_after']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 20);
    }

    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function findForUpdate(int $id): Order
    {
        return Order::whereKey($id)->lockForUpdate()->firstOrFail();
    }

    public function findWithRelations(int $id): Order
    {
        return Order::with(['items.menu', 'table', 'assignedUser'])->findOrFail($id);
    }

    public function findActiveByTableForUpdate(int $tableId): ?Order
    {
        return Order::where('restaurant_table_id', $tableId)
            ->whereIn('status', Order::ACTIVE_STATUSES)
            ->lockForUpdate()
            ->first();
    }

    public function updateStatus(Order $order, string $status): Order
    {
        $order->update(['status' => $status]);

        return $order;
    }

    public function hasActiveByTable(int $tableId): bool
    {
        return Order::where('restaurant_table_id', $tableId)
            ->whereIn('status', Order::ACTIVE_STATUSES)
            ->exists();
    }

    public function getQueueNumber(Order $order): ?int
    {
        if (!in_array($order->status, Order::ACTIVE_STATUSES, true)) {
            return null;
        }

        if (!$order->created_at) {
            return null;
        }

        return Order::whereIn('status', Order::ACTIVE_STATUSES)
            ->where(function ($query) use ($order) {
                $query->where('created_at', '<', $order->created_at)
                    ->orWhere(function ($nested) use ($order) {
                        $nested->where('created_at', $order->created_at)
                            ->where('id', '<=', $order->id);
                    });
            })
            ->count();
    }

    public function getCurrentProcessingQueueNumber(): ?int
    {
        $currentProcessing = Order::where('status', Order::STATUS_PROCESSING)
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();

        if (!$currentProcessing) {
            return null;
        }

        return $this->getQueueNumber($currentProcessing);
    }
}
