<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateOrderRequest;
use App\Http\Requests\Api\V1\AssignOrderRequest;
use App\Http\Requests\Api\V1\UpdateOrderStatusRequest;
use App\Http\Resources\Api\V1\OrderPollResource;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService)
    {
        $this->authorizeResource(Order::class, 'order');
    }

    public function index(Request $request)
    {
        $filters = $request->only([
            'search',
            'status',
            'restaurant_table_id',
            'updated_after',
            'per_page',
        ]);

        if (!$request->user()->isStaff()) {
            $filters['user_id'] = $request->user()->id;
        }

        $orders = $this->orderService->list($filters);

        return OrderResource::collection($orders);
    }

    public function poll(Request $request)
    {
        $filters = $request->only([
            'search',
            'status',
            'restaurant_table_id',
            'updated_after',
            'per_page',
        ]);

        if (!$request->user()->isStaff()) {
            $filters['user_id'] = $request->user()->id;
        }

        $perPage = (int)($filters['per_page'] ?? 20);
        $filters['per_page'] = max(1, min($perPage, 50));

        $orders = $this->orderService->listForPolling($filters);

        return OrderPollResource::collection($orders);
    }

    public function store(CreateOrderRequest $request)
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey) {
            throw ValidationException::withMessages([
                'idempotency_key' => ['Idempotency-Key wajib diisi.'],
            ]);
        }

        $order = $this->orderService->create(
            $request->user()->id,
            $request->validated(),
            $idempotencyKey
        );

        return new OrderResource($this->orderService->withQueueMeta($order));
    }

    public function show(Order $order)
    {
        $order = $this->orderService->withQueueMeta(
            $order->load(['items.menu', 'table', 'assignedUser'])
        );

        return new OrderResource($order);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order)
    {
        $this->authorize('update', $order);

        $order = $this->orderService->updateStatus($order, $request->validated()['status']);

        return new OrderResource($this->orderService->withQueueMeta($order));
    }

    public function assign(AssignOrderRequest $request, Order $order)
    {
        $this->authorize('update', $order);

        $order = $this->orderService->assign(
            $order,
            $request->validated()['assigned_to_user_id']
        );

        return new OrderResource($this->orderService->withQueueMeta($order));
    }

    public function stream(Request $request)
    {
        $this->authorize('viewAny', Order::class);
        $user = $request->user();

        return response()->stream(function () use ($user) {
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', false);
            @ini_set('implicit_flush', true);

            $lastUpdated = Carbon::now()->subSeconds(1);

            while (true) {
                if (connection_aborted()) {
                    break;
                }

                $orders = Order::query()
                    ->when(!$user->isStaff(), function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })
                    ->where('updated_at', '>', $lastUpdated)
                    ->orderBy('updated_at')
                    ->limit(50)
                    ->get(['id', 'status', 'assigned_to_user_id', 'updated_at']);

                foreach ($orders as $order) {
                    $lastUpdated = $order->updated_at ?? $lastUpdated;
                    echo "event: order_update\n";
                    echo 'data: ' . json_encode([
                        'id' => $order->id,
                        'status' => $order->status,
                        'assigned_to_user_id' => $order->assigned_to_user_id,
                        'updated_at' => $order->updated_at,
                    ]) . "\n\n";
                }

                echo "event: heartbeat\n";
                echo "data: ping\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }

                flush();
                sleep(3);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }
}
