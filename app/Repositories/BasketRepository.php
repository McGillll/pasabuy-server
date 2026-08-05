<?php

namespace App\Repositories;

use App\Interfaces\Basket\BasketRepositoryInterface;
use App\Models\Basket;
use App\Models\BasketItem;
use App\Traits\InteractsWithCache;

class BasketRepository implements BasketRepositoryInterface
{
    use InteractsWithCache;

    public function findOrCreateForUser(int $userId)
    {
        $basket = Basket::with('items.product.category')->where('user_id', $userId)->first();

        if ($basket) {
            return $basket;
        }

        $basket = new Basket;
        $basket->user_id = $userId;
        $basket->save();

        return $basket->fresh('items.product.category');
    }

    public function findItemByUuid(object $basket, string $itemUuid)
    {
        return BasketItem::where('basket_id', $basket->id)->where('uuid', $itemUuid)->first();
    }

    public function upsertItem(object $basket, int $productId, int $quantity)
    {
        $item = BasketItem::where('basket_id', $basket->id)->where('product_id', $productId)->first();

        if ($item) {
            $item->quantity += $quantity;
            $item->save();
        } else {
            $item = new BasketItem;
            $item->basket_id = $basket->id;
            $item->product_id = $productId;
            $item->quantity = $quantity;
            $item->save();
        }

        $this->bumpCacheVersion('baskets');

        return $basket->fresh('items.product.category');
    }

    public function updateItemQuantity(object $item, int $quantity)
    {
        $item->quantity = $quantity;
        $item->save();

        $this->bumpCacheVersion('baskets');

        return $item->basket->fresh('items.product.category');
    }

    public function removeItem(object $item)
    {
        $basket = $item->basket;
        $item->delete();

        $this->bumpCacheVersion('baskets');

        return $basket->fresh('items.product.category');
    }

    public function clear(object $basket)
    {
        $basket->items()->delete();

        $this->bumpCacheVersion('baskets');

        return $basket->fresh('items.product.category');
    }
}
