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
        // Defense-in-depth: also enforced by the 'admin' middleware on the route.
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                Rule::in(PaymentFailureReason::cardRejectionValues()),
            ],
        ];
    }
}
