<?php

namespace Tests\Feature;

use App\Models\CustomerBlock;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerBlockingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_admin_can_block_customer_with_existing_identity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $customer = CustomerProfile::create([
            'ip_address' => '203.0.113.20',
            'session_id' => '4d50df10-f3f7-42c1-b7ce-3358ef3cc0d3',
            'is_active' => true,
        ]);

        $this->postJson("/api/admin/customers/{$customer->id}/block")
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'customer_id' => $customer->id,
                    'is_active' => false,
                ],
            ]);

        $this->assertDatabaseHas('customer_blocks', [
            'customer_profile_id' => $customer->id,
            'ip_address' => '203.0.113.20',
            'session_id' => '4d50df10-f3f7-42c1-b7ce-3358ef3cc0d3',
            'blocked_by' => $admin->id,
        ]);
        $this->assertFalse($customer->fresh()->is_active);
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $admin->id,
            'action' => 'block_customer',
            'target_id' => $customer->id,
        ]);
    }

    public function test_admin_can_unblock_customer_with_existing_identity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $customer = CustomerProfile::create([
            'ip_address' => '203.0.113.20',
            'session_id' => '4d50df10-f3f7-42c1-b7ce-3358ef3cc0d3',
            'is_active' => true,
        ]);

        $this->postJson("/api/admin/customers/{$customer->id}/block")
            ->assertOk();

        $this->postJson("/api/admin/customers/{$customer->id}/unblock")
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'customer_id' => $customer->id,
                    'is_active' => true,
                    'is_blocked' => false,
                ],
            ]);

        $this->assertDatabaseMissing('customer_blocks', [
            'customer_profile_id' => $customer->id,
            'session_id' => '4d50df10-f3f7-42c1-b7ce-3358ef3cc0d3',
        ]);
        $this->assertTrue($customer->fresh()->is_active);
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $admin->id,
            'action' => 'unblock_customer',
            'target_id' => $customer->id,
        ]);
    }

    public function test_admin_block_does_not_deactivate_another_customer_on_the_same_ip(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $blockedCustomer = CustomerProfile::create([
            'ip_address' => '203.0.113.20',
            'session_id' => '4d50df10-f3f7-42c1-b7ce-3358ef3cc0d3',
            'is_active' => true,
        ]);
        $otherCustomer = CustomerProfile::create([
            'ip_address' => '203.0.113.20',
            'session_id' => '21fd54d4-569f-4c7c-8faf-2b13021a540c',
            'is_active' => true,
        ]);

        $this->postJson("/api/admin/customers/{$blockedCustomer->id}/block")
            ->assertOk();

        $this->assertFalse($blockedCustomer->fresh()->is_active);
        $this->assertTrue($otherCustomer->fresh()->is_active);
    }

    public function test_admin_customer_list_marks_blocked_customers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $customer = CustomerProfile::create([
            'ip_address' => '203.0.113.20',
            'session_id' => '4d50df10-f3f7-42c1-b7ce-3358ef3cc0d3',
            'is_active' => true,
        ]);

        $this->postJson("/api/admin/customers/{$customer->id}/block")
            ->assertOk();

        $this->getJson('/api/admin/customers')
            ->assertOk()
            ->assertJsonPath('data.0.id', $customer->id)
            ->assertJsonPath('data.0.is_blocked', true);
    }

    public function test_admin_single_customer_show_marks_blocked_customers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $customer = CustomerProfile::create([
            'ip_address' => '203.0.113.20',
            'session_id' => '4d50df10-f3f7-42c1-b7ce-3358ef3cc0d3',
            'is_active' => true,
        ]);

        $this->postJson("/api/admin/customers/{$customer->id}/block")
            ->assertOk();

        $this->getJson("/api/admin/customers/{$customer->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $customer->id)
            ->assertJsonPath('data.is_blocked', true);
    }

    public function test_blocked_customer_is_rejected_by_existing_heartbeat(): void
    {
        CustomerBlock::create([
            'ip_address' => '127.0.0.1',
            'session_id' => '031f439f-fcc7-457a-9b75-620524842065',
        ]);

        $this->withHeader('X-Session-Token', '031f439f-fcc7-457a-9b75-620524842065')
            ->postJson('/api/customer/page', ['current_page' => '/insurance'])
            ->assertStatus(423)
            ->assertJson([
                'success' => false,
                'blocked' => true,
            ]);
    }

    public function test_customer_sharing_a_blocked_customers_ip_remains_available(): void
    {
        CustomerBlock::create([
            'ip_address' => '127.0.0.1',
            'session_id' => '031f439f-fcc7-457a-9b75-620524842065',
        ]);

        $this->withHeader('X-Session-Token', 'b6dfdddb-1be1-46ed-8fe9-63a00a1e9359')
            ->postJson('/api/customer/page', ['current_page' => '/insurance'])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_geo_check_is_not_blocked_by_customer_ip_rule(): void
    {
        CustomerBlock::create([
            'ip_address' => '127.0.0.1',
            'session_id' => '031f439f-fcc7-457a-9b75-620524842065',
        ]);

        $this->withHeader('X-Session-Token', '031f439f-fcc7-457a-9b75-620524842065')
            ->getJson('/api/geo/check')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'customer_blocked' => true,
            ]);
    }

    public function test_admin_routes_are_not_blocked_by_customer_ip_rule(): void
    {
        CustomerBlock::create(['ip_address' => '127.0.0.1']);
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/me')->assertOk();
    }
}
