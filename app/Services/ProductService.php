<?php

namespace App\Services;

use App\Exceptions\InvalidPayloadException;
use App\Exceptions\NotFoundException;
use App\Interfaces\PasabuySchedule\PasabuyScheduleRepositoryInterface;
use App\Interfaces\Product\ProductRepositoryInterface;
use App\Interfaces\Product\ProductServiceInterface;
use App\Interfaces\ProductCategory\ProductCategoryRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use RuntimeException;

class ProductService implements ProductServiceInterface
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository,
        protected ProductCategoryRepositoryInterface $productCategoryRepository,
        protected PasabuyScheduleRepositoryInterface $pasabuyScheduleRepository,
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
            $previousImagePath = $product->image_path;
            $payload->image_path = $this->storeImage($payload);

            if ($previousImagePath) {
                Storage::delete($previousImagePath);
            }
        }

        return $this->productRepository->update($uuid, $payload);
    }

    protected function storeImage(object $payload)
    {
        $manager = new ImageManager(Driver::class);
        $image = $manager->decodePath($payload->image->getRealPath())->orient();
        $encoded = $image->encode(new WebpEncoder(quality: 82));

        $path = 'products/'.Str::slug($payload->name).'-'.uniqid().'.webp';

        if (! Storage::put($path, (string) $encoded)) {
            throw new RuntimeException("Failed to store product image at {$path}.");
        }

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

        if ($category->children()->exists()) {
            throw new InvalidPayloadException('Products can only be assigned to a subcategory, not a parent category.');
        }

        $payload->category_id = $category->id;
    }

    public function delete(string $uuid)
    {
        $product = $this->findByUuid($uuid);

        $this->pasabuyScheduleRepository->removeProductFromOpenSchedules($product->id);

        if ($product->image_path) {
            Storage::delete($product->image_path);
        }

        return $this->productRepository->delete($uuid);
    }

    public function topSelling(object $payload)
    {
        return $this->productRepository->topSelling($payload);
    }
}
