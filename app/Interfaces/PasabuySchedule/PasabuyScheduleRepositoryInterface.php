<?php

namespace App\Interfaces\PasabuySchedule;

use App\Models\PasabuySchedule;
use App\Models\Product;

interface PasabuyScheduleRepositoryInterface
{
    public function all(object $payload);

    public function findByUuid(string $uuid);

    public function create(object $payload);

    public function update(string $uuid, object $payload);

    public function delete(string $uuid);

    public function updateStatus(string $uuid, string $status);

    public function orders(string $uuid);

    public function confirmProductPrice(string $scheduleUuid, string $scheduleProductUuid, object $payload);

    public function findScheduleProduct(int $scheduleId, int $productId);

    public function findOrCreateScheduleProduct(PasabuySchedule $schedule, Product $product);

    public function summary(string $uuid);

    public function productBuyers(string $scheduleUuid, string $productUuid);

    public function checklist(string $uuid);

    public function toggleChecklistItem(string $scheduleUuid, string $productUuid, bool $isChecked);

    public function syncChecklistOnClose(PasabuySchedule $schedule);

    public function findByChecklistShareToken(string $token);

    public function getOrCreateChecklistShareToken(string $uuid);

    public function regenerateChecklistShareToken(string $uuid);
}
