<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BasketItemStoreRequest;
use App\Http\Requests\BasketItemUpdateRequest;
use App\Http\Resources\BasketResource;
use App\Interfaces\Basket\BasketServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\InteractsWithCache;
use Illuminate\Http\Request;

class BasketController extends Controller
{
    use ApiResponse, InteractsWithCache;

    public function __construct(
        protected BasketServiceInterface $basketService,
    ) {}

    public function show(Request $request)
    {
        $etag = $this->etagFor('baskets', (object) ['user_id' => $request->user()->id]);

        if ($notModified = $this->notModified($request, $etag)) {
            return $notModified;
        }

        $basket = $this->basketService->myBasket((object) ['user_id' => $request->user()->id]);

        return $this->success(new BasketResource($basket))->header('ETag', $etag);
    }

    public function storeItem(BasketItemStoreRequest $request)
    {
        $payload = (object) $request->validated();
        $payload->user_id = $request->user()->id;

        $basket = $this->basketService->addItem($payload);

        return $this->success(new BasketResource($basket), 'Added to Blue Basket', 201);
    }

    public function updateItem(BasketItemUpdateRequest $request, string $uuid)
    {
        $payload = (object) $request->validated();
        $payload->user_id = $request->user()->id;

        $basket = $this->basketService->updateItem($uuid, $payload);

        return $this->success(new BasketResource($basket), 'Basket updated');
    }

    public function destroyItem(Request $request, string $uuid)
    {
        $basket = $this->basketService->removeItem($uuid, (object) ['user_id' => $request->user()->id]);

        return $this->success(new BasketResource($basket), 'Removed from basket');
    }

    public function clear(Request $request)
    {
        $basket = $this->basketService->clear((object) ['user_id' => $request->user()->id]);

        return $this->success(new BasketResource($basket), 'Basket cleared');
    }
}
