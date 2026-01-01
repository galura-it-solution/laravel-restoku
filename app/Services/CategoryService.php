<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    private const CACHE_TTL_SECONDS = 60;
    private const CACHE_VERSION_KEY = 'categories:list:version';

    public function __construct(private CategoryRepository $categories)
    {
    }

    public function list(array $filters): LengthAwarePaginator
    {
        $cacheKey = $this->listCacheKey($filters);

        if (Cache::supportsTags()) {
            return Cache::tags(['categories'])->remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($filters) {
                return $this->categories->paginate($filters);
            });
        }

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($filters) {
            return $this->categories->paginate($filters);
        });
    }

    public function create(array $data): Category
    {
        $category = $this->categories->create($data);
        $this->bustCache();

        return $category;
    }

    public function update(Category $category, array $data): Category
    {
        $category = $this->categories->update($category, $data);
        $this->bustCache();

        return $category;
    }

    public function delete(Category $category): void
    {
        $this->categories->delete($category);
        $this->bustCache();
    }

    private function listCacheKey(array $filters): string
    {
        $version = Cache::get(self::CACHE_VERSION_KEY, 1);

        return 'categories:list:' . $version . ':' . md5(json_encode($filters));
    }

    private function bustCache(): void
    {
        if (Cache::supportsTags()) {
            Cache::tags(['categories'])->flush();
            return;
        }

        if (!Cache::add(self::CACHE_VERSION_KEY, 1)) {
            Cache::increment(self::CACHE_VERSION_KEY);
        }
    }
}
