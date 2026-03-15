<?php

namespace App\Http\Middleware\Traits;

use Illuminate\Http\Request;

/**
 * Shared helper for extracting the real client IP address.
 *
 * Delegates to $request->ip() which respects TrustProxies config
 * (bootstrap/app.php). Only headers from trusted proxy CIDRs are honoured.
 *
 * ⚠️  NEVER read X-Forwarded-For / CF-Connecting-IP manually —
 *     that bypasses the proxy trust check and enables IP spoofing.
 */
trait ResolvesRealIP
{
    protected function getRealIP(Request $request): string
    {
        return $request->ip() ?? '127.0.0.1';
    }
}
