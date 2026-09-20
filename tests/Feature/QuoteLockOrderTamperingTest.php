<?php

namespace Tests\Feature;

use App\Http\Middleware\ApiGeoRestriction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class QuoteLockOrderTamperingTest extends TestCase
{
    use RefreshDatabase;

    private function middlewareBypass(): array
    {
        return [
            ApiGeoRestriction::class,
            ThrottleRequests::class,
        ];
    }

    private function addonName(int $id): string
    {
        return (string) (config('pricing.addons_prices.' . $id . '.name') ?? '');
    }

    private function issueQuoteLock(array $overrides = []): array
    {
        $payload = array_merge([
            'plan_id' => 1001,
            'company_id' => 1,
            'plan_sub_type' => 'thirdParty',
            'plan_name' => 'خطة اختبار',
            'insurance_company' => 'شركة اختبار',
            'insurance_type' => 'third_party',
            'plan_type' => 'thirdParty',
            // Intentionally wrong client values: server must recalculate.
            'subtotal' => 1,
            'vat_amount' => 1,
            'total' => 2,
            'deductible' => 1000,
            'addon_ids' => [0],
            'addons' => [
                [
                    'id' => 0,
                    'name' => $this->addonName(0),
                    'price' => 999999,
                ],
            ],
            'session_id' => 'sess-lock-' . uniqid(),
        ], $overrides);

        $response = $this->withoutMiddleware($this->middlewareBypass())
            ->postJson('/api/quotes/lock', $payload);

        $response->assertOk()->assertJson(['success' => true]);

        return $response->json();
    }

    private function orderPayloadFromLock(array $lock, array $overrides = []): array
    {
        return array_merge([
            'plan_id' => 1001,
            'company_id' => 1,
            'plan_sub_type' => 'thirdParty',
            'plan_name' => 'خطة اختبار',
            'insurance_company' => 'شركة اختبار',
            'insurance_type' => 'third_party',
            'plan_type' => 'thirdParty',
            'subtotal' => $lock['subtotal'],
            'vat_amount' => $lock['vat_amount'],
            'total' => $lock['total'],
            'deductible' => 1000,
            'accept_terms' => true,
            'addon_ids' => [0],
            'addons' => [
                [
                    'id' => 0,
                    'name' => $this->addonName(0),
                    'price' => 1,
                ],
            ],
            'quote_lock_token' => $lock['quote_lock_token'],
        ], $overrides);
    }

    public function test_quote_lock_uses_server_addon_prices_and_ignores_client_tampering(): void
    {
        $lock = $this->issueQuoteLock([
            'plan_sub_type' => 'comprehensive',
            'insurance_type' => 'comprehensive',
            'addon_ids' => [0, 1],
            'addons' => [
                ['id' => 0, 'name' => $this->addonName(0), 'price' => 1],
                ['id' => 1, 'name' => $this->addonName(1), 'price' => 999999],
            ],
        ]);

        // company 1 (399) + comprehensive gap (250) + deductible 1000 (0) + addons (85 + 510)
        $this->assertEqualsWithDelta(1244.0, (float) $lock['subtotal'], 0.001);
        $this->assertEqualsWithDelta(186.6, (float) $lock['vat_amount'], 0.001);
        $this->assertEqualsWithDelta(1430.6, (float) $lock['total'], 0.001);

        $snapshot = Cache::get('quote_lock:' . $lock['quote_lock_token']);
        $this->assertIsArray($snapshot);
        $this->assertSame([0, 1], $snapshot['addon_ids']);
        $this->assertEqualsWithDelta(595.0, (float) $snapshot['addons_total'], 0.001);

        $this->assertCount(2, $snapshot['addons']);
        $this->assertSame(0, (int) $snapshot['addons'][0]['id']);
        $this->assertSame(1, (int) $snapshot['addons'][1]['id']);
        $this->assertEqualsWithDelta(85.0, (float) $snapshot['addons'][0]['price'], 0.001);
        $this->assertEqualsWithDelta(510.0, (float) $snapshot['addons'][1]['price'], 0.001);
    }

    public function test_order_rejects_when_addon_ids_do_not_match_quote_lock_snapshot(): void
    {
        $lock = $this->issueQuoteLock([
            'addon_ids' => [0],
            'addons' => [
                ['id' => 0, 'name' => $this->addonName(0), 'price' => 123456],
            ],
        ]);

        $payload = $this->orderPayloadFromLock($lock, [
            // Tampering: change addon selection after quote lock.
            'addon_ids' => [1],
            'addons' => [
                ['id' => 1, 'name' => $this->addonName(1), 'price' => 510],
            ],
        ]);

        $response = $this->withoutMiddleware($this->middlewareBypass())
            ->postJson('/api/orders', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'الإضافات المختارة لا تطابق عرض السعر المحفوظ.',
            ]);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_rejects_when_legacy_addons_payload_maps_to_different_addon_ids(): void
    {
        $lock = $this->issueQuoteLock([
            'addon_ids' => [0],
            'addons' => [
                ['id' => 0, 'name' => $this->addonName(0), 'price' => 333333],
            ],
        ]);

        $payload = $this->orderPayloadFromLock($lock, [
            // Tampering: omit addon_ids and switch legacy addon object to the other addon name.
            'addon_ids' => null,
            'addons' => [
                ['name' => $this->addonName(1), 'price' => 1],
            ],
        ]);

        $response = $this->withoutMiddleware($this->middlewareBypass())
            ->postJson('/api/orders', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'الإضافات المختارة لا تطابق عرض السعر المحفوظ.',
            ]);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_accepts_matching_addon_ids_even_if_addon_prices_are_client_tampered(): void
    {
        $lock = $this->issueQuoteLock([
            'addon_ids' => [0, 1],
            'addons' => [
                ['id' => 0, 'name' => $this->addonName(0), 'price' => 777777],
                ['id' => 1, 'name' => $this->addonName(1), 'price' => 666666],
            ],
        ]);

        $payload = $this->orderPayloadFromLock($lock, [
            // Keep addon_ids consistent with lock, but keep forged addon price.
            'addon_ids' => [0, 1],
            'addons' => [
                ['id' => 0, 'name' => $this->addonName(0), 'price' => 999999],
                ['id' => 1, 'name' => $this->addonName(1), 'price' => 888888],
            ],
        ]);

        $response = $this->withoutMiddleware($this->middlewareBypass())
            ->postJson('/api/orders', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('orders', [
            'plan_id' => 1001,
            'company_id' => 1,
            'insurance_type' => 'third_party',
            'deductible' => 1000,
        ]);
    }

    public function test_order_without_quote_lock_rejects_tampered_totals_against_server_recalculation(): void
    {
        $payload = [
            'plan_id' => 1001,
            'company_id' => 1,
            'plan_sub_type' => 'thirdParty',
            'plan_name' => 'خطة اختبار',
            'insurance_company' => 'شركة اختبار',
            'insurance_type' => 'third_party',
            'plan_type' => 'thirdParty',
            // Tampered totals (real subtotal should be 584 with addon 0).
            'subtotal' => 999,
            'vat_amount' => 150,
            'total' => 1149,
            'deductible' => 1000,
            'accept_terms' => true,
            'addon_ids' => [0],
            'addons' => [
                [
                    'id' => 0,
                    'name' => $this->addonName(0),
                    'price' => 999999,
                ],
            ],
        ];

        $response = $this->withoutMiddleware($this->middlewareBypass())
            ->postJson('/api/orders', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'الأسعار المرسلة لا تطابق آلية التسعير الثابتة.',
            ]);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_rejects_when_terms_are_not_accepted(): void
    {
        $lock = $this->issueQuoteLock([
            'addon_ids' => [0],
            'addons' => [
                ['id' => 0, 'name' => $this->addonName(0), 'price' => 123],
            ],
        ]);

        $payload = $this->orderPayloadFromLock($lock, [
            'accept_terms' => false,
        ]);

        $response = $this->withoutMiddleware($this->middlewareBypass())
            ->postJson('/api/orders', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['accept_terms']);

        $this->assertDatabaseCount('orders', 0);
    }
}
