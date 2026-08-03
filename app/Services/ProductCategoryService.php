<?php

namespace App\Services;

use App\Exceptions\InvalidPayloadException;
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
        if (isset($payload->parent_uuid)) {
            $parent = $this->productCategoryRepository->findByUuid($payload->parent_uuid);

            if (! $parent) {
                throw new NotFoundException("Product category {$payload->parent_uuid} not found.");
            }

            $payload->parent_id = $parent->id;
        }

        return $this->productCategoryRepository->all($payload);
    }

    public function publicChildren(object $payload)
    {
        if (isset($payload->parent_uuid)) {
            $parent = $this->productCategoryRepository->findByUuid($payload->parent_uuid);

            if (! $parent || ! $parent->is_active) {
                throw new NotFoundException("Product category {$payload->parent_uuid} not found.");
            }

            $payload->parent_id = $parent->id;
        }

        return $this->productCategoryRepository->publicChildren($payload);
    }

    public function findByUuid(string $uuid)
    {
        $category = $this->productCategoryRepository->findByUuid($uuid);

        if (! $category) {
            throw new NotFoundException('Product category not found.');
        }

        return $category;
    }

    public function publicFindByUuid(string $uuid)
    {
        $category = $this->productCategoryRepository->publicFindByUuid($uuid);

        if (! $category) {
            throw new NotFoundException('Product category not found.');
        }

        return $category;
    }

    public function create(object $payload)
    {
        $this->resolveParent($payload);

        if (isset($payload->image)) {
            $payload->image_path = $this->storeImage($payload);
        }

        return $this->productCategoryRepository->create($payload);
    }

    public function update(string $uuid, object $payload)
    {
        $category = $this->findByUuid($uuid);

        $this->resolveParent($payload, $category);

        if (isset($payload->image)) {
            $previousImagePath = $category->image_path;
            $payload->image_path = $this->storeImage($payload);

            if ($previousImagePath) {
                Storage::delete($previousImagePath);
            }
        }

        return $this->productCategoryRepository->update($uuid, $payload);
    }

    public function delete(string $uuid)
    {
        $category = $this->findByUuid($uuid);

        if ($category->children()->exists()) {
            throw new InvalidPayloadException('This category still has subcategories. Move or delete them first.');
        }

        if ($category->image_path) {
            Storage::delete($category->image_path);
        }

        return $this->productCategoryRepository->delete($uuid);
    }

    protected function storeImage(object $payload)
    {
        $manager = new ImageManager(Driver::class);
        $image = $manager->decodePath($payload->image->getRealPath())->orient();
        $encoded = $image->encode(new WebpEncoder(quality: 82));

        $path = 'product-categories/'.Str::slug($payload->name).'-'.uniqid().'.webp';

        if (! Storage::put($path, (string) $encoded)) {
            throw new RuntimeException("Failed to store product category image at {$path}.");
        }

        return $path;
    }

    protected function resolveParent(object $payload, $category = null)
    {
        if (! isset($payload->parent_uuid)) {
            $payload->parent_id = null;

            return;
        }

        $parent = $this->productCategoryRepository->findByUuid($payload->parent_uuid);

        if (! $parent) {
            throw new NotFoundException("Product category {$payload->parent_uuid} not found.");
        }

        if ($category) {
            $this->assertNotDescendant($category, $parent);
        }

        $payload->parent_id = $parent->id;
    }

    protected function assertNotDescendant($category, $candidateParent)
    {
        $ancestor = $candidateParent;

        while ($ancestor) {
            if ($ancestor->id === $category->id) {
                throw new InvalidPayloadException('A category cannot be moved under itself or one of its own subcategories.');
            }

            $ancestor = $ancestor->parent;
        }
    }
}
