<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared request for admin rejection actions that require otp_id + customer_ip + reason.
 *
 * Used by: Phone verification reject, STC waiting/otp/call reject
 */
class AdminOtpRejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'otp_id' => 'required|integer',
            'customer_ip' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ];
    }
}
