<?php

namespace App\Interfaces\Customer;

interface CustomerServiceInterface
{
    public function all(object $payload);

    public function findByUuid(string $uuid);

    public function register(object $payload);

    public function approve(string $uuid);

    public function reject(string $uuid);
}
