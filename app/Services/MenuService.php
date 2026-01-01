<?php

namespace App\Services;

use App\Models\Menu;
use App\Repositories\MenuRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class MenuService
{
    private const CACHE_TTL_SECONDS = 60;
    private const CACHE_VERSION_KEY = 'menus:list:version';

    public function __construct(private MenuRepository $menus)
    {
    }

    public function list(array $filters): LengthAwarePaginator
    {
        $cacheKey = $this->listCacheKey($filters);

        if (Cache::supportsTags()) {
            return Cache::tags(['menus'])->remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($filters) {
                return $this->menus->paginate($filters);
            });
        }

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($filters) {
            return $this->menus->paginate($filters);
        });
    }

    public function create(array $data): Menu
    {
        $menu = $this->menus->create($data);
        $this->bustCache();

        return $menu;
    }

    public function update(Menu $menu, array $data): Menu
    {
        $menu = $this->menus->update($menu, $data);
        $this->bustCache();

        return $menu;
    }

    public function delete(Menu $menu): void
    {
        if ($menu->image_object_key) {
            Storage::disk(config('filesystems.default'))->delete($menu->image_object_key);
        }

        $this->menus->delete($menu);
        $this->bustCache();
    }

    private function listCacheKey(array $filters): string
    {
        $version = Cache::get(self::CACHE_VERSION_KEY, 1);

        return 'menus:list:' . $version . ':' . md5(json_encode($filters));
    }

    private function bustCache(): void
    {
        if (Cache::supportsTags()) {
            Cache::tags(['menus'])->flush();
            return;
        }

        if (!Cache::add(self::CACHE_VERSION_KEY, 1)) {
            Cache::increment(self::CACHE_VERSION_KEY);
        }
    }
}
