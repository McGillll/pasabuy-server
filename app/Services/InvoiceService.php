<?php

namespace App\Services;

use App\Interfaces\Invoice\InvoiceServiceInterface;
use App\Models\PasabuyOrder;

class InvoiceService implements InvoiceServiceInterface
{
    public function buildForOrder(PasabuyOrder $order)
    {
        $order->loadMissing('items.pasabuyScheduleProduct');

        return array_merge(
            ['items' => $this->buildItemLines($order)],
            $order->invoiceBreakdown(),
        );
    }

    public function summaryForOrder(PasabuyOrder $order)
    {
        $breakdown = $order->invoiceBreakdown();

        return [
            'amount_paid' => $breakdown['amount_paid'],
            'balance_due' => $breakdown['balance_due'],
            'due_date' => $breakdown['due_date'],
            'is_overdue' => $breakdown['is_overdue'],
        ];
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
}
