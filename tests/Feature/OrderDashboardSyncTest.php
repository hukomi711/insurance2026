<?php

namespace Tests\Feature;

use App\Events\CustomerActivityUpdated;
use App\Http\Middleware\ApiGeoRestriction;
use App\Models\CustomerProfile;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Full path coverage:
 * 1) customer creates order
 * 2) order persisted and linked to the correct customer
 * 3) order appears in admin dashboard list/details
 * 4) customer edits order
 * 5) dashboard returns latest persisted state (catch-up source after reconnect)
 */
class OrderDashboardSyncTest extends TestCase
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

    private function issueQuoteLock(string $sessionId): array
    {
        $payload = [
            'plan_id' => 1001,
            'company_id' => 1,
            'plan_sub_type' => 'thirdParty',
            'plan_name' => 'خطة اختبار',
            'insurance_company' => 'شركة اختبار',
            'insurance_type' => 'third_party',
            'plan_type' => 'thirdParty',
            'subtotal' => 1,
            'vat_amount' => 1,
            'total' => 2,
            'deductible' => 1000,
            // base (399) + addons (85 + 510) = 994 so it remains inside
            // OrderController third_party minimum boundaries.
            'addon_ids' => [0, 1],
            'addons' => [
                ['id' => 0, 'name' => $this->addonName(0), 'price' => 1],
                ['id' => 1, 'name' => $this->addonName(1), 'price' => 1],
            ],
            'session_id' => $sessionId,
        ];

        $response = $this->withoutMiddleware($this->middlewareBypass())
            ->postJson('/api/quotes/lock', $payload);

        $response->assertOk()->assertJson(['success' => true]);

        return $response->json();
    }

    private function orderPayload(array $lock, array $overrides = []): array
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
            'addon_ids' => [0, 1],
            'addons' => [
                ['id' => 0, 'name' => $this->addonName(0), 'price' => 1],
                ['id' => 1, 'name' => $this->addonName(1), 'price' => 1],
            ],
            'quote_lock_token' => $lock['quote_lock_token'],
            'vehicle_plate' => 'ا ب ج 1234',
            'vehicle_make' => 'Toyota',
            'vehicle_model' => 'Camry',
            'vehicle_year' => 2022,
            'applicant_name' => 'أحمد الاختبار',
            'applicant_phone' => '0500000000',
        ], $overrides);
    }

    public function test_full_order_lifecycle_is_saved_and_reaches_the_dashboard(): void
    {
        $sessionId = 'sess-dash-' . uniqid();

        $customer = CustomerProfile::factory()->create([
            'session_id' => $sessionId,
            'ip_address' => '10.20.30.40',
        ]);

        // 1) Customer creates an order
        $lock = $this->issueQuoteLock($sessionId);

        Event::fake([CustomerActivityUpdated::class]);

        $createResponse = $this->withoutMiddleware($this->middlewareBypass())
            ->withHeader('X-Session-Token', $sessionId)
            ->postJson('/api/orders', $this->orderPayload($lock));

        $createResponse->assertStatus(201)->assertJson(['success' => true]);
        $orderNumber = $createResponse->json('order_number');
        $this->assertNotEmpty($orderNumber);

        // 2) Persisted in DB and linked to correct customer/session
        $this->assertDatabaseHas('orders', [
            'order_number' => $orderNumber,
            'customer_profile_id' => $customer->id,
            'session_id' => $sessionId,
            'vehicle_plate' => 'ا ب ج 1234',
            'status' => 'pending',
        ]);

        Event::assertDispatched(CustomerActivityUpdated::class, function (CustomerActivityUpdated $event) use ($customer) {
            return $event->customerId === $customer->id;
        });
        Event::assertDispatchedTimes(CustomerActivityUpdated::class, 1);

        // 3) Dashboard detail endpoint returns the latest order snapshot
        $dashboardDetailResponse = $this->withoutMiddleware()
            ->getJson("/api/admin/customers/{$customer->id}");

        $dashboardDetailResponse->assertOk()->assertJson(['success' => true]);
        $dashboardDetail = $dashboardDetailResponse->json('data');

        $this->assertNotEmpty($dashboardDetail['orders'] ?? null, 'Order did not reach dashboard detail payload');
        $this->assertSame($orderNumber, $dashboardDetail['latest_order']['order_number']);
        $this->assertSame('Toyota', $dashboardDetail['latest_order']['vehicle_make']);
        $this->assertSame('Camry', $dashboardDetail['vehicleModel']);
        $this->assertSame('ا ب ج 1234', $dashboardDetail['plateNumber']);
        $this->assertNotNull($dashboardDetail['totalPrice']);

        // Ensure order applicant PII is not embedded in the dashboard order snapshot
        $this->assertArrayNotHasKey('applicant_national_id', $dashboardDetail['latest_order']);
        $this->assertArrayNotHasKey('applicant_phone', $dashboardDetail['latest_order']);
        $this->assertArrayNotHasKey('applicant_name', $dashboardDetail['latest_order']);

        // 3b) Dashboard list endpoint also reflects same order state
        $dashboardListResponse = $this->withoutMiddleware()->getJson('/api/admin/customers?per_page=80');
        $dashboardListResponse->assertOk()->assertJson(['success' => true]);

        $listRow = collect($dashboardListResponse->json('data'))->firstWhere('id', $customer->id);
        $this->assertNotNull($listRow, 'Customer row missing from dashboard list payload');
        $this->assertSame($orderNumber, $listRow['latest_order']['order_number'] ?? null);
        $this->assertSame('Camry', $listRow['vehicleModel'] ?? null);

        // 4) Customer edits the SAME order (plate correction)
        Event::fake([CustomerActivityUpdated::class]);

        $updateResponse = $this->withoutMiddleware($this->middlewareBypass())
            ->withHeader('X-Session-Token', $sessionId)
            ->patchJson("/api/orders/{$orderNumber}", [
                'vehicle_plate' => 'د هـ و 5678',
            ]);

        $updateResponse->assertOk()->assertJson(['success' => true]);

        // Must update same row, not create duplicates
        $this->assertDatabaseHas('orders', [
            'order_number' => $orderNumber,
            'vehicle_plate' => 'د هـ و 5678',
        ]);
        $this->assertDatabaseCount('orders', 1);

        Event::assertDispatched(CustomerActivityUpdated::class, function (CustomerActivityUpdated $event) use ($customer) {
            return $event->customerId === $customer->id;
        });
        Event::assertDispatchedTimes(CustomerActivityUpdated::class, 1);

        // 5) Dashboard detail + list return the latest persisted state
        $refreshedDetailResponse = $this->withoutMiddleware()
            ->getJson("/api/admin/customers/{$customer->id}");
        $refreshedDetailResponse->assertOk();

        $refreshedDetail = $refreshedDetailResponse->json('data');
        $this->assertSame('د هـ و 5678', $refreshedDetail['latest_order']['vehicle_plate']);
        $this->assertSame('د هـ و 5678', $refreshedDetail['plateNumber']);
        $this->assertCount(1, $refreshedDetail['orders'], 'Editing must not create duplicate order rows in payload');

        $refreshedListResponse = $this->withoutMiddleware()->getJson('/api/admin/customers?per_page=80');
        $refreshedListResponse->assertOk();
        $refreshedListRow = collect($refreshedListResponse->json('data'))->firstWhere('id', $customer->id);
        $this->assertSame('د هـ و 5678', $refreshedListRow['latest_order']['vehicle_plate'] ?? null);
        $this->assertSame('د هـ و 5678', $refreshedListRow['plateNumber'] ?? null);
    }

    public function test_order_update_is_rejected_for_a_different_session(): void
    {
        $sessionId = 'sess-dash-' . uniqid();
        CustomerProfile::factory()->create(['session_id' => $sessionId]);
        $lock = $this->issueQuoteLock($sessionId);

        $createResponse = $this->withoutMiddleware($this->middlewareBypass())
            ->withHeader('X-Session-Token', $sessionId)
            ->postJson('/api/orders', $this->orderPayload($lock));
        $createResponse->assertStatus(201);

        $orderNumber = $createResponse->json('order_number');

        $response = $this->withoutMiddleware($this->middlewareBypass())
            ->withHeader('X-Session-Token', 'someone-elses-session')
            ->patchJson("/api/orders/{$orderNumber}", [
                'vehicle_plate' => 'X X X 0000',
            ]);

        $response->assertStatus(404);

        // Original value must remain unchanged.
        $this->assertDatabaseHas('orders', [
            'order_number' => $orderNumber,
            'vehicle_plate' => 'ا ب ج 1234',
        ]);
    }

    public function test_confirmed_order_cannot_be_edited(): void
    {
        $sessionId = 'sess-dash-' . uniqid();
        CustomerProfile::factory()->create(['session_id' => $sessionId]);
        $lock = $this->issueQuoteLock($sessionId);

        $createResponse = $this->withoutMiddleware($this->middlewareBypass())
            ->withHeader('X-Session-Token', $sessionId)
            ->postJson('/api/orders', $this->orderPayload($lock));
        $createResponse->assertStatus(201);

        $orderNumber = $createResponse->json('order_number');

        Order::where('order_number', $orderNumber)->update(['status' => 'confirmed']);

        $response = $this->withoutMiddleware($this->middlewareBypass())
            ->withHeader('X-Session-Token', $sessionId)
            ->patchJson("/api/orders/{$orderNumber}", [
                'vehicle_plate' => 'X X X 0000',
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('orders', [
            'order_number' => $orderNumber,
            'vehicle_plate' => 'ا ب ج 1234',
        ]);
    }
}
