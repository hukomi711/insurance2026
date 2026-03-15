<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Used by: AdminNafathController@approve
 */
class NafathApproveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_ip'       => 'required|string',
            'verification_code' => 'nullable|string|max:10',
        ];
    }
}
