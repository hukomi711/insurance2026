<?php

namespace App\Console\Commands;

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Emergency recovery command for admin OTP lockouts.
 *
 * Usage:
 *   php artisan admin:reset-lockout                    (clear all recent failed attempts)
 *   php artisan admin:reset-lockout admin@example.com  (clear for a specific user)
 *   php artisan admin:reset-lockout --ip=1.2.3.4       (clear for a specific IP)
 *   php artisan admin:reset-lockout --all              (purge entire failed-attempts history)
 */
class AdminResetLockout extends Command
{
    /** @var string */
    protected $signature = 'admin:reset-lockout
                            {email? : Optional admin email to reset}
                            {--ip= : Optional IP address to reset}
                            {--all : Purge ALL failed login attempts (use with caution)}
                            {--force : Skip confirmation when using --all}';

    /** @var string */
    protected $description = 'Clear failed LoginAttempt rows that enforce the admin login lockout';

    public function handle(): int
    {
        $email = $this->argument('email');
        $ip = $this->option('ip');
        $all = (bool) $this->option('all');

        if ($all && ! $this->option('force')
            && ! $this->confirm('Purge every failed admin login attempt?', false)) {
            $this->warn('Reset cancelled.');

            return self::INVALID;
        }

        $query = LoginAttempt::query()->where('status', 'failed');

        if ($all) {
            $this->warn('Purging ALL failed login attempts.');
        } elseif ($email) {
            $user = User::where('email', $email)->first();
            if (! $user) {
                $this->error("No user found for email: {$email}");

                return self::FAILURE;
            }
            $query->where(function ($q) use ($user, $email) {
                $q->where('user_id', $user->id)->orWhere('email', $email);
            });
            $this->info("Scoped to user: {$email}");
        } elseif ($ip) {
            $query->where('ip_address', $ip);
            $this->info("Scoped to IP: {$ip}");
        } else {
            // Default: only the recent lockout window
            $query->where('created_at', '>=', now()->subMinutes(30));
            $this->info('Scoped to last 30 minutes of failures.');
        }

        $count = (clone $query)->count();
        $deleted = $query->delete();

        $this->info("Deleted {$deleted} failed login attempt(s) (matched {$count}).");

        return self::SUCCESS;
    }
}
