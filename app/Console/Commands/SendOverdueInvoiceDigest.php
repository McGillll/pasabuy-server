<?php

namespace App\Console\Commands;

use App\Interfaces\Invoice\InvoiceServiceInterface;
use App\Mail\OverdueInvoiceDigest;
use App\Models\PasabuyOrder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

#[Signature('invoices:send-overdue-digest')]
#[Description('Email each seller a digest of their overdue, unpaid invoices')]
class SendOverdueInvoiceDigest extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $invoiceService = app(InvoiceServiceInterface::class);

        $orders = PasabuyOrder::where('status', 'confirmed')
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->with('pasabuySchedule.user', 'payments')
            ->get();

        $overdueBySeller = collect();

        foreach ($orders as $order) {
            $seller = $order->pasabuySchedule?->user;

            if (! $seller) {
                continue;
            }

            $invoice = $invoiceService->buildForOrder($order);

            if (! $invoice['is_overdue'] || $invoice['balance_due'] <= 0) {
                continue;
            }

            $overdueBySeller->push([
                'seller' => $seller,
                'row' => [
                    'order_uuid' => $order->uuid,
                    'customer_name' => $order->customer_name,
                    'invoice_total' => $invoice['invoice_total'],
                    'interest_amount' => $invoice['interest_amount'],
                    'balance_due' => $invoice['balance_due'],
                    'due_date' => $invoice['due_date'],
                ],
            ]);
        }

        $overdueBySeller->groupBy(fn ($entry) => $entry['seller']->id)
            ->each(function ($entries) {
                $seller = $entries->first()['seller'];
                $rows = $entries->pluck('row');

                Mail::to($seller->email)->send(new OverdueInvoiceDigest($seller, $rows));

                Log::info('Overdue invoice digest sent', [
                    'seller_id' => $seller->id,
                    'order_count' => $rows->count(),
                    'total_balance_due' => round($rows->sum('balance_due'), 2),
                ]);
            });
    }
}
