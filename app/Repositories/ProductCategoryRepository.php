<?php

namespace App\Repositories;

use App\Interfaces\ProductCategory\ProductCategoryRepositoryInterface;
use App\Models\ProductCategory;

class ProductCategoryRepository implements ProductCategoryRepositoryInterface
{
    public function all(object $payload)
    {
        return ProductCategory::filter((array) $payload)
            ->orderBy('name')
            ->paginate($payload->per_page ?? 15);
    }

    public function findByUuid(string $uuid)
    {
        return ProductCategory::where('uuid', $uuid)->first();
    }

    public function create(object $payload)
    {
        $category = new ProductCategory;
        $category->name = $payload->name;
        $category->description = $payload->description ?? null;
        $category->is_active = $payload->is_active ?? true;
        $category->save();

        return $category;
    }

    public function update(string $uuid, object $payload)
    {
        $category = $this->findByUuid($uuid);

        $category->name = $payload->name;
        $category->description = $payload->description ?? null;
        $category->is_active = $payload->is_active ?? $category->is_active;
        $category->save();

        return $category;
    }

    public function delete(string $uuid)
    {
        $category = $this->findByUuid($uuid);
        $category->delete();

        return $category;
    }
}
