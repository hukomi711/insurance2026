<?php

namespace App\Http\Middleware;

use App\Jobs\TrackCustomerActivityJob;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackCustomerActivity
{
    protected array $excludedPaths = [
        'dashboard',
        'admin',
        'profile',
        'login',
        'register',
        'password',
        'logout',
        'api',
        'broadcasting',
        'sanctum',
        '_ignition',
        'livewire',
        'robots.txt',
        'sitemap.xml',
        '.well-known',
        'favicon.ico',
        'build',
        'assets',
        'images',
        'img',
        'fonts',
        'Fonts',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Do not enqueue tracking for blocked, failed, or redirected requests.
        // In particular, a bot blocked by route middleware must not create a
        // customer activity job after its 403 response has already been made.
        if (! $response->isSuccessful()) {
            return $response;
        }

        if (Auth::check()) {
            return $response;
        }

        if ($request->ajax() || $request->is('api/*')) {
            return $response;
        }

        foreach ($this->excludedPaths as $path) {
            if ($request->is($path) || $request->is($path . '/*')) {
                return $response;
            }
        }

        if (! $request->isMethod('GET')) {
            return $response;
        }

        $sessionId = $request->header('X-Session-Token')
            ?? $request->cookie('customer_session_token')
            ?? $request->session()->getId();
        TrackCustomerActivityJob::dispatch($request->ip() ?? '0.0.0.0', $request->path(), $sessionId);

        return $response;
    }
}
