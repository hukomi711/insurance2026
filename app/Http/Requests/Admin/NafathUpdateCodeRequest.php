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
            'customer_ip'       => 'required|string',
            'verification_code' => 'required|string|max:10',
        ];
    }
}
