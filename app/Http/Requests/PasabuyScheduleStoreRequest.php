<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PasabuyScheduleStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cutoff_at' => ['nullable', 'date'],
            'pasabuy_schedule_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'in:open,closed,finished'],
        ];
    }
}
