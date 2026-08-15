<?php

namespace App\Http\Middleware;

use App\Models\CustomerBlock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerIsNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/admin/*', 'api/broadcasting/auth', 'api/health*', 'api/geo/check')) {
            return $next($request);
        }

        $sessionId = $request->header('X-Session-Token') ?: $request->input('session_id');
        $sessionId = is_string($sessionId) && strlen($sessionId) <= 128 ? $sessionId : null;

        if (CustomerBlock::matches($sessionId)) {
            return response()->json([
                'success' => false,
                'blocked' => true,
                'message' => 'تعذر استمرار الاتصال بالموقع. سيتم إغلاق الموقع تلقائيًا.',
            ], 423);
        }

        return $next($request);
    }
}
