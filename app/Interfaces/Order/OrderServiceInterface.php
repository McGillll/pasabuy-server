<?php

namespace App\Interfaces\Order;

interface OrderServiceInterface
{
    public function updateStatus(string $uuid, object $payload);

    public function createForSchedule(string $scheduleUuid, object $payload);

    public function updateItemsForCustomer(string $orderUuid, object $payload);

    public function myOrders(object $payload);

    public function findOwnedByUuid(string $uuid, object $payload);

    public function cancelForCustomer(string $uuid, object $payload);
}
