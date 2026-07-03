<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PasabuyScheduleSyncProductsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'products' => ['sometimes', 'array'],
            'products.*.product_uuid' => ['required', 'string'],
            'products.*.price' => ['required', 'numeric', 'min:0'],
            'products.*.max_quantity' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
