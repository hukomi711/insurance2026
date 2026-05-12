<?php

namespace App\Services;

/**
 * Pricing Signature Service
 *
 * Generates and verifies HMAC signatures for quoted prices.
 * Prevents client-side tampering with the totalPrice between
 * quote display and order submission.
 *
 * Signature format:
 *   HMAC-SHA256(planId.totalPrice.timestamp, PRICING_SIGNATURE_KEY)
 *
 * NOTE: The signature alone is NOT enough — OrderController MUST
 * re-calculate the price server-side and compare it to the signed
 * value before charging. The signature only proves the price was
 * issued by this server within the TTL window; it does not guarantee
 * the plan/customer context still produces the same number.
 */
class PricingSignatureService
{
    /**
     * Generate a signature for a quoted price.
     *
     * @param  string|int  $planId       Plan/quote identifier.
     * @param  int         $totalPrice   Integer total in the unit the caller uses
     *                                   consistently (currently SAR, with VAT).
     *                                   Prefer halalas in future iterations to
     *                                   avoid float rounding issues.
     * @param  int|null    $timestamp    Unix timestamp; defaults to now().
     *
     * @return array{signature: string, timestamp: int, expiresAt: int}
     */
    public function generateSignature(string|int $planId, int $totalPrice, ?int $timestamp = null): array
    {
        $timestamp = $timestamp ?? time();
        $expiresAt = $timestamp + $this->ttl();

        $canonical = $this->canonical($planId, $totalPrice, $timestamp);

        return [
            'signature' => $this->sign($canonical),
            'timestamp' => $timestamp,
            'expiresAt' => $expiresAt,
        ];
    }

    /**
     * Verify a signature.
     */
    public function verifySignature(string $signature, string|int $planId, int $totalPrice, int $timestamp): bool
    {
        if ($signature === '' || $timestamp <= 0) {
            return false;
        }

        $now = time();

        // Reject expired signatures.
        if ($now > $timestamp + $this->ttl()) {
            return false;
        }

        // Reject timestamps significantly in the future (clock skew tolerance: 60s).
        if ($timestamp > $now + 60) {
            return false;
        }

        $expected = $this->sign($this->canonical($planId, $totalPrice, $timestamp));

        return hash_equals($expected, $signature);
    }

    /**
     * Verify a complete signature packet sent from the client.
     *
     * @param  array<string, mixed>  $packet
     * @return array{valid: bool, error?: string}
     */
    public function verifyPacket(array $packet): array
    {
        foreach (['signature', 'timestamp', 'planId', 'totalPrice'] as $key) {
            if (! array_key_exists($key, $packet)) {
                return [
                    'valid' => false,
                    'error' => "Missing required field: {$key}",
                ];
            }
        }

        if (! is_string($packet['signature']) || $packet['signature'] === '') {
            return [
                'valid' => false,
                'error' => 'Invalid signature',
            ];
        }

        if (! is_numeric($packet['timestamp'])) {
            return [
                'valid' => false,
                'error' => 'Invalid timestamp',
            ];
        }

        // planId may legitimately be a string slug (e.g. "tawuniya_basic")
        // or a numeric id — accept either, reject anything else.
        if (! is_string($packet['planId']) && ! is_int($packet['planId'])) {
            return [
                'valid' => false,
                'error' => 'Invalid planId',
            ];
        }

        if (! is_numeric($packet['totalPrice'])) {
            return [
                'valid' => false,
                'error' => 'Invalid totalPrice',
            ];
        }

        $isValid = $this->verifySignature(
            $packet['signature'],
            is_int($packet['planId']) ? $packet['planId'] : (string) $packet['planId'],
            (int) $packet['totalPrice'],
            (int) $packet['timestamp']
        );

        if (! $isValid) {
            return [
                'valid' => false,
                'error' => 'Signature verification failed — possible tampering detected',
            ];
        }

        return ['valid' => true];
    }

    protected function canonical(string|int $planId, int $totalPrice, int $timestamp): string
    {
        return sprintf('%s.%d.%d', (string) $planId, $totalPrice, $timestamp);
    }

    protected function sign(string $canonical): string
    {
        return hash_hmac('sha256', $canonical, $this->key());
    }

    /**
     * Resolve the HMAC key.
     *
     * Prefers a dedicated PRICING_SIGNATURE_KEY so rotating APP_KEY does
     * not invalidate in-flight quote signatures (and vice versa). Falls
     * back to APP_KEY for backward compatibility with already-issued
     * signatures during the rollout window.
     */
    protected function key(): string
    {
        $key = (string) config('pricing.signature_key', '');

        if ($key === '') {
            $key = (string) config('app.key', '');
        }

        return $key;
    }

    protected function ttl(): int
    {
        return max(60, (int) config('pricing.signature_ttl', 3600));
    }
}
