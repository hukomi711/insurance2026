<?php

namespace App\Console\Commands;

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

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
                            {--all : Purge ALL failed login attempts (use with caution)}';

    /** @var string */
    protected $description = 'Clear admin login lockout: failed LoginAttempt rows + cached rate limiters';

    public function handle(): int
    {
        $email = $this->argument('email');
        $ip = $this->option('ip');
        $all = (bool) $this->option('all');

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

        // Best-effort: clear Laravel's rate-limiter cache buckets for admin login routes.
        // Keys are hashed by RateLimiter; we cannot target specific ones without the
        // exact resolver, but flushing the cache store is too aggressive. Instead we
        // rely on the LoginAttempt counter (now cleared) being the controller's gate.
        $this->line('Cache rate-limiter buckets will expire naturally within 60s.');

        // Optionally clear pending 2fa tokens if --all
        if ($all) {
            try {
                Cache::flush();
                $this->info('Cache flushed (--all).');
            } catch (\Throwable $e) {
                $this->warn('Cache flush failed: '.$e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
