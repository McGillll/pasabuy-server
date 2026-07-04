<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Interfaces\Customer\CustomerServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CustomerServiceInterface $customerService,
    ) {}

    public function index(Request $request)
    {
        $customers = $this->customerService->all((object) $request->query());

        return $this->success([
            'data' => CustomerResource::collection($customers->items()),
            'current_page' => $customers->currentPage(),
            'last_page' => $customers->lastPage(),
            'total' => $customers->total(),
        ]);
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
}
