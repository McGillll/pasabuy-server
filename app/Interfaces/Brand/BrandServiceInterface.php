<?php

namespace App\Interfaces\Brand;

interface BrandServiceInterface
{
    public function all(object $payload);

    public function findByUuid(string $uuid);

    public function create(object $payload);

    public function update(string $uuid, object $payload);

    public function delete(string $uuid);
}
