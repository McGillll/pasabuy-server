<?php

namespace App\Interfaces\PasabuySchedule;

interface PasabuyScheduleRepositoryInterface
{
    public function all(object $payload);

    public function findByUuid(string $uuid);

    public function create(object $payload);

    public function update(string $uuid, object $payload);

    public function delete(string $uuid);

    public function updateStatus(string $uuid, string $status);

    public function syncProducts(string $uuid, object $payload);

    public function orders(string $uuid);

    public function confirmProductPrice(string $scheduleUuid, string $scheduleProductUuid, object $payload);

    public function findScheduleProduct(int $scheduleId, int $productId);

    public function summary(string $uuid);
}
