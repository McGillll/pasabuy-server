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
        if (! $order->relationLoaded('items')) {
            $order->load('items');
        }

        if (! $order->relationLoaded('payments')) {
            $order->load('payments');
        }

        $settings = $this->invoiceSettingRepository->getForUser($order->pasabuySchedule->user_id);

        $subtotal = (float) $order->total_amount;
        $markupAmount = $subtotal > (float) $settings->markup_threshold_amount
            ? round($subtotal * (float) $settings->markup_percentage / 100, 2)
            : (float) $settings->markup_fixed_amount;

        $total = round($subtotal + $markupAmount, 2);
        $dueDate = $this->resolveDueDate($order, $settings);
        $isOverdue = $dueDate !== null && now()->gt($dueDate);

        $paymentLines = $this->buildPaymentLines($order);
        $amountPaid = round(array_sum(array_column($paymentLines, 'amount')), 2);

        $interestBaseAmount = $settings->interest_base === 'outstanding_balance'
            ? $total - $amountPaid
            : $total;

        $elapsedPeriods = $this->resolveElapsedPeriods($dueDate, $interestBaseAmount, $settings);
        $interestLines = $this->buildInterestLines($elapsedPeriods, $interestBaseAmount, $settings);
        $interestTotal = round(array_sum(array_column($interestLines, 'interest_amount')), 2);
        $grandTotal = $interestLines !== [] ? end($interestLines)['running_total'] : $total;

        return [
            'items' => $this->buildItemLines($order),
            'subtotal' => $subtotal,
            'markup_amount' => $markupAmount,
            'total' => $total,
            'due_date' => $dueDate,
            'is_overdue' => $isOverdue,
            'interest_base' => $settings->interest_base,
            'interest_lines' => $interestLines,
            'interest_total' => $interestTotal,
            'grand_total' => $grandTotal,
            'payments' => $paymentLines,
            'amount_paid' => $amountPaid,
            'balance_due' => round($grandTotal - $amountPaid, 2),
        ];
    }

    protected function resolveDueDate(PasabuyOrder $order, $settings)
    {
        $sourceDate = $settings->due_date_reference === 'schedule_finish'
            ? $order->pasabuySchedule->finished_at
            : $order->delivered_at;

        return $sourceDate ? $sourceDate->copy()->addDays($settings->due_days) : null;
    }

    protected function resolveElapsedPeriods(?Carbon $dueDate, float $interestBaseAmount, $settings)
    {
        if (! $settings->is_active || ! $dueDate || $interestBaseAmount <= 0 || now()->lte($dueDate)) {
            return 0;
        }

        $periods = match ($settings->interest_period) {
            'day' => $dueDate->diffInDays(now()),
            'week' => $dueDate->diffInWeeks(now()),
            'month' => $dueDate->diffInMonths(now()),
        };

        return max((int) floor($periods), 1);
    }

    protected function buildInterestLines(int $periods, float $base, $settings)
    {
        if ($periods <= 0) {
            return [];
        }

        $rate = (float) $settings->interest_rate / 100;
        $unit = $settings->interest_period;
        $runningTotal = $base;
        $lines = [];

        for ($period = 1; $period <= $periods; $period++) {
            $interestAmount = round($runningTotal * $rate, 2);
            $runningTotal = round($runningTotal + $interestAmount, 2);

            $lines[] = [
                'period' => $period,
                'label' => "Overdue interest ({$unit} {$period})",
                'interest_amount' => $interestAmount,
                'running_total' => $runningTotal,
            ];
        }

        return $lines;
    }

    protected function buildItemLines(PasabuyOrder $order)
    {
        return $order->items->map(fn ($item) => [
            'product_name' => $item->product_name,
            'quantity' => $item->quantity + $item->bonus_quantity,
            'unit_price' => (float) ($item->actual_price ?? $item->estimated_price),
            'line_total' => (float) $item->subtotal,
        ])->all();
    }

    protected function buildPaymentLines(PasabuyOrder $order)
    {
        return $order->payments->sortBy('paid_at')->values()->map(fn ($payment) => [
            'paid_at' => $payment->paid_at,
            'amount' => (float) $payment->amount,
            'method' => $payment->method,
            'reference_number' => $payment->reference_number,
        ])->all();
    }
}
