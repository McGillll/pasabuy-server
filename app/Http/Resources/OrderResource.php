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
            'payment_status' => $this->payment_status,
            'delivery_status' => $this->delivery_status,
            'is_editable' => in_array($this->status, Order::EDITABLE_STATUSES)
                && $this->relationLoaded('pasabuySchedule')
                && $this->pasabuySchedule->is_open,
            'is_cancellable' => in_array($this->status, Order::EDITABLE_STATUSES)
                && $this->relationLoaded('pasabuySchedule')
                && $this->pasabuySchedule->is_open,
            'pasabuy_schedule' => $this->whenLoaded('pasabuySchedule', fn () => new PasabuyScheduleResource($this->pasabuySchedule)),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
        ];
    }
}
