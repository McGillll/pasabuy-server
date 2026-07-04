<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerStoreRequest;
use App\Http\Resources\CustomerResource;
use App\Interfaces\Customer\CustomerServiceInterface;
use App\Traits\ApiResponse;

class PublicCustomerController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CustomerServiceInterface $customerService,
    ) {}

    public function register(CustomerStoreRequest $request)
    {
        $customer = $this->customerService->register((object) $request->validated());

        return $this->success(new CustomerResource($customer), 'Registration submitted. Please wait for admin approval.', 201);
    }
}
