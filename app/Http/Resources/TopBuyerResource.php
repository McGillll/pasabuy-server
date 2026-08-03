<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopBuyerResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'uuid' => $this->uuid,
            'name' => trim("{$this->first_name} {$this->last_name}"),
            'total_spend' => (string) $this->total_spend,
            'order_count' => (int) $this->order_count,
        ];
    }
}
