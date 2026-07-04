<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PasabuyScheduleResource;
use App\Interfaces\PasabuySchedule\PasabuyScheduleServiceInterface;
use App\Traits\ApiResponse;

class PublicPasabuyController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PasabuyScheduleServiceInterface $pasabuyScheduleService,
    ) {}

    public function show(string $uuid)
    {
        $schedule = $this->pasabuyScheduleService->findByUuid($uuid);

        return $this->success(new PasabuyScheduleResource($schedule));
    }
}
