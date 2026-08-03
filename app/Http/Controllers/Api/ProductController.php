<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\TopProductResource;
use App\Interfaces\Product\ProductServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\InteractsWithCache;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponse, InteractsWithCache;

    public function __construct(
        protected ProductServiceInterface $productService,
    ) {}

    public function index(Request $request)
    {
        $payload = (object) $request->query();
        $etag = $this->etagFor('products', $payload);

        if ($notModified = $this->notModified($request, $etag)) {
            return $notModified;
        }

        $products = $this->productService->all($payload);

        return $this->success([
            'items' => ProductResource::collection($products->items()),
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'total' => $products->total(),
        ])->header('ETag', $etag);
    }

    public function show(Request $request, string $uuid)
    {
        $etag = $this->etagFor('products', (object) ['uuid' => $uuid]);

        if ($notModified = $this->notModified($request, $etag)) {
            return $notModified;
        }

        $product = $this->productService->findByUuid($uuid);

        return $this->success(new ProductResource($product))->header('ETag', $etag);
    }

    public function store(ProductStoreRequest $request)
    {
        $payload = (object) $request->validated();

        if ($request->hasFile('image')) {
            $payload->image = $request->file('image');
        }

        $product = $this->productService->create($payload);

        return $this->success(new ProductResource($product), 'Product created successfully', 201);
    }

    public function update(ProductUpdateRequest $request, string $uuid)
    {
        $payload = (object) $request->validated();

        if ($request->hasFile('image')) {
            $payload->image = $request->file('image');
        }

        $product = $this->productService->update($uuid, $payload);

        return $this->success(new ProductResource($product), 'Product updated successfully');
    }

    public function destroy(string $uuid)
    {
        $this->productService->delete($uuid);

        return $this->success(null, 'Product deleted successfully');
    }

    public function topSelling(Request $request)
    {
        $payload = (object) $request->query();
        $payload->seller_id = $request->user()->id;

        $products = $this->productService->topSelling($payload);

        return $this->success(TopProductResource::collection($products));
    }
}
