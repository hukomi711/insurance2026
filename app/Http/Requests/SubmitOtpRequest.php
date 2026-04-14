<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\SaudiNationalId;

class SubmitOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint — no auth required
    }

    public function rules(): array
    {
        return [
            'session_id'  => 'required|string|max:100',
            'otp'         => 'required|string|min:4|max:6|regex:/^\d+$/',
            'type'        => 'nullable|string|in:otp,stc_otp',
            'phone'       => 'nullable|string|max:20',
            'national_id' => ['nullable', 'string', 'max:20', new SaudiNationalId],
        ];
    }

    public function messages(): array
    {
        return [
            'otp.required' => 'رمز التحقق مطلوب',
            'otp.min'      => 'رمز التحقق يجب أن يكون 4 أرقام على الأقل',
            'otp.max'      => 'رمز التحقق يجب ألا يتجاوز 6 أرقام',
        ];
    }
}
