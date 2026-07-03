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
            'cutoff_at' => $this->cutoff_at,
            'status' => $this->status,
            'is_open' => $this->status === 'open' && (! $this->cutoff_at || $this->cutoff_at->isFuture()),
            'products' => PasabuyScheduleProductResource::collection($this->whenLoaded('scheduleProducts')),
            'created_at' => $this->created_at,
        ];
    }
}
