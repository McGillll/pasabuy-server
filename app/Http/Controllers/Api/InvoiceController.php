<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Interfaces\Invoice\InvoiceServiceInterface;
use App\Interfaces\Order\OrderRepositoryInterface;
use App\Traits\ApiResponse;

class InvoiceController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected InvoiceServiceInterface $invoiceService,
        protected OrderRepositoryInterface $orderRepository,
    ) {}

    public function show(string $orderUuid)
    {
        $order = $this->orderRepository->findByUuid($orderUuid);

        if (! $order) {
            throw new NotFoundException('Order not found.');
        }

        $invoice = $this->invoiceService->buildForOrder($order);

        return $this->success(new InvoiceResource($invoice));
    }
}
