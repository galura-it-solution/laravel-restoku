<?php

namespace App\Repositories;

use App\Models\RestaurantTable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RestaurantTableRepository
{
    public function paginate(array $filters): LengthAwarePaginator;
    public function create(array $data): RestaurantTable;
    public function update(RestaurantTable $table, array $data): RestaurantTable;
    public function delete(RestaurantTable $table): void;
    public function findForUpdate(int $id): RestaurantTable;
    public function updateStatus(int $id, string $status): void;
}
