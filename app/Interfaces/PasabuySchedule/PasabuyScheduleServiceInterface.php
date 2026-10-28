<?php

namespace App\Interfaces\PasabuySchedule;

use App\Models\User;

interface PasabuyScheduleServiceInterface
{
    public function all(object $payload);

    public function openForCustomer(object $payload);

    public function findByUuid(string $uuid);

    public function create(object $payload);

    public function update(string $uuid, object $payload);

    public function delete(string $uuid, User $user);

    public function close(string $uuid, User $user);

    public function reopen(string $uuid, User $user);

    public function finish(string $uuid, User $user, string $dueDate);

    public function orders(string $uuid);

    public function confirmProductPrice(string $scheduleUuid, string $scheduleProductUuid, object $payload);

    public function summary(string $uuid);

    public function productBuyers(string $scheduleUuid, string $productUuid);

    public function checklist(string $uuid);

    public function toggleChecklistItem(string $scheduleUuid, string $productUuid, object $payload);

    public function regenerateChecklist(string $uuid, User $user);

    public function getChecklistShareLink(string $uuid, User $user);

    public function regenerateChecklistShareLink(string $uuid, User $user);

    public function publicChecklist(string $token);

    public function publicToggleChecklistItem(string $token, string $productUuid, bool $isChecked);

    public function publicConfirmChecklistItemPrice(string $token, string $productUuid, object $payload);
}
