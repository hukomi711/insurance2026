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
            'customer_ip' => 'required|string',
            'reason'      => 'nullable|string|max:500',
        ];
    }
}
