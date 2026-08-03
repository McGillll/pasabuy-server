<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PasabuyScheduleChecklistToggleRequest;
use App\Http\Requests\PasabuyScheduleFinishRequest;
use App\Http\Requests\PasabuyScheduleProductConfirmPriceRequest;
use App\Http\Requests\PasabuyScheduleOrderItemsUpdateRequest;
use App\Http\Requests\PasabuyScheduleStoreRequest;
use App\Http\Requests\PasabuyScheduleUpdateRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PasabuyScheduleChecklistItemResource;
use App\Http\Resources\PasabuyScheduleProductBuyerResource;
use App\Http\Resources\PasabuyScheduleProductResource;
use App\Http\Resources\PasabuyScheduleResource;
use App\Http\Resources\PasabuyScheduleSummaryItemResource;
use App\Interfaces\Order\OrderServiceInterface;
use App\Interfaces\PasabuySchedule\PasabuyScheduleServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\InteractsWithCache;
use Illuminate\Http\Request;

class PasabuyScheduleController extends Controller
{
    use ApiResponse, InteractsWithCache;

    public function __construct(
        protected PasabuyScheduleServiceInterface $pasabuyScheduleService,
        protected OrderServiceInterface $orderService,
    ) {}

    public function index(Request $request)
    {
        $payload = (object) $request->query();
        $payload->seller_id = $request->user()->id;
        $etag = $this->etagFor('pasabuy_schedules', $payload);

        if ($notModified = $this->notModified($request, $etag)) {
            return $notModified;
        }

        $schedules = $this->pasabuyScheduleService->all($payload);

        return $this->success([
            'items' => PasabuyScheduleResource::collection($schedules->items()),
            'current_page' => $schedules->currentPage(),
            'last_page' => $schedules->lastPage(),
            'total' => $schedules->total(),
        ])->header('ETag', $etag);
    }

    public function browseOpenForCustomer(Request $request)
    {
        $schedules = $this->pasabuyScheduleService->openForCustomer((object) $request->query());

        return $this->success([
            'items' => PasabuyScheduleResource::collection($schedules->items()),
            'current_page' => $schedules->currentPage(),
            'last_page' => $schedules->lastPage(),
            'total' => $schedules->total(),
        ]);
    }

    public function show(Request $request, string $uuid)
    {
        $etag = $this->etagFor('pasabuy_schedules', (object) ['uuid' => $uuid]);

        if ($notModified = $this->notModified($request, $etag)) {
            return $notModified;
        }

        $schedule = $this->pasabuyScheduleService->findByUuid($uuid);

        return $this->success(new PasabuyScheduleResource($schedule))->header('ETag', $etag);
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
        $payload = (object) $request->validated();
        $payload->user_id = $request->user()->id;

        $schedule = $this->pasabuyScheduleService->update($uuid, $payload);

        return $this->success(new PasabuyScheduleResource($schedule), 'Pasabuy schedule updated successfully');
    }

    public function destroy(Request $request, string $uuid)
    {
        $this->pasabuyScheduleService->delete($uuid, $request->user()->id);

        return $this->success(null, 'Pasabuy schedule deleted successfully');
    }

    public function close(Request $request, string $uuid)
    {
        $schedule = $this->pasabuyScheduleService->close($uuid, $request->user()->id);

        return $this->success(new PasabuyScheduleResource($schedule), 'Pasabuy schedule closed');
    }

    public function reopen(Request $request, string $uuid)
    {
        $schedule = $this->pasabuyScheduleService->reopen($uuid, $request->user()->id);

        return $this->success(new PasabuyScheduleResource($schedule), 'Pasabuy schedule reopened');
    }

    public function finish(PasabuyScheduleFinishRequest $request, string $uuid)
    {
        $payload = (object) $request->validated();

        $schedule = $this->pasabuyScheduleService->finish($uuid, $request->user()->id, $payload->due_date);

        return $this->success(new PasabuyScheduleResource($schedule), 'Pasabuy schedule finished');
    }

    public function orders(string $uuid)
    {
        $orders = $this->pasabuyScheduleService->orders($uuid);

        return $this->success(OrderResource::collection($orders));
    }

    public function confirmProductPrice(PasabuyScheduleProductConfirmPriceRequest $request, string $uuid, string $scheduleProductUuid)
    {
        $payload = (object) $request->validated();
        $payload->user_id = $request->user()->id;

        $result = $this->pasabuyScheduleService->confirmProductPrice($uuid, $scheduleProductUuid, $payload);

        return $this->success([
            'schedule_product' => new PasabuyScheduleProductResource($result->schedule_product),
            'summary' => $result->summary,
        ], 'Price confirmed');
    }

    public function summary(string $uuid)
    {
        $items = $this->pasabuyScheduleService->summary($uuid);

        return $this->success(PasabuyScheduleSummaryItemResource::collection($items));
    }

    public function productBuyers(string $uuid, string $productUuid)
    {
        $buyers = $this->pasabuyScheduleService->productBuyers($uuid, $productUuid);

        return $this->success(PasabuyScheduleProductBuyerResource::collection($buyers));
    }

    public function checklist(string $uuid)
    {
        $items = $this->pasabuyScheduleService->checklist($uuid);

        return $this->success(PasabuyScheduleChecklistItemResource::collection($items));
    }

    public function toggleChecklistItem(PasabuyScheduleChecklistToggleRequest $request, string $uuid, string $productUuid)
    {
        $payload = (object) $request->validated();
        $payload->user_id = $request->user()->id;

        $item = $this->pasabuyScheduleService->toggleChecklistItem($uuid, $productUuid, $payload);

        return $this->success(new PasabuyScheduleChecklistItemResource($item), 'Checklist item updated');
    }

    public function regenerateChecklist(Request $request, string $uuid)
    {
        $items = $this->pasabuyScheduleService->regenerateChecklist($uuid, $request->user()->id);

        return $this->success(PasabuyScheduleChecklistItemResource::collection($items), 'Checklist regenerated');
    }

    public function checklistShareLink(Request $request, string $uuid)
    {
        $token = $this->pasabuyScheduleService->getChecklistShareLink($uuid, $request->user()->id);

        return $this->success(['token' => $token]);
    }

    public function regenerateChecklistShareLink(Request $request, string $uuid)
    {
        $token = $this->pasabuyScheduleService->regenerateChecklistShareLink($uuid, $request->user()->id);

        return $this->success(['token' => $token], 'Checklist share link regenerated');
    }

    public function updateOrderItems(PasabuyScheduleOrderItemsUpdateRequest $request, string $uuid, string $orderUuid)
    {
        $payload = (object) $request->validated();
        $payload->user_id = $request->user()->id;

        $order = $this->orderService->updateItemsForSeller($uuid, $orderUuid, $payload);

        return $this->success(new OrderResource($order), 'Order items updated');
    }
}
