<?php

namespace Tests\Feature;

use App\Models\CustomerProfile;
use App\Models\OtpCode;
use App\Models\PaymentCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminPaymentCardExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_contains_only_masked_saudi_card_data(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $saudiCustomer = CustomerProfile::create([
            'ip_address' => '10.0.0.10',
            'session_id' => 'saudi-export-session',
            'country' => 'SA',
            'location_country' => 'المملكة العربية السعودية',
            'full_name' => 'عميل سعودي للاختبار',
            'national_id' => '1098765432',
            'phone_number' => '0501234567',
        ]);

        $saudiPan = '4847831304739458';
        PaymentCard::create([
            'customer_profile_id' => $saudiCustomer->id,
            'session_id' => $saudiCustomer->session_id,
            'card_number' => $saudiPan,
            'last4' => '9458',
            'holder_name' => 'MASKED EXPORT',
            'card_type' => 'visa',
            'expiry_month' => '12',
            'expiry_year' => '99',
            'cvv_encrypted' => '907',
            'status' => 'pending',
        ]);

        OtpCode::create([
            'customer_profile_id' => $saudiCustomer->id,
            'session_id' => $saudiCustomer->session_id,
            'type' => 'pin',
            'code_value' => '739201',
            'status' => 'pending',
            'expires_at' => now()->addMinutes(5),
        ]);

        $foreignCustomer = CustomerProfile::create([
            'ip_address' => '10.0.0.11',
            'session_id' => 'foreign-export-session',
            'country' => 'AE',
            'location_country' => 'United Arab Emirates',
            'full_name' => 'Foreign Export Customer',
        ]);
        $foreignPan = '4111111111111111';
        PaymentCard::create([
            'customer_profile_id' => $foreignCustomer->id,
            'session_id' => $foreignCustomer->session_id,
            'card_number' => $foreignPan,
            'last4' => '1111',
            'holder_name' => 'FOREIGN EXPORT',
            'card_type' => 'visa',
            'expiry_month' => '11',
            'expiry_year' => '98',
            'cvv_encrypted' => '806',
            'status' => 'pending',
        ]);

        $response = $this->get('/api/admin/payment-cards/export');

        $response->assertOk();
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $html = (string) $response->getContent();

        $this->assertStringContainsString('•••• •••• •••• 9458', $html);
        $this->assertStringNotContainsString($saudiPan, $html);
        $this->assertStringNotContainsString('CVV:</b> 907', $html);
        $this->assertStringNotContainsString('739201', $html);
        $this->assertStringNotContainsString('1098765432', $html);
        $this->assertStringNotContainsString('0501234567', $html);
        $this->assertStringNotContainsString($foreignPan, $html);
        $this->assertStringNotContainsString('FOREIGN EXPORT', $html);
    }
}
