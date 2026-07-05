<?php

namespace App\Services;

use App\Exceptions\InvalidPayloadException;
use App\Exceptions\NotFoundException;
use App\Interfaces\Customer\CustomerRepositoryInterface;
use App\Interfaces\Customer\CustomerServiceInterface;

class CustomerService implements CustomerServiceInterface
{
    public function __construct(
        protected CustomerRepositoryInterface $customerRepository,
    ) {}

    public function all(object $payload)
    {
        return $this->customerRepository->all($payload);
    }

    public function findByUuid(string $uuid)
    {
        $customer = $this->customerRepository->findByUuid($uuid);

        if (! $customer) {
            throw new NotFoundException('Customer not found.');
        }

        return $customer;
    }

    public function register(object $payload)
    {
        if ($this->customerRepository->findByEmail($payload->email)) {
            throw new InvalidPayloadException('An account with this email already exists.');
        }

        return $this->customerRepository->create($payload);
    }

    public function approve(string $uuid)
    {
        $this->findByUuid($uuid);

        return $this->customerRepository->approve($uuid);
    }

    public function reject(string $uuid)
    {
        $this->findByUuid($uuid);

        return $this->customerRepository->reject($uuid);
    }

    public function orders(string $uuid)
    {
        $this->findByUuid($uuid);

        return $this->customerRepository->orders($uuid);
    }
}
