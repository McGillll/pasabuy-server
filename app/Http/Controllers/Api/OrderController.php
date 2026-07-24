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
use App\Traits\InteractsWithCache;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponse, InteractsWithCache;

    public function __construct(
        protected OrderServiceInterface $orderService,
    ) {}

    public function index(Request $request)
    {
        $payload = (object) $request->query();
        $payload->seller_id = $request->user()->id;
        $etag = $this->etagFor('orders', $payload);

        if ($notModified = $this->notModified($request, $etag)) {
            return $notModified;
        }

        $orders = $this->orderService->all($payload);

        return $this->success([
            'data' => OrderResource::collection($orders->items()),
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
            'total' => $orders->total(),
        ])->header('ETag', $etag);
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
        $etag = $this->etagFor('orders', (object) array_merge($request->query(), ['user_id' => $request->user()->id]));

        if ($notModified = $this->notModified($request, $etag)) {
            return $notModified;
        }

        $orders = $this->orderService->myOrders($payload);

        return $this->success([
            'data' => OrderResource::collection($orders->items()),
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
            'total' => $orders->total(),
        ])->header('ETag', $etag);
    }

    public function myOrderShow(Request $request, string $uuid)
    {
        $etag = $this->etagFor('orders', (object) ['uuid' => $uuid, 'user_id' => $request->user()->id]);

        if ($notModified = $this->notModified($request, $etag)) {
            return $notModified;
        }

        $order = $this->orderService->findOwnedByUuid($uuid, (object) ['user' => $request->user()]);

        return $this->success(new OrderResource($order))->header('ETag', $etag);
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
