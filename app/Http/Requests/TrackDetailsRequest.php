<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrackDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint — no auth required
    }

    public function rules(): array
    {
        return [
            'full_name'            => 'nullable|string|max:100',
            'phone'                => 'nullable|string|max:15',
            'email'                => 'nullable|email|max:150',
            'purpose_of_use'       => 'nullable|string|max:50',
            'estimated_value'      => 'nullable|numeric|min:0|max:999999999999',
            'region'               => 'nullable|string|max:100',
            'city'                 => 'nullable|string|max:100',
            'vehicle_type'         => 'nullable|string|max:100',
            'repair_method'        => 'nullable|string|max:100',
            'policy_start_date'    => 'nullable|string|max:20',
            'insurance_type'       => 'nullable|string|max:50',
            'current_page'         => 'nullable|string|max:1024',
            'has_additional_driver' => 'nullable|boolean',
            'additional_driver_name' => 'nullable|string|max:100',
            'additional_driver_national_id' => 'nullable|string|max:10',
            'additional_driver_birth_date' => 'nullable|string|max:20',
            'usage_purpose'        => 'nullable|string|max:50',
            // Extra details from تفاصيل أخرى modal
            'night_parking'        => 'nullable|string|max:10',
            'expected_km'          => 'nullable|string|max:10',
            'transmission_type'    => 'nullable|string|max:10',
            'accident_counts'      => 'nullable|string|max:10',
            'education'            => 'nullable|string|max:10',
            'work_location'        => 'nullable|string|max:200',
            'children_under_16'    => 'nullable|string|max:10',
            'car_modification'     => 'nullable|string|max:10',
            'modification_desc'    => 'nullable|string|max:200',
            'has_trailer'          => 'nullable|string|max:10',
            'trailer_value'        => 'nullable|string|max:50',
            'foreign_license'      => 'nullable|string|max:10',
            'health_conditions'    => 'nullable|string|max:10',
            'traffic_violations'   => 'nullable|string|max:10',
            // Drivers
            'drivers'              => 'nullable|array',
            'drivers.*.name'       => 'nullable|string|max:100',
            'drivers.*.nationalId' => 'nullable|string|max:10',
            'drivers.*.birthDateH' => 'nullable|string|max:20',
            'drivers.*.education'  => 'nullable|string|max:50',
            'drivers.*.drivingPercentage' => 'nullable|string|max:10',
            'drivers.*.id'         => 'nullable|integer',
            'drivers.*.isPolicyHolder'   => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'estimated_value.numeric' => 'القيمة التقديرية يجب أن تكون رقم',
            'estimated_value.min'     => 'القيمة التقديرية يجب أن تكون أكبر من صفر',
        ];
    }
}
