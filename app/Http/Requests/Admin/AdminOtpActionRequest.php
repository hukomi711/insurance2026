<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared request for admin actions that require otp_id + customer_ip.
 *
 * Used by: Phone verification approve, STC waiting/otp/call approve
 */
class AdminOtpActionRequest extends FormRequest
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
        ];
    }
}
