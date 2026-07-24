<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsUpdateRequest;
use App\Http\Resources\SettingsResource;
use App\Interfaces\Settings\SettingsServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\InteractsWithCache;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    use ApiResponse, InteractsWithCache;

    public function __construct(
        protected SettingsServiceInterface $settingsService,
    ) {}

    public function show(Request $request)
    {
        $etag = $this->etagFor('settings', (object) []);

        if ($notModified = $this->notModified($request, $etag)) {
            return $notModified;
        }

        $settings = $this->settingsService->get();

        return $this->success(new SettingsResource($settings))->header('ETag', $etag);
    }

    public function update(SettingsUpdateRequest $request)
    {
        $payload = (object) $request->validated();

        $settings = $this->settingsService->update($payload);

        return $this->success(new SettingsResource($settings), 'Settings updated successfully');
    }
}
