<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicOrderStoreRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PasabuyScheduleResource;
use App\Interfaces\Order\OrderServiceInterface;
use App\Interfaces\PasabuySchedule\PasabuyScheduleServiceInterface;
use App\Traits\ApiResponse;

class PublicPasabuyController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PasabuyScheduleServiceInterface $pasabuyScheduleService,
        protected OrderServiceInterface $orderService,
    ) {}

    public function show(string $uuid)
    {
        $schedule = $this->pasabuyScheduleService->findByUuid($uuid);

        return $this->success(new PasabuyScheduleResource($schedule));
    }

    public function storeOrder(PublicOrderStoreRequest $request, string $uuid)
    {
        $order = $this->orderService->createForSchedule($uuid, (object) $request->validated());

        return $this->success(new OrderResource($order), 'Order placed successfully', 201);
    }
}
