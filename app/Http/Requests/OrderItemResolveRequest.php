<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderItemResolveRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'action' => ['required', 'in:accept,remove'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
