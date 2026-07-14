<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'items' => $this->resource['items'],
            'subtotal' => $this->resource['subtotal'],
            'markup_amount' => $this->resource['markup_amount'],
            'total' => $this->resource['total'],
            'due_date' => $this->resource['due_date']?->toIso8601String(),
            'is_overdue' => $this->resource['is_overdue'],
            'interest_base' => $this->resource['interest_base'],
            'interest_lines' => $this->resource['interest_lines'],
            'interest_total' => $this->resource['interest_total'],
            'grand_total' => $this->resource['grand_total'],
            'payments' => collect($this->resource['payments'])->map(fn ($payment) => [
                'paid_at' => $payment['paid_at']?->toIso8601String(),
                'amount' => $payment['amount'],
                'method' => $payment['method'],
                'reference_number' => $payment['reference_number'],
            ]),
            'amount_paid' => $this->resource['amount_paid'],
            'balance_due' => $this->resource['balance_due'],
        ];
    }
}
