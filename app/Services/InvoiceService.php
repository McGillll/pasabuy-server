<?php

namespace App\Services;

use App\Interfaces\Invoice\InvoiceServiceInterface;
use App\Interfaces\InvoiceSetting\InvoiceSettingRepositoryInterface;
use App\Interfaces\ScheduleInvoiceSetting\ScheduleInvoiceSettingRepositoryInterface;
use App\Models\PasabuyOrder;
use Illuminate\Support\Carbon;

class InvoiceService implements InvoiceServiceInterface
{
    public function __construct(
        protected InvoiceSettingRepositoryInterface $invoiceSettingRepository,
        protected ScheduleInvoiceSettingRepositoryInterface $scheduleInvoiceSettingRepository,
    ) {}

    public function buildForOrder(PasabuyOrder $order)
    {
        $order->loadMissing('items.pasabuyScheduleProduct');

        return array_merge(
            ['items' => $this->buildItemLines($order)],
            $this->calculateFinancials($order),
        );
    }

    public function summaryForOrder(PasabuyOrder $order)
    {
        $financials = $this->calculateFinancials($order);

        return [
            'amount_paid' => $financials['amount_paid'],
            'balance_due' => $financials['balance_due'],
        ];
    }

    protected function calculateFinancials(PasabuyOrder $order)
    {
        if (! $order->relationLoaded('payments')) {
            $order->load('payments');
        }

        $settings = $order->pasabuySchedule->status === 'finished'
            ? $this->scheduleInvoiceSettingRepository->findForSchedule($order->pasabuy_schedule_id)
            : $this->invoiceSettingRepository->getForUser($order->pasabuySchedule->user_id);

        $subtotal = (float) $order->total_amount;
        $markupAmount = $subtotal > (float) $settings->markup_threshold_amount
            ? round($subtotal * (float) $settings->markup_percentage / 100, 2)
            : (float) $settings->markup_fixed_amount;

        $total = round($subtotal + $markupAmount, 2);
        $dueDate = $this->resolveDueDate($order, $settings);
        $isOverdue = $dueDate !== null && now()->gt($dueDate);

        $paymentLines = $this->buildPaymentLines($order);
        $amountPaid = round(array_sum(array_column($paymentLines, 'amount')), 2);

        $elapsedPeriods = $this->resolveElapsedPeriods($dueDate, $settings);

        $ledger = match (true) {
            $elapsedPeriods <= 0 => [],
            $settings->interest_base === 'outstanding_balance' => $this->buildOutstandingBalanceLedger($total, $dueDate, $elapsedPeriods, $paymentLines, $settings),
            default => $this->buildFullTotalLedger($total, $dueDate, $elapsedPeriods, $settings),
        };

        $interestTotal = round(array_sum(array_map(
            fn ($row) => $row['type'] === 'interest' ? $row['amount'] : 0,
            $ledger
        )), 2);

        if ($ledger !== []) {
            $grandTotal = end($ledger)['running_total'];
            // outstanding_balance ledger already nets every payment as it walks the timeline,
            // so balance_due is the ledger's final running total, not a second subtraction.
            $balanceDue = $settings->interest_base === 'outstanding_balance'
                ? $grandTotal
                : $grandTotal - $amountPaid;
        } else {
            $grandTotal = $total;
            $balanceDue = $total - $amountPaid;
        }

        return [
            'subtotal' => $subtotal,
            'markup_amount' => $markupAmount,
            'total' => $total,
            'due_date' => $dueDate,
            'is_overdue' => $isOverdue,
            'interest_base' => $settings->interest_base,
            'interest_lines' => $ledger,
            'interest_total' => $interestTotal,
            'grand_total' => $grandTotal,
            'payments' => $paymentLines,
            'amount_paid' => $amountPaid,
            'balance_due' => round($balanceDue, 2),
        ];
    }

    protected function resolveDueDate(PasabuyOrder $order, $settings)
    {
        $sourceDate = $settings->due_date_reference === 'schedule_finish'
            ? $order->pasabuySchedule->finished_at
            : $order->delivered_at;

        return $sourceDate ? $sourceDate->copy()->addDays($settings->due_days) : null;
    }

