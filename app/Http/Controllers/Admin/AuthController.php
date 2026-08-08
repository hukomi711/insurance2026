<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminLoginVerification;
use App\Models\AdminLoginCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\LoginAttempt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthController extends Controller
{
    /** Maximum consecutive failed login attempts before lockout */
    private const MAX_ATTEMPTS = 5;

    /** Lockout duration in minutes */
    private const LOCKOUT_MINUTES = 15;

    /**
     * @return list<string>
     */
    private static function verificationEmails(): array
    {
        $configured = config('services.admin.verification_email');
        $emails = [];

        if (is_string($configured) && trim($configured) !== '') {
            $emails = preg_split('/\s*,\s*/', trim($configured), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        return array_values(array_unique(array_filter($emails, static fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)));
    }

    private static function createPendingToken(User $user): string
    {
        // TTL must match AdminLoginCode::generateFor expires_at (5 min) so the
        // token cannot outlive every code it could be used to verify.
        $pendingToken = bin2hex(random_bytes(32));
        self::putPendingToken($pendingToken, $user);

        return $pendingToken;
    }

    private static function putPendingToken(string $pendingToken, User $user): void
    {
        Cache::store(config('cache.default'))->put("2fa_pending:{$pendingToken}", $user->id, now()->addMinutes(5));
    }

    /**
     * Step 1 — Validate credentials, send 2FA code
     * POST /api/admin/login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
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

        // ── Generate 2FA code and send to the configured verification email ─────
        try {
            $loginCode = AdminLoginCode::generateFor($user, $request->ip());
            $verificationEmails = self::verificationEmails();
            if (empty($verificationEmails)) {
                $verificationEmails = [$user->email];
            }

            Mail::to($verificationEmails)->send(new AdminLoginVerification($loginCode));

            return response()->json([
                'success' => true,
                'requires_2fa' => true,
                'pending_token' => self::createPendingToken($user),
                'message' => 'تم إرسال رمز التأكيد إلى البريد الإلكتروني المعتمد.',
            ]);
        } catch (Throwable $exception) {
            Log::error('Admin login verification flow failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'error' => $exception->getMessage(),
            ]);

            try {
                $fallbackToken = self::createPendingToken($user);
            } catch (Throwable $fallbackException) {
                $fallbackToken = null;
                Log::error('Admin login fallback token creation failed', [
                    'user_id' => $user->id,
                    'error' => $fallbackException->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'requires_2fa' => true,
                'pending_token' => $fallbackToken,
                'message' => 'تم إنشاء طلب التحقق، لكن البريد غير متاح حالياً. يرجى متابعة العملية أو التواصل مع الدعم.',
            ]);
        }
    }

    /**
     * Step 2 — Verify 2FA code and issue token
     * POST /api/admin/verify-code
     */
    public function verifyCode(Request $request): JsonResponse
    {
        $request->validate([
            'pending_token' => 'required|string|size:64',
            'code' => 'required|string|size:6',
        ]);

        $userId = Cache::get("2fa_pending:{$request->pending_token}");
        if (! $userId) {
            return response()->json([
                'success' => false,
                'message' => 'انتهت صلاحية الجلسة. أعد تسجيل الدخول.',
            ], 422);
        }
        $user = User::findOrFail($userId);

        // ── Brute-force protection on code verification ──────────
        $recentFailures = LoginAttempt::where('user_id', $user->id)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subMinutes(self::LOCKOUT_MINUTES))
            ->count();

        if ($recentFailures >= self::MAX_ATTEMPTS) {
            return response()->json([
                'success' => false,
                'message' => 'تم تجاوز الحد الأقصى لمحاولات التحقق. حاول مرة أخرى لاحقاً.',
            ], 429);
        }

        $loginCode = AdminLoginCode::verify($user, $request->code);

        if (! $loginCode) {
            LoginAttempt::record($user->email, $request->ip(), $request->userAgent(), 'failed', $user->id);

            return response()->json([
                'success' => false,
                'message' => 'رمز التأكيد غير صحيح أو منتهي الصلاحية.',
            ], 422);
        }

        // Successful 2FA — clear stale failed attempts so a single later
        // mistake doesn't trigger lockout from old counters.
        LoginAttempt::where('user_id', $user->id)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subMinutes(self::LOCKOUT_MINUTES))
            ->delete();
        LoginAttempt::where('ip_address', $request->ip())
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subMinutes(self::LOCKOUT_MINUTES))
            ->delete();

        // Mark code as used
        $loginCode->update(['used' => true]);

        // Consume the pending token (one-time use)
        Cache::forget("2fa_pending:{$request->pending_token}");

        // Revoke previous tokens (single-session approach)
        $user->tokens()->delete();

        $token = $user->createToken('admin-dashboard')->plainTextToken;

        // Regenerate session to prevent fixation attacks (only if session is available)
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            session(['admin_authenticated' => true, 'admin_user_id' => $user->id]);
        }

        LoginAttempt::record($user->email, $request->ip(), $request->userAgent(), 'success', $user->id);

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
     * Resend 2FA code
     * POST /api/admin/resend-code
     */
    public function resendCode(Request $request): JsonResponse
    {
        $request->validate([
            'pending_token' => 'required|string|size:64',
        ]);

        $userId = Cache::get("2fa_pending:{$request->pending_token}");
        if (! $userId) {
            return response()->json([
                'success' => false,
                'message' => 'انتهت صلاحية الجلسة. أعد تسجيل الدخول.',
            ], 422);
        }
        $user = User::findOrFail($userId);

        if (($user->role ?? null) !== 'admin') {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 403);
        }

        $loginCode = AdminLoginCode::generateFor($user, $request->ip());
        self::putPendingToken($request->pending_token, $user);

        try {
            Mail::to(self::verificationEmails())->send(new AdminLoginVerification($loginCode));
        } catch (Throwable $exception) {
            Log::error('Admin verification resend email failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'error' => $exception->getMessage(),
            ]);

            if (config('services.admin.login_email_fallback')) {
                Log::warning('Admin verification resend email fallback used', [
                    'user_id' => $user->id,
                    'code_id' => $loginCode->id,
                    'ip' => $request->ip(),
                    'expires_at' => $loginCode->expires_at?->toIso8601String(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم إنشاء رمز تأكيد جديد. البريد غير متاح حالياً، استخدم الرمز من السيرفر.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'تعذر إعادة إرسال رمز التحقق حالياً. يرجى المحاولة لاحقاً أو التواصل مع الدعم.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة إرسال رمز التأكيد.',

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
        if ($request->hasSession()) {
            session()->invalidate();
            session()->regenerateToken();
        }

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
