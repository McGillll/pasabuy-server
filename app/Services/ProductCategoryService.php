<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Interfaces\ProductCategory\ProductCategoryRepositoryInterface;
use App\Interfaces\ProductCategory\ProductCategoryServiceInterface;

class ProductCategoryService implements ProductCategoryServiceInterface
{
    public function __construct(
        protected ProductCategoryRepositoryInterface $productCategoryRepository,
    ) {}

    public function all(object $payload)
    {
        return $this->productCategoryRepository->all($payload);
    }

    public function findByUuid(string $uuid)
    {
        $category = $this->productCategoryRepository->findByUuid($uuid);

        if (! $category) {
            throw new NotFoundException('Product category not found.');
        }

        return $category;
    }

    public function create(object $payload)
    {
        return $this->productCategoryRepository->create($payload);
    }

    public function update(string $uuid, object $payload)
    {
        $this->findByUuid($uuid);

        return $this->productCategoryRepository->update($uuid, $payload);
    }

    public function delete(string $uuid)
    {
        $this->findByUuid($uuid);

        return $this->productCategoryRepository->delete($uuid);
    }
}
