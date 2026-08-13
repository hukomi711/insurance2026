<?php

namespace Tests\Unit;

use App\Enums\PaymentFailureReason;
use Tests\TestCase;

class PaymentFailureReasonTest extends TestCase
{
    public function test_php_metadata_matches_the_shared_contract(): void
    {
        $fixtures = json_decode(
            file_get_contents(base_path('tests/Fixtures/payment-failure-reasons.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        foreach ($fixtures['cases'] as $case) {
            $this->assertSame(
                $case['expected'],
                PaymentFailureReason::meta($case['reason']),
                "Payment failure fixture [{$case['reason']}]",
            );
        }
    }

    public function test_unknown_and_empty_reasons_normalize_to_card_declined(): void
    {
        $this->assertSame('card_declined', PaymentFailureReason::meta('unknown_reason')['reason']);
        $this->assertSame('card_declined', PaymentFailureReason::meta(null)['reason']);
        $this->assertSame('card_declined', PaymentFailureReason::meta('   ')['reason']);
    }

    public function test_card_rejection_values_exclude_system_failure_reasons(): void
    {
        $values = PaymentFailureReason::cardRejectionValues();

        $this->assertContains('card_invalid', $values);
        $this->assertNotContains('otp_failed', $values);
        $this->assertNotContains('network_error', $values);
        $this->assertNotContains('rajhi_not_supported', $values);
    }
}
