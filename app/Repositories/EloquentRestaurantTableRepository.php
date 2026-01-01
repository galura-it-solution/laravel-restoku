<?php

namespace App\Repositories;

use App\Models\RestaurantTable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentRestaurantTableRepository implements RestaurantTableRepository
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = RestaurantTable::query();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): RestaurantTable
    {
        return RestaurantTable::create($data);
    }

    public function update(RestaurantTable $table, array $data): RestaurantTable
    {
        $table->update($data);

        return $table;
    }

    public function delete(RestaurantTable $table): void
    {
        $table->delete();
    }

    public function findForUpdate(int $id): RestaurantTable
    {
        return RestaurantTable::whereKey($id)->lockForUpdate()->firstOrFail();
    }

    public function updateStatus(int $id, string $status): void
    {
        RestaurantTable::whereKey($id)->update(['status' => $status]);
    }
}
