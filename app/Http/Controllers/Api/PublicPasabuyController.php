<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PasabuyScheduleResource;
use App\Interfaces\PasabuySchedule\PasabuyScheduleServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\InteractsWithCache;
use Illuminate\Http\Request;

class PublicPasabuyController extends Controller
{
    use ApiResponse, InteractsWithCache;

    public function __construct(
        protected PasabuyScheduleServiceInterface $pasabuyScheduleService,
    ) {}

    public function show(Request $request, string $uuid)
    {
        $etag = $this->etagFor('pasabuy_schedules', (object) ['uuid' => $uuid]);

        if ($notModified = $this->notModified($request, $etag)) {
            return $notModified;
        }

        $schedule = $this->pasabuyScheduleService->findByUuid($uuid);

        return $this->success(new PasabuyScheduleResource($schedule))->header('ETag', $etag);
    }
}
