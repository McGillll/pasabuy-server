<?php

namespace App\Interfaces\Basket;

interface BasketServiceInterface
{
    public function myBasket(object $payload);

    public function addItem(object $payload);

    public function updateItem(string $itemUuid, object $payload);

    public function removeItem(string $itemUuid, object $payload);

    public function clear(object $payload);
}
