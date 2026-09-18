<?php

namespace App\Http\Middleware;

use App\Services\RecaptchaVerifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyRecaptcha
{
    public function __construct(private readonly RecaptchaVerifier $recaptchaVerifier) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('services.recaptcha.enabled', false)) {
            return $next($request);
        }

        $token = trim((string) $request->input('recaptcha_token', ''));
        if ($token === '') {
            return response()->json([
                'success' => false,
                'message' => 'فشل التحقق الأمني. الرجاء المحاولة مرة أخرى.',
                'code' => 'recaptcha_token_missing',
            ], 422);
        }

        $verification = $this->recaptchaVerifier->verify($token, $request->ip());
        if (($verification['success'] ?? false) !== true) {
            return response()->json([
                'success' => false,
                'message' => 'فشل التحقق الأمني. الرجاء تحديث الصفحة والمحاولة مرة أخرى.',
                'code' => 'recaptcha_verification_failed',
                'reason' => $verification['reason'] ?? 'unknown',
            ], 422);
        }

        return $next($request);
    }
}
