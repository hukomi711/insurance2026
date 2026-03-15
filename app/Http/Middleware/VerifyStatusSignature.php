<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verify HMAC signature on public status-polling endpoints.
 *
 * Each endpoint signs: "{purpose}|{sessionId}" with STATUS_POLL_SECRET.
 * Purpose is derived from the route segment (otp, pin, payment-card, phone).
 * This prevents enumeration + cross-endpoint replay attacks.
 */
class VerifyStatusSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $sig = (string) $request->query('sig', '');
        $secret = config('services.status_poll.secret');

        if (! $secret || ! $sig) {
            return response()->json([
                'success' => false,
                'message' => 'Missing signature.',
            ], 403);
        }

        // Derive purpose from route: /api/status/{purpose}/{identifier}
        $segments = $request->segments();
        // segments: ['api', 'status', 'otp', '{sessionId}']
        //   or      ['api', 'status', 'payment-card', '{sessionId}']
        $purpose = $segments[2] ?? '';
        $identifier = $segments[3] ?? '';

        if (! $purpose || ! $identifier) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request.',
            ], 403);
        }

        $expected = hash_hmac('sha256', "{$purpose}|{$identifier}", $secret);

        if (! hash_equals($expected, $sig)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid signature.',
            ], 403);
        }

        return $next($request);
    }
}
