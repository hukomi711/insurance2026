<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Security headers handled by nginx for all responses (including static files).
        // Only set headers here that are specific to PHP responses or need dynamic values.

        // Global anti-indexing policy to reduce automated crawling on this domain.
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');

        return $response;
    }
}
