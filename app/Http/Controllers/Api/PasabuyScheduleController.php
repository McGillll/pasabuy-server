<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PasabuyScheduleStoreRequest;
use App\Http\Requests\PasabuyScheduleSyncProductsRequest;
use App\Http\Requests\PasabuyScheduleUpdateRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PasabuyScheduleResource;
use App\Interfaces\PasabuySchedule\PasabuyScheduleServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PasabuyScheduleController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PasabuyScheduleServiceInterface $pasabuyScheduleService,
    ) {}

    public function index(Request $request)
    {
        $schedules = $this->pasabuyScheduleService->all((object) $request->query());

        return $this->success([
            'data' => PasabuyScheduleResource::collection($schedules->items()),
            'current_page' => $schedules->currentPage(),
            'last_page' => $schedules->lastPage(),
            'total' => $schedules->total(),
        ]);
    }

    public function show(string $uuid)
    {
        $schedule = $this->pasabuyScheduleService->findByUuid($uuid);

        return $this->success(new PasabuyScheduleResource($schedule));
    }

    public function store(PasabuyScheduleStoreRequest $request)
    {
        $payload = (object) $request->validated();
        $payload->user_id = $request->user()->id;

        $schedule = $this->pasabuyScheduleService->create($payload);

        return $this->success(new PasabuyScheduleResource($schedule), 'Pasabuy schedule created successfully', 201);
    }

    public function update(PasabuyScheduleUpdateRequest $request, string $uuid)
    {
        $schedule = $this->pasabuyScheduleService->update($uuid, (object) $request->validated());

        return $this->success(new PasabuyScheduleResource($schedule), 'Pasabuy schedule updated successfully');
    }

    public function destroy(string $uuid)
    {
        $this->pasabuyScheduleService->delete($uuid);

        return $this->success(null, 'Pasabuy schedule deleted successfully');
    }

    public function syncProducts(PasabuyScheduleSyncProductsRequest $request, string $uuid)
    {
        $schedule = $this->pasabuyScheduleService->syncProducts($uuid, (object) $request->validated());

        return $this->success(new PasabuyScheduleResource($schedule), 'Products updated successfully');
    }

    public function orders(string $uuid)
    {
        $orders = $this->pasabuyScheduleService->orders($uuid);

        return $this->success(OrderResource::collection($orders));
    }
}
