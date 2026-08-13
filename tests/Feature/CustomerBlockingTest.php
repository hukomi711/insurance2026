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

    public function test_admin_routes_are_not_blocked_by_customer_ip_rule(): void
    {
        CustomerBlock::create(['ip_address' => '127.0.0.1']);
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/me')->assertOk();
    }
}
