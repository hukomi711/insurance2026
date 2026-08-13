<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Used by: AdminNafathController@reject
 */
class NafathRejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|integer|exists:customer_profiles,id',
            'customer_ip' => 'nullable|string',
            'reason'      => 'nullable|string|max:500',
        ];
    }
}
