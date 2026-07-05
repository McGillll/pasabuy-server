<?php

namespace App\Interfaces\Order;

interface OrderRepositoryInterface
{
    public function findByUuid(string $uuid);

    public function findOwnedByUuid(string $uuid, int $userId);

    public function findActiveForSchedule(int $userId, int $scheduleId);

    public function myOrders(int $userId, object $payload);

    public function updateStatus(string $uuid, object $payload);

    public function create(object $payload);

    public function addItems(object $order, object $payload);

    public function replaceItems(object $order, object $payload);

    public function orderedQuantity(int $scheduleId, int $productId, ?int $excludeOrderId = null);
}
