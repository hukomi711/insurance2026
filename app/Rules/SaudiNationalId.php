<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SaudiNationalId implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('رقم الهوية غير صالح');
            return;
        }

        if (! preg_match('/^\d{10}$/', $value)) {
            $fail('رقم الهوية يجب أن يتكون من 10 أرقام');
            return;
        }

        if (! preg_match('/^[12]/', $value)) {
            $fail('رقم الهوية يجب أن يبدأ بالرقم 1 أو 2');
            return;
        }

        if (! $this->passesLuhn($value)) {
            $fail('رقم الهوية غير صحيح، يرجى التحقق من الرقم المدخل');
        }
    }

    private function passesLuhn(string $digits): bool
    {
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
}
