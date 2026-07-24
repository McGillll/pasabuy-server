<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductCategoryStoreRequest;
use App\Http\Requests\ProductCategoryUpdateRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Interfaces\ProductCategory\ProductCategoryServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\InteractsWithCache;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    use ApiResponse, InteractsWithCache;

    public function __construct(
        protected ProductCategoryServiceInterface $productCategoryService,
    ) {}

    public function index(Request $request)
    {
        $payload = (object) $request->query();
        $etag = $this->etagFor('product_categories', $payload);

        if ($notModified = $this->notModified($request, $etag)) {
            return $notModified;
        }

        $categories = $this->productCategoryService->all($payload);

        return $this->success([
            'data' => ProductCategoryResource::collection($categories->items()),
            'current_page' => $categories->currentPage(),
            'last_page' => $categories->lastPage(),
            'total' => $categories->total(),
        ])->header('ETag', $etag);
    }

    public function show(Request $request, string $uuid)
    {
        $etag = $this->etagFor('product_categories', (object) ['uuid' => $uuid]);

        if ($notModified = $this->notModified($request, $etag)) {
            return $notModified;
        }

        $category = $this->productCategoryService->findByUuid($uuid);

        return $this->success(new ProductCategoryResource($category))->header('ETag', $etag);
    }

    public function store(ProductCategoryStoreRequest $request)
    {
        $payload = (object) $request->validated();

        $category = $this->productCategoryService->create($payload);

        return $this->success(new ProductCategoryResource($category), 'Product category created successfully', 201);
    }

    public function update(ProductCategoryUpdateRequest $request, string $uuid)
    {
        $payload = (object) $request->validated();

        $category = $this->productCategoryService->update($uuid, $payload);

        return $this->success(new ProductCategoryResource($category), 'Product category updated successfully');
    }

    public function destroy(string $uuid)
    {
        $this->productCategoryService->delete($uuid);

        return $this->success(null, 'Product category deleted successfully');
    }
}
