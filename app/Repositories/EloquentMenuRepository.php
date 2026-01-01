<?php

namespace App\Repositories;

use App\Models\Menu;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentMenuRepository implements MenuRepository
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Menu::query()->with('category');

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['in_stock'])) {
            $query->where(function ($builder) {
                $builder->whereNull('stock')
                    ->orWhere('stock', '>', 0);
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Menu
    {
        return Menu::create($data);
    }

    public function update(Menu $menu, array $data): Menu
    {
        $menu->update($data);

        return $menu;
    }

    public function delete(Menu $menu): void
    {
        $menu->delete();
    }

    public function findByIds(array $ids): Collection
    {
        return Menu::whereIn('id', $ids)->get()->keyBy('id');
    }

    public function findByIdsForUpdate(array $ids): Collection
    {
        return Menu::whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');
    }
}
