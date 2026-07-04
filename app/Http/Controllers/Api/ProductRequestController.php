<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequestStoreRequest;
use App\Http\Resources\ProductRequestResource;
use App\Interfaces\PasabuySchedule\PasabuyScheduleServiceInterface;
use App\Interfaces\ProductRequest\ProductRequestServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ProductRequestController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ProductRequestServiceInterface $productRequestService,
        protected PasabuyScheduleServiceInterface $pasabuyScheduleService,
    ) {}

    public function index(Request $request)
    {
        $requests = $this->productRequestService->all((object) $request->query());

        return $this->success([
            'data' => ProductRequestResource::collection($requests->items()),
            'current_page' => $requests->currentPage(),
            'last_page' => $requests->lastPage(),
            'total' => $requests->total(),
        ]);
    }

    public function myRequests(Request $request)
    {
        $payload = (object) $request->query();
        $payload->user = $request->user();

        $requests = $this->productRequestService->myRequests($payload);

        return $this->success([
            'data' => ProductRequestResource::collection($requests->items()),
            'current_page' => $requests->currentPage(),
            'last_page' => $requests->lastPage(),
            'total' => $requests->total(),
        ]);
    }

    public function store(ProductRequestStoreRequest $request)
    {
        $payload = (object) $request->validated();
        $payload->user = $request->user();

        if (! empty($payload->pasabuy_schedule_uuid)) {
            $schedule = $this->pasabuyScheduleService->findByUuid($payload->pasabuy_schedule_uuid);
            $payload->pasabuy_schedule_id = $schedule->id;
        }

        $productRequest = $this->productRequestService->create($payload);

        return $this->success(new ProductRequestResource($productRequest), 'Request submitted successfully', 201);
    }

    public function markReviewed(string $uuid)
    {
        $productRequest = $this->productRequestService->markReviewed($uuid);

        return $this->success(new ProductRequestResource($productRequest), 'Marked as reviewed');
    }
}
