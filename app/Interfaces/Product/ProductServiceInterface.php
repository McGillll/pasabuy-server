<?php

namespace App\Interfaces\Product;

interface ProductServiceInterface
{
    public function all(object $payload);

    public function publicCatalog(object $payload);

    public function publicTopSelling(object $payload);

    public function publicFindByUuid(string $uuid);

    public function findByUuid(string $uuid);

    public function create(object $payload);

    public function update(string $uuid, object $payload);

    public function delete(string $uuid);

    public function topSelling(object $payload);
}
