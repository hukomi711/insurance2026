<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RefreshCustomerPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Admin middleware already enforces auth and admin role
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customer_profiles,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'معرّف العميل مطلوب',
            'customer_id.integer' => 'معرّف العميل يجب أن يكون رقماً صحيحاً',
            'customer_id.exists' => 'العميل المطلوب غير موجود',
        ];
    }
}
