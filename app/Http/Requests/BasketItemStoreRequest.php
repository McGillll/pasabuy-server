<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BasketItemStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'product_uuid' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
