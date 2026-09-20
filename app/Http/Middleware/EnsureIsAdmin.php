<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure the authenticated user has a recognized dashboard role
 * (admin, super_admin, or viewer). Rejects everyone else with 403.
 * Write-restricted actions are additionally gated by 'admin.write'.
 */
class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->hasDashboardAccess()) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول إلى هذا المورد',
            ], 403);
        }

        return $next($request);
    }
}
