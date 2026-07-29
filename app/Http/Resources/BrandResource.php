<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'logo_url' => $this->logo_url,
            'is_active' => $this->is_active,
        ];
    }
}
