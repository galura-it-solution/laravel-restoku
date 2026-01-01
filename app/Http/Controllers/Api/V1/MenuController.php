<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MenuImageRequest;
use App\Http\Requests\Api\V1\MenuRequest;
use App\Http\Resources\Api\V1\MenuResource;
use App\Models\Menu;
use App\Services\MenuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MenuController extends Controller
{
    public function __construct(private MenuService $menuService)
    {
        $this->authorizeResource(Menu::class, 'menu');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'category_id', 'per_page']);
        if (!$request->user()->isStaff()) {
            $filters['is_active'] = true;
            $filters['in_stock'] = true;
        }

        $menus = $this->menuService->list($filters);

        return MenuResource::collection($menus);
    }

    public function store(MenuRequest $request)
    {
        $data = $request->validated();
        unset($data['image']);

        if ($request->hasFile('image')) {
            $path = Storage::disk(config('filesystems.default'))
                ->putFile('menus', $request->file('image'));
            $data['image_object_key'] = $path;
        }

        $menu = $this->menuService->create($data);

        return new MenuResource($menu->load('category'));
    }

    public function show(Request $request, Menu $menu)
    {
        if (!$request->user()->isStaff() &&
            (!$menu->is_active || ($menu->stock !== null && $menu->stock <= 0))) {
            return response()->json(['message' => 'Menu tidak tersedia.'], 404);
        }

        return new MenuResource($menu->load('category'));
    }

    public function update(MenuRequest $request, Menu $menu)
    {
        $data = $request->validated();
        unset($data['image']);

        if ($request->hasFile('image')) {
            $path = Storage::disk(config('filesystems.default'))
                ->putFile('menus', $request->file('image'));

            if ($menu->image_object_key) {
                Storage::disk(config('filesystems.default'))
                    ->delete($menu->image_object_key);
            }
            $data['image_object_key'] = $path;
        }

        $menu = $this->menuService->update($menu, $data);

        return new MenuResource($menu->load('category'));
    }

    public function destroy(Menu $menu)
    {
        $this->menuService->delete($menu);

        return response()->json(['message' => 'Menu dihapus.']);
    }

    public function uploadImage(MenuImageRequest $request, Menu $menu)
    {
        $this->authorize('update', $menu);

        $file = $request->file('image');
        $path = Storage::disk(config('filesystems.default'))->putFile('menus', $file);

        if ($menu->image_object_key) {
            Storage::disk(config('filesystems.default'))->delete($menu->image_object_key);
        }

        $menu->update(['image_object_key' => $path]);

        return new MenuResource($menu->load('category'));
    }
}
