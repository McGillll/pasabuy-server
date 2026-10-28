<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Interfaces\Invoice\InvoiceServiceInterface;
use App\Interfaces\Order\OrderServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected InvoiceServiceInterface $invoiceService,
        protected OrderServiceInterface $orderService,
    ) {}

    public function show(Request $request, string $orderUuid)
    {
        $order = $this->orderService->findOwnedBySellerUuid($orderUuid, (object) ['user' => $request->user()]);

        $invoice = $this->invoiceService->buildForOrder($order);

        return $this->success(new InvoiceResource($invoice));
    }

    public function myInvoice(Request $request, string $orderUuid)
    {
        $order = $this->orderService->findOwnedByUuid($orderUuid, (object) ['user' => $request->user()]);

        $invoice = $this->invoiceService->buildForOrder($order);

        return $this->success(new InvoiceResource($invoice));
    }
}
