<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint — no auth required
    }

    public function rules(): array
    {
        return [
            'current_page' => 'required|string|max:1024',
            'session_id'   => 'nullable|string|max:64',
        ];
    }

    public function messages(): array
    {
        return [
            'current_page.required' => 'الصفحة الحالية مطلوبة',
        ];
    }
}
