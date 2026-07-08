<?php

namespace App\Interfaces\PasabuySchedule;

interface PasabuyScheduleServiceInterface
{
    public function all(object $payload);

    public function findByUuid(string $uuid);

    public function create(object $payload);

    public function update(string $uuid, object $payload);

    public function delete(string $uuid, int $userId);

    public function close(string $uuid, int $userId);

    public function reopen(string $uuid, int $userId);

    public function syncProducts(string $uuid, object $payload);

    public function orders(string $uuid);

    public function confirmProductPrice(string $scheduleUuid, string $scheduleProductUuid, object $payload);

    public function summary(string $uuid);
}
