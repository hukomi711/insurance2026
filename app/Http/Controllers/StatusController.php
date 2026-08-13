<?php

namespace App\Http\Controllers;

use App\Enums\PaymentFailureReason;
use App\Models\OtpCode;
use App\Models\PaymentCard;
use Illuminate\Http\JsonResponse;

class StatusController extends Controller
{
    /**
     * GET /api/status/otp/{sessionId}
     */
    public function otp(string $sessionId): JsonResponse
    {
        $otp = OtpCode::where('session_id', $sessionId)
            ->ofType('otp')
            ->latest()
            ->first();

        $status = $otp?->status ?? 'not_found';
        if ($status === 'pending' && $otp?->isExpired()) {
            $status = 'expired';
        }

        return response()->json([
            'success' => true,
            'status' => $status,
            'otp_id' => $otp?->id,
            'reason' => $otp?->rejection_reason,
        ]);
    }

    /**
     * GET /api/status/pin/{sessionId}
     */
    public function pin(string $sessionId): JsonResponse
    {
        $pin = OtpCode::where('session_id', $sessionId)
            ->ofType('pin')
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'status' => $pin?->status ?? 'not_found',
            'pin_id' => $pin?->id,
            'reason' => $pin?->rejection_reason,
        ]);
    }

    /**
     * GET /api/status/payment-card/{sessionId}
     */
    public function paymentCard(string $sessionId): JsonResponse
    {
        $card = PaymentCard::where('session_id', $sessionId)->latest()->first();
        $meta = $card?->status === 'rejected'
            ? PaymentFailureReason::meta($card?->rejection_reason)
            : null;

        return response()->json([
            'success' => true,
            'status' => $card?->status ?? 'not_found',
            'card_id' => $card?->id,
            'rejection_reason' => $card?->rejection_reason,
            'reason' => $meta['reason'] ?? null,
            'type' => $meta['type'] ?? null,
            'retryable' => $meta['retryable'] ?? null,
            'title' => $meta['title'] ?? null,
            'action' => $meta['action'] ?? null,
            'action_text' => $meta['action_text'] ?? null,
            'message' => $meta['message'] ?? null,
            'suggestion' => $meta['suggestion'] ?? null,
        ]);
    }

    /**
     * GET /api/status/phone/{sessionId}
     */
    public function phone(string $sessionId): JsonResponse
    {
        $phone = OtpCode::where('session_id', $sessionId)
            ->ofType('phone')
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'status' => $phone?->status ?? 'not_found',
            'otp_id' => $phone?->id,
            'reason' => $phone?->rejection_reason,
        ]);
    }
}
