<?php

namespace Database\Seeders;

use App\Models\CustomerProfile;
use App\Models\OtpCode;
use App\Models\PaymentCard;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Demo / development fixtures.
 *
 * Inserts three illustrative customer profiles with OTPs and payment
 * cards so the admin dashboard has something to render in local/testing
 * environments.
 *
 * SAFETY: this seeder must NEVER run in production. It is only invoked
 * by DatabaseSeeder when the application environment is `local` or
 * `testing`. Running it directly in production will throw.
 */
class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException(
                'DemoDataSeeder must not run in production. '
                .'It is intended for local/testing environments only.'
            );
        }

        $admin = User::where('email', 'admin@insurance.com')->first();
        if (! $admin) {
            throw new \RuntimeException(
                'DemoDataSeeder requires the admin user to exist. '
                .'Run DatabaseSeeder (which creates the admin) before this seeder.'
            );
        }

        // ─── Customer 1: أحمد محمد العتيبي ──────────────────────────
        $customer1 = CustomerProfile::updateOrCreate(
            ['ip_address' => '192.168.1.105', 'session_id' => 'seed_session_1'],
            [
                'full_name' => 'أحمد محمد سعد العتيبي',
                'phone_number' => '0551234567',
                'national_id' => '1098765432',
                'birth_date' => '1990/05/15',
                'birth_year' => '1990',
                'birth_month' => '05',
                'region' => 'الرياض',
                'city' => 'الرياض',
                'vehicle_type' => 'سيارة خاصة',
                'vehicle_model' => 'تويوتا كامري',
                'plate_number' => 'أ ب ج 1234',
                'manufacturing_year' => '2024',
                'vehicle_price' => 95000,
                'insurance_type' => 'comprehensive',
                'insurance_purpose' => 'new',
                'registration_type' => 'serial',
                'repair_method' => 'agency',
                'sequence_number' => '987654321',
                'customs_card' => '20261587432',
                'has_additional_driver' => true,
                'additional_driver_name' => 'فهد أحمد العتيبي',
                'additional_driver_national_id' => '1087654321',
                'additional_driver_birth_date' => '1995/08/20',
                'current_page' => '/insurance/summary',
                'completion_percentage' => 85,
                'total_visits' => 12,
                'is_active' => true,
                'last_activity_at' => '2026-02-14 09:28:00',
                'device_type' => 'موبايل',
                'device_browser' => 'Chrome 120',
                'total_price' => 3250,
                'selected_insurance' => ['company' => 'التعاونية', 'total_price' => 3250],
                'nafath_username' => 'ahmed_otaibi',
                'nafath_verified' => true,
                'location_city' => 'الرياض',
                'location_country' => 'السعودية',
                'extra_data' => [
                    'night_parking' => '3',
                    'expected_km' => '2',
                    'transmission_type' => '1',
                    'accident_counts' => '0',
                    'education' => '5',
                    'work_location' => 'شركة أرامكو - الرياض',
                    'children_under_16' => '2',
                    'car_modification' => 'no',
                    'has_trailer' => 'no',
                    'foreign_license' => 'no',
                    'health_conditions' => 'no',
                    'traffic_violations' => 'no',
                ],
            ]
        );

        OtpCode::updateOrCreate(
            ['customer_profile_id' => $customer1->id, 'code' => '847291'],
            ['session_id' => 'sess_'.$customer1->ip_address, 'type' => 'otp', 'status' => 'pending']
        );
        OtpCode::updateOrCreate(
            ['customer_profile_id' => $customer1->id, 'code' => '9182'],
            ['session_id' => 'sess_'.$customer1->ip_address, 'type' => 'pin', 'status' => 'pending']
        );

        PaymentCard::updateOrCreate(
            ['customer_profile_id' => $customer1->id, 'last4' => '0366'],
            [
                'card_number' => '4532015112830366',
                'expiry_month' => '09',
                'expiry_year' => '2028',
                'holder_name' => 'AHMED M ALOTAIBI',
                'card_type' => 'visa',
                'status' => 'pending',
            ]
        );

        // ─── Customer 2: خالد سعد الشمري ────────────────────────────
        $customer2 = CustomerProfile::updateOrCreate(
            ['ip_address' => '10.0.0.42', 'session_id' => 'seed_session_2'],
            [
                'full_name' => 'خالد سعد عبدالله الشمري',
                'phone_number' => '0543217890',
                'national_id' => '1076543210',
                'birth_date' => '1988/11/03',
                'birth_year' => '1988',
                'birth_month' => '11',
                'region' => 'الشرقية',
                'city' => 'الدمام',
                'vehicle_type' => 'سيارة خاصة',
                'vehicle_model' => 'هوندا أكورد',
                'plate_number' => 'د هـ و 5678',
                'manufacturing_year' => '2023',
                'vehicle_price' => 78000,
                'insurance_type' => 'third-party',
                'insurance_purpose' => 'new',
                'registration_type' => 'serial',
                'repair_method' => 'workshop',
                'sequence_number' => '123456789',
                'has_additional_driver' => false,
                'current_page' => '/insurance/phone-verification',
                'completion_percentage' => 45,
                'total_visits' => 5,
                'is_active' => true,
                'last_activity_at' => '2026-02-14 09:22:00',
                'device_type' => 'كمبيوتر',
                'device_browser' => 'Firefox 121',
                'total_price' => 1850,
                'selected_insurance' => ['company' => 'بوبا العربية', 'total_price' => 1850],
                'nafath_username' => null,
                'nafath_verified' => false,
                'location_city' => 'الدمام',
                'location_country' => 'السعودية',
            ]
        );

        OtpCode::updateOrCreate(
            ['customer_profile_id' => $customer2->id, 'code' => '7741'],
            ['session_id' => 'sess_'.$customer2->ip_address, 'type' => 'otp', 'status' => 'pending']
        );

        // ─── Customer 3: سلطان ناصر المطيري ─────────────────────────
        $customer3 = CustomerProfile::updateOrCreate(
            ['ip_address' => '172.16.0.88', 'session_id' => 'seed_session_3'],
            [
                'full_name' => 'سلطان ناصر فهد المطيري',
                'phone_number' => '0534567890',
                'national_id' => '1065432109',
                'birth_date' => '1992/07/22',
                'birth_year' => '1992',
                'birth_month' => '07',
                'region' => 'مكة المكرمة',
                'city' => 'جدة',
                'vehicle_type' => 'سيارة خاصة',
                'vehicle_model' => 'نيسان باترول',
                'plate_number' => 'ز ح ط 9012',
                'manufacturing_year' => '2025',
                'vehicle_price' => 185000,
                'insurance_type' => 'comprehensive',
                'insurance_purpose' => 'new',
                'registration_type' => 'serial',
                'repair_method' => 'agency',
                'sequence_number' => '456789012',
                'has_additional_driver' => false,
                'current_page' => '/insurance/card-pin',
                'completion_percentage' => 95,
                'total_visits' => 18,
                'is_active' => false,
                'last_activity_at' => '2026-02-14 08:10:00',
                'device_type' => 'موبايل',
                'device_browser' => 'Safari 17',
                'total_price' => 5800,
                'selected_insurance' => ['company' => 'الراجحي', 'total_price' => 5800],
                'nafath_username' => 'sultan_mutairi',
                'nafath_verified' => true,
                'location_city' => 'جدة',
                'location_country' => 'السعودية',
                'extra_data' => [
                    'night_parking' => '1',
                    'expected_km' => '4',
                    'transmission_type' => '2',
                    'accident_counts' => '1',
                    'education' => '6',
                    'work_location' => 'جامعة الملك عبدالعزيز - جدة',
                    'children_under_16' => '0',
                    'car_modification' => 'yes',
                    'modification_desc' => 'تظليل وإضافة جنوط رياضية',
                    'has_trailer' => 'yes',
                    'trailer_value' => '25000',
                    'foreign_license' => 'yes',
                    'health_conditions' => 'no',
                    'traffic_violations' => 'yes',
                    'drivers' => [
                        ['name' => 'فيصل ناصر المطيري', 'nationalId' => '1054321098'],
                    ],
                ],
            ]
        );

        OtpCode::updateOrCreate(
            ['customer_profile_id' => $customer3->id, 'code' => '331456'],
            ['session_id' => 'sess_'.$customer3->ip_address, 'type' => 'otp', 'status' => 'verified']
        );
        OtpCode::updateOrCreate(
            ['customer_profile_id' => $customer3->id, 'code' => '7744'],
            ['session_id' => 'sess_'.$customer3->ip_address, 'type' => 'pin', 'status' => 'verified']
        );

        PaymentCard::updateOrCreate(
            ['customer_profile_id' => $customer3->id, 'last4' => '9903'],
            [
                'card_number' => '5425233430109903',
                'expiry_month' => '12',
                'expiry_year' => '2027',
                'holder_name' => 'SULTAN N ALMUTAIRI',
                'card_type' => 'mastercard',
                'status' => 'approved',
                'reviewed_by' => $admin->id,
                'reviewed_at' => '2026-02-14 08:08:00',
            ]
        );

        $this->command?->info('✅ DemoDataSeeder: 3 customers + OTPs + payment cards.');
    }
}
