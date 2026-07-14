<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceSettingUpdateRequest;
use App\Http\Resources\InvoiceSettingResource;
use App\Interfaces\InvoiceSetting\InvoiceSettingServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class InvoiceSettingController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected InvoiceSettingServiceInterface $invoiceSettingService,
    ) {}

    public function show(Request $request)
    {
        $setting = $this->invoiceSettingService->getForUser($request->user()->id);

        return $this->success(new InvoiceSettingResource($setting));
    }

    public function update(InvoiceSettingUpdateRequest $request)
    {
        $payload = (object) $request->validated();

        $setting = $this->invoiceSettingService->update($payload, $request->user()->id);

        return $this->success(new InvoiceSettingResource($setting), 'Invoice settings updated successfully');
    }
}
