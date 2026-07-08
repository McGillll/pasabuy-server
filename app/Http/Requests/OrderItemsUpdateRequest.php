<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderItemsUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_uuid' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.max_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.auto_buy_if_under_max' => ['nullable', 'boolean'],
        ];
    }
}
