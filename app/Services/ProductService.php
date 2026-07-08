<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Interfaces\Product\ProductRepositoryInterface;
use App\Interfaces\Product\ProductServiceInterface;
use App\Interfaces\ProductCategory\ProductCategoryRepositoryInterface;

class ProductService implements ProductServiceInterface
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository,
        protected ProductCategoryRepositoryInterface $productCategoryRepository,
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
        $this->resolveCategory($payload);

        return $this->productRepository->create($payload);
    }

    public function update(string $uuid, object $payload)
    {
        $this->findByUuid($uuid);
        $this->resolveCategory($payload);

        return $this->productRepository->update($uuid, $payload);
    }

    protected function resolveCategory(object $payload)
    {
        if (! isset($payload->category_uuid)) {
            $payload->category_id = null;

            return;
        }

        $category = $this->productCategoryRepository->findByUuid($payload->category_uuid);

        if (! $category) {
            throw new NotFoundException("Product category {$payload->category_uuid} not found.");
        }

        $payload->category_id = $category->id;
    }

    public function delete(string $uuid)
    {
        $this->findByUuid($uuid);

        return $this->productRepository->delete($uuid);
    }
}
