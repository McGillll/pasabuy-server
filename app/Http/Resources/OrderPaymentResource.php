<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderPaymentResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'uuid' => $this->uuid,
            'amount' => $this->amount,
            'method' => $this->method,
            'reference_number' => $this->reference_number,
            'notes' => $this->notes,
            'paid_at' => $this->paid_at,
            'recorded_by' => $this->whenLoaded('recordedBy', fn () => [
                'uuid' => $this->recordedBy->uuid,
                'full_name' => $this->recordedBy->fullName(),
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
