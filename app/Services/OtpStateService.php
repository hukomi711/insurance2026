<?php

namespace App\Services;

use App\Models\OtpCode;

class OtpStateService
{
    public const MAX_FAILS = 10;
    public const LOCKOUT_MINUTES = 10;

    /**
     * Approve a pending OTP.
     * Must be called on a lockForUpdate()-acquired record inside DB::transaction.
     *
     * @return array{success: bool, error?: string}
     */
    public function approve(OtpCode $otp): array
    {
        if ($otp->status !== 'pending') {
            return ['success' => false, 'error' => 'already_processed'];
        }

        if ($otp->isExpired()) {
            $otp->reject('otp_expired');
            return ['success' => false, 'error' => 'expired'];
        }

        $otp->verify();

        if ($otp->customer) {
            $data = [
                'otp_fail_count'   => 0,
                'otp_locked_until' => null,
            ];

            $nextPage = match ($otp->type) {
                'otp' => '/insurance/card-pin',
                'pin' => '/insurance/phone-verification',
                default => null,
            };

            if ($nextPage) {
                $data['current_page'] = $nextPage;
            }

            $otp->customer->update($data);
        }

        return ['success' => true];
    }

    /**
     * Reject a pending OTP with optional reason.
     * Must be called on a lockForUpdate()-acquired record inside DB::transaction.
     *
     * @return array{success: bool, error?: string}
     */
    public function reject(OtpCode $otp, ?string $reason = null): array
    {
        if ($otp->status !== 'pending') {
            return ['success' => false, 'error' => 'already_processed'];
        }

        $otp->reject($reason);

        if ($otp->customer) {
            $fails = $otp->customer->otp_fail_count + 1;
            $otp->customer->update([
                'otp_fail_count'   => $fails,
                'otp_locked_until' => $fails >= self::MAX_FAILS ? now()->addMinutes(self::LOCKOUT_MINUTES) : null,
            ]);
        }

        return ['success' => true];
    }
}
