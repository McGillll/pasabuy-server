<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PasabuyScheduleProductConfirmPriceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'actual_price' => ['required_unless:product_status,out_of_stock', 'nullable', 'numeric', 'min:0'],
            'product_status' => ['required', 'in:none,b1t1,out_of_stock'],
        ];
    }
}
