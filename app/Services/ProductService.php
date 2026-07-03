<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Interfaces\Product\ProductRepositoryInterface;
use App\Interfaces\Product\ProductServiceInterface;

class ProductService implements ProductServiceInterface
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository,
    ) {}

    public function all(object $payload)
    {
        return $this->productRepository->all($payload);
    }

    public function findByUuid(string $uuid)
    {
        $product = $this->productRepository->findByUuid($uuid);

        if (! $product) {
            throw new NotFoundException('Product not found.');
        }

        return $product;
    }

    public function create(object $payload)
    {
        return $this->productRepository->create($payload);
    }

    public function update(string $uuid, object $payload)
    {
        $this->findByUuid($uuid);

        return $this->productRepository->update($uuid, $payload);
    }

    public function delete(string $uuid)
    {
        $this->findByUuid($uuid);

        return $this->productRepository->delete($uuid);
    }
}
