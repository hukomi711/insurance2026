<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint — no auth required
    }

    public function rules(): array
    {
        return [
            'session_id'  => 'nullable|string|max:100',
            'customer_ip' => 'nullable|string|max:45',
        ];
    }
}
