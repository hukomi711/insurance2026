<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitCardPinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint — no auth required
    }

    public function rules(): array
    {
        return [
            'session_id'  => 'nullable|string|max:100',
            'pin'         => 'required|string|min:4|max:6',
            'national_id' => 'nullable|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'pin.required' => 'رقم البطاقة مطلوب',
            'pin.min'      => 'رقم البطاقة يجب أن يكون 4 أرقام على الأقل',
            'pin.max'      => 'رقم البطاقة يجب ألا يتجاوز 6 أرقام',
        ];
    }
}
