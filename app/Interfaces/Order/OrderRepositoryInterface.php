<?php

namespace App\Interfaces\Order;

interface OrderRepositoryInterface
{
    public function findByUuid(string $uuid);

    public function findOwnedByUuid(string $uuid, int $userId);

    public function findByUuidForSchedule(string $uuid, int $scheduleId);

    public function findActiveForSchedule(int $userId, int $scheduleId);

    public function myOrders(int $userId, object $payload);

    public function updateStatus(string $uuid, object $payload);

    public function create(object $payload);

    public function addItems(object $order, object $payload);

    public function replaceItems(object $order, object $payload);

    public function cancel(object $order);

    public function findLiveItemsForProduct(int $scheduleId, int $productId);

    public function applyResolvedPrice(object $item, ?float $actualPrice, ?int $quantity = null);

    public function flagNeedsConfirmation(object $item, ?float $proposedPrice);

    public function addOrUpdateBonusItem(object $order, object $scheduleProduct);

    public function removeBonusItem(object $order, int $productId);

    public function markItemRemoved(object $item);

    public function revertToPendingIfResolved(object $order);
}
