<?php

namespace App\Interfaces\ProductRequest;

interface ProductRequestRepositoryInterface
{
    public function all(object $payload);

    public function myRequests(int $userId, object $payload);

    public function findByUuid(string $uuid);

    public function create(object $payload);

    public function markReviewed(string $uuid);
}
