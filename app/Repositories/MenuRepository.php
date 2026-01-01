<?php

namespace App\Repositories;

use App\Models\Menu;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface MenuRepository
{
    public function paginate(array $filters): LengthAwarePaginator;
    public function create(array $data): Menu;
    public function update(Menu $menu, array $data): Menu;
    public function delete(Menu $menu): void;
    public function findByIds(array $ids): Collection;
    public function findByIdsForUpdate(array $ids): Collection;
}
