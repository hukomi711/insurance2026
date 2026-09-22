<?php

namespace Tests\Feature\Admin;

use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Events\CustomerRedirected;
use App\Events\ForcePageRefresh;

/**
 * Tests for admin customer redirect and refresh functionality.
 *
 * Covers:
 * - Single and successive redirects with command ID deduplication
 * - Force refresh endpoint authorization and validation
 * - Broadcast event generation with unique command IDs
 */
class AdminCustomerRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected CustomerProfile $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->customer = CustomerProfile::factory()->create([
            'session_id' => 'test-session-uuid-12345',
            'ip_address' => '192.168.1.100',
        ]);
    }

    /**
     * Test that admin can redirect a customer to a single page.
     */
    public function test_admin_can_redirect_customer_to_page(): void
    {
        Event::fake();
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/actions/redirect-customer', [
            'customer_id' => $this->customer->id,
            'customer_ip' => $this->customer->ip_address,
            'redirect_url' => '/compare',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['command_id']);

        Event::assertDispatched(CustomerRedirected::class, function ($event) {
            return $event->customerId === $this->customer->id
                && $event->redirectUrl === '/compare'
                && !empty($event->commandId);
        });
    }

    /**
     * Test that admin can redirect a customer twice rapidly.
     * Both commands should have different command IDs.
     */
    public function test_admin_can_redirect_customer_twice_with_different_command_ids(): void
    {
        Event::fake();
        Sanctum::actingAs($this->admin);

        $response1 = $this->postJson('/api/admin/actions/redirect-customer', [
            'customer_id' => $this->customer->id,
            'customer_ip' => $this->customer->ip_address,
            'redirect_url' => '/checkout',
        ]);

        $response2 = $this->postJson('/api/admin/actions/redirect-customer', [
            'customer_id' => $this->customer->id,
            'customer_ip' => $this->customer->ip_address,
            'redirect_url' => '/confirmation',
        ]);

        $response1->assertOk()->assertJson(['success' => true]);
        $response2->assertOk()->assertJson(['success' => true]);

        $commandId1 = $response1->json('command_id');
        $commandId2 = $response2->json('command_id');

        // Command IDs must be different (proven by ULID uniqueness)
        $this->assertNotEquals($commandId1, $commandId2);

        Event::assertDispatched(CustomerRedirected::class, 2);
    }

    /**
     * Test that viewer cannot redirect customers.
     */
    public function test_viewer_cannot_redirect_customer(): void
    {
        $viewer = User::factory()->viewer()->create();
        Sanctum::actingAs($viewer);

        $response = $this->postJson('/api/admin/actions/redirect-customer', [
            'customer_id' => $this->customer->id,
            'customer_ip' => $this->customer->ip_address,
            'redirect_url' => '/compare',
        ]);

        $response->assertForbidden();
    }

    /**
     * Test that admin can refresh a customer page.
     */
    public function test_admin_can_refresh_customer_page(): void
    {
        Event::fake();
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/actions/refresh-customer-page', [
            'customer_id' => $this->customer->id,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['command_id', 'message']);

        Event::assertDispatched(ForcePageRefresh::class, function ($event) {
            return $event->customerId === $this->customer->id
                && !empty($event->commandId);
        });
    }

    /**
     * Test that refresh endpoint requires admin authorization.
     */
    public function test_viewer_cannot_refresh_customer_page(): void
    {
        $viewer = User::factory()->viewer()->create();
        Sanctum::actingAs($viewer);

        $response = $this->postJson('/api/admin/actions/refresh-customer-page', [
            'customer_id' => $this->customer->id,
        ]);

        $response->assertForbidden();
    }

    /**
     * Test that refresh endpoint validates customer exists.
     */
    public function test_refresh_endpoint_validates_customer_exists(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/actions/refresh-customer-page', [
            'customer_id' => 99999,
        ]);

        $response->assertUnprocessable(); // 422 — validation error
    }

    /**
     * Test that refresh endpoint requires active session_id.
     */
    public function test_refresh_endpoint_requires_active_session(): void
    {
        // Customer with no session_id
        $noSessionCustomer = CustomerProfile::factory()->create([
            'session_id' => null,
            'ip_address' => '192.168.1.101',
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/actions/refresh-customer-page', [
            'customer_id' => $noSessionCustomer->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    /**
     * Test that multiple refresh commands generate unique command IDs.
     */
    public function test_refresh_commands_generate_unique_command_ids(): void
    {
        Event::fake();
        Sanctum::actingAs($this->admin);

        $response1 = $this->postJson('/api/admin/actions/refresh-customer-page', [
            'customer_id' => $this->customer->id,
        ]);

        $response2 = $this->postJson('/api/admin/actions/refresh-customer-page', [
            'customer_id' => $this->customer->id,
        ]);

        $commandId1 = $response1->json('command_id');
        $commandId2 = $response2->json('command_id');

        $this->assertNotEquals($commandId1, $commandId2);

        Event::assertDispatched(ForcePageRefresh::class, 2);
    }

    /**
     * Test that redirect event includes required broadcast fields.
     */
    public function test_redirect_event_broadcasts_required_fields(): void
    {
        Event::fake();
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/admin/actions/redirect-customer', [
            'customer_id' => $this->customer->id,
            'customer_ip' => $this->customer->ip_address,
            'redirect_url' => '/checkout',
        ]);

        Event::assertDispatched(CustomerRedirected::class, function ($event) {
            $broadcast = $event->broadcastWith();
            return isset($broadcast['customer_id'])
                && isset($broadcast['redirect_url'])
                && isset($broadcast['command_id']);
        });
    }

    /**
     * Test that refresh event includes required broadcast fields.
     */
    public function test_refresh_event_broadcasts_required_fields(): void
    {
        Event::fake();
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/admin/actions/refresh-customer-page', [
            'customer_id' => $this->customer->id,
        ]);

        Event::assertDispatched(ForcePageRefresh::class, function ($event) {
            $broadcast = $event->broadcastWith();
            return isset($broadcast['customer_id'])
                && isset($broadcast['command_id'])
                && $event->broadcastAs() === 'ForcePageRefresh';
        });
    }

    /**
     * Test that unauthenticated users cannot redirect customers.
     */
    public function test_unauthenticated_user_cannot_redirect_customer(): void
    {
        $response = $this->postJson('/api/admin/actions/redirect-customer', [
            'customer_id' => $this->customer->id,
            'customer_ip' => $this->customer->ip,
            'redirect_url' => '/compare',
        ]);

        $response->assertUnauthorized();
    }

    /**
     * Test that unauthenticated users cannot refresh customer page.
     */
    public function test_unauthenticated_user_cannot_refresh_customer_page(): void
    {
        $response = $this->postJson('/api/admin/actions/refresh-customer-page', [
            'customer_id' => $this->customer->id,
        ]);

        $response->assertUnauthorized();
    }
}
