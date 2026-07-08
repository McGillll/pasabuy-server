<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PasabuyScheduleChecklistToggleRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'is_checked' => ['required', 'boolean'],
        ];
    }
}
