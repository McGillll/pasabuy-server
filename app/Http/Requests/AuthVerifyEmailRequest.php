<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthVerifyEmailRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id' => ['required', 'string'],
            'hash' => ['required', 'string'],
            'expires' => ['required', 'string'],
            'signature' => ['required', 'string'],
        ];
    }
}
