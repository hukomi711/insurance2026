<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\LoginAttempt;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Maximum consecutive failed login attempts before lockout */
    private const MAX_ATTEMPTS = 5;

    /** Lockout duration in minutes */
    private const LOCKOUT_MINUTES = 15;

    /**
     * Login — returns Sanctum token
     * POST /api/admin/login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        // ── Brute-force lockout check ────────────────────────────
        $recentFailures = LoginAttempt::where('ip_address', $request->ip())
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subMinutes(self::LOCKOUT_MINUTES))
            ->count();

        if ($recentFailures >= self::MAX_ATTEMPTS) {
            $oldestFailure = LoginAttempt::where('ip_address', $request->ip())
                ->where('status', 'failed')
                ->where('created_at', '>=', now()->subMinutes(self::LOCKOUT_MINUTES))
                ->oldest()
                ->first();

            $unlockAt = $oldestFailure->created_at->addMinutes(self::LOCKOUT_MINUTES);
            $remainingSeconds = (int) now()->diffInSeconds($unlockAt, false);
            $remainingMinutes = (int) ceil($remainingSeconds / 60);

            return response()->json([
                'success' => false,
                'message' => "تم تجاوز الحد الأقصى لمحاولات الدخول. حاول مرة أخرى بعد {$remainingMinutes} دقيقة.",
            ], 429);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            LoginAttempt::record($request->email, $request->ip(), $request->userAgent(), 'failed', $user?->id);

            $remaining = self::MAX_ATTEMPTS - ($recentFailures + 1);
            $msg = $remaining > 0
                ? "بيانات الدخول غير صحيحة. المحاولات المتبقية: {$remaining}"
                : 'تم تجاوز الحد الأقصى لمحاولات الدخول. حاول مرة أخرى لاحقاً.';

            throw ValidationException::withMessages([
                'email' => [$msg],
            ]);
        }

        // ── Ensure user has admin role ───────────────────────────
        if (($user->role ?? null) !== 'admin') {
            LoginAttempt::record($request->email, $request->ip(), $request->userAgent(), 'failed', $user->id);
            throw ValidationException::withMessages([
                'email' => ['ليس لديك صلاحية للدخول إلى لوحة التحكم.'],
            ]);
        }

        // Revoke previous tokens (single-session approach)
        $user->tokens()->delete();

        $token = $user->createToken('admin-dashboard')->plainTextToken;

        // وسم الجلسة كمسؤول حتى يتجاوز CountryRestriction على مسارات الويب
        session(['admin_authenticated' => true, 'admin_user_id' => $user->id]);

        LoginAttempt::record($request->email, $request->ip(), $request->userAgent(), 'success', $user->id);

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'admin',
            ],
        ]);
    }

    /**
     * Logout — revoke current token
     * POST /api/admin/logout
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var \Laravel\Sanctum\PersonalAccessToken $token */
        $token = $request->user()->currentAccessToken();
        $token->delete();

        // إزالة وسم الأدمن من الجلسة
        session()->forget(['admin_authenticated', 'admin_user_id']);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }

    /**
     * Get current authenticated user
     * GET /api/admin/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'admin',
            ],
        ]);
    }
}
