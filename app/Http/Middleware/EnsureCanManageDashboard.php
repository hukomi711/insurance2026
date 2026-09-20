<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for write/mutating admin actions.
 * Requires full dashboard access (admin or super_admin) — blocks viewer.
 */
class EnsureCanManageDashboard
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لتنفيذ هذا الإجراء',
            ], 403);
        }

        return $next($request);
    }
}
