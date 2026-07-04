<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequestStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'note' => ['required', 'string', 'max:1000'],
            'pasabuy_schedule_uuid' => ['nullable', 'string'],
        ];
    }
}
