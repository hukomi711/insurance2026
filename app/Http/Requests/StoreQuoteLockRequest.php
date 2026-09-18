<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteLockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id'           => 'required|integer',
            'company_id'        => 'required|integer|between:1,21',
            'plan_sub_type'     => 'required|string|in:thirdParty,comprehensive',
            'plan_name'         => 'required|string|max:255',
            'insurance_company' => 'required|string|max:255',
            'insurance_type'    => 'required|string|in:comprehensive,third_party',
            'plan_type'         => 'nullable|string|max:100',

            'subtotal'          => 'required|numeric|min:0',
            'vat_amount'        => 'required|numeric|min:0',
            'total'             => 'required|numeric|min:0',
            'deductible'        => 'nullable|integer|min:0',
            'addons'            => 'nullable|array',
            'addons.*.name'     => 'nullable|string|max:255',
            'addons.*.price'    => 'nullable|numeric|min:0',

            'session_id'        => 'required|string|max:255',
        ];
    }
}
