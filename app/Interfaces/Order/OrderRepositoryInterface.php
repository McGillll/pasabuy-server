<?php

namespace App\Interfaces\Order;

interface OrderRepositoryInterface
{
    public function findByUuid(string $uuid);

    public function updateStatus(string $uuid, object $payload);

    public function create(object $payload);

    public function addItems(object $order, object $payload);

    public function orderedQuantity(int $scheduleId, int $productId);
}
