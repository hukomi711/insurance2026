<?php

namespace App\Console\Commands;

use App\Models\AdminLoginCode;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Operational fallback when SMTP is unreachable.
 *
 * Prints the latest active admin login code for the given email
 * (or ADMIN_EMAIL from config) so ops can complete 2FA manually.
 */
class AdminLatestLoginCode extends Command
{
    /** @var string */
    protected $signature = 'admin:latest-login-code
                            {email? : Optional admin email (defaults to ADMIN_EMAIL)}
                            {--json : Output as JSON}';

    /** @var string */
    protected $description = 'Show the latest active admin login verification code (SMTP fallback)';

    public function handle(): int
    {
        $email = trim((string) ($this->argument('email') ?: config('services.admin.email', '')));
        if ($email === '') {
            $this->error('Admin email is empty. Pass {email} or set ADMIN_EMAIL.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error("No user found for email: {$email}");

            return self::FAILURE;
        }

        $code = AdminLoginCode::query()
            ->where('user_id', $user->id)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $code) {
            $payload = [
                'success' => true,
                'active_code' => false,
                'email' => $email,
                'message' => 'No active unexpired login code.',
            ];

            if ($this->option('json')) {
                $this->line(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            } else {
                $this->warn('No active unexpired login code.');
            }

            return self::SUCCESS;
        }

        $secondsLeft = max((int) now()->diffInSeconds($code->expires_at, false), 0);

        $payload = [
            'success' => true,
            'active_code' => true,
            'email' => $email,
            'code' => $code->code,
            'expires_at' => $code->expires_at?->toIso8601String(),
            'seconds_left' => $secondsLeft,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info("Email       : {$email}");
        $this->info("Code        : {$code->code}");
        $this->info('Expires UTC : '.$code->expires_at?->utc()->format('Y-m-d H:i:s'));
        $this->info("Seconds left: {$secondsLeft}");

        return self::SUCCESS;
    }
}
