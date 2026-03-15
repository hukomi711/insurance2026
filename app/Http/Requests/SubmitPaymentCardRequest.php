<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates payment card submission from CheckoutPage.
 *
 * POST /api/payment-card/submit
 */
class SubmitPaymentCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'card_number'  => ['required', 'string', 'min:13', 'max:19'],
            'holder_name'  => ['required', 'string', 'max:100'],
            'expiry_month' => ['required', 'string', 'size:2'],
            'expiry_year'  => ['required', 'string', 'size:2'],
            'cvv'          => ['required', 'string', 'min:3', 'max:4'],
            'session_id'        => ['nullable', 'string', 'max:100'],
            'total_price'       => ['nullable', 'numeric', 'min:0'],
            'selected_insurance' => ['nullable', 'array'],
            'national_id'       => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'card_number.required'  => 'رقم البطاقة مطلوب',
            'card_number.min'       => 'رقم البطاقة غير صالح',
            'holder_name.required'  => 'اسم حامل البطاقة مطلوب',
            'expiry_month.required' => 'شهر الانتهاء مطلوب',
            'expiry_year.required'  => 'سنة الانتهاء مطلوبة',
            'cvv.required'          => 'رمز CVV مطلوب',
        ];
    }
}
