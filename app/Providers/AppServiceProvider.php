<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fix for shared hosting MySQL < 5.7.7 — utf8mb4 key length limit
        Schema::defaultStringLength(191);

        // ── Auto-populate BIN/bank metadata when payment_cards rows are saved
        \App\Models\PaymentCard::observe(\App\Observers\PaymentCardObserver::class);

        // ── Eloquent strict mode (dev/testing only) ──────────────────
        // Catches lazy loading (N+1), silently discarded attributes,
        // and missing attributes before they hit production.
        Model::shouldBeStrict(! $this->app->environment('production'));

        // ── Fail-fast production guards ──────────────────────────────
        // Prevent the app from running with dangerous misconfigurations.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');

            Log::info('App boot', ['build' => config('app.build')]);

            $errors = [];

            if (config('app.debug') === true) {
                $errors[] = 'APP_DEBUG must be false in production.';
            }
            if (! is_string(config('app.build'))
                || trim((string) config('app.build')) === ''
                || config('app.build') === 'unknown') {
                $errors[] = 'APP_BUILD_SHA must identify the release commit in production.';
            }
            if (config('mail.default') === 'log') {
                $errors[] = 'MAIL_MAILER must not be "log" in production.';
            }
            if (in_array('log', (array) config('mail.mailers.failover.mailers', []), true)) {
                $errors[] = 'MAIL_FAILOVER_MAILERS must not contain "log" in production.';
            }
            $defaultMailer = config('mail.default');
            $activeMailers = $defaultMailer === 'failover'
                ? (array) config('mail.mailers.failover.mailers', [])
                : [$defaultMailer];
            $smtpScheme = config('mail.mailers.smtp.scheme');
            if (in_array('smtp', $activeMailers, true)
                && $smtpScheme !== 'smtps'
                && config('mail.mailers.smtp.require_tls') !== true) {
                $errors[] = 'SMTP must use MAIL_SCHEME=smtps or MAIL_REQUIRE_TLS=true in production.';
            }
            if (config('database.default') === 'sqlite') {
                $errors[] = 'DB_CONNECTION must not be "sqlite" in production.';
            }
            if (! in_array(config('queue.default'), ['redis', 'database'])) {
                $errors[] = 'QUEUE_CONNECTION must be "redis" or "database" in production (got "' . config('queue.default') . '").';
            }

            $dbDriver = config('database.default', 'mysql');
            foreach (['host', 'database', 'username'] as $key) {
                if (empty(config("database.connections.{$dbDriver}.{$key}"))) {
                    $label = strtoupper("DB_{$key}");
                    $errors[] = "Required env variable {$label} is missing.";
                }
            }

            if (! empty($errors)) {
                throw new \RuntimeException(
                    "Production configuration errors:\n• " . implode("\n• ", $errors)
                );
            }
        }

        // Share cached Vite font URLs with all views (zero disk I/O per request)
        View::share('viteFonts', app(\App\Services\ViteFontService::class)->getFontUrls());

        // ── OTP Rate Limiters ───────────────────────────────────
        // Global API rate limiter — generous enough for SPA navigation + tracking
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(300)->by($request->ip());
        });

        // Per-session limits protect a single flow; the independent IP bucket
        // prevents rotating session_id from creating unlimited fresh buckets.
        RateLimiter::for('otp-submit', function (Request $request) {
            $ip = $request->ip();
            $sessionKey = $ip . '|' . $request->input('session_id', '_') . '|' . $request->input('type', 'otp');

            return [
                Limit::perMinute(10)->by('otp-submit:ip:' . $ip),
                Limit::perMinute(3)->by('otp-submit:session:' . $sessionKey),
            ];
        });

        // Resend has a lower per-session limit plus an independent IP ceiling.
        RateLimiter::for('otp-resend', function (Request $request) {
            $ip = $request->ip();
            $sessionKey = $ip . '|' . $request->input('session_id', '_');

            return [
                Limit::perMinute(6)->by('otp-resend:ip:' . $ip),
                Limit::perMinute(2)->by('otp-resend:session:' . $sessionKey),
            ];
        });

        // 40 polls per minute per IP+sessionId for status polling
        RateLimiter::for('status-poll', function (Request $request) {
            $sessionId = $request->route('sessionId') ?? '_';
            $key = $request->ip() . '|' . $sessionId;
            return Limit::perMinute(40)->by($key);
        });

        // 200 requests per minute per IP for visitor tracking (page views, heartbeats, IP fetch)
        RateLimiter::for('customer-tracking', function (Request $request) {
            return Limit::perMinute(200)->by($request->ip());
        });

        // Admin OTP verify: key by pending token + IP so shared networks (iPad/Wi-Fi)
        // do not throttle each other aggressively while still rate-limiting brute-force.
        RateLimiter::for('admin-verify-code', function (Request $request) {
            $pending = (string) $request->input('pending_token', '_');
            $key = $request->ip() . '|' . $pending;
            return Limit::perMinute(12)->by($key);
        });
    }
}
