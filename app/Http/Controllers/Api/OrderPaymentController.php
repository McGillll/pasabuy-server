<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderPaymentStoreRequest;
use App\Http\Resources\OrderPaymentResource;
use App\Interfaces\OrderPayment\OrderPaymentServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class OrderPaymentController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected OrderPaymentServiceInterface $orderPaymentService,
    ) {}

    public function index(Request $request, string $orderUuid)
    {
        $payments = $this->orderPaymentService->listForOrder($orderUuid, $request->user()->id);

        return $this->success(OrderPaymentResource::collection($payments));
    }

    public function store(OrderPaymentStoreRequest $request, string $orderUuid)
    {
        $payload = (object) $request->validated();
        $payload->recorded_by_user_id = $request->user()->id;
        $payload->user_id = $request->user()->id;

        $payment = $this->orderPaymentService->create($orderUuid, $payload);

        return $this->success(new OrderPaymentResource($payment), 'Payment recorded successfully', 201);
    }

    public function destroy(Request $request, string $orderUuid, string $uuid)
    {
        $this->orderPaymentService->delete($orderUuid, $uuid, $request->user()->id);

        return $this->success(null, 'Payment removed');
    }
}
