<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'contact_number' => $this->contact_number,
            'facebook_url' => $this->facebook_url,
            'is_approved' => $this->is_approved,
            'approved_at' => $this->approved_at,
        ];
    }
}
