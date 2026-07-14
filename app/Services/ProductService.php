<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Interfaces\Product\ProductRepositoryInterface;
use App\Interfaces\Product\ProductServiceInterface;
use App\Interfaces\ProductCategory\ProductCategoryRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

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

    public function publicCatalog(object $payload)
    {
        return $this->productRepository->publicCatalog($payload);
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

        if (isset($payload->image)) {
            $payload->image_path = $this->storeImage($payload);
        }

        return $this->productRepository->create($payload);
    }

    public function update(string $uuid, object $payload)
    {
        $product = $this->findByUuid($uuid);
        $this->resolveCategory($payload);

        if (isset($payload->image)) {
            if ($product->image_path) {
                Storage::delete($product->image_path);
            }
            $payload->image_path = $this->storeImage($payload);
        }

        return $this->productRepository->update($uuid, $payload);
    }

    protected function storeImage(object $payload)
    {
        $manager = new ImageManager(Driver::class);
        $image = $manager->decodePath($payload->image->getRealPath())->orient();
        $encoded = $image->encode(new WebpEncoder(quality: 82));

        $path = 'products/'.Str::slug($payload->name).'-'.uniqid().'.webp';
        Storage::put($path, (string) $encoded);

        return $path;
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
        $product = $this->findByUuid($uuid);

        if ($product->image_path) {
            Storage::delete($product->image_path);
        }

        return $this->productRepository->delete($uuid);
    }
}
