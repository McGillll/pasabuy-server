<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PasabuyScheduleChecklistToggleRequest;
use App\Http\Resources\PasabuyScheduleChecklistItemResource;
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
}
