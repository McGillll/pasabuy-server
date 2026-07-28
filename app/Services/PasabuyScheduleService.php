<?php

namespace App\Services;

use App\Events\ChecklistItemToggled;
use App\Events\ChecklistRegenerated;
use App\Exceptions\InvalidPayloadException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedActionException;
use App\Interfaces\Invoice\InvoiceMailerServiceInterface;
use App\Interfaces\InvoiceSetting\InvoiceSettingRepositoryInterface;
use App\Interfaces\Order\OrderServiceInterface;
use App\Interfaces\PasabuySchedule\PasabuyScheduleRepositoryInterface;
use App\Interfaces\PasabuySchedule\PasabuyScheduleServiceInterface;
use App\Interfaces\Product\ProductRepositoryInterface;
use App\Interfaces\ScheduleInvoiceSetting\ScheduleInvoiceSettingRepositoryInterface;
use App\Models\PasabuySchedule;

class PasabuyScheduleService implements PasabuyScheduleServiceInterface
{
    public function __construct(
        protected PasabuyScheduleRepositoryInterface $pasabuyScheduleRepository,
        protected ProductRepositoryInterface $productRepository,
        protected OrderServiceInterface $orderService,
        protected InvoiceMailerServiceInterface $invoiceMailer,
        protected InvoiceSettingRepositoryInterface $invoiceSettingRepository,
        protected ScheduleInvoiceSettingRepositoryInterface $scheduleInvoiceSettingRepository,
    ) {}

    public function all(object $payload)
    {
        return $this->pasabuyScheduleRepository->all($payload);
    }

    public function openForCustomer(object $payload)
    {
        return $this->pasabuyScheduleRepository->openForCustomer($payload);
    }

    public function findByUuid(string $uuid)
    {
        $schedule = $this->pasabuyScheduleRepository->findByUuid($uuid);

        if (! $schedule) {
            throw new NotFoundException('Pasabuy schedule not found.');
        }

        return $schedule;
    }

    public function create(object $payload)
    {
        return $this->pasabuyScheduleRepository->create($payload);
    }

    public function update(string $uuid, object $payload)
    {
        $schedule = $this->findByUuid($uuid);
        $this->ensureOwnership($schedule, $payload->user_id);
        $this->assertNotFinished($schedule);

        if (isset($payload->status) && $payload->status !== $schedule->status) {
            $this->assertValidTransition($schedule, $payload->status);
        }

        return $this->pasabuyScheduleRepository->update($uuid, $payload);
    }

    public function delete(string $uuid, int $userId)
    {
        $schedule = $this->findByUuid($uuid);
        $this->ensureOwnership($schedule, $userId);
        $this->assertOpen($schedule);

        return $this->pasabuyScheduleRepository->delete($uuid);
    }

    public function close(string $uuid, int $userId)
    {
        $schedule = $this->findByUuid($uuid);
        $this->ensureOwnership($schedule, $userId);
        $this->assertValidTransition($schedule, 'closed');

        $updated = $this->pasabuyScheduleRepository->updateStatus($uuid, 'closed');
        $this->pasabuyScheduleRepository->syncChecklistOnClose($updated);

        return $updated;
    }

    public function reopen(string $uuid, int $userId)
    {
        $schedule = $this->findByUuid($uuid);
        $this->ensureOwnership($schedule, $userId);
        $this->assertValidTransition($schedule, 'open');

        return $this->pasabuyScheduleRepository->updateStatus($uuid, 'open');
    }

    public function finish(string $uuid, int $userId)
    {
        $schedule = $this->findByUuid($uuid);
        $this->ensureOwnership($schedule, $userId);
        $this->assertValidTransition($schedule, 'finished');

        $updated = $this->pasabuyScheduleRepository->updateStatus($uuid, 'finished');

        $this->snapshotInvoiceSettings($updated);
        $this->sendInvoiceEmails($uuid);

        return $updated;
    }

    public function orders(string $uuid)
    {
        $this->findByUuid($uuid);

        $orders = $this->pasabuyScheduleRepository->orders($uuid);

        $this->orderService->attachInvoiceSummary($orders->all());

        return $orders;
    }

    public function confirmProductPrice(string $scheduleUuid, string $scheduleProductUuid, object $payload)
    {
        $schedule = $this->findByUuid($scheduleUuid);
        $this->ensureOwnership($schedule, $payload->user_id);
        $this->assertNotFinished($schedule);

        if ($payload->product_status !== 'out_of_stock' && (! isset($payload->actual_price) || $payload->actual_price < 0)) {
            throw new InvalidPayloadException('A valid actual price is required unless marking the item out of stock.');
        }

        $scheduleProduct = $this->pasabuyScheduleRepository->confirmProductPrice($scheduleUuid, $scheduleProductUuid, $payload);

        if (! $scheduleProduct) {
            throw new NotFoundException('Schedule product not found.');
        }

        $summary = $this->orderService->recalculateForScheduleProduct($scheduleProduct);

        return (object) ['schedule_product' => $scheduleProduct, 'summary' => $summary];
    }

    public function summary(string $uuid)
    {
        $this->findByUuid($uuid);

        return $this->pasabuyScheduleRepository->summary($uuid);
    }

