<?php

namespace App\Repositories;

use App\Interfaces\ProductRequest\ProductRequestRepositoryInterface;
use App\Models\ProductRequest;

class ProductRequestRepository implements ProductRequestRepositoryInterface
{
    public function all(object $payload)
    {
        return ProductRequest::with('user', 'pasabuySchedule')
            ->latest()
            ->paginate($payload->per_page ?? 15);
    }

    public function myRequests(int $userId, object $payload)
    {
        return ProductRequest::with('pasabuySchedule')
            ->where('user_id', $userId)
            ->latest()
            ->paginate($payload->per_page ?? 15);
    }

    public function findByUuid(string $uuid)
    {
        return ProductRequest::where('uuid', $uuid)->first();
    }

    public function create(object $payload)
    {
        $productRequest = new ProductRequest;
        $productRequest->user_id = $payload->user_id;
        $productRequest->pasabuy_schedule_id = $payload->pasabuy_schedule_id ?? null;
        $productRequest->note = $payload->note;
        $productRequest->status = 'open';
        $productRequest->save();

        return $productRequest;
    }

    public function markReviewed(string $uuid)
    {
        $productRequest = $this->findByUuid($uuid);
        $productRequest->status = 'reviewed';
        $productRequest->save();

        return $productRequest;
    }
}
