<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCategoryResource;
use App\Interfaces\ProductCategory\ProductCategoryServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\InteractsWithCache;
use Illuminate\Http\Request;

class PublicProductCategoryController extends Controller
{
    use ApiResponse, InteractsWithCache;

    public function __construct(
        protected ProductCategoryServiceInterface $productCategoryService,
    ) {}

    public function children(Request $request)
    {
        $payload = (object) $request->query();
        $etag = $this->etagFor('product_categories', $payload);

        if ($notModified = $this->notModified($request, $etag)) {
            return $notModified;
        }

        $categories = $this->productCategoryService->publicChildren($payload);

        return $this->success(ProductCategoryResource::collection($categories))->header('ETag', $etag);
    }

    public function show(Request $request, string $uuid)
    {
        $etag = $this->etagFor('product_categories', (object) ['uuid' => $uuid]);

        if ($notModified = $this->notModified($request, $etag)) {
            return $notModified;
        }

        $category = $this->productCategoryService->publicFindByUuid($uuid);

        return $this->success(new ProductCategoryResource($category))->header('ETag', $etag);
    }
}
