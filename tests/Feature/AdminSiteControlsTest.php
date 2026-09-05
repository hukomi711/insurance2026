<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminSiteControlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_and_read_operational_controls(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->putJson('/api/admin/site-settings', [
            'allowed_countries' => ['SA'],
            'blocked_ip_addresses' => ['203.0.113.10', '2001:db8::10'],
            'blocked_card_bins' => ['123456', '65432100'],
            'smart_rejection_enabled' => true,
            'bank_transfer_enabled' => true,
            'bank_transfer_beneficiary' => 'شركة التأمين',
            'bank_transfer_iban' => 'SA0000000000000000000000',
            'livechat_enabled' => false,
            'profanity_filter_enabled' => true,
            'profanity_words' => ['عبارة محظورة'],
        ])->assertOk()
            ->assertJsonPath('data.allowed_countries.0', 'SA')
            ->assertJsonPath('data.smart_rejection_enabled', true)
            ->assertJsonPath('data.livechat_enabled', false);

        $this->assertSame(['203.0.113.10', '2001:db8::10'], SiteSetting::value('blocked_ip_addresses'));
        $this->assertSame(['123456', '65432100'], SiteSetting::value('blocked_card_bins'));
    }

    public function test_operational_controls_reject_invalid_network_values(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->putJson('/api/admin/site-settings', [
            'blocked_ip_addresses' => ['not-an-ip'],
            'blocked_card_bins' => ['1234'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['blocked_ip_addresses.0', 'blocked_card_bins.0']);
    }

    public function test_disabled_livechat_rejects_new_messages_without_storing_them(): void
    {
        SiteSetting::set('livechat_enabled', '0', 'boolean', 'communication');

        $this->postJson('/api/livechat/send', [
            'session_id' => 'disabled-chat-session',
            'message' => 'رسالة اختبار',
        ])->assertStatus(503)
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('livechat_conversations', 0);
        $this->assertDatabaseCount('livechat_messages', 0);
    }

    public function test_allowed_country_policy_is_locked_to_saudi_arabia(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->putJson('/api/admin/site-settings', [
            'allowed_countries' => ['AE'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['allowed_countries.0']);
    }

    public function test_public_config_exposes_only_customer_safe_feature_flags(): void
    {
        SiteSetting::set('livechat_enabled', '0', 'boolean', 'communication');
        SiteSetting::set('bank_transfer_enabled', '1', 'boolean', 'payments');
        SiteSetting::set('bank_transfer_beneficiary', 'شركة التأمين', 'string', 'payments');
        SiteSetting::set('bank_transfer_iban', 'SA0000000000000000000000', 'string', 'payments');
        SiteSetting::set('blocked_ip_addresses', ['203.0.113.10'], 'json', 'access');

        $this->getJson('/api/site-config')->assertOk()
            ->assertJsonPath('features.livechat_enabled', false)
            ->assertJsonPath('features.bank_transfer_enabled', true)
            ->assertJsonPath('bank_transfer.beneficiary', 'شركة التأمين')
            ->assertJsonMissingPath('blocked_ip_addresses')
            ->assertJsonMissingPath('blocked_card_bins')
            ->assertJsonMissingPath('profanity_words');
    }
}
