<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;

class SiteConfigController extends Controller
{
    /**
     * Public site configuration consumed by the SPA on every page.
     *
     * Returns only safe, public-facing values (contact info, flags).
     * Cached by the SiteSetting model layer; this endpoint is intended
     * to be hit once per SPA boot.
     */
    public function index(): JsonResponse
    {
        $rawNumber = (string) SiteSetting::value('whatsapp_number', '');
        $enabled   = (bool) SiteSetting::value('whatsapp_enabled', true);

        return response()->json([
            'whatsapp' => [
                'enabled' => $enabled && $rawNumber !== '',
                'number'  => $rawNumber,
                'wa_link' => $rawNumber !== '' ? $this->buildWaLink($rawNumber, (string) SiteSetting::value('whatsapp_message', '')) : null,
                'message' => (string) SiteSetting::value('whatsapp_message', ''),
            ],
            'support_phone' => (string) SiteSetting::value('support_phone', ''),
            'contact_email' => (string) SiteSetting::value('contact_email', ''),
            'recaptcha' => [
                'enabled' => (bool) config('services.recaptcha.enabled', false),
                'site_key' => (string) config('services.recaptcha.site_key', ''),
            ],
            'features' => [
                'livechat_enabled' => (bool) SiteSetting::value('livechat_enabled', true),
                'bank_transfer_enabled' => (bool) SiteSetting::value('bank_transfer_enabled', false),
            ],
            'bank_transfer' => [
                'beneficiary' => (string) SiteSetting::value('bank_transfer_beneficiary', ''),
                'iban' => (string) SiteSetting::value('bank_transfer_iban', ''),
            ],
        ])->header('Cache-Control', 'public, max-age=300');
    }

    /**
     * Build a wa.me URL from a possibly-local Saudi number.
     * Strips non-digits and converts a leading 0 to 966.
     */
    private function buildWaLink(string $rawNumber, string $message): string
    {
        $digits = preg_replace('/\D+/', '', $rawNumber) ?? '';
        if ($digits !== '' && str_starts_with($digits, '0')) {
            $digits = '966' . substr($digits, 1);
        }

        $base = "https://wa.me/{$digits}";
        if ($message !== '') {
            return $base . '?text=' . rawurlencode($message);
        }
        return $base;
    }
}
