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
     * @var array<string, array{rules: array<int, string>, type: string, group: string}>
     */
    private const SCHEMA = [
        'whatsapp_enabled' => ['rules' => ['required', 'boolean'],                     'type' => 'boolean', 'group' => 'contact'],
        'whatsapp_number'  => ['rules' => ['nullable', 'string', 'max:32', 'regex:/^[\d+\s\-()]*$/'], 'type' => 'string',  'group' => 'contact'],
        'whatsapp_message' => ['rules' => ['nullable', 'string', 'max:255'],           'type' => 'string',  'group' => 'contact'],
        'support_phone'    => ['rules' => ['nullable', 'string', 'max:32', 'regex:/^[\d+\s\-()]*$/'], 'type' => 'string',  'group' => 'contact'],
        'contact_email'    => ['rules' => ['nullable', 'email', 'max:120'],            'type' => 'string',  'group' => 'contact'],
    ];

    public function index(): JsonResponse
    {
        $values = [];
        foreach (array_keys(self::SCHEMA) as $key) {
            $values[$key] = SiteSetting::value($key, self::SCHEMA[$key]['type'] === 'boolean' ? false : '');
        }

        return response()->json(['data' => $values]);
    }

    public function update(Request $request): JsonResponse
    {
        $rules = [];
        foreach (self::SCHEMA as $key => $meta) {
            $rules[$key] = $meta['rules'];
        }
        $validated = $request->validate($rules);

        DB::transaction(function () use ($validated) {
            foreach ($validated as $key => $value) {
                SiteSetting::set(
                    $key,
                    self::SCHEMA[$key]['type'] === 'boolean' ? ($value ? '1' : '0') : (string) ($value ?? ''),
                    self::SCHEMA[$key]['type'],
                    self::SCHEMA[$key]['group'],
                );
            }
        });

        SiteSetting::flushCache();

        return $this->index();
    }
}
