<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CategoryImageRequest;
use App\Http\Requests\Api\V1\CategoryRequest;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function __construct(private CategoryService $categoryService)
    {
        $this->authorizeResource(Category::class, 'category');
    }

    public function index(Request $request)
    {
        $categories = $this->categoryService->list($request->only(['search', 'per_page']));

        return CategoryResource::collection($categories);
    }

    public function store(CategoryRequest $request)
    {
        $data = $request->validated();
        unset($data['image']);

        if ($request->hasFile('image')) {
            $path = Storage::disk(config('filesystems.default'))
                ->putFile('categories', $request->file('image'));
            $data['image_url'] = $path;
        }

        $category = $this->categoryService->create($data);

        return new CategoryResource($category);
    }

    public function show(Category $category)
    {
        return new CategoryResource($category);
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $data = $request->validated();
        unset($data['image']);

        if ($request->hasFile('image')) {
            $path = Storage::disk(config('filesystems.default'))
                ->putFile('categories', $request->file('image'));

            $this->deleteLocalImageIfNeeded($category->image_url);
            $data['image_url'] = $path;
        }

        $category = $this->categoryService->update($category, $data);

        return new CategoryResource($category);
    }

    public function destroy(Category $category)
    {
        $this->deleteLocalImageIfNeeded($category->image_url);
        $this->categoryService->delete($category);

        return response()->json(['message' => 'Category dihapus.']);
    }

    public function uploadImage(CategoryImageRequest $request, Category $category)
    {
        $this->authorize('update', $category);

        $path = Storage::disk(config('filesystems.default'))
            ->putFile('categories', $request->file('image'));

        $this->deleteLocalImageIfNeeded($category->image_url);
        $category = $this->categoryService->update($category, ['image_url' => $path]);

        return new CategoryResource($category);
    }

    private function deleteLocalImageIfNeeded(?string $imageUrl): void
    {
        if (!$imageUrl) {
            return;
        }

        if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            return;
        }

        Storage::disk(config('filesystems.default'))->delete($imageUrl);
    }
}
