<?php

namespace App\Interfaces\Basket;

interface BasketRepositoryInterface
{
    public function findOrCreateForUser(int $userId);

    public function findItemByUuid(object $basket, string $itemUuid);

    public function upsertItem(object $basket, int $productId, int $quantity);

    public function updateItemQuantity(object $item, int $quantity);

    public function removeItem(object $item);

    public function clear(object $basket);
}
