<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'subtotal' => $this->resource['subtotal'],
            'markup_amount' => $this->resource['markup_amount'],
            'invoice_total' => $this->resource['invoice_total'],
            'due_date' => $this->resource['due_date']?->toIso8601String(),
            'is_overdue' => $this->resource['is_overdue'],
            'elapsed_periods' => $this->resource['elapsed_periods'],
            'interest_amount' => $this->resource['interest_amount'],
            'total_payable' => $this->resource['total_payable'],
            'amount_paid' => $this->resource['amount_paid'],
            'balance_due' => $this->resource['balance_due'],
        ];
    }
}
