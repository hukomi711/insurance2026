<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalculateQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint — no auth required
    }

    public function rules(): array
    {
        return [
            // ─── Plans (batch) ───
            'plans' => 'required|array|min:1',
            'plans.*.companyId' => 'required|integer|between:1,17',
            'plans.*.subType' => 'required|string|in:thirdParty,thirdPartyPlus,vehicleDamagePlus,comprehensive',
            'plans.*.deductible' => 'required|integer|in:0,500,1000,1500,2000,2500,3000,5000',

            // ─── Vehicle ───
            'vehicle' => 'required|array',
            'vehicle.year' => 'required|integer|between:2000,2027',
            'vehicle.make' => 'required|integer|between:1,20',
            'vehicle.estimatedValue' => 'required|integer|min:1',
            'vehicle.purposeOfUse' => 'required|string|in:personal,commercial,rental,rideshare,cargo,petroleum',
            'vehicle.carModification' => 'required|string|in:yes,no',
            'vehicle.hasTrailer' => 'required|string|in:yes,no',
            'vehicle.transmissionType' => 'required|string|in:1,2',

            // ─── Driver ───
            'driver' => 'required|array',
            'driver.dateOfBirth' => 'nullable|date',
            'driver.drivingExperience' => 'nullable|string|in:1,2,3,4,5',
            'driver.accidentCounts' => 'required|string|in:0,1,2,3,4,5',
            'driver.trafficViolations' => 'required|string|in:yes,no',
            'driver.education' => 'required|string|in:1,2,3,4,5,6,7',
            'driver.foreignLicense' => 'required|string|in:yes,no',
            'driver.healthConditions' => 'required|string|in:yes,no',
            'driver.ncdYears' => 'nullable|string|in:0,1,2,3,4,5,6,7',
            'driver.city' => 'required|string|max:100',
            'driver.nightParking' => 'required|string|in:1,2,3',
            'driver.expectedKM' => 'required|string|in:1,2,3,4,5',
            'driver.additionalDrivers' => 'nullable|array',
            'driver.additionalDrivers.*' => 'nullable|array',

            // ─── Policy ───
            'policy' => 'required|array',
            'policy.repairMethod' => 'required|string|in:workshop,authorized,agency',
            'policy.deductible' => 'nullable|integer|in:0,500,1000,1500,2000,2500,3000,5000',
        ];
    }

    public function messages(): array
    {
        return [
            'plans.required' => 'يجب تحديد خطة واحدة على الأقل',
            'plans.*.companyId.required' => 'معرف الشركة مطلوب',
            'plans.*.subType.required' => 'نوع التأمين مطلوب',
            'plans.*.subType.in' => 'نوع التأمين غير صالح',
            'vehicle.year.required' => 'سنة التصنيع مطلوبة',
            'vehicle.make.required' => 'الشركة المصنعة مطلوبة',
            'vehicle.estimatedValue.required' => 'القيمة التقديرية مطلوبة',
            'vehicle.estimatedValue.min' => 'القيمة التقديرية يجب أن تكون أكبر من صفر',
            'driver.accidentCounts.required' => 'عدد الحوادث مطلوب',
            'driver.education.required' => 'المستوى التعليمي مطلوب',
            'driver.city.required' => 'المدينة مطلوبة',
            'driver.nightParking.required' => 'مكان الركن الليلي مطلوب',
            'driver.expectedKM.required' => 'المسافة السنوية المتوقعة مطلوبة',
            'policy.repairMethod.required' => 'طريقة الإصلاح مطلوبة',
        ];
    }
}
