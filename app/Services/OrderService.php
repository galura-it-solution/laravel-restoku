<?php

namespace App\Services;

use App\Models\Order;
use App\Models\RestaurantTable;
use App\Repositories\IdempotencyKeyRepository;
use App\Repositories\MenuRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\OrderRepository;
use App\Repositories\RestaurantTableRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private OrderRepository $orders,
        private OrderItemRepository $orderItems,
        private MenuRepository $menus,
        private RestaurantTableRepository $tables,
        private IdempotencyKeyRepository $idempotencyKeys
    ) {
    }

    public function list(array $filters): LengthAwarePaginator
    {
        return $this->orders->paginate($filters);
    }

    public function listForPolling(array $filters): LengthAwarePaginator
    {
        return $this->orders->paginateForPolling($filters);
    }

    public function create(int $userId, array $payload, string $idempotencyKey): Order
    {
        $requestHash = hash('sha256', json_encode($payload));

        return DB::transaction(function () use ($userId, $payload, $idempotencyKey, $requestHash) {
            $idempotency = $this->idempotencyKeys->findForUpdate($userId, $idempotencyKey);
            if (!$idempotency) {
                try {
                    $idempotency = $this->idempotencyKeys->create([
                        'user_id' => $userId,
                        'key' => $idempotencyKey,
                        'request_hash' => $requestHash,
                    ]);
                } catch (QueryException $e) {
                    if ($e->getCode() !== '23000') {
                        throw $e;
                    }

                    $idempotency = $this->idempotencyKeys->findForUpdate($userId, $idempotencyKey);

                    if (!$idempotency) {
                        throw $e;
                    }
                }
            }

            if ($idempotency->request_hash !== $requestHash) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Idempotency key sudah digunakan dengan payload berbeda.',
                ], 409));
            }

            if ($idempotency->order_id) {
                return $this->orders->findWithRelations($idempotency->order_id);
            }

            $table = $this->tables->findForUpdate($payload['restaurant_table_id']);
            $activeOrder = $this->orders->findActiveByTableForUpdate($table->id);

            if ($activeOrder && $activeOrder->user_id !== $userId) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Meja sudah memiliki order aktif.',
                ], 409));
            }

            $menuIds = collect($payload['items'])->pluck('menu_id')->all();
            $menus = $this->menus->findByIdsForUpdate($menuIds);

            $subtotal = 0;
            $items = [];
            $requestedQuantities = [];

            foreach ($payload['items'] as $item) {
                $menu = $menus[$item['menu_id']];
                if (!$menu->is_active) {
                    throw new HttpResponseException(response()->json([
                        'message' => 'Menu tidak tersedia.',
                    ], 409));
                }
                $requestedQuantities[$menu->id] = ($requestedQuantities[$menu->id] ?? 0) + $item['quantity'];

                if ($menu->stock !== null && $menu->stock < $requestedQuantities[$menu->id]) {
                    throw new HttpResponseException(response()->json([
                        'message' => 'Stok menu tidak mencukupi.',
                    ], 409));
                }
                $lineSubtotal = $menu->price * $item['quantity'];
                $subtotal += $lineSubtotal;

                $items[] = [
                    'menu_id' => $menu->id,
                    'menu_name' => $menu->name,
                    'menu_description' => $menu->description,
                    'quantity' => $item['quantity'],
                    'price' => $menu->price,
                    'subtotal' => $lineSubtotal,
                    'notes' => $item['notes'] ?? null,
                ];

            }

            $serviceChargePercent = (int) config('restoku.service_charge_percent', 0);
            $taxPercent = (int) config('restoku.tax_percent', 0);
            $serviceCharge = intdiv($subtotal * $serviceChargePercent, 100);
            $tax = intdiv($subtotal * $taxPercent, 100);
            $total = $subtotal + $serviceCharge + $tax;

            $order = $this->orders->create([
                'user_id' => $userId,
                'restaurant_table_id' => $table->id,
                'status' => Order::STATUS_PENDING,
                'note' => $payload['note'] ?? null,
                'subtotal' => $subtotal,
                'service_charge' => $serviceCharge,
                'tax' => $tax,
                'total_price' => $total,
            ]);

            foreach ($items as $item) {
                $item['order_id'] = $order->id;
                $this->orderItems->create($item);
            }

            foreach ($requestedQuantities as $menuId => $quantity) {
                $menu = $menus[$menuId];
                if ($menu->stock === null) {
                    continue;
                }
                $newStock = $menu->stock - $quantity;
                $menu->update([
                    'stock' => $newStock,
                    'is_active' => $newStock > 0 ? $menu->is_active : false,
                ]);
            }

            $this->tables->updateStatus($table->id, RestaurantTable::STATUS_OCCUPIED);
            $this->idempotencyKeys->update($idempotency, ['order_id' => $order->id]);

            Log::info('Order created', ['order_id' => $order->id, 'user_id' => $userId]);

            return $this->orders->findWithRelations($order->id);
        });
    }

    public function updateStatus(Order $order, string $nextStatus): Order
    {
        return DB::transaction(function () use ($order, $nextStatus) {
            $order = $this->orders->findForUpdate($order->id);

            if (!in_array($nextStatus, Order::STATUS_TRANSITIONS[$order->status] ?? [], true)) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Transisi status tidak valid.',
                ], 409));
            }

            if (in_array($nextStatus, [Order::STATUS_PROCESSING, Order::STATUS_DONE], true)
                && !$order->assigned_to_user_id) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Order belum di-assign.',
                ], 409));
            }

            $this->orders->updateStatus($order, $nextStatus);

            if ($nextStatus === Order::STATUS_DONE) {
                $activeOrder = $this->orders->hasActiveByTable($order->restaurant_table_id);

                if (!$activeOrder) {
                    $this->tables->updateStatus($order->restaurant_table_id, RestaurantTable::STATUS_AVAILABLE);
                }
            }

            Log::info('Order status updated', ['order_id' => $order->id, 'status' => $nextStatus]);

            return $this->orders->findWithRelations($order->id);
        });
    }

    public function assign(Order $order, int $assignedUserId): Order
    {
        $order->update(['assigned_to_user_id' => $assignedUserId]);

        return $this->orders->findWithRelations($order->id);
    }

    public function withQueueMeta(Order $order): Order
    {
        $order->setAttribute('queue_number', $this->orders->getQueueNumber($order));
        $order->setAttribute(
            'current_processing_queue_number',
            $this->orders->getCurrentProcessingQueueNumber()
        );

        return $order;
    }
}
