<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentFailureReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Used by: AdminPaymentCardController@reject
 */
class RejectPaymentCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                Rule::in(PaymentFailureReason::allPaymentReasonsForValidation()),
            ],
        ];
    }
}
