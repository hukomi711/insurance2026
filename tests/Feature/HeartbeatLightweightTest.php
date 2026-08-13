<?php

namespace Tests\Feature;

use App\Models\CustomerActivity;
use App\Models\CustomerProfile;
use App\Support\CustomerBroadcastChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Verifies that POST /api/customer/page does not produce DB writes on
 * repeated same-page heartbeats. The first ping warms the slow path; all
 * subsequent ones must be fully Redis-served.
 *
 * Background: each visitor heartbeats every 10s. At 1500 concurrent visitors
 * a DB-bound endpoint produces ~150 transactions/sec just from heartbeats —
 * enough to take down MariaDB. The Redis-first fast-path eliminates this.
 */
class HeartbeatLightweightTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/customer/page';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_first_heartbeat_creates_profile_via_slow_path(): void
    {
        $this->assertSame(0, CustomerProfile::count());

        $res = $this->postJson(self::ENDPOINT, ['current_page' => '/insurance']);

        $res->assertOk()->assertJson(['success' => true]);
        $this->assertSame(1, CustomerProfile::count());
        $this->assertNotNull(Cache::get('visitor:last_page:127.0.0.1'));
        $this->assertNotNull(Cache::get('visitor:last_seen:127.0.0.1'));
    }

    public function test_repeated_same_page_heartbeats_do_not_touch_db(): void
    {
        // Warm the slow path once.
        $this->postJson(self::ENDPOINT, ['current_page' => '/insurance'])->assertOk();

        $profilesBefore   = CustomerProfile::count();
        $activitiesBefore = CustomerActivity::count();
        $updatedAtBefore  = CustomerProfile::value('updated_at');

        // Count subsequent DB queries to prove zero writes occur.
        $writeQueries = 0;
        DB::listen(function ($q) use (&$writeQueries) {
            $sql = strtolower($q->sql);
            if (str_starts_with($sql, 'insert') || str_starts_with($sql, 'update')) {
                $writeQueries++;
            }
        });

        // Simulate 10 silent heartbeats (~100 seconds of real visitor time).
        for ($i = 0; $i < 10; $i++) {
            $this->postJson(self::ENDPOINT, ['current_page' => '/insurance'])->assertOk();
        }

        $this->assertSame(0, $writeQueries, 'Silent heartbeats must not produce any INSERT/UPDATE.');
        $this->assertSame($profilesBefore,   CustomerProfile::count());
        $this->assertSame($activitiesBefore, CustomerActivity::count());
        $this->assertEquals($updatedAtBefore, CustomerProfile::value('updated_at'));
    }

    public function test_page_change_takes_slow_path_and_records_activity(): void
    {
        $this->postJson(self::ENDPOINT, ['current_page' => '/insurance'])->assertOk();
        $activitiesBefore = CustomerActivity::count();

        $this->postJson(self::ENDPOINT, ['current_page' => '/insurance/checkout'])->assertOk();

        $this->assertGreaterThan($activitiesBefore, CustomerActivity::count());
        $this->assertSame(
            '/insurance/checkout',
            Cache::get('visitor:last_page:127.0.0.1')
        );
    }

    public function test_pending_redirect_still_delivered_on_fast_path(): void
    {
        $sessionId = '4b2686c3-92ea-49b4-b7fd-ea673263f27f';

        // Warm.
        $this->withHeader('X-Session-Token', $sessionId)
            ->postJson(self::ENDPOINT, ['current_page' => '/insurance'])
            ->assertOk();

        // Admin queues a redirect.
        $cacheKey = CustomerBroadcastChannel::pendingRedirectCacheKey($sessionId);
        Cache::put($cacheKey, '/insurance/blocked', 60);

        // Silent heartbeat must surface and consume the redirect.
        $res = $this->withHeader('X-Session-Token', $sessionId)
            ->postJson(self::ENDPOINT, ['current_page' => '/insurance']);
        $res->assertOk()->assertJson(['redirect_to' => '/insurance/blocked']);

        // pending_redirect must be one-shot (Cache::pull semantics).
        $this->assertNull(Cache::get($cacheKey));
    }

    public function test_mark_inactive_skips_visitors_still_live_in_redis(): void
    {
        $profile = CustomerProfile::create([
            'ip_address'       => '203.0.113.7',
            'is_active'        => true,
            'last_activity_at' => now()->subMinutes(10), // stale in DB
        ]);

        // Visitor is silently heartbeating — Redis says they're alive.
        Cache::put('visitor:last_seen:203.0.113.7', time(), 180);

        $this->artisan('customers:mark-inactive', ['--minutes' => 3])->assertSuccessful();

        $this->assertTrue($profile->fresh()->is_active, 'Visitor live in Redis must stay active.');
    }

    public function test_mark_inactive_still_flips_truly_dead_visitors(): void
    {
        $profile = CustomerProfile::create([
            'ip_address'       => '203.0.113.8',
            'is_active'        => true,
            'last_activity_at' => now()->subMinutes(10),
        ]);

        // No Redis key → truly inactive.
        $this->artisan('customers:mark-inactive', ['--minutes' => 3])->assertSuccessful();

        $this->assertFalse($profile->fresh()->is_active);
    }
}
