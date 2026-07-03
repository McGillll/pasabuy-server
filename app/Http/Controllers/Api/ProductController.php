<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Interfaces\Product\ProductServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ProductServiceInterface $productService,
    ) {}

    public function index(Request $request)
    {
        $products = $this->productService->all((object) $request->query());

        return $this->success([
            'data' => ProductResource::collection($products->items()),
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'total' => $products->total(),
        ]);
    }

    public function show(string $uuid)
    {
        $product = $this->productService->findByUuid($uuid);

        return $this->success(new ProductResource($product));
    }

    public function store(ProductStoreRequest $request)
    {
        $payload = (object) $request->validated();

        if ($request->hasFile('image')) {
            $payload->image_path = $request->file('image')->store('products', 'public');
        }

        $product = $this->productService->create($payload);

        return $this->success(new ProductResource($product), 'Product created successfully', 201);
    }

    public function update(ProductUpdateRequest $request, string $uuid)
    {
        $payload = (object) $request->validated();

        if ($request->hasFile('image')) {
            $existing = $this->productService->findByUuid($uuid);
            if ($existing->image_path) {
                Storage::disk('public')->delete($existing->image_path);
            }
            $payload->image_path = $request->file('image')->store('products', 'public');
        }

        $product = $this->productService->update($uuid, $payload);

        return $this->success(new ProductResource($product), 'Product updated successfully');
    }

    public function destroy(string $uuid)
    {
        $this->productService->delete($uuid);

        return $this->success(null, 'Product deleted successfully');
    }
}
