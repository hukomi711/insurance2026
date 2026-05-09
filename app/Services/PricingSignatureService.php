<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;

/**
 * Pricing Signature Service
 *
 * Generates and verifies digital signatures for pricing data.
 * Prevents tampering with prices on the client side.
 *
 * Signature format: HMAC-SHA256(planId.totalPrice.timestamp, APP_KEY)
 * Verified on payment submission (OrderController).
 */
class PricingSignatureService
{
    /**
     * Generate a signature for a quoted price.
     *
     * @param string|int $planId - The plan/quote identifier
     * @param int $totalPrice - The total price in SAR (with VAT)
     * @param int|null $timestamp - Unix timestamp (default: now)
     * @return array { signature, timestamp, expiresAt }
     */
    public function generateSignature($planId, int $totalPrice, ?int $timestamp = null): array
    {
        $timestamp = $timestamp ?? time();
        $expiryDuration = (int) config('pricing.signature_ttl', 3600); // 1 hour default
        $expiresAt = $timestamp + $expiryDuration;

        // Build canonical string for hashing
        $canonical = sprintf('%s.%d.%d', (string) $planId, $totalPrice, $timestamp);

        // Generate HMAC-SHA256 using app key
        $signature = hash_hmac('sha256', $canonical, config('app.key'));

        return [
            'signature' => $signature,
            'timestamp' => $timestamp,
            'expiresAt' => $expiresAt,
            'canonical' => $canonical, // For debugging/logging
        ];
    }

    /**
     * Verify a signature.
     *
     * @param string $signature - The provided signature
     * @param string|int $planId - The plan ID
     * @param int $totalPrice - The total price
     * @param int $timestamp - The timestamp from the signature
     * @return bool - True if valid, False otherwise
     */
    public function verifySignature(string $signature, $planId, int $totalPrice, int $timestamp): bool
    {
        // Check expiry
        if (time() > $timestamp + (int) config('pricing.signature_ttl', 3600)) {
            return false;
        }

        // Regenerate and compare
        $canonical = sprintf('%s.%d.%d', (string) $planId, $totalPrice, $timestamp);
        $expectedSignature = hash_hmac('sha256', $canonical, config('app.key'));

        // Use hash_equals to prevent timing attacks
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify a complete signature packet (from frontend).
     *
     * @param array $packet - { signature, timestamp, planId, totalPrice }
     * @return array { valid: bool, error?: string }
     */
    public function verifyPacket(array $packet): array
    {
        $required = ['signature', 'timestamp', 'planId', 'totalPrice'];
        foreach ($required as $key) {
            if (!isset($packet[$key])) {
                return [
                    'valid' => false,
                    'error' => "Missing required field: {$key}",
                ];
            }
        }

        if (!is_numeric($packet['timestamp']) || !is_numeric($packet['planId']) || !is_numeric($packet['totalPrice'])) {
            return [
                'valid' => false,
                'error' => 'Invalid data types in signature packet',
            ];
        }

        $isValid = $this->verifySignature(
            $packet['signature'],
            (int) $packet['planId'],
            (int) $packet['totalPrice'],
            (int) $packet['timestamp']
        );

        if (!$isValid) {
            return [
                'valid' => false,
                'error' => 'Signature verification failed — possible tampering detected',
            ];
        }

        return ['valid' => true];
    }
}
