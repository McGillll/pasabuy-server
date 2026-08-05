<?php

namespace App\Services;

use App\Exceptions\InvalidPayloadException;
use App\Exceptions\NotFoundException;
use App\Interfaces\Basket\BasketRepositoryInterface;
use App\Interfaces\Basket\BasketServiceInterface;
use App\Interfaces\Product\ProductRepositoryInterface;

class BasketService implements BasketServiceInterface
{
    public function __construct(
        protected BasketRepositoryInterface $basketRepository,
        protected ProductRepositoryInterface $productRepository,
    ) {}

    public function myBasket(object $payload)
    {
        return $this->basketRepository->findOrCreateForUser($payload->user_id);
    }

    public function addItem(object $payload)
    {
        $product = $this->productRepository->findByUuid($payload->product_uuid);

        if (! $product || ! $product->is_active) {
            throw new InvalidPayloadException('This item is not available.');
        }

        $basket = $this->basketRepository->findOrCreateForUser($payload->user_id);

        return $this->basketRepository->upsertItem($basket, $product->id, $payload->quantity);
    }

    public function updateItem(string $itemUuid, object $payload)
    {
        $basket = $this->basketRepository->findOrCreateForUser($payload->user_id);
        $item = $this->basketRepository->findItemByUuid($basket, $itemUuid);

        if (! $item) {
            throw new NotFoundException('Basket item not found.');
        }

        return $this->basketRepository->updateItemQuantity($item, $payload->quantity);
    }

    public function removeItem(string $itemUuid, object $payload)
    {
        $basket = $this->basketRepository->findOrCreateForUser($payload->user_id);
        $item = $this->basketRepository->findItemByUuid($basket, $itemUuid);

        if (! $item) {
            throw new NotFoundException('Basket item not found.');
        }

        return $this->basketRepository->removeItem($item);
    }

    public function clear(object $payload)
    {
        $basket = $this->basketRepository->findOrCreateForUser($payload->user_id);

        return $this->basketRepository->clear($basket);
    }
}
