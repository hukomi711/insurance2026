<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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
        // ── Eloquent strict mode (dev/testing only) ──────────────────
        // Catches lazy loading (N+1), silently discarded attributes,
        // and missing attributes before they hit production.
        Model::shouldBeStrict(! $this->app->environment('production'));

        // Force HTTPS in production to protect sensitive card/personal data
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Share cached Vite font URLs with all views (zero disk I/O per request)
        View::share('viteFonts', app(\App\Services\ViteFontService::class)->getFontUrls());

        // ── OTP Rate Limiters ───────────────────────────────────
        // Global API rate limiter — generous enough for SPA navigation + tracking
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(300)->by($request->ip());
        });

        // 3 attempts per minute per IP+session+type for OTP submissions
        RateLimiter::for('otp-submit', function (Request $request) {
            $key = $request->ip() . '|' . $request->input('session_id', '_') . '|' . $request->input('type', 'otp');
            return Limit::perMinute(3)->by($key);
        });

        // 2 attempts per minute per IP+session for OTP resend
        RateLimiter::for('otp-resend', function (Request $request) {
            $key = $request->ip() . '|' . $request->input('session_id', '_');
            return Limit::perMinute(2)->by($key);
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
    }
}
