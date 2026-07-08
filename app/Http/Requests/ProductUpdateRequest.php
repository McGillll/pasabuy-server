<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
            'is_active' => ['sometimes', 'boolean'],
            'default_price' => ['required', 'numeric', 'min:0'],
            'category_uuid' => ['nullable', 'string', 'exists:product_categories,uuid'],
        ];
    }
}
