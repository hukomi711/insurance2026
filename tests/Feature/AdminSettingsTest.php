<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_notification_update_preserves_other_settings(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'settings' => [
                'notifications' => [
                    'weeklyReport' => ['enabled' => true],
                    'systemAlerts' => ['enabled' => true],
                ],
            ],
        ]);
        Sanctum::actingAs($admin);

        $this->putJson('/api/admin/settings', [
            'notifications' => [
                'systemAlerts' => ['enabled' => false],
            ],
        ])->assertOk()
            ->assertJsonPath('settings.notifications.weeklyReport.enabled', true)
            ->assertJsonPath('settings.notifications.systemAlerts.enabled', false);
    }

    public function test_notification_settings_reject_unknown_payload_keys(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->putJson('/api/admin/settings', [
            'notifications' => [
                'unexpected' => ['enabled' => true, 'payload' => str_repeat('x', 1000)],
            ],
        ])->assertUnprocessable();
    }
}
