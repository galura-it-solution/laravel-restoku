<?php

namespace App\Services;

use App\Models\RestaurantTable;
use App\Repositories\OrderRepository;
use App\Repositories\RestaurantTableRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Exceptions\HttpResponseException;

class TableService
{
    public function __construct(
        private RestaurantTableRepository $tables,
        private OrderRepository $orders
    )
    {
    }

    public function list(array $filters): LengthAwarePaginator
    {
        return $this->tables->paginate($filters);
    }

    public function create(array $data): RestaurantTable
    {
        return $this->tables->create($data);
    }

    public function update(RestaurantTable $table, array $data): RestaurantTable
    {
        return $this->tables->update($table, $data);
    }

    public function delete(RestaurantTable $table): void
    {
        if ($this->orders->hasActiveByTable($table->id)) {
            throw new HttpResponseException(response()->json([
                'message' => 'Meja tidak dapat dihapus karena masih memiliki order aktif.',
            ], 409));
        }

        $this->tables->delete($table);
    }
}
