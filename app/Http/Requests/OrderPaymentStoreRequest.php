<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderPaymentStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:cash,gcash,bank_transfer,maya,other'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'paid_at' => ['required', 'date'],
        ];
    }
}
