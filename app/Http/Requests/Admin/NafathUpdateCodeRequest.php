<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Used by: AdminNafathController@updateCode
 */
class NafathUpdateCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id'       => 'required|integer|exists:customer_profiles,id',
            'customer_ip'       => 'nullable|string',
            'verification_code' => 'required|string|max:10',
        ];
    }
}
