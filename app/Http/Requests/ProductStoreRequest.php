<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
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
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'is_active' => ['sometimes', 'boolean'],
            'default_price' => ['required', 'numeric', 'min:0'],
            'default_product_status' => ['sometimes', 'in:none,b1t1,out_of_stock'],
            'category_uuid' => ['nullable', 'string', 'exists:product_categories,uuid'],
        ];
    }
}
