<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\OrderResource;
use App\Interfaces\Customer\CustomerServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\InteractsWithCache;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use ApiResponse, InteractsWithCache;

    public function __construct(
        protected CustomerServiceInterface $customerService,
    ) {}

    public function index(Request $request)
    {
        $payload = (object) $request->query();
        $etag = $this->etagFor('customers', $payload);

        if ($notModified = $this->notModified($request, $etag)) {
            return $notModified;
        }

        $customers = $this->customerService->all($payload);

        return $this->success([
            'data' => CustomerResource::collection($customers->items()),
            'current_page' => $customers->currentPage(),
            'last_page' => $customers->lastPage(),
            'total' => $customers->total(),
        ])->header('ETag', $etag);
    }

    public function approve(string $uuid)
    {
        $customer = $this->customerService->approve($uuid);

        return $this->success(new CustomerResource($customer), 'Customer approved successfully');
    }

    public function reject(string $uuid)
    {
        $customer = $this->customerService->reject($uuid);

        return $this->success(new CustomerResource($customer), 'Customer rejected successfully');
    }

    public function orders(Request $request, string $uuid)
    {
        $etag = $this->etagFor('orders', (object) ['customer_uuid' => $uuid]);

        if ($notModified = $this->notModified($request, $etag)) {
            return $notModified;
        }

        $orders = $this->customerService->orders($uuid);

        return $this->success(OrderResource::collection($orders))->header('ETag', $etag);
    }
}
