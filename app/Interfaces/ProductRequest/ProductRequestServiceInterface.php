<?php

namespace App\Interfaces\ProductRequest;

interface ProductRequestServiceInterface
{
    public function all(object $payload);

    public function myRequests(object $payload);

    public function create(object $payload);

    public function markReviewed(string $uuid);
}
