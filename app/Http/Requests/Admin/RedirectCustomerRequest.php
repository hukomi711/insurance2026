<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Used by: AdminCustomerController@redirectCustomer
 */
class RedirectCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Admin middleware already enforces auth
    }

    public function rules(): array
    {
        return [
            'customer_ip' => 'required|string',
            'redirect_url' => ['required', 'string', 'regex:/^\/[a-zA-Z0-9\-\/\.\?\&\=\_]*$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'redirect_url.regex' => 'يجب أن يكون رابط التحويل مساراً داخلياً يبدأ بـ /',
        ];
    }
}
