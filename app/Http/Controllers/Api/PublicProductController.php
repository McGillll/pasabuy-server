<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Interfaces\Product\ProductServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PublicProductController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ProductServiceInterface $productService,
    ) {}

    public function index(Request $request)
    {
        $products = $this->productService->publicCatalog((object) $request->query());

        return $this->success([
            'data' => ProductResource::collection($products->items()),
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'total' => $products->total(),
        ]);
    }
}
