<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Used by: AdminCustomerController@markViewed
 */
class MarkViewedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data_type' => 'required|in:vehicle,insurance,payment,chat',
        ];
    }
}
