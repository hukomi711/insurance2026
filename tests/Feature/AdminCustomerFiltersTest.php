<?php

namespace Tests\Feature;

use App\Models\CustomerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomerFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_index_is_strictly_saudi_without_hiding_duplicate_session_rows(): void
    {
        // Same session appears twice. Both historical rows must remain visible.
        CustomerProfile::create([
            'ip_address' => '10.10.10.1',
            'session_id' => 'same-browser-session',
            'country' => 'SA',
            'location_country' => 'Saudi Arabia',
            'is_active' => false,
            'last_activity_at' => now()->subMinutes(5),
        ]);
        CustomerProfile::create([
            'ip_address' => '10.10.10.1',
            'session_id' => 'same-browser-session',
            'country' => 'SA',
            'location_country' => 'Saudi Arabia',
            'is_active' => true,
            'last_activity_at' => now()->subMinutes(1),
        ]);

        CustomerProfile::create([
            'ip_address' => '10.10.10.2',
            'session_id' => 'shared-ip-browser-a',
            'country' => 'SA',
            'location_country' => 'السعودية',
            'is_active' => false,
            'last_activity_at' => now()->subMinutes(4),
        ]);
        CustomerProfile::create([
            'ip_address' => '10.10.10.2',
            'session_id' => 'shared-ip-browser-b',
            'country' => 'SA',
            'location_country' => 'السعودية',
            'is_active' => false,
            'last_activity_at' => now()->subMinutes(2),
        ]);

        CustomerProfile::create([
            'ip_address' => '10.10.10.3',
            'country' => 'US',
            'location_country' => 'United States',
            'is_active' => true,
            'last_activity_at' => now()->subMinutes(3),
        ]);

        CustomerProfile::create([
            'ip_address' => '10.10.10.4',
            'country' => null,
            'location_country' => null,
            'is_active' => true,
            'last_activity_at' => now(),
        ]);

        $response = $this->withoutMiddleware()->getJson('/api/admin/customers?per_page=50');

        $response->assertOk()->assertJson(['success' => true]);

        $total = $response->json('total');
        $data = $response->json('data');
        $ips = array_map(static fn (array $row) => $row['ip'] ?? null, $data);
        $uniqueIps = array_values(array_unique(array_filter($ips)));

        // All Saudi profile rows remain visible, including both rows from the
        // same browser session. US and unknown rows are excluded by the
        // server-enforced product rule.
        $this->assertSame(4, $total);
        $this->assertCount(4, $data);
        $this->assertCount(2, $uniqueIps);
    }

    public function test_active_count_matches_current_filters(): void
    {
        // SA (including empty country fallback)
        CustomerProfile::create([
            'ip_address' => '10.20.10.1',
            'country' => 'SA',
            'location_country' => 'Saudi Arabia',
            'is_active' => true,
            'last_activity_at' => now()->subMinutes(2),
        ]);
        CustomerProfile::create([
            'ip_address' => '10.20.10.2',
            'country' => 'SA',
            'location_country' => 'السعودية',
            'is_active' => false,
            'last_activity_at' => now()->subMinutes(1),
        ]);
        CustomerProfile::create([
            'ip_address' => '10.20.10.3',
            'country' => null,
            'location_country' => null,
            'is_active' => true,
            'last_activity_at' => now()->subMinutes(4),
        ]);

        // Other countries
        CustomerProfile::create([
            'ip_address' => '10.20.10.4',
            'country' => 'US',
            'location_country' => 'United States',
            'is_active' => true,
            'last_activity_at' => now()->subMinutes(2),
        ]);
        CustomerProfile::create([
            'ip_address' => '10.20.10.5',
            'country' => 'US',
            'location_country' => 'United States',
            'is_active' => false,
            'last_activity_at' => now()->subMinutes(5),
        ]);

        $sa = $this->withoutMiddleware()->getJson('/api/admin/customers?country=SA&per_page=50');
        $sa->assertOk()->assertJson(['success' => true]);
        $this->assertSame(2, $sa->json('total'));
        $this->assertSame(2, $sa->json('active_count'));

        $other = $this->withoutMiddleware()->getJson('/api/admin/customers?country=other&per_page=50');
        $other->assertOk()->assertJson(['success' => true]);
        $this->assertSame(2, $other->json('total'));
        $this->assertSame(2, $other->json('active_count'));

        $activeOnly = $this->withoutMiddleware()->getJson('/api/admin/customers?active_only=1&per_page=50');
        $activeOnly->assertOk()->assertJson(['success' => true]);
        $this->assertSame(2, $activeOnly->json('total'));
        $this->assertSame(2, $activeOnly->json('active_count'));
    }

    public function test_customer_payload_includes_saved_contact_details(): void
    {
        CustomerProfile::create([
            'ip_address' => '10.40.10.1',
            'session_id' => 'contact-details-session',
            'country' => 'SA',
            'location_country' => 'Saudi Arabia',
            'full_name' => 'Test Customer',
            'phone_number' => '0501234567',
            'email' => 'customer@example.test',
            'last_activity_at' => now(),
        ]);

        $response = $this->withoutMiddleware()->getJson('/api/admin/customers?per_page=50');

        $response->assertOk()
            ->assertJsonPath('data.0.fullName', 'Test Customer')
            ->assertJsonPath('data.0.phoneNumber', '0501234567')
            ->assertJsonPath('data.0.email', 'customer@example.test');
    }

    public function test_search_with_no_matches_returns_zero_total_and_active_count(): void
    {
        CustomerProfile::create([
            'ip_address' => '10.30.10.1',
            'full_name' => 'Ahmed Saleh',
            'country' => 'SA',
            'location_country' => 'Saudi Arabia',
            'is_active' => true,
            'last_activity_at' => now()->subMinute(),
        ]);

        CustomerProfile::create([
            'ip_address' => '10.30.10.2',
            'full_name' => 'Sara Ali',
            'country' => 'US',
            'location_country' => 'United States',
            'is_active' => true,
            'last_activity_at' => now(),
        ]);

        $response = $this->withoutMiddleware()->getJson('/api/admin/customers?search=NO_MATCH_TERM_123&per_page=50');

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSame(0, $response->json('total'));
        $this->assertSame(0, $response->json('active_count'));
        $this->assertCount(0, $response->json('data'));
    }

    public function test_non_saudi_customer_cannot_be_loaded_by_dashboard_patch_endpoint(): void
    {
        $customer = CustomerProfile::create([
            'ip_address' => '10.40.10.1',
            'country' => 'US',
            'location_country' => 'United States',
            'is_active' => true,
            'last_activity_at' => now(),
        ]);

        $this->withoutMiddleware()
            ->getJson("/api/admin/customers/{$customer->id}")
            ->assertNotFound();
    }
}
