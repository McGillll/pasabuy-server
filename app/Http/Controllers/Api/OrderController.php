<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderDeliveryStatusUpdateRequest;
use App\Http\Requests\OrderItemsStoreRequest;
use App\Http\Requests\OrderItemsUpdateRequest;
use App\Http\Requests\OrderStatusUpdateRequest;
use App\Http\Resources\OrderResource;
use App\Interfaces\Order\OrderServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected OrderServiceInterface $orderService,
    ) {}

    public function index(Request $request)
    {
        $orders = $this->orderService->all((object) $request->query());

        return $this->success([
            'data' => OrderResource::collection($orders->items()),
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
            'total' => $orders->total(),
        ]);
    }

    public function updateStatus(OrderStatusUpdateRequest $request, string $uuid)
    {
        $payload = (object) $request->validated();
        $payload->user_id = $request->user()->id;

        $order = $this->orderService->updateStatus($uuid, $payload);

        return $this->success(new OrderResource($order), 'Order status updated successfully');
    }

    public function updateDeliveryStatus(OrderDeliveryStatusUpdateRequest $request, string $uuid)
    {
        $payload = (object) $request->validated();
        $payload->user_id = $request->user()->id;

        $order = $this->orderService->updateDeliveryStatus($uuid, $payload);

        return $this->success(new OrderResource($order), 'Order delivery status updated successfully');
    }

    public function storeForCustomer(OrderItemsStoreRequest $request, string $scheduleUuid)
    {
        $payload = (object) $request->validated();
        $payload->user = $request->user();

        $order = $this->orderService->createForSchedule($scheduleUuid, $payload);

        return $this->success(new OrderResource($order), 'Order placed successfully', 201);
    }

    public function myOrders(Request $request)
    {
        $payload = (object) $request->query();
        $payload->user = $request->user();

        $orders = $this->orderService->myOrders($payload);

        return $this->success([
            'data' => OrderResource::collection($orders->items()),
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
            'total' => $orders->total(),
        ]);
    }

    public function myOrderShow(Request $request, string $uuid)
    {
        $order = $this->orderService->findOwnedByUuid($uuid, (object) ['user' => $request->user()]);

        return $this->success(new OrderResource($order));
    }

    public function updateMyOrder(OrderItemsUpdateRequest $request, string $uuid)
    {
        $payload = (object) $request->validated();
        $payload->user = $request->user();

        $order = $this->orderService->updateItemsForCustomer($uuid, $payload);

        return $this->success(new OrderResource($order), 'Order updated successfully');
    }

    public function cancelMyOrder(Request $request, string $uuid)
    {
        $order = $this->orderService->cancelForCustomer($uuid, (object) ['user' => $request->user()]);

        return $this->success(new OrderResource($order), 'Order cancelled');
    }
}
