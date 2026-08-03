<?php

namespace App\Interfaces\ProductCategory;

interface ProductCategoryServiceInterface
{
    public function all(object $payload);

    public function publicChildren(object $payload);

    public function publicFindByUuid(string $uuid);

    public function findByUuid(string $uuid);

    public function create(object $payload);

    public function update(string $uuid, object $payload);

    public function delete(string $uuid);
}
