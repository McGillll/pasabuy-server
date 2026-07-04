<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductRequestResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'uuid' => $this->uuid,
            'note' => $this->note,
            'status' => $this->status,
            'customer' => $this->whenLoaded('user', fn () => [
                'name' => $this->user->name,
                'contact_number' => $this->user->contact_number,
            ]),
            'pasabuy_schedule' => $this->whenLoaded('pasabuySchedule', fn () => $this->pasabuySchedule ? [
                'uuid' => $this->pasabuySchedule->uuid,
                'title' => $this->pasabuySchedule->title,
            ] : null),
            'created_at' => $this->created_at,
        ];
    }
}
