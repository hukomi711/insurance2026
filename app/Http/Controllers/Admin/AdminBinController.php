<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CardBinRange;
use App\Models\IssuerBank;
use App\Models\PaymentCard;
use App\Services\Bin\CardBinResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

/**
 * Admin CRUD over the BIN database (issuer_banks + card_bin_ranges) and a
 * lookup endpoint that exposes {@see CardBinResolver} to the dashboard.
 *
 * All routes are mounted under /api/admin/bin/* and protected by the same
 * auth:sanctum + admin gate as the rest of the admin API.
 */
class AdminBinController extends Controller
{
    public function __construct(private readonly CardBinResolver $resolver) {}

    /**
     * Resolve a single PAN/BIN — used by the live admin card UI.
     */
    public function lookup(Request $request): JsonResponse
    {
        $request->validate([
            'pan' => ['required', 'string', 'min:6', 'max:25'],
        ]);

        $result = $this->resolver->resolve($request->string('pan')->toString());

        return response()->json($result->toArray());
    }

    /**
     * List all BIN ranges, optionally filtered.
     */
    public function index(Request $request): JsonResponse
    {
        $q = CardBinRange::query()->with('issuerBank');

        if ($search = $request->string('search')->toString()) {
            $digits = preg_replace('/\D/', '', $search);
            if ($digits !== '') {
                $padded = (int) str_pad($digits, 8, '0', STR_PAD_RIGHT);
                $q->where(function ($w) use ($padded, $digits) {
                    $w->where('bin_start', '<=', $padded)
                        ->where('bin_end', '>=', $padded)
                        ->orWhere('issuer_bank_key', 'like', "%{$digits}%");
                });
            } else {
                $q->where('issuer_bank_key', 'like', "%{$search}%");
            }
        }

        if ($bank = $request->string('bank_key')->toString()) {
            $q->where('issuer_bank_key', $bank);
        }
        if ($network = $request->string('network')->toString()) {
            $q->where('primary_network', $network);
        }
        if ($request->has('is_active')) {
            $q->where('is_active', $request->boolean('is_active'));
        }

        $rows = $q->orderBy('bin_start')->paginate((int) $request->integer('per_page', 50));

        return response()->json($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);
        $row = CardBinRange::create($data);
        $this->flushBinCaches();

        return response()->json($row->load('issuerBank'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $row = CardBinRange::findOrFail($id);
        $row->update($this->validatePayload($request));
        $this->flushBinCaches();

        return response()->json($row->load('issuerBank'));
    }

    public function destroy(int $id): JsonResponse
    {
        CardBinRange::findOrFail($id)->delete();
        $this->flushBinCaches();

        return response()->json(['ok' => true]);
    }

    /**
     * List issuer banks (for the BIN form dropdown + UI logo lookup).
     */
    public function banks(): JsonResponse
    {
        $banks = IssuerBank::query()->orderBy('name_ar')->get();

        return response()->json($banks);
    }

    /**
     * Cards whose detection_confidence is below threshold — for human review.
     */
    public function pendingReview(Request $request): JsonResponse
    {
        $threshold = (int) $request->integer('threshold', 60);

        $rows = PaymentCard::query()
            ->where(function ($w) use ($threshold) {
                $w->whereNull('detection_confidence')
                    ->orWhere('detection_confidence', '<', $threshold);
            })
            ->orderByDesc('created_at')
            ->limit(200)
            ->get(['id', 'last4', 'bin_8', 'bin_6', 'detected_bank_key', 'detected_network', 'detection_confidence', 'detection_match_type', 'created_at']);

        return response()->json($rows);
    }

    /** @return array<string,mixed> */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'bin_start'         => ['required', 'integer', 'min:0'],
            'bin_end'           => ['required', 'integer', 'min:0', 'gte:bin_start'],
            'bin_length'        => ['required', Rule::in([4, 6, 8])],
            'issuer_bank_key'   => ['nullable', 'string', 'max:32', 'exists:issuer_banks,key'],
            'primary_network'   => ['nullable', Rule::in(['visa', 'mastercard', 'mada', 'amex', 'discover', 'unionpay'])],
            'secondary_network' => ['nullable', Rule::in(['visa', 'mastercard', 'mada', 'amex', 'discover', 'unionpay'])],
            'card_type'         => ['nullable', Rule::in(['debit', 'credit', 'prepaid', 'charge'])],
            'card_level'        => ['nullable', 'string', 'max:32'],
            'product_name'      => ['nullable', 'string', 'max:255'],
            'country_code'      => ['nullable', 'string', 'size:2'],
            'currency'          => ['nullable', 'string', 'size:3'],
            'confidence'        => ['nullable', 'integer', 'min:0', 'max:100'],
            'source'            => ['nullable', 'string', 'max:64'],
            'is_active'         => ['nullable', 'boolean'],
        ]);
    }

    private function flushBinCaches(): void
    {
        // Resolver caches per-bin8 — flush all bin:resolve:* keys.
        // Simple wildcard not supported on every cache driver; instead we
        // rely on a low TTL (1h) and flush nothing here. If you want immediate
        // invalidation, switch to tagged cache or call Cache::flush() in dev.
        Cache::forget('admin:bin:list');
    }
}
