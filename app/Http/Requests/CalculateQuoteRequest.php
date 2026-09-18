<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CalculateQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint — no auth required
    }

    public function rules(): array
    {
        $supportedCompanies = array_map('intval', array_keys(config('pricing.fixed_company_prices', [])));
        $supportedDeductibles = array_map('intval', array_keys(config('pricing.deductible_increase', [])));

        return [
            // ─── Plans (batch) ───
            'plans' => 'required|array|min:1|max:50',
            'plans.*.id' => 'nullable|integer|min:1',
            'plans.*.companyId' => ['required', 'integer', Rule::in($supportedCompanies)],
            'plans.*.subType' => 'required|string|in:thirdParty,thirdPartyPlus,vehicleDamagePlus,comprehensive',
            'plans.*.deductible' => ['required', 'integer', Rule::in($supportedDeductibles)],

            // ─── Vehicle ───
            'vehicle' => 'required|array',
            'vehicle.year' => 'required|integer|between:2000,2027',
            'vehicle.make' => 'required|integer|between:1,95',
            'vehicle.estimatedValue' => 'required|integer|min:1',
            'vehicle.purposeOfUse' => 'required|string|in:personal,commercial,rental,rideshare,cargo,petroleum',
            'vehicle.carModification' => 'required|string|in:yes,no',
            'vehicle.hasTrailer' => 'required|string|in:yes,no',
            'vehicle.transmissionType' => 'required|string|in:1,2',

            // ─── Driver ───
            'driver' => 'required|array',
            'driver.dateOfBirth' => 'nullable|date',
            'driver.drivingExperience' => 'nullable|string|in:1,2,3,4,5',
            'driver.accidentCounts' => 'nullable|string|in:0,1,2,3,4,5',
            'driver.trafficViolations' => 'required|string|in:yes,no',
            'driver.education' => 'nullable|string|in:1,2,3,4,5,6,7',
            'driver.foreignLicense' => 'required|string|in:yes,no',
            'driver.healthConditions' => 'required|string|in:yes,no',
            'driver.ncdYears' => 'nullable|string|in:0,1,2,3,4,5,6,7',
            'driver.city' => 'nullable|string|max:100',
            'driver.nightParking' => 'nullable|string|in:1,2,3',
            'driver.expectedKM' => 'nullable|string|in:1,2,3,4,5',
            'driver.additionalDrivers' => 'nullable|array',
            'driver.additionalDrivers.*' => 'nullable|array',

            // ─── Policy ───
            'policy' => 'required|array',
            'policy.repairMethod' => 'required|string|in:workshop,authorized,agency',
            'policy.coverageLimit' => 'nullable|integer|min:1',
            'policy.deductible' => ['nullable', 'integer', Rule::in($supportedDeductibles)],
        ];
    }

    public function messages(): array
    {
        return [
            'plans.required' => 'يجب تحديد خطة واحدة على الأقل',
            'plans.max' => 'لا يمكن حساب أكثر من 50 خطة في الطلب الواحد',
            'plans.*.companyId.required' => 'معرف الشركة مطلوب',
            'plans.*.subType.required' => 'نوع التأمين مطلوب',
            'plans.*.subType.in' => 'نوع التأمين غير صالح',
            'vehicle.year.required' => 'سنة التصنيع مطلوبة',
            'vehicle.make.required' => 'الشركة المصنعة مطلوبة',
            'vehicle.estimatedValue.required' => 'القيمة التقديرية مطلوبة',
            'vehicle.estimatedValue.min' => 'القيمة التقديرية يجب أن تكون أكبر من صفر',
            'policy.repairMethod.required' => 'طريقة الإصلاح مطلوبة',
        ];
    }
}
