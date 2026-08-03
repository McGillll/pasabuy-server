<?php

namespace App\Services;

use App\Interfaces\Invoice\InvoiceMailerServiceInterface;
use App\Interfaces\Invoice\InvoiceServiceInterface;
use App\Mail\OverdueReminderMail;
use App\Mail\PasabuyInvoiceMail;
use App\Models\PasabuyOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class InvoiceMailerService implements InvoiceMailerServiceInterface
{
    public function __construct(
        protected InvoiceServiceInterface $invoiceService,
    ) {}

    /**
     * Sends the invoice email only once per order — guarded by invoice_sent_at so re-running
     * the schedule-finish sweep is a no-op for orders already emailed.
     */
    public function sendIfDue(PasabuyOrder $order)
    {
        if ($order->invoice_sent_at !== null) {
            return;
        }

        if (! in_array($order->status, PasabuyOrder::LIVE_STATUSES, true)) {
            return;
        }

        if (! $order->user || ! $order->user->email) {
            Log::warning('Skipped pasabuy invoice email — order has no customer email on file.', [
                'order_uuid' => $order->uuid,
            ]);

            return;
        }

        try {
            $invoice = $this->invoiceService->buildForOrder($order);

            Mail::to($order->user->email)->queue(new PasabuyInvoiceMail($order, $invoice));

            $order->invoice_sent_at = now();
            $order->save();
        } catch (Throwable $exception) {
            Log::warning('Failed to queue pasabuy invoice email for order.', [
                'order_uuid' => $order->uuid,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Reminder cadence: day 1, day 7, day 14, then every 7 days — not literal daily, since
     * the balance itself already communicates urgency each time; the reminder's job is to
     * report fresh news (more days overdue, more interest), not repeat the same email.
     * Guarded by last_overdue_reminder_at so re-running the sweep the same day is a no-op.
     */
    public function sendOverdueReminderIfDue(PasabuyOrder $order, array $invoice)
    {
        if (! $invoice['due_date']) {
            return;
        }

        $daysOverdue = (int) floor($invoice['due_date']->diffInDays(now()));

        if (! $this->isReminderDueDay($daysOverdue)) {
            return;
        }

        if ($order->last_overdue_reminder_at && $order->last_overdue_reminder_at->isSameDay(now())) {
            return;
        }

        if (! $order->user || ! $order->user->email) {
            Log::warning('Skipped overdue reminder email — order has no customer email on file.', [
                'order_uuid' => $order->uuid,
            ]);

            return;
        }

        try {
            Mail::to($order->user->email)->queue(new OverdueReminderMail($order, $invoice, $daysOverdue));

            $order->last_overdue_reminder_at = now();
            $order->save();
        } catch (Throwable $exception) {
            Log::warning('Failed to queue overdue reminder email for order.', [
                'order_uuid' => $order->uuid,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function isReminderDueDay(int $daysOverdue)
    {
        if ($daysOverdue < 1) {
            return false;
        }

        if (in_array($daysOverdue, [1, 7, 14], true)) {
            return true;
        }

        return $daysOverdue > 14 && ($daysOverdue - 14) % 7 === 0;
    }
}
