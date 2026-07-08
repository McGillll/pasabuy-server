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
        ];
    }
}
