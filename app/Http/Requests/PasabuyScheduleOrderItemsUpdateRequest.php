<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PasabuyScheduleOrderItemsUpdateRequest extends FormRequest
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
        ];
    }
}