    protected function resolveElapsedPeriods(?Carbon $dueDate, $settings)
    {
        if (! $settings->is_active || ! $dueDate || now()->lte($dueDate)) {
            return 0;
        }

        $periods = match ($settings->interest_period) {
            'day' => $dueDate->diffInDays(now()),
            'week' => $dueDate->diffInWeeks(now()),
            'month' => $dueDate->diffInMonths(now()),
        };

        return max((int) floor($periods), 1);
    }

    /**
     * Full total mode: interest always compounds on the original total, unaffected by payments.
     * Payments only reduce balance_due at the end, never the interest-bearing principal.
     */
    protected function buildFullTotalLedger(float $total, Carbon $dueDate, int $periods, $settings)
    {
        $rate = (float) $settings->interest_rate / 100;
        $unit = $settings->interest_period;
        $runningTotal = $total;
        $ledger = [];

        for ($period = 1; $period <= $periods; $period++) {
            $periodEnd = match ($unit) {
                'day' => $dueDate->copy()->addDays($period),
                'week' => $dueDate->copy()->addWeeks($period),
                'month' => $dueDate->copy()->addMonths($period),
            };

            $interestAmount = round($runningTotal * $rate, 2);
            $runningTotal = round($runningTotal + $interestAmount, 2);

            $ledger[] = [
                'type' => 'interest',
                'label' => $unit === 'day'
                    ? 'Overdue interest ('.$periodEnd->format('M j').')'
                    : "Overdue interest ({$unit} {$period})",
                'amount' => $interestAmount,
                'running_total' => $runningTotal,
            ];
        }

        return $ledger;
    }

    /**
     * Outstanding balance mode: walks the timeline period by period from the due date.
     * Within each period, interest compounds first on the balance as it stood entering
     * that period, then any payment received during the period is applied — reducing
     * the balance for the next period's interest, not this one.
     */
    protected function buildOutstandingBalanceLedger(float $total, Carbon $dueDate, int $periods, array $paymentLines, $settings)
    {
        $rate = (float) $settings->interest_rate / 100;
        $unit = $settings->interest_period;
        $running = $total;
        $cursor = $dueDate->copy();
        $ledger = [];

        $applyPayment = function ($payment) use (&$running, &$ledger) {
            $running = round($running - $payment['amount'], 2);
            $ledger[] = [
                'type' => 'payment',
                'label' => 'Payment applied ('.$payment['paid_at']->format('M j').')',
                'amount' => -$payment['amount'],
                'running_total' => $running,
            ];
        };

        foreach ($paymentLines as $payment) {
            if ($payment['paid_at'] && $payment['paid_at']->lte($cursor)) {
                $applyPayment($payment);
            }
        }

        for ($period = 1; $period <= $periods; $period++) {
            $periodEnd = match ($unit) {
                'day' => $dueDate->copy()->addDays($period),
                'week' => $dueDate->copy()->addWeeks($period),
                'month' => $dueDate->copy()->addMonths($period),
            };

            $interestAmount = $running > 0 ? round($running * $rate, 2) : 0.0;
            $running = round($running + $interestAmount, 2);

            $ledger[] = [
                'type' => 'interest',
                'label' => $unit === 'day'
                    ? 'Overdue interest ('.$periodEnd->format('M j').')'
                    : "Overdue interest ({$unit} {$period})",
                'amount' => $interestAmount,
                'running_total' => $running,
            ];

            foreach ($paymentLines as $payment) {
                if ($payment['paid_at'] && $payment['paid_at']->gt($cursor) && $payment['paid_at']->lte($periodEnd)) {
                    $applyPayment($payment);
                }
            }

            $cursor = $periodEnd;
        }

        return $ledger;
    }

    protected function buildItemLines(PasabuyOrder $order)
    {
        return $order->items
            ->reject(fn ($item) => $item->is_out_of_stock)
            ->map(fn ($item) => [
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'bonus_quantity' => $item->bonus_quantity,
                'unit_price' => (float) ($item->actual_price ?? $item->estimated_price),
                'line_total' => (float) $item->subtotal,
            ])
            ->values()
            ->all();
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
