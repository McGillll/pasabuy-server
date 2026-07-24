<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Interfaces\Product\ProductServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\InteractsWithCache;
use Illuminate\Http\Request;

class PublicProductController extends Controller
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

        $products = $this->productService->publicCatalog($payload);

        return $this->success([
            'items' => ProductResource::collection($products->items()),
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'total' => $products->total(),
        ])->header('ETag', $etag);
    }
}
