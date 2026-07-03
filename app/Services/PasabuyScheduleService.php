<?php

namespace App\Services;

use App\Exceptions\InvalidPayloadException;
use App\Exceptions\NotFoundException;
use App\Interfaces\PasabuySchedule\PasabuyScheduleRepositoryInterface;
use App\Interfaces\PasabuySchedule\PasabuyScheduleServiceInterface;
use App\Interfaces\Product\ProductRepositoryInterface;

class PasabuyScheduleService implements PasabuyScheduleServiceInterface
{
    public function __construct(
        protected PasabuyScheduleRepositoryInterface $pasabuyScheduleRepository,
        protected ProductRepositoryInterface $productRepository,
    ) {}

    public function all(object $payload)
    {
        return $this->pasabuyScheduleRepository->all($payload);
    }

    public function findByUuid(string $uuid)
    {
        $schedule = $this->pasabuyScheduleRepository->findByUuid($uuid);

        if (! $schedule) {
            throw new NotFoundException('Pasabuy schedule not found.');
        }

        return $schedule;
    }

    public function create(object $payload)
    {
        return $this->pasabuyScheduleRepository->create($payload);
    }

    public function update(string $uuid, object $payload)
    {
        $this->findByUuid($uuid);

        return $this->pasabuyScheduleRepository->update($uuid, $payload);
    }

    public function delete(string $uuid)
    {
        $this->findByUuid($uuid);

        return $this->pasabuyScheduleRepository->delete($uuid);
    }

    public function syncProducts(string $uuid, object $payload)
    {
        $this->findByUuid($uuid);

        $products = collect($payload->products ?? [])->map(function ($item) {
            $product = $this->productRepository->findByUuid($item['product_uuid']);

            if (! $product) {
                throw new NotFoundException("Product {$item['product_uuid']} not found.");
            }

            if (! isset($item['price']) || $item['price'] < 0) {
                throw new InvalidPayloadException("A valid price is required for product {$product->name}.");
            }

            return [
                'product_id' => $product->id,
                'price' => $item['price'],
                'max_quantity' => $item['max_quantity'] ?? null,
            ];
        })->all();

        return $this->pasabuyScheduleRepository->syncProducts($uuid, (object) ['products' => $products]);
    }

    public function orders(string $uuid)
    {
        $this->findByUuid($uuid);

        return $this->pasabuyScheduleRepository->orders($uuid);
    }
}
