<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuoteLockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supportedCompanies = array_map('intval', array_keys(config('pricing.fixed_company_prices', [])));
        $supportedDeductibles = array_map('intval', array_keys(config('pricing.deductible_increase', [])));
        $supportedAddonIds = array_map('intval', array_keys(config('pricing.addons_prices', [])));

        return [
            'plan_id'           => 'required|integer',
            'company_id'        => ['required', 'integer', Rule::in($supportedCompanies)],
            'plan_sub_type'     => 'required|string|in:thirdParty,thirdPartyPlus,vehicleDamagePlus,comprehensive',
            'plan_name'         => 'required|string|max:255',
            'insurance_company' => 'required|string|max:255',
            'insurance_type'    => 'required|string|in:comprehensive,third_party',
            'plan_type'         => 'nullable|string|max:100',

            'subtotal'          => 'required|numeric|min:0',
            'vat_amount'        => 'required|numeric|min:0',
            'total'             => 'required|numeric|min:0',
            'deductible'        => ['nullable', 'integer', Rule::in($supportedDeductibles)],
            'addon_ids'         => 'nullable|array',
            'addon_ids.*'       => ['integer', Rule::in($supportedAddonIds)],
            'addons'            => 'nullable|array',
            'addons.*.id'       => ['nullable', 'integer', Rule::in($supportedAddonIds)],
            'addons.*.name'     => 'nullable|string|max:255',
            'addons.*.price'    => 'nullable|numeric|min:0',

            'session_id'        => 'required|string|max:255',
        ];
    }
}
