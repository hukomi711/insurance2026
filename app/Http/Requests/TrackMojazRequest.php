<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates Mojaz vehicle inspection form data (Step 1).
 *
 * POST /api/customer/track-mojaz
 */
class TrackMojazRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint — no auth required
    }

    public function rules(): array
    {
        return [
            'sequence_number' => 'required|string|max:10|regex:/^\d+$/',
            'mobile_number' => 'required|string|regex:/^05\d{8}$/',
            'manufacturing_year' => 'required|string|max:4',
            'vehicle_make' => 'required|string|max:100',
            'vehicle_model' => 'required|string|max:100',
            'plate_number' => 'required|string|max:20',
            'vin' => 'required|string|size:17',
            'current_page' => 'nullable|string|max:1024',
        ];
    }

    public function messages(): array
    {
        return [
            'sequence_number.required' => 'الرقم التسلسلي مطلوب',
            'sequence_number.regex' => 'الرقم التسلسلي يجب أن يكون أرقام فقط',
            'mobile_number.required' => 'رقم الجوال مطلوب',
            'mobile_number.regex' => 'رقم الجوال يجب أن يبدأ بـ 05 ويتكون من 10 أرقام',
            'manufacturing_year.required' => 'سنة الصنع مطلوبة',
            'vehicle_make.required' => 'الشركة المصنعة مطلوبة',
            'vehicle_model.required' => 'الموديل مطلوب',
            'plate_number.required' => 'رقم اللوحة مطلوب',
            'vin.required' => 'رقم الهيكل مطلوب',
            'vin.size' => 'رقم الهيكل يجب أن يتكون من 17 خانة',
        ];
    }
}
