<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SiteSettingsController extends Controller
{
    /**
     * Keys this controller manages, with validation rules.
     * Any key not listed here is rejected to prevent arbitrary writes.
     *
     * @var array<string, array{rules: array<int, string>, type: string, group: string, default: mixed}>
     */
    private const SCHEMA = [
        'whatsapp_enabled' => ['rules' => ['sometimes', 'boolean'], 'type' => 'boolean', 'group' => 'contact', 'default' => false],
        'whatsapp_number' => ['rules' => ['sometimes', 'nullable', 'string', 'max:32', 'regex:/^[\d+\s\-()]*$/'], 'type' => 'string', 'group' => 'contact', 'default' => ''],
        'whatsapp_message' => ['rules' => ['sometimes', 'nullable', 'string', 'max:255'], 'type' => 'string', 'group' => 'contact', 'default' => ''],
        'support_phone' => ['rules' => ['sometimes', 'nullable', 'string', 'max:32', 'regex:/^[\d+\s\-()]*$/'], 'type' => 'string', 'group' => 'contact', 'default' => ''],
        'contact_email' => ['rules' => ['sometimes', 'nullable', 'email', 'max:120'], 'type' => 'string', 'group' => 'contact', 'default' => ''],
        'allowed_countries' => ['rules' => ['sometimes', 'array', 'size:1'], 'type' => 'json', 'group' => 'access', 'default' => ['SA']],
        'blocked_ip_addresses' => ['rules' => ['sometimes', 'array', 'max:100'], 'type' => 'json', 'group' => 'access', 'default' => []],
        'blocked_card_bins' => ['rules' => ['sometimes', 'array', 'max:100'], 'type' => 'json', 'group' => 'payments', 'default' => []],
        'bank_transfer_enabled' => ['rules' => ['sometimes', 'boolean'], 'type' => 'boolean', 'group' => 'payments', 'default' => false],
        'bank_transfer_beneficiary' => ['rules' => ['sometimes', 'nullable', 'string', 'max:120'], 'type' => 'string', 'group' => 'payments', 'default' => ''],
        'bank_transfer_iban' => ['rules' => ['sometimes', 'nullable', 'string', 'max:34', 'regex:/^$|^SA\d{22}$/i'], 'type' => 'string', 'group' => 'payments', 'default' => ''],
        'livechat_enabled' => ['rules' => ['sometimes', 'boolean'], 'type' => 'boolean', 'group' => 'communication', 'default' => true],
        'profanity_filter_enabled' => ['rules' => ['sometimes', 'boolean'], 'type' => 'boolean', 'group' => 'communication', 'default' => false],
        'profanity_words' => ['rules' => ['sometimes', 'array', 'max:100'], 'type' => 'json', 'group' => 'communication', 'default' => []],
        'smart_rejection_enabled' => ['rules' => ['sometimes', 'boolean'], 'type' => 'boolean', 'group' => 'payments', 'default' => false],
    ];

    public function index(): JsonResponse
    {
        $values = [];
        foreach (array_keys(self::SCHEMA) as $key) {
            $values[$key] = SiteSetting::value($key, self::SCHEMA[$key]['default']);
        }

        return response()->json(['data' => $values]);
    }

    public function update(Request $request): JsonResponse
    {
        $rules = [];
        foreach (self::SCHEMA as $key => $meta) {
            $rules[$key] = $meta['rules'];
        }
        $rules['allowed_countries.*'] = ['string', 'size:2', 'in:SA'];
        $rules['blocked_ip_addresses.*'] = ['string', 'max:45', 'ip'];
        $rules['blocked_card_bins.*'] = ['string', 'regex:/^\d{6,8}$/'];
        $rules['profanity_words.*'] = ['string', 'min:2', 'max:40'];
        $validated = $request->validate($rules);

        DB::transaction(function () use ($validated) {
            foreach ($validated as $key => $value) {
                SiteSetting::set(
                    $key,
                    self::SCHEMA[$key]['type'] === 'boolean' ? ($value ? '1' : '0') : ($value ?? ''),
                    self::SCHEMA[$key]['type'],
                    self::SCHEMA[$key]['group'],
                );
            }
        });

        SiteSetting::flushCache();

        return $this->index();
    }
}
