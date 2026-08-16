<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Fillable(['customer_name', 'customer_contact', 'status', 'total_amount', 'payment_status', 'delivery_status'])]
class PasabuyOrder extends Model
{
    use Filterable, HasFactory, UsesUuid;

    public const EDITABLE_STATUSES = ['pending'];

    public const LIVE_STATUSES = ['pending', 'confirmed'];

    public const PAYMENT_STATUSES = ['unpaid', 'partially_paid', 'paid'];

    public const DELIVERY_STATUSES = ['not_delivered', 'delivered'];

    protected function casts()
    {
        return [
            'delivered_at' => 'datetime',
            'invoice_sent_at' => 'datetime',
            'last_overdue_reminder_at' => 'datetime',
        ];
    }

    protected static function booted()
    {
        static::creating(function (PasabuyOrder $order) {
            $order->order_no = static::generateOrderNo();
        });
    }

    protected static function generateOrderNo()
    {
        do {
            $orderNo = sprintf('PSBY-%s-%s', now()->format('Ymd'), Str::upper(Str::random(6)));
        } while (static::where('order_no', $orderNo)->exists());

        return $orderNo;
    }

    public function pasabuySchedule()
    {
        return $this->belongsTo(PasabuySchedule::class);
    }

    public function items()
    {
        return $this->hasMany(PasabuyOrderItem::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(PasabuyOrderPayment::class, 'order_id');
    }

    public function paidOffAt()
    {
        return $this->payment_status === 'paid'
            ? $this->payments->max('paid_at')
            : null;
    }

    /**
     * The full amount this order is expected to settle overall: subtotal, markup,
     * handling fee, and any interest accrued for being overdue. This is the number
     * "paid so far" must be compared against to know if the order is truly settled,
     * not just the base total_amount.
     */
    public function overallBalance()
    {
        return $this->invoiceBreakdown()['grand_total'];
    }

    public function invoiceBreakdown()
    {
        $this->loadMissing(['items.pasabuyScheduleProduct', 'payments', 'pasabuySchedule.pasabuyScheduleInvoiceSetting', 'pasabuySchedule.user.invoiceSetting']);

        $settings = $this->resolveInvoiceSettings();

        $subtotal = (float) $this->items->reject(fn ($item) => $item->is_out_of_stock)->sum('subtotal');
        $markupAmount = $subtotal > (float) $settings->markup_threshold_amount
            ? ceil($subtotal * (float) $settings->markup_percentage / 100)
            : (float) $settings->markup_fixed_amount;

        $handlingFee = (float) $settings->handling_fee;
        $total = round($subtotal + $markupAmount + $handlingFee, 2);
        $dueDate = $this->pasabuySchedule->due_date;
        $isOverdue = $dueDate !== null && now()->gt($dueDate) && $this->payment_status !== 'paid';

        $paymentLines = $this->buildPaymentLines();
        $amountPaid = round(array_sum(array_column($paymentLines, 'amount')), 2);

        // Once the order is fully paid, interest must stop accruing as of the last payment date
        // rather than continuing to compound against "now" indefinitely.
        $asOf = $this->paidOffAt() ?? now();

        $elapsedPeriods = $this->resolveElapsedInterestPeriods($dueDate, $settings, $asOf);

        $ledger = match (true) {
            $elapsedPeriods <= 0 => [],
            $settings->interest_base === 'outstanding_balance' => $this->buildOutstandingBalanceLedger($total, $dueDate, $elapsedPeriods, $paymentLines, $settings),
            default => $this->buildFullTotalLedger($total, $dueDate, $elapsedPeriods, $settings),
        };

        $interestTotal = round(array_sum(array_map(
            fn ($row) => $row['type'] === 'interest' ? $row['amount'] : 0,
            $ledger
        )), 2);

        // Grand total is what the order was actually worth (total plus accrued interest) —
        // it must never net out payments, since that's what balance_due is for.
        $grandTotal = round($total + $interestTotal, 2);
        $balanceDue = round($grandTotal - $amountPaid, 2);

        return [
            'subtotal' => $subtotal,
            'markup_amount' => $markupAmount,
            'handling_fee' => $handlingFee,
            'total' => $total,
            'due_date' => $dueDate,
            'is_overdue' => $isOverdue,
            'interest_base' => $settings->interest_base,
            'interest_lines' => $ledger,
            'interest_total' => $interestTotal,
            'grand_total' => $grandTotal,
            'payments' => $paymentLines,
            'amount_paid' => $amountPaid,
            'balance_due' => $balanceDue,
        ];
    }

    protected function resolveInvoiceSettings()
    {
        if ($this->pasabuySchedule->status === 'finished' && $this->pasabuySchedule->pasabuyScheduleInvoiceSetting) {
            return $this->pasabuySchedule->pasabuyScheduleInvoiceSetting;
        }

        return $this->pasabuySchedule->user->invoiceSetting ?? $this->defaultInvoiceSettings();
    }

    protected function defaultInvoiceSettings()
    {
        $settings = new PasabuySellerInvoiceSetting;
        $settings->markup_threshold_amount = 1000.00;
        $settings->markup_percentage = 10.00;
        $settings->markup_fixed_amount = 100.00;
        $settings->handling_fee = 50.00;
        $settings->interest_rate = 1.00;
        $settings->interest_period = 'month';
        $settings->interest_base = 'outstanding_balance';
        $settings->is_active = true;

        return $settings;
    }

    protected function buildPaymentLines()
    {
        return $this->payments->sortBy('paid_at')->values()->map(fn ($payment) => [
            'paid_at' => $payment->paid_at,
            'amount' => (float) $payment->amount,
            'method' => $payment->method,
            'reference_number' => $payment->reference_number,
        ])->all();
    }

    protected function resolveElapsedInterestPeriods(?Carbon $dueDate, $settings, Carbon $asOf)
    {
        if (! $settings->is_active || ! $dueDate || $asOf->lte($dueDate)) {
            return 0;
        }

        $periods = match ($settings->interest_period) {
            'day' => $dueDate->diffInDays($asOf),
            'week' => $dueDate->diffInWeeks($asOf),
            'month' => $dueDate->diffInMonths($asOf),
        };

        return (int) floor($periods);
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

            // Interest is rounded up to the nearest whole peso so no centavos accrue.
            $interestAmount = ceil($runningTotal * $rate);
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

            // Interest is rounded up to the nearest whole peso so no centavos accrue.
            $interestAmount = $running > 0 ? ceil($running * $rate) : 0.0;
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
}
