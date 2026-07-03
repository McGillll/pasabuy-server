<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderStatusUpdateRequest;
use App\Http\Resources\OrderResource;
use App\Interfaces\Order\OrderServiceInterface;
use App\Traits\ApiResponse;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected OrderServiceInterface $orderService,
    ) {}

    public function updateStatus(OrderStatusUpdateRequest $request, string $uuid)
    {
        $order = $this->orderService->updateStatus($uuid, (object) $request->validated());

        return $this->success(new OrderResource($order), 'Order status updated successfully');
    }
}
