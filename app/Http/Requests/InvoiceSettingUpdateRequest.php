<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceSettingUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'markup_threshold_amount' => ['required', 'numeric', 'min:0'],
            'markup_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'markup_fixed_amount' => ['required', 'numeric', 'min:0'],
            'due_days' => ['required', 'integer', 'min:1'],
            'due_date_reference' => ['required', 'in:delivery,schedule_finish'],
            'interest_rate' => ['required', 'numeric', 'min:0'],
            'interest_period' => ['required', 'in:day,week,month'],
            'interest_base' => ['required', 'in:outstanding_balance,full_total'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
