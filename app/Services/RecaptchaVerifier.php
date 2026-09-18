<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class RecaptchaVerifier
{
    /**
     * Verify a reCAPTCHA token with Google's siteverify API.
     *
     * @return array{success: bool, reason?: string}
     */
    public function verify(string $token, ?string $remoteIp = null): array
    {
        if (! config('services.recaptcha.enabled', false)) {
            return ['success' => true, 'reason' => 'disabled'];
        }

        $secret = trim((string) config('services.recaptcha.secret_key', ''));
        $verifyUrl = trim((string) config('services.recaptcha.verify_url', 'https://www.google.com/recaptcha/api/siteverify'));
        $timeout = (int) config('services.recaptcha.timeout', 5);

        if ($secret === '' || $token === '' || $verifyUrl === '') {
            return ['success' => false, 'reason' => 'misconfigured'];
        }

        $payload = [
            'secret' => $secret,
            'response' => $token,
        ];

        if ($remoteIp !== null && $remoteIp !== '') {
            $payload['remoteip'] = $remoteIp;
        }

        try {
            $response = Http::asForm()->timeout($timeout)->post($verifyUrl, $payload);
        } catch (ConnectionException) {
            return ['success' => false, 'reason' => 'upstream_unreachable'];
        }

        if (! $response->ok()) {
            return ['success' => false, 'reason' => 'upstream_http_error'];
        }

        $json = $response->json();
        if (! is_array($json)) {
            return ['success' => false, 'reason' => 'invalid_response'];
        }

        if (($json['success'] ?? false) !== true) {
            return ['success' => false, 'reason' => 'verification_failed'];
        }

        return ['success' => true];
    }
}
