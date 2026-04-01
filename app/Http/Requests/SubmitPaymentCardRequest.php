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
            'card_number'  => [
                'required', 'string', 'min:13', 'max:19',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $digits = preg_replace('/\D/', '', $value);
                    if (! $this->passesLuhn($digits)) {
                        $fail('رقم البطاقة غير صالح');
                    }
                },
            ],
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

    /**
     * Luhn checksum validation for card numbers.
     */
    private function passesLuhn(string $digits): bool
    {
        if (strlen($digits) < 13) {
            return false;
        }

        $sum = 0;
        $alt = false;

        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $n = (int) $digits[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alt = ! $alt;
        }

        return $sum % 10 === 0;
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
