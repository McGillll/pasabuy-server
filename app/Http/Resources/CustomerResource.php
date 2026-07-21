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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'contact_number' => $this->contact_number,
            'messenger_name' => $this->messenger_name,
            'is_approved' => $this->is_approved,
            'approved_at' => $this->approved_at,
            'terms_accepted_at' => $this->terms_accepted_at,
            'created_at' => $this->created_at,
        ];
    }
}
