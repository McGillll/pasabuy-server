<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsUpdateRequest;
use App\Http\Resources\SettingsResource;
use App\Interfaces\Settings\SettingsServiceInterface;
use App\Traits\ApiResponse;

class SettingsController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SettingsServiceInterface $settingsService,
    ) {}

    public function show()
    {
        $settings = $this->settingsService->get();

        return $this->success(new SettingsResource($settings));
    }

    public function update(SettingsUpdateRequest $request)
    {
        $payload = (object) $request->validated();

        $settings = $this->settingsService->update($payload);

        return $this->success(new SettingsResource($settings), 'Settings updated successfully');
    }
}
