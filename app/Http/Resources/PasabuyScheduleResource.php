<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PasabuyScheduleResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'description' => $this->description,
            'pasabuy_schedule_date' => $this->pasabuy_schedule_date?->toDateString(),
            'status' => $this->status,
            'is_open' => $this->is_open,
            'seller_name' => $this->whenLoaded('user', fn () => $this->user->fullName()),
            'product_count' => $this->whenCounted('scheduleProducts'),
            'products' => PasabuyScheduleProductResource::collection($this->whenLoaded('scheduleProducts')),
            'created_at' => $this->created_at,
        ];
    }
}
