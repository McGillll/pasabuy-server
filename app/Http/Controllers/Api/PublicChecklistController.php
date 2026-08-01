<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PasabuyScheduleChecklistToggleRequest;
use App\Http\Requests\PasabuyScheduleProductConfirmPriceRequest;
use App\Http\Resources\PasabuyScheduleChecklistItemResource;
use App\Http\Resources\PasabuyScheduleProductResource;
use App\Interfaces\PasabuySchedule\PasabuyScheduleServiceInterface;
use App\Traits\ApiResponse;

class PublicChecklistController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PasabuyScheduleServiceInterface $pasabuyScheduleService,
    ) {}

    public function show(string $token)
    {
        $items = $this->pasabuyScheduleService->publicChecklist($token);

        return $this->success(PasabuyScheduleChecklistItemResource::collection($items));
    }

    public function toggle(PasabuyScheduleChecklistToggleRequest $request, string $token, string $productUuid)
    {
        $payload = (object) $request->validated();

        $item = $this->pasabuyScheduleService->publicToggleChecklistItem($token, $productUuid, $payload->is_checked);

        return $this->success(new PasabuyScheduleChecklistItemResource($item), 'Checklist item updated');
    }

    public function confirmPrice(PasabuyScheduleProductConfirmPriceRequest $request, string $token, string $productUuid)
    {
        $payload = (object) $request->validated();

        $result = $this->pasabuyScheduleService->publicConfirmChecklistItemPrice($token, $productUuid, $payload);

        return $this->success([
            'schedule_product' => new PasabuyScheduleProductResource($result->schedule_product),
            'summary' => $result->summary,
            'checklist_item' => new PasabuyScheduleChecklistItemResource($result->checklist_item),
        ], 'Price confirmed');
    }
}
