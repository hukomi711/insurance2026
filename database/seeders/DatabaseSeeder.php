<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Production-safe: only the admin user is created here. Demo
     * customers / OTPs / payment cards are isolated in DemoDataSeeder
     * and only invoked in `local` or `testing` environments.
     */
    public function run(): void
    {
        $adminPassword = env('ADMIN_PASSWORD');
        if (empty($adminPassword)) {
            throw new \RuntimeException(
                'ADMIN_PASSWORD environment variable is required. '
                .'Set it in .env before running the seeder.'
            );
        }

        User::updateOrCreate(
            ['email' => 'admin@insurance.com'],
            [
                'name' => 'مدير النظام',
                'password' => Hash::make($adminPassword),
                'role' => 'admin',
            ]
        );

        $this->command?->info('✅ DatabaseSeeder: admin user ensured.');

        $this->call(SiteSettingsSeeder::class);

        if (app()->environment(['local', 'testing'])) {
            $this->call(DemoDataSeeder::class);
        } else {
            $this->command?->warn(
                'Skipping DemoDataSeeder — current environment is "'
                .app()->environment().'". Demo data is local/testing only.'
            );
        }
    }
}
