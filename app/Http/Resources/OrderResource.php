<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'uuid' => $this->uuid,
            'customer_name' => $this->customer_name,
            'customer_contact' => $this->customer_contact,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'is_editable' => in_array($this->status, Order::EDITABLE_STATUSES)
                && $this->relationLoaded('pasabuySchedule')
                && $this->pasabuySchedule->status === 'open'
                && (! $this->pasabuySchedule->cutoff_at || $this->pasabuySchedule->cutoff_at->isFuture()),
            'is_cancellable' => in_array($this->status, Order::CONFIRMABLE_STATUSES),
            'needs_confirmation_count' => $this->whenLoaded('items', fn () => $this->items->where('confirmation_status', 'pending')->count()),
            'pasabuy_schedule' => $this->whenLoaded('pasabuySchedule', fn () => new PasabuyScheduleResource($this->pasabuySchedule)),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
        ];
    }
}
