<?php

namespace App\Services;

use App\Interfaces\Invoice\InvoiceServiceInterface;
use App\Interfaces\InvoiceSetting\InvoiceSettingRepositoryInterface;
use App\Models\PasabuyOrder;
use Illuminate\Support\Carbon;

class InvoiceService implements InvoiceServiceInterface
{
    public function __construct(
        protected InvoiceSettingRepositoryInterface $invoiceSettingRepository,
    ) {}

    public function buildForOrder(PasabuyOrder $order)
    {
        $settings = $this->invoiceSettingRepository->getForUser($order->pasabuySchedule->user_id);

        $subtotal = (float) $order->total_amount;
        $markupAmount = $subtotal > (float) $settings->markup_threshold_amount
            ? round($subtotal * (float) $settings->markup_percentage / 100, 2)
            : (float) $settings->markup_fixed_amount;

        $invoiceTotal = round($subtotal + $markupAmount, 2);
        $dueDate = $this->resolveDueDate($order, $settings);
        $amountPaid = (float) $order->payments()->sum('amount');

        $interestBaseAmount = $settings->interest_base === 'outstanding_balance'
            ? $invoiceTotal - $amountPaid
            : $invoiceTotal;

        [$elapsedPeriods, $interestAmount] = $settings->is_active
            ? $this->calculateInterest($dueDate, $interestBaseAmount, $settings)
            : [0, 0.0];

        $totalPayable = round($invoiceTotal + $interestAmount, 2);

        return [
            'subtotal' => $subtotal,
            'markup_amount' => $markupAmount,
            'invoice_total' => $invoiceTotal,
            'due_date' => $dueDate,
            'is_overdue' => $dueDate !== null && now()->gt($dueDate),
            'elapsed_periods' => $elapsedPeriods,
            'interest_amount' => round($interestAmount, 2),
            'total_payable' => $totalPayable,
            'amount_paid' => $amountPaid,
            'balance_due' => round($totalPayable - $amountPaid, 2),
        ];
    }

    protected function resolveDueDate(PasabuyOrder $order, $settings)
    {
        $sourceDate = $settings->due_date_reference === 'schedule_finish'
            ? $order->pasabuySchedule->finished_at
            : $order->delivered_at;

        return $sourceDate ? $sourceDate->copy()->addDays($settings->due_days) : null;
    }

    protected function calculateInterest(?Carbon $dueDate, float $interestBaseAmount, $settings)
    {
        if (! $dueDate || $interestBaseAmount <= 0 || now()->lte($dueDate)) {
            return [0, 0.0];
        }

        $periods = match ($settings->interest_period) {
            'day' => $dueDate->diffInDays(now()),
            'week' => $dueDate->diffInWeeks(now()),
            'month' => $dueDate->diffInMonths(now()),
        };

        $periods = max((int) floor($periods), 1);
        $rate = (float) $settings->interest_rate / 100;
        $interest = $interestBaseAmount * ((1 + $rate) ** $periods - 1);

        return [$periods, $interest];
    }
}
