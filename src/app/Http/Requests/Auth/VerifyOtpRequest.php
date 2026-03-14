<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'otp' => 'required|digits:6',
        ];
    }

    public function messages(): array
    {
        return [
            'otp.required' => 'OTP is required.',
            'otp.digits' => 'OTP must be 6 digits.',
        ];
    }
}
