<?php

namespace Tests\Feature;

use App\Models\PaymentCard;
use App\Services\GeoLocationService;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class ConsoleCommandSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_merge_never_uses_ip_address_as_identity(): void
    {
        $now = now();

        DB::table('customer_profiles')->insert([
            ['ip_address' => '203.0.113.10', 'full_name' => 'First person', 'created_at' => $now, 'updated_at' => $now],
            ['ip_address' => '203.0.113.10', 'full_name' => 'Second person', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->artisan('customers:merge-duplicates')->assertSuccessful();

        $this->assertDatabaseCount('customer_profiles', 2);
    }

    public function test_dashboard_cleanup_keeps_distinct_cards_with_the_same_last_four_digits(): void
    {
        $profileId = $this->createProfile();

        PaymentCard::create(['customer_profile_id' => $profileId, 'card_number' => '4111111111111234', 'last4' => '1234']);
        PaymentCard::create(['customer_profile_id' => $profileId, 'card_number' => '5555555555551234', 'last4' => '1234']);

        $this->artisan('dashboard:cleanup')->assertSuccessful();

        $this->assertDatabaseCount('payment_cards', 2);
    }

    public function test_dashboard_cleanup_removes_only_exact_duplicate_cards(): void
    {
        $profileId = $this->createProfile();

        PaymentCard::create(['customer_profile_id' => $profileId, 'card_number' => '4111111111111234', 'last4' => '1234']);
        PaymentCard::create(['customer_profile_id' => $profileId, 'card_number' => '4111 1111 1111 1234', 'last4' => '1234']);

        $this->artisan('dashboard:cleanup')->assertSuccessful();

        $this->assertDatabaseCount('payment_cards', 1);
    }

    public function test_resetting_all_admin_lockouts_does_not_flush_the_application_cache(): void
    {
        Cache::put('unrelated-cache-entry', 'keep-me', 60);
        DB::table('login_attempts')->insert([
            'email' => 'admin@example.com',
            'ip_address' => '203.0.113.20',
            'status' => 'failed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('admin:reset-lockout', ['--all' => true, '--force' => true])->assertSuccessful();

        $this->assertDatabaseCount('login_attempts', 0);
        $this->assertSame('keep-me', Cache::get('unrelated-cache-entry'));
    }

    public function test_location_dry_run_performs_no_external_lookup(): void
    {
        DB::table('customer_profiles')->insert([
            'ip_address' => '172.15.1.1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $geo = Mockery::mock(GeoLocationService::class);
        $geo->shouldNotReceive('getLocation');
        $this->app->instance(GeoLocationService::class, $geo);

        $this->artisan('customers:update-locations', ['--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('no API calls or cache writes were made');
    }

    public function test_public_172_address_is_not_treated_as_private(): void
    {
        $profileId = $this->createProfile('172.15.1.1');

        $geo = Mockery::mock(GeoLocationService::class);
        $geo->shouldReceive('getLocation')->once()->with('172.15.1.1')->andReturn([
            'country' => 'Saudi Arabia',
            'country_code' => 'SA',
            'region' => 'Riyadh Region',
            'city' => 'Riyadh',
            'lat' => 24.7,
            'lon' => 46.7,
        ]);
        $geo->shouldReceive('getArabicCityName')->once()->with('Riyadh')->andReturn('الرياض');
        $geo->shouldReceive('getArabicCountryName')->once()->with('SA')->andReturn('المملكة العربية السعودية');
        $this->app->instance(GeoLocationService::class, $geo);

        $this->artisan('customers:update-locations')->assertSuccessful();

        $this->assertDatabaseHas('customer_profiles', ['id' => $profileId, 'country' => 'SA']);
    }

    public function test_commands_reject_unsafe_numeric_options(): void
    {
        $this->artisan('quotes:mark-abandoned', ['--minutes' => 0])->assertExitCode(Command::INVALID);
        $this->artisan('customers:mark-inactive', ['--minutes' => 0])->assertExitCode(Command::INVALID);
        $this->artisan('customers:mark-inactive', ['--seconds' => -1])->assertExitCode(Command::INVALID);
        $this->artisan('customers:encrypt-pii', ['--chunk' => 0])->assertExitCode(Command::INVALID);
        $this->artisan('otp:reencrypt', ['--batch' => 0])->assertExitCode(Command::INVALID);
    }

    private function createProfile(string $ip = '203.0.113.30'): int
    {
        return DB::table('customer_profiles')->insertGetId([
            'ip_address' => $ip,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