    public function productBuyers(string $scheduleUuid, string $productUuid)
    {
        $this->findByUuid($scheduleUuid);

        $product = $this->productRepository->findByUuidWithTrashed($productUuid);

        if (! $product) {
            throw new NotFoundException('Product not found.');
        }

        return $this->pasabuyScheduleRepository->productBuyers($scheduleUuid, $productUuid);
    }

    public function checklist(string $uuid)
    {
        $this->findByUuid($uuid);

        return $this->pasabuyScheduleRepository->checklist($uuid);
    }

    public function toggleChecklistItem(string $scheduleUuid, string $productUuid, object $payload)
    {
        $schedule = $this->findByUuid($scheduleUuid);
        $this->ensureOwnership($schedule, $payload->user_id);

        $item = $this->pasabuyScheduleRepository->toggleChecklistItem($scheduleUuid, $productUuid, $payload->is_checked);

        $this->broadcastChecklistItemToggled($schedule, $item);

        return $item;
    }

    public function regenerateChecklist(string $uuid, int $userId)
    {
        $schedule = $this->findByUuid($uuid);
        $this->ensureOwnership($schedule, $userId);

        if (! in_array($schedule->status, ['closed', 'finished'], true)) {
            throw new InvalidPayloadException('Checklist can only be regenerated for a closed or finished schedule.');
        }

        $this->pasabuyScheduleRepository->syncChecklistOnClose($schedule);

        $items = $this->pasabuyScheduleRepository->checklist($uuid);

        $this->broadcastChecklistRegenerated($schedule, $items);

        return $items;
    }

    public function getChecklistShareLink(string $uuid, int $userId)
    {
        $schedule = $this->findByUuid($uuid);
        $this->ensureOwnership($schedule, $userId);

        return $this->pasabuyScheduleRepository->getOrCreateChecklistShareToken($uuid);
    }

    public function regenerateChecklistShareLink(string $uuid, int $userId)
    {
        $schedule = $this->findByUuid($uuid);
        $this->ensureOwnership($schedule, $userId);

        return $this->pasabuyScheduleRepository->regenerateChecklistShareToken($uuid);
    }

    public function publicChecklist(string $token)
    {
        $schedule = $this->findByChecklistShareToken($token);

        return $this->pasabuyScheduleRepository->checklist($schedule->uuid);
    }

    public function publicToggleChecklistItem(string $token, string $productUuid, bool $isChecked)
    {
        $schedule = $this->findByChecklistShareToken($token);

        $item = $this->pasabuyScheduleRepository->toggleChecklistItem($schedule->uuid, $productUuid, $isChecked);

        $this->broadcastChecklistItemToggled($schedule, $item);

        return $item;
    }

    protected function findByChecklistShareToken(string $token)
    {
        $schedule = $this->pasabuyScheduleRepository->findByChecklistShareToken($token);

        if (! $schedule) {
            throw new NotFoundException('Checklist link not found.');
        }

        if (! in_array($schedule->status, ['closed', 'finished'], true)) {
            throw new InvalidPayloadException('This checklist is not ready yet — the seller hasn\'t closed ordering.');
        }

        return $schedule;
    }

    protected function broadcastChecklistItemToggled(PasabuySchedule $schedule, $item)
    {
        if (! $item || ! $schedule->checklist_share_token) {
            return;
        }

        broadcast(new ChecklistItemToggled($schedule->checklist_share_token, $item));
    }

    protected function broadcastChecklistRegenerated(PasabuySchedule $schedule, $items)
    {
        if (! $schedule->checklist_share_token) {
            return;
        }

        broadcast(new ChecklistRegenerated($schedule->checklist_share_token, $items));
    }

    protected function ensureOwnership(PasabuySchedule $schedule, int $userId)
    {
        if ($schedule->user_id !== $userId) {
            throw new UnauthorizedActionException('You do not own this pasabuy schedule.');
        }
    }

    protected function assertNotFinished(PasabuySchedule $schedule)
    {
        if ($schedule->status === 'finished') {
            throw new InvalidPayloadException('This pasabuy schedule is finished and can no longer be edited.');
        }
    }

    protected function assertOpen(PasabuySchedule $schedule)
    {
        if ($schedule->status !== 'open') {
            throw new InvalidPayloadException('This pasabuy schedule is no longer open and can no longer be deleted.');
        }
    }

    protected function snapshotInvoiceSettings(PasabuySchedule $schedule)
    {
        $settings = $this->invoiceSettingRepository->getForUser($schedule->user_id);

        $this->scheduleInvoiceSettingRepository->createFromSource($schedule, $settings);
    }

    protected function sendInvoiceEmails(string $uuid)
    {
        $orders = $this->pasabuyScheduleRepository->orders($uuid);

        foreach ($orders as $order) {
            $this->invoiceMailer->sendIfDue($order, 'schedule_finish');
        }
    }

    protected function assertValidTransition(PasabuySchedule $schedule, string $newStatus)
    {
        $allowed = PasabuySchedule::ALLOWED_STATUS_TRANSITIONS[$schedule->status] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw new InvalidPayloadException("Cannot change schedule status from \"{$schedule->status}\" to \"{$newStatus}\".");
        }

        if ($newStatus === 'finished') {
            $this->assertNoPendingOrders($schedule);
        }
    }

    protected function assertNoPendingOrders(PasabuySchedule $schedule)
    {
        if ($schedule->orders()->where('status', 'pending')->exists()) {
            throw new InvalidPayloadException('Cannot finish this schedule while it has pending orders that have not been confirmed or cancelled.');
        }
    }
}
