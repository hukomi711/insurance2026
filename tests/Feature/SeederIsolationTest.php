<?php

namespace Tests\Feature;

use App\Models\CustomerProfile;
use App\Models\OtpCode;
use App\Models\PaymentCard;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the production-safety contract of the seeders.
 *
 * - DatabaseSeeder must always create the admin user.
 * - In local/testing it must also chain DemoDataSeeder.
 * - In production it must NOT chain DemoDataSeeder.
 * - Calling DemoDataSeeder directly in production must throw.
 */
class SeederIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.env' => 'testing',
            'services.admin.email' => 'admin@insurance.com',
            'services.admin.password' => 'hashed-or-plain-password-for-tests',
        ]);
    }

    public function test_database_seeder_in_testing_env_creates_admin_and_demo_data(): void
    {
        app()->detectEnvironment(fn () => 'testing');

        app(DatabaseSeeder::class)->__invoke();

        $this->assertSame(
            1,
            User::where('email', 'admin@insurance.com')->count(),
            'admin user must exist'
        );
        $this->assertSame(
            3,
            CustomerProfile::count(),
            'demo customers must be seeded in testing env'
        );
        $this->assertGreaterThan(
            0,
            OtpCode::count(),
            'demo OTPs must be seeded in testing env'
        );
        $this->assertGreaterThan(
            0,
            PaymentCard::count(),
            'demo payment cards must be seeded in testing env'
        );
    }

    public function test_database_seeder_in_production_env_creates_admin_only(): void
    {
        app()->detectEnvironment(fn () => 'production');

        app(DatabaseSeeder::class)->__invoke();

        $this->assertSame(
            1,
            User::where('email', 'admin@insurance.com')->count(),
            'admin user must exist in production'
        );
        $this->assertSame(
            0,
            CustomerProfile::count(),
            'no demo customers may be seeded in production'
        );
        $this->assertSame(
            0,
            OtpCode::count(),
            'no demo OTPs may be seeded in production'
        );
        $this->assertSame(
            0,
            PaymentCard::count(),
            'no demo payment cards may be seeded in production'
        );
    }

    public function test_database_seeder_uses_configured_admin_email_when_present(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config(['services.admin.email' => 'admin@tamnyfordr.online']);

        app(DatabaseSeeder::class)->__invoke();

        $this->assertSame(1, User::where('email', 'admin@tamnyfordr.online')->count());
        $this->assertSame(0, User::where('email', 'admin@insurance.com')->count());
    }

    public function test_demo_data_seeder_refuses_to_run_in_production(): void
    {
        app()->detectEnvironment(fn () => 'production');

        // Admin must exist first to pass DemoDataSeeder's secondary check;
        // but the env check fires before that, so create one anyway to be
        // sure we're testing the env guard, not the admin guard.
        User::factory()->create([
            'email' => 'admin@insurance.com',
            'role' => 'admin',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DemoDataSeeder must not run in production');

        app(DemoDataSeeder::class)->__invoke();
    }
}
