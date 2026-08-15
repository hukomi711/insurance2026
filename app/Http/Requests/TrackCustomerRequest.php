<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\SaudiNationalId;

class TrackCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint — no auth required
    }

    public function rules(): array
    {
        return [
            'insurance_type'     => 'nullable|string|in:renew,buy,import',
            'national_id'        => ['required', 'string', 'size:10', new SaudiNationalId],
            'nationality'        => 'nullable|string|size:2',
            'birth_month'        => 'nullable|string|max:2',
            'birth_year'         => 'nullable|string|max:4',
            'sequence_number'    => 'nullable|string|max:10',
            'customs_card'       => 'nullable|string|max:15',
            'manufacturing_year' => 'nullable|string|max:4',
            'registration_type'  => 'nullable|string|in:sequence,customs',
            'current_page'       => 'nullable|string|max:1024',
        ];
    }

    public function messages(): array
    {
        return [
            'insurance_type.required' => 'نوع التأمين مطلوب',
            'insurance_type.in'       => 'نوع التأمين غير صالح',
            'national_id.required'    => 'رقم الهوية مطلوب',
            'national_id.size'        => 'رقم الهوية يجب أن يكون 10 أرقام',
        ];
    }
}
