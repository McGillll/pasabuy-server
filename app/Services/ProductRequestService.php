<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Interfaces\ProductRequest\ProductRequestRepositoryInterface;
use App\Interfaces\ProductRequest\ProductRequestServiceInterface;

class ProductRequestService implements ProductRequestServiceInterface
{
    public function __construct(
        protected ProductRequestRepositoryInterface $productRequestRepository,
    ) {}

    public function all(object $payload)
    {
        return $this->productRequestRepository->all($payload);
    }

    public function myRequests(object $payload)
    {
        return $this->productRequestRepository->myRequests($payload->user->id, $payload);
    }

    public function create(object $payload)
    {
        return $this->productRequestRepository->create((object) [
            'user_id' => $payload->user->id,
            'pasabuy_schedule_id' => $payload->pasabuy_schedule_id ?? null,
            'note' => $payload->note,
        ]);
    }

    public function markReviewed(string $uuid)
    {
        $productRequest = $this->productRequestRepository->findByUuid($uuid);

        if (! $productRequest) {
            throw new NotFoundException('Product request not found.');
        }

        return $this->productRequestRepository->markReviewed($uuid);
    }
}
