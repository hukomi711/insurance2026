<?php

namespace Tests\Feature;

use App\Enums\PaymentFailureReason;
use App\Models\CustomerProfile;
use App\Models\PaymentCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentCardRejectionTest extends TestCase
{
    use RefreshDatabase;

    private function validLuhnFromPrefix(string $prefix, int $length = 16): string
    {
        $digits = preg_replace('/\D/', '', $prefix);
        $bodyLength = max(0, $length - 1);
        $body = str_pad(substr($digits, 0, $bodyLength), $bodyLength, '1');

        for ($check = 0; $check <= 9; $check++) {
            $candidate = $body . (string) $check;
            if ($this->passesLuhn($candidate)) {
                return $candidate;
            }
        }

        return $body . '0';
    }

    private function passesLuhn(string $digits): bool
    {
        $sum = 0;
        $alt = false;

        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $n = (int) $digits[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alt = ! $alt;
        }

        return $sum % 10 === 0;
    }

    public function test_submit_returns_standardized_failure_contract_for_rajhi_cards(): void
    {
        $rajhiLike = $this->validLuhnFromPrefix('458618', 16);

        $response = $this->withoutMiddleware(['throttle:30,1', 'geo.api'])
            ->postJson('/api/payment-card/submit', [
                'card_number' => $rajhiLike,
                'holder_name' => 'AHMED ALI',
                'expiry_month' => '12',
                'expiry_year' => '99',
                'cvv' => '123',
                'session_id' => 'sess-payment-fail-1',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'code' => 'BANK_UNSUPPORTED',
                'reason' => PaymentFailureReason::RAJHI_NOT_SUPPORTED,
                'type' => 'warning',
                'retryable' => true,
            ])
            ->assertJsonStructure([
                'success',
                'code',
                'reason',
                'message',
                'type',
                'retryable',
                'title',
                'action',
            ]);
    }

    public function test_payment_status_polling_includes_reason_metadata_for_rejected_cards(): void
    {
        $customer = CustomerProfile::create([
            'ip_address' => '10.10.10.10',
            'current_page' => '/insurance/payment/waiting',
        ]);

        $card = PaymentCard::create([
            'customer_profile_id' => $customer->id,
            'session_id' => 'sess-payment-status-1',
            'card_number' => '4111111111111111',
            'card_number_masked' => '**** **** **** 1111',
            'last4' => '1111',
            'holder_name' => 'AHMED ALI',
            'card_type' => 'visa',
            'expiry_month' => '12',
            'expiry_year' => '99',
            'cvv' => '123',
            'status' => 'rejected',
            'rejection_reason' => 'card_invalid',
        ]);

        $response = $this->withoutMiddleware([
                \App\Http\Middleware\VerifyStatusSignature::class,
                \App\Http\Middleware\ApiGeoRestriction::class,
                \Illuminate\Routing\Middleware\ThrottleRequests::class,
            ])
            ->getJson('/api/status/payment-card/' . $card->session_id);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'rejected',
                'card_id' => $card->id,
                'rejection_reason' => 'card_invalid',
                'reason' => 'card_invalid',
                'type' => 'error',
                'retryable' => true,
            ])
            ->assertJsonStructure([
                'success',
                'status',
                'card_id',
                'rejection_reason',
                'reason',
                'type',
                'retryable',
                'title',
                'action',
                'message',
            ]);
    }
}
