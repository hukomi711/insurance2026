<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PruneOldRecordsTest extends TestCase
{
    use RefreshDatabase;

    // ── Tier 1 ──────────────────────────────────────────────

    public function test_tier1_deletes_old_customer_activities(): void
    {
        DB::table('customer_activities')->insert([
            ['customer_profile_id' => null, 'customer_name' => 'Old', 'stage' => 'quote', 'activity_type' => 'page_view', 'description' => 'test', 'status' => 'completed', 'created_at' => now()->subDays(31), 'updated_at' => now()->subDays(31)],
            ['customer_profile_id' => null, 'customer_name' => 'New', 'stage' => 'quote', 'activity_type' => 'page_view', 'description' => 'test', 'status' => 'active', 'created_at' => now()->subDays(5), 'updated_at' => now()->subDays(5)],
        ]);

        $this->artisan('app:prune-old-records', ['--tier' => '1', '--only' => 'customer_activities'])
            ->assertSuccessful();

        $this->assertDatabaseCount('customer_activities', 1);
    }

    public function test_tier1_deletes_old_login_attempts(): void
    {
        DB::table('login_attempts')->insert([
            ['ip_address' => '1.1.1.1', 'email' => 'old@test.com', 'status' => 'success', 'created_at' => now()->subDays(91), 'updated_at' => now()->subDays(91)],
            ['ip_address' => '1.1.1.2', 'email' => 'new@test.com', 'status' => 'success', 'created_at' => now()->subDays(10), 'updated_at' => now()->subDays(10)],
        ]);

        $this->artisan('app:prune-old-records', ['--tier' => '1', '--only' => 'login_attempts'])
            ->assertSuccessful();

        $this->assertDatabaseCount('login_attempts', 1);
    }

    public function test_tier1_deletes_old_quote_heartbeats(): void
    {
        $sessionId = DB::table('quote_sessions')->insertGetId([
            'uuid' => 'test-uuid-1', 'customer_ip' => '1.1.1.1', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('quote_heartbeats')->insert([
            ['quote_session_id' => $sessionId, 'current_step' => 'step1', 'customer_ip' => '1.1.1.1', 'tab_visible' => true, 'pinged_at' => now()->subDays(4)],
            ['quote_session_id' => $sessionId, 'current_step' => 'step1', 'customer_ip' => '1.1.1.1', 'tab_visible' => true, 'pinged_at' => now()->subDay()],
        ]);

        $this->artisan('app:prune-old-records', ['--tier' => '1', '--only' => 'quote_heartbeats'])
            ->assertSuccessful();

        $this->assertDatabaseCount('quote_heartbeats', 1);
    }

    public function test_tier1_deletes_old_user_activities(): void
    {
        DB::table('user_activities')->insert([
            ['user_id' => null, 'ip_address' => '1.1.1.1', 'page' => '/admin', 'action' => 'page_view', 'created_at' => now()->subDays(61), 'updated_at' => now()->subDays(61)],
            ['user_id' => null, 'ip_address' => '1.1.1.2', 'page' => '/admin', 'action' => 'page_view', 'created_at' => now()->subDays(10), 'updated_at' => now()->subDays(10)],
        ]);

        $this->artisan('app:prune-old-records', ['--tier' => '1', '--only' => 'user_activities'])
            ->assertSuccessful();

        $this->assertDatabaseCount('user_activities', 1);
    }

    // ── Tier 2 ──────────────────────────────────────────────

    public function test_tier2_deletes_abandoned_quote_sessions(): void
    {
        DB::table('quote_sessions')->insert([
            ['uuid' => 'abandoned-old', 'customer_ip' => '1.1.1.1', 'status' => 'abandoned', 'created_at' => now()->subDays(91), 'updated_at' => now()->subDays(91)],
            ['uuid' => 'abandoned-new', 'customer_ip' => '1.1.1.2', 'status' => 'abandoned', 'created_at' => now()->subDays(30), 'updated_at' => now()->subDays(30)],
            ['uuid' => 'completed-old', 'customer_ip' => '1.1.1.3', 'status' => 'completed', 'created_at' => now()->subDays(91), 'updated_at' => now()->subDays(91)],
        ]);

        $this->artisan('app:prune-old-records', ['--tier' => '2', '--only' => 'quote_sessions'])
            ->assertSuccessful();

        $this->assertDatabaseCount('quote_sessions', 2);
        $this->assertDatabaseHas('quote_sessions', ['uuid' => 'abandoned-new']);
        $this->assertDatabaseHas('quote_sessions', ['uuid' => 'completed-old']);
    }

    public function test_tier2_cascade_deletes_step_logs_and_heartbeats(): void
    {
        $sessionId = DB::table('quote_sessions')->insertGetId([
            'uuid' => 'cascade-test', 'customer_ip' => '1.1.1.1', 'status' => 'abandoned',
            'created_at' => now()->subDays(91), 'updated_at' => now()->subDays(91),
        ]);

        DB::table('quote_step_logs')->insert([
            'quote_session_id' => $sessionId, 'step_name' => 'step1', 'step_number' => 1,
            'entered_at' => now()->subDays(91), 'duration_seconds' => 10, 'interaction_count' => 1,
            'created_at' => now()->subDays(91), 'updated_at' => now()->subDays(91),
        ]);

        DB::table('quote_heartbeats')->insert([
            'quote_session_id' => $sessionId, 'current_step' => 'step1',
            'customer_ip' => '1.1.1.1', 'tab_visible' => true, 'pinged_at' => now()->subDays(91),
        ]);

        $this->artisan('app:prune-old-records', ['--tier' => '2', '--only' => 'quote_sessions'])
            ->assertSuccessful();

        $this->assertDatabaseCount('quote_sessions', 0);
        $this->assertDatabaseCount('quote_step_logs', 0);
        $this->assertDatabaseCount('quote_heartbeats', 0);
    }

    public function test_tier2_deletes_closed_livechat_conversations(): void
    {
        DB::table('livechat_conversations')->insert([
            ['session_id' => 'closed-old', 'visitor_ip' => '1.1.1.1', 'status' => 'closed', 'created_at' => now()->subDays(181), 'updated_at' => now()->subDays(181)],
            ['session_id' => 'active-old', 'visitor_ip' => '1.1.1.2', 'status' => 'active', 'created_at' => now()->subDays(181), 'updated_at' => now()->subDays(181)],
        ]);

        $this->artisan('app:prune-old-records', ['--tier' => '2', '--only' => 'livechat_conversations'])
            ->assertSuccessful();

        $this->assertDatabaseCount('livechat_conversations', 1);
        $this->assertDatabaseHas('livechat_conversations', ['session_id' => 'active-old']);
    }

    // ── Tier 3 ──────────────────────────────────────────────

    public function test_tier3_scrubs_otp_codes_but_keeps_rows(): void
    {
        $profileId = $this->createCustomerProfile();

        DB::table('otp_codes')->insert([
            ['customer_profile_id' => $profileId, 'type' => 'otp', 'code' => '1234', 'code_value' => 'val', 'status' => 'verified', 'created_at' => now()->subDays(8), 'updated_at' => now()->subDays(8)],
            ['customer_profile_id' => $profileId, 'type' => 'otp', 'code' => '5678', 'code_value' => 'val', 'status' => 'pending', 'created_at' => now()->subMinutes(30), 'updated_at' => now()->subMinutes(30)],
        ]);

        $this->artisan('app:prune-old-records', ['--tier' => '3', '--only' => 'otp_codes'])
            ->assertSuccessful();

        // Row count unchanged — scrub, not delete
        $this->assertDatabaseCount('otp_codes', 2);

        // Verified OTP scrubbed
        $scrubbed = DB::table('otp_codes')->where('status', 'verified')->first();
        $this->assertNull($scrubbed->code);
        $this->assertNull($scrubbed->code_value);

        // Fresh pending OTP NOT scrubbed (still active)
        $pending = DB::table('otp_codes')->where('code', '5678')->first();
        $this->assertEquals('pending', $pending->status);
    }

    public function test_tier3_scrubs_payment_cards_but_keeps_rows(): void
    {
        $profileId = $this->createCustomerProfile();

        DB::table('payment_cards')->insert([
            'customer_profile_id' => $profileId,
            'card_number' => 'encrypted-number',
            'card_number_masked' => '****1234',
            'last4' => '1234',
            'status' => 'approved',
            'created_at' => now()->subDays(31),
            'updated_at' => now()->subDays(31),
        ]);

        $this->artisan('app:prune-old-records', ['--tier' => '3', '--only' => 'payment_cards'])
            ->assertSuccessful();

        $this->assertDatabaseCount('payment_cards', 1);
        $card = DB::table('payment_cards')->first();
        $this->assertNull($card->card_number);
        $this->assertEquals('1234', $card->last4); // metadata kept
    }

    public function test_tier3_expires_stale_pending_otps(): void
    {
        $profileId = $this->createCustomerProfile();

        DB::table('otp_codes')->insert([
            ['customer_profile_id' => $profileId, 'type' => 'otp', 'code' => '1111', 'status' => 'pending', 'created_at' => now()->subHours(25), 'updated_at' => now()->subHours(25)],
            ['customer_profile_id' => $profileId, 'type' => 'otp', 'code' => '2222', 'status' => 'pending', 'created_at' => now()->subMinutes(30), 'updated_at' => now()->subMinutes(30)],
        ]);

        $this->artisan('app:prune-old-records', ['--tier' => '3', '--only' => 'otp_codes'])
            ->assertSuccessful();

        $stale = DB::table('otp_codes')->where('code', '1111')->first();
        $this->assertEquals('expired', $stale->status);

        $fresh = DB::table('otp_codes')->where('code', '2222')->first();
        $this->assertEquals('pending', $fresh->status);
    }

    // ── Tier 4 ──────────────────────────────────────────────

    public function test_tier4_anonymizes_old_inactive_profiles(): void
    {
        DB::table('customer_profiles')->insert([
            'ip_address' => '1.1.1.1',
            'full_name' => 'Ali Ahmed',
            'phone_number' => '0501234567',
            'national_id' => '1234567890',
            'email' => 'ali@test.com',
            'last_activity_at' => now()->subDays(200),
            'created_at' => now()->subDays(200),
            'updated_at' => now()->subDays(200),
        ]);

        $this->artisan('app:prune-old-records', ['--tier' => '1,2,3', '--include-anonymize' => true])
            ->assertSuccessful();

        $profile = DB::table('customer_profiles')->first();
        $this->assertNull($profile->full_name);
        $this->assertNull($profile->phone_number);
        $this->assertNull($profile->national_id);
        $this->assertNull($profile->email);
        $this->assertNotNull($profile->anonymized_at);
    }

    public function test_tier4_skips_profiles_with_linked_orders(): void
    {
        $profileId = DB::table('customer_profiles')->insertGetId([
            'ip_address' => '1.1.1.1',
            'full_name' => 'Protected User',
            'phone_number' => '0509999999',
            'last_activity_at' => now()->subDays(200),
            'created_at' => now()->subDays(200),
            'updated_at' => now()->subDays(200),
        ]);

        DB::table('orders')->insert([
            'customer_profile_id' => $profileId,
            'order_number' => 'ORD-001',
            'subtotal' => 1000,
            'vat_amount' => 150,
            'total' => 1150,
            'deductible' => 500,
            'payment_status' => 'paid',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('app:prune-old-records', ['--tier' => '1,2,3', '--include-anonymize' => true])
            ->assertSuccessful();

        $profile = DB::table('customer_profiles')->first();
        $this->assertEquals('Protected User', $profile->full_name);
        $this->assertNull($profile->anonymized_at);
    }

    public function test_tier4_skips_already_anonymized_profiles(): void
    {
        DB::table('customer_profiles')->insert([
            'ip_address' => '1.1.1.1',
            'full_name' => null,
            'anonymized_at' => now()->subDays(30),
            'last_activity_at' => now()->subDays(200),
            'created_at' => now()->subDays(200),
            'updated_at' => now()->subDays(200),
        ]);

        $this->artisan('app:prune-old-records', ['--tier' => '1,2,3', '--include-anonymize' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('0 to anonymize');
    }

    // ── Dry Run ─────────────────────────────────────────────

    public function test_dry_run_changes_nothing(): void
    {
        DB::table('customer_activities')->insert([
            'customer_profile_id' => null, 'customer_name' => 'Test', 'stage' => 'quote',
            'activity_type' => 'page_view', 'description' => 'test', 'status' => 'completed',
            'created_at' => now()->subDays(60), 'updated_at' => now()->subDays(60),
        ]);

        $profileId = $this->createCustomerProfile();

        DB::table('otp_codes')->insert([
            'customer_profile_id' => $profileId, 'type' => 'otp',
            'code' => '1234', 'code_value' => 'val', 'status' => 'verified',
            'created_at' => now()->subDays(8), 'updated_at' => now()->subDays(8),
        ]);

        $this->artisan('app:prune-old-records', ['--dry-run' => true])
            ->assertSuccessful();

        // Nothing deleted
        $this->assertDatabaseCount('customer_activities', 1);
        // Nothing scrubbed
        $otp = DB::table('otp_codes')->first();
        $this->assertEquals('1234', $otp->code);
    }

    // ── --only filter ───────────────────────────────────────

    public function test_only_flag_restricts_to_single_table(): void
    {
        DB::table('customer_activities')->insert([
            'customer_profile_id' => null, 'customer_name' => 'Test', 'stage' => 'quote',
            'activity_type' => 'page_view', 'description' => 'test', 'status' => 'completed',
            'created_at' => now()->subDays(60), 'updated_at' => now()->subDays(60),
        ]);

        DB::table('login_attempts')->insert([
            'ip_address' => '1.1.1.1', 'email' => 'test@test.com', 'status' => 'success',
            'created_at' => now()->subDays(91), 'updated_at' => now()->subDays(91),
        ]);

        $this->artisan('app:prune-old-records', ['--tier' => '1', '--only' => 'customer_activities'])
            ->assertSuccessful();

        $this->assertDatabaseCount('customer_activities', 0);
        $this->assertDatabaseCount('login_attempts', 1); // untouched
    }

    // ── Tier 4 requires --include-anonymize ─────────────────

    public function test_tier4_skipped_without_include_anonymize_flag(): void
    {
        DB::table('customer_profiles')->insert([
            'ip_address' => '1.1.1.1',
            'full_name' => 'Should Stay',
            'last_activity_at' => now()->subDays(200),
            'created_at' => now()->subDays(200),
            'updated_at' => now()->subDays(200),
        ]);

        $this->artisan('app:prune-old-records', ['--tier' => '4'])
            ->assertSuccessful();

        $profile = DB::table('customer_profiles')->first();
        $this->assertEquals('Should Stay', $profile->full_name);
    }

    // ── Helpers ──────────────────────────────────────────────

    private static int $ipCounter = 0;

    private function createCustomerProfile(): int
    {
        return DB::table('customer_profiles')->insertGetId([
            'ip_address' => '10.0.0.'.(++self::$ipCounter),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
