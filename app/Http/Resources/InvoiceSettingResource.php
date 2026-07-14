<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceSettingResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'uuid' => $this->uuid,
            'markup_threshold_amount' => $this->markup_threshold_amount,
            'markup_percentage' => $this->markup_percentage,
            'markup_fixed_amount' => $this->markup_fixed_amount,
            'due_days' => $this->due_days,
            'due_date_reference' => $this->due_date_reference,
            'interest_rate' => $this->interest_rate,
            'interest_period' => $this->interest_period,
            'interest_base' => $this->interest_base,
            'is_active' => $this->is_active,
        ];
    }
}
