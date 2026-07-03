<?php

namespace App\Repositories;

use App\Interfaces\Product\ProductRepositoryInterface;
use App\Models\Product;

class ProductRepository implements ProductRepositoryInterface
{
    public function all(object $payload)
    {
        return Product::filter((array) $payload)
            ->orderBy('name')
            ->paginate($payload->per_page ?? 15);
    }

    public function findByUuid(string $uuid)
    {
        return Product::where('uuid', $uuid)->first();
    }

    public function create(object $payload)
    {
        $product = new Product;
        $product->name = $payload->name;
        $product->description = $payload->description ?? null;
        $product->image_path = $payload->image_path ?? null;
        $product->is_active = $payload->is_active ?? true;
        $product->save();

        return $product;
    }

    public function update(string $uuid, object $payload)
    {
        $product = $this->findByUuid($uuid);

        $product->name = $payload->name;
        $product->description = $payload->description ?? null;
        if (isset($payload->image_path)) {
            $product->image_path = $payload->image_path;
        }
        $product->is_active = $payload->is_active ?? $product->is_active;
        $product->save();

        return $product;
    }

    public function delete(string $uuid)
    {
        $product = $this->findByUuid($uuid);
        $product->delete();

        return $product;
    }
}
