<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Covers the admin/super_admin/viewer role model:
 * - viewer is read-only across the dashboard (blocked from every write route)
 * - admin has full write access except create/delete users (super_admin-only)
 * - super_admin has every permission admin has, plus create/delete users
 */
class UserManagementRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_allows_all_three_dashboard_roles(): void
    {
        foreach (['admin', 'super_admin', 'viewer'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $response = $this->postJson('/api/admin/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

            $response->assertOk()->assertJson(['success' => true, 'requires_2fa' => true]);
        }
    }

    public function test_viewer_can_read_but_is_blocked_from_write_routes(): void
    {
        $viewer = User::factory()->viewer()->create();
        Sanctum::actingAs($viewer);

        $this->getJson('/api/admin/customers')->assertOk();
        $this->getJson('/api/admin/notifications')->assertOk();

        $this->postJson('/api/admin/notifications/read')->assertForbidden();
        $this->postJson('/api/admin/actions/redirect-customer', [])->assertForbidden();
    }

    public function test_viewer_is_blocked_from_user_management_entirely(): void
    {
        $viewer = User::factory()->viewer()->create();
        Sanctum::actingAs($viewer);

        $this->getJson('/api/admin/users')->assertForbidden();
        $this->postJson('/api/admin/users', [])->assertForbidden();
    }

    public function test_admin_has_full_write_access_except_create_and_delete_users(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->viewer()->create();
        Sanctum::actingAs($admin);

        // Full read + write access shared with super_admin.
        $this->getJson('/api/admin/users')->assertOk();

        $this->putJson("/api/admin/users/{$other->id}/role", ['role' => 'admin'])
            ->assertOk()
            ->assertJson(['success' => true]);
        $this->assertSame('admin', $other->fresh()->role);

        $this->putJson("/api/admin/users/{$other->id}/password", [
            'password' => 'NewPassw0rd!123',
            'password_confirmation' => 'NewPassw0rd!123',
        ])->assertOk()->assertJson(['success' => true]);

        // Exclusive super_admin actions are rejected for plain admin.
        $this->postJson('/api/admin/users', [
            'name' => 'Should Fail',
            'email' => 'should-fail@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertForbidden();

        $this->deleteJson("/api/admin/users/{$other->id}")->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $other->id]);
    }

    public function test_super_admin_can_create_and_delete_users(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        Sanctum::actingAs($superAdmin);

        $createResponse = $this->postJson('/api/admin/users', [
            'name' => 'New Viewer',
            'email' => 'new-viewer@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $createResponse->assertStatus(201)->assertJson(['success' => true]);
        // Least-privilege default when role is omitted.
        $this->assertDatabaseHas('users', ['email' => 'new-viewer@example.com', 'role' => 'viewer']);

        $target = User::factory()->admin()->create();
        $this->deleteJson("/api/admin/users/{$target->id}")
            ->assertOk()
            ->assertJson(['success' => true]);
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_store_accepts_an_explicit_role(): void
    {
        Sanctum::actingAs(User::factory()->superAdmin()->create());

        $this->postJson('/api/admin/users', [
            'name' => 'New Admin',
            'email' => 'new-admin@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'admin',
        ])->assertStatus(201);

        $this->assertDatabaseHas('users', ['email' => 'new-admin@example.com', 'role' => 'admin']);
    }

    public function test_self_role_change_is_blocked(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->putJson("/api/admin/users/{$admin->id}/role", ['role' => 'viewer'])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_self_delete_is_blocked_even_for_super_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        Sanctum::actingAs($superAdmin);

        $this->deleteJson("/api/admin/users/{$superAdmin->id}")
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
    }
}
