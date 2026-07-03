<?php

namespace App\Interfaces\Order;

interface OrderServiceInterface
{
    public function updateStatus(string $uuid, object $payload);

    public function createForSchedule(string $scheduleUuid, object $payload);
}
