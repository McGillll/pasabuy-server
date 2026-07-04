<?php

namespace App\Interfaces\Customer;

interface CustomerRepositoryInterface
{
    public function all(object $payload);

    public function findByUuid(string $uuid);

    public function findByEmail(string $email);

    public function create(object $payload);

    public function approve(string $uuid);

    public function reject(string $uuid);
}
