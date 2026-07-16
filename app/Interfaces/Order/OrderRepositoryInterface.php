<?php

namespace App\Interfaces\Order;

interface OrderRepositoryInterface
{
    public function all(object $payload);

    public function findByUuid(string $uuid);

    public function findOwnedByUuid(string $uuid, int $userId);

    public function findByUuidForSchedule(string $uuid, int $scheduleId);

    public function findActiveForSchedule(int $userId, int $scheduleId);

    public function findUnpaidLiveOrderForUser(int $userId);

    public function myOrders(int $userId, object $payload);

    public function updateStatus(string $uuid, object $payload);

    public function updateDeliveryStatus(string $uuid, object $payload);

    public function recalcPaymentStatus(object $order);

    public function create(object $payload);

    public function addItems(object $order, object $payload);

    public function replaceItems(object $order, object $payload);

    public function cancel(object $order);

    public function findLiveItemsForScheduleProduct(int $scheduleProductId);

    public function applyResolvedPrice(object $item, ?float $actualPrice, ?int $quantity = null, ?int $bonusQuantity = null);
}
