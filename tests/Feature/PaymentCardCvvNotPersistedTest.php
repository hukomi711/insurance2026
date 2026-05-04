<?php

namespace Tests\Feature;

use App\Models\CustomerProfile;
use App\Models\PaymentCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * PCI-DSS Requirement 3.3.1: card verification value (CVV/CVV2/CVC2/CID)
 * MUST NOT be retained after authorization. These tests guard against any
 * regression that re-introduces persistent CVV storage.
 */
class PaymentCardCvvNotPersistedTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_cards_table_has_no_cvv_column(): void
    {
        $this->assertFalse(
            Schema::hasColumn('payment_cards', 'cvv'),
            'payment_cards.cvv column must not exist (PCI-DSS 3.3.1).'
        );
        $this->assertFalse(
            Schema::hasColumn('payment_cards', 'cvv_encrypted'),
            'payment_cards.cvv_encrypted column must not exist (PCI-DSS 3.3.1).'
        );
        $this->assertFalse(
            Schema::hasColumn('payment_cards', 'cvv_verified'),
            'payment_cards.cvv_verified column must not exist (PCI-DSS 3.3.1).'
        );
    }

    public function test_payment_card_model_does_not_expose_cvv(): void
    {
        $model = new PaymentCard();
        $this->assertNotContains('cvv', $model->getFillable());
        $this->assertNotContains('cvv_encrypted', $model->getFillable());
        $this->assertArrayNotHasKey('cvv', $model->getCasts());
        $this->assertArrayNotHasKey('cvv_encrypted', $model->getCasts());
    }

    public function test_submit_endpoint_does_not_persist_cvv(): void
    {
        CustomerProfile::create([
            'ip_address' => '10.20.30.40',
            'current_page' => '/insurance/checkout',
        ]);

        $response = $this->withoutMiddleware(['throttle:30,1', 'geo.api'])
            ->postJson('/api/payment-card/submit', [
                'card_number'  => '4111 1111 1111 1111',
                'holder_name'  => 'AHMED ALI',
                'expiry_month' => '12',
                'expiry_year'  => '99',
                'cvv'          => '321',
                'session_id'   => 'sess-cvv-leak-test',
            ]);

        $response->assertStatus(200);

        $rows = DB::table('payment_cards')->get();
        $this->assertCount(1, $rows);
        $row = (array) $rows->first();

        // No CVV field with any name should exist on the persisted row.
        $forbidden = ['cvv', 'cvv_encrypted', 'cvv_verified', 'cvc', 'cvc2'];
        foreach ($forbidden as $key) {
            $this->assertArrayNotHasKey(
                $key,
                $row,
                "Persisted payment_cards row must not contain `{$key}` (PCI-DSS 3.3.1)."
            );
        }
    }

    public function test_payment_card_serialized_payload_never_contains_cvv(): void
    {
        $customer = CustomerProfile::create([
            'ip_address' => '10.20.30.41',
            'current_page' => '/insurance/checkout',
        ]);

        $card = PaymentCard::create([
            'customer_profile_id' => $customer->id,
            'session_id'          => 'sess-serialize-test',
            'card_number'         => '4111111111111111',
            'card_number_masked'  => '**** **** **** 1111',
            'last4'               => '1111',
            'holder_name'         => 'AHMED ALI',
            'card_type'           => 'visa',
            'expiry_month'        => '12',
            'expiry_year'         => '99',
            'status'              => 'pending',
        ]);

        $payload = json_encode($card->fresh()->toArray(), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('cvv', $payload);
    }

    public function test_payment_card_rejects_cvv_via_mass_assignment(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\MassAssignmentException::class);

        PaymentCard::create([
            'customer_profile_id' => 1,
            'session_id'          => 'sess-cvv-mass-assign',
            'card_number'         => '4111111111111111',
            'card_number_masked'  => '**** **** **** 1111',
            'last4'               => '1111',
            'holder_name'         => 'AHMED ALI',
            'card_type'           => 'visa',
            'expiry_month'        => '12',
            'expiry_year'         => '99',
            'status'              => 'pending',
            'cvv_encrypted'       => '321',
        ]);
    }
}
