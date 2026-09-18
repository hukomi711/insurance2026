<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentCard;
use App\Services\Bin\CardBinResolver;
use App\Services\CardDisplay\CardDisplayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AdminCardDetailController — Comprehensive card detail endpoints for admins
 *
 * Provides:
 * - Full BIN-based card information
 * - Display formatting (masked/unmasked/partial)
 * - Bank branding data
 * - Audit logging for sensitive data access
 */
class AdminCardDetailController extends Controller
{
    public function __construct(
        private readonly CardBinResolver $resolver,
        private readonly CardDisplayService $displayService,
    ) {}

    /**
     * GET /api/admin/payment-cards/{id}
     *
     * Get complete card details with BIN information and formatted display data
     *
     * @param Request $request
     * @param PaymentCard $card
     * @return JsonResponse
     */
    public function show(Request $request, PaymentCard $card): JsonResponse
    {
        $this->authorize('view', $card);

        // Get display format from query parameter
        $displayMode = $request->query('display', 'masked');
        $showCvv = $request->boolean('show_cvv', false);
        $context = $request->query('context', 'dashboard');

        // Check authorization for unmasked display
        if ($displayMode === 'unmasked' && !$this->displayService->canShowUnmasked(auth()->id())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view unmasked card data',
            ], 403);
        }

        // Audit log unmasked access
        if ($displayMode === 'unmasked') {
            $this->displayService->auditUnmaskedAccess(
                cardId: $card->id,
                userId: auth()->id(),
                action: 'view-detail',
                metadata: [
                    'endpoint' => 'show',
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            );
        }

        // Resolve BIN and get branding
        $bin = $this->resolver->resolve($card->card_number);
        $branding = $this->displayService->getBrandingFromBin($bin);

        // Format card display
        $formatted = $this->displayService->formatForContext(
            cardNumber: $card->card_number,
            expiryMonth: $card->expiry_month,
            expiryYear: $card->expiry_year,
            cvv: $showCvv ? $card->cvv_encrypted : null,
            context: $context,
            last4: $card->last4,
        );

        // Get card shape
        $cardShape = $this->displayService->getCardShape('standard');

        return response()->json([
            'success' => true,
            'data' => [
                // Card Identity
                'id' => $card->id,
                'status' => $card->status,
                'created_at' => $card->created_at,
                'updated_at' => $card->updated_at,

                // Display Data (Context-Aware)
                'display' => [
                    'mode' => $formatted['mode'],
                    'card_number' => $formatted['card_number'],
                    'card_number_masked' => $formatted['card_number_masked'],
                    'card_number_unmasked' => $formatted['card_number_unmasked'],
                    'card_number_partial' => $formatted['card_number_partial'],
                    'last4' => $formatted['card_number_last4'],
                    'expiry' => $formatted['expiry'],
                    'cvv' => $formatted['cvv'],
                    'holder_name' => $card->holder_name,
                    'context' => $formatted['context'],
                ],

                // BIN Data (Raw)
                'bin' => [
                    'bin_6' => $bin->bin6,
                    'bin_8' => $bin->bin8,
                    'last4' => $bin->last4,
                    'is_valid_luhn' => $bin->isValidLuhn,
                    'confidence' => $bin->confidence,
                    'match_type' => $bin->matchType,
                ],

                // Bank/Network Information
                'bank' => [
                    'key' => $branding['bank_key'],
                    'name_ar' => $branding['bank_name'],
                    'name_en' => $branding['bank_name_en'],
                    'logo_url' => $branding['bank_logo'],
                    'brand_color' => $branding['brand_color'],
                    'text_color' => $branding['text_color'],
                    'card_style' => $branding['card_style'],
                ],

                // Card Properties
                'card' => [
                    'type' => $branding['card_type'],
                    'level' => $branding['card_level'],
                    'product_name' => $branding['product_name'],
                    'currency' => $branding['currency'],
                ],

                // Network Information
                'networks' => [
                    'primary' => $branding['network'],
                    'secondary' => $branding['secondary_network'],
                ],

                // Card Visual Properties
                'visual' => [
                    'shape' => 'standard',
                    'dimensions' => $cardShape,
                    'brand_class' => 'brand-' . strtolower($branding['network']),
                ],

                // Database/Resolver Storage
                'stored_bin' => [
                    'bin_6' => $card->bin_6,
                    'bin_8' => $card->bin_8,
                    'detected_bank_key' => $card->detected_bank_key,
                    'detected_network' => $card->detected_network,
                    'detected_secondary_network' => $card->detected_secondary_network,
                    'detected_type' => $card->detected_type,
                    'detected_level' => $card->detected_level,
                    'detection_confidence' => $card->detection_confidence,
                    'detection_match_type' => $card->detection_match_type,
                ],

                // Customer Information
                'customer' => [
                    'id' => $card->customer_profile_id,
                    'name' => $card->customer?->full_name,
                ],

                // Review Information
                'review' => [
                    'reviewed_at' => $card->reviewed_at,
                    'reviewed_by' => $card->reviewed_by,
                    'rejection_reason' => $card->rejection_reason,
                ],

                // Security Notice
                '_notice' => 'This endpoint logs all access to unmasked card data for audit compliance.',
            ],
        ]);
    }

    /**
     * POST /api/admin/card-display-config
     *
     * Get display configuration for a card (respecting user permissions)
     *
     * @param Request $request
     * @param PaymentCard $card
     * @return JsonResponse
     */
    public function getDisplayConfig(Request $request, PaymentCard $card): JsonResponse
    {
        $this->authorize('view', $card);

        $canShowUnmasked = $this->displayService->canShowUnmasked(auth()->id(), 'display');
        $canShowCvv = $canShowUnmasked && config('card_display.show_cvv', false);

        return response()->json([
            'success' => true,
            'data' => [
                'can_show_unmasked' => $canShowUnmasked,
                'can_show_cvv' => $canShowCvv,
                'available_modes' => [
                    'masked',
                    'partial',
                    $canShowUnmasked ? 'unmasked' : null,
                ],
                'default_mode' => config('card_display.mode', 'masked'),
                'default_expiry_mode' => config('card_display.show_expiry', 'visible'),
                'audit_logging_enabled' => config('card_display.audit.log_unmasked_access', true),
                'formats' => config('card_display.formats', []),
            ],
        ]);
    }

    /**
     * GET /api/admin/card-bin-lookup
     *
     * Lookup BIN information without accessing stored card data
     * (Useful for form validation during card entry)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function binLookup(Request $request): JsonResponse
    {
        $this->authorize('create', PaymentCard::class);

        $validated = $request->validate([
            'card_number' => 'required|string|regex:/^\d{13,19}$/',
        ]);

        $bin = $this->resolver->resolve($validated['card_number']);
        $branding = $this->displayService->getBrandingFromBin($bin);

        $formatted = $this->displayService->formatForContext(
            cardNumber: $validated['card_number'],
            context: 'dashboard',
        );

        return response()->json([
            'success' => true,
            'data' => [
                'bin' => [
                    'bin_6' => $bin->bin6,
                    'bin_8' => $bin->bin8,
                    'last4' => $bin->last4,
                ],
                'bank' => [
                    'key' => $branding['bank_key'],
                    'name_ar' => $branding['bank_name'],
                    'name_en' => $branding['bank_name_en'],
                    'logo_url' => $branding['bank_logo'],
                ],
                'card' => [
                    'type' => $branding['card_type'],
                    'network' => $branding['network'],
                ],
                'validation' => [
                    'is_valid_luhn' => $bin->isValidLuhn,
                    'confidence' => $bin->confidence,
                    'match_type' => $bin->matchType,
                ],
                'display' => [
                    'card_number_partial' => $formatted['card_number_partial'],
                    'card_number_masked' => $formatted['card_number_masked'],
                ],
            ],
        ]);
    }

    /**
     * GET /api/admin/cards/export-unmasked
     *
     * Export card list with unmasked data (admin only, logged)
     *
     * IMPORTANT: Requires explicit 'export_cards_unmasked' permission
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exportUnmasked(Request $request): JsonResponse
    {
        $this->authorize('view', PaymentCard::class);

        // Require explicit permission for unmasked export
        if (!auth()->user()?->hasPermissionTo('export_cards_unmasked')) {
            return response()->json([
                'success' => false,
                'message' => 'Not authorized to export unmasked card data',
            ], 403);
        }

        // Audit log this dangerous operation
        logger()->warning('Unmasked card export requested', [
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'timestamp' => now(),
        ]);

        $limit = min($request->integer('limit', 10), 100);  // Max 100 cards per request
        $skip = min($request->integer('skip', 0), 1000);    // Max 1000 cards offset

        $cards = PaymentCard::query()
            ->select([
                'id', 'card_number', 'last4', 'holder_name',
                'expiry_month', 'expiry_year', 'cvv_encrypted',
                'bin_6', 'detected_bank_key', 'detected_network',
                'detected_type', 'detected_level', 'status', 'created_at'
            ])
            ->skip($skip)
            ->take($limit)
            ->get();

        $cardData = $cards->map(function (PaymentCard $card) {
            $bin = $this->resolver->resolve($card->card_number);
            $formatted = $this->displayService->formatForContext(
                cardNumber: $card->card_number,
                expiryMonth: $card->expiry_month,
                expiryYear: $card->expiry_year,
                cvv: $card->cvv_encrypted,
                context: 'admin_details',
                last4: $card->last4,
            );

            // Audit each access
            $this->displayService->auditUnmaskedAccess(
                cardId: $card->id,
                userId: auth()->id(),
                action: 'export-unmasked',
                metadata: [
                    'endpoint' => 'exportUnmasked',
                    'bulk_export' => true,
                ],
            );

            return [
                'id' => $card->id,
                'card_number' => $formatted['card_number_unmasked'],
                'last4' => $formatted['card_number_last4'],
                'holder_name' => $card->holder_name,
                'expiry' => $formatted['expiry'],
                'cvv' => $formatted['cvv'],
                'bank_key' => $card->detected_bank_key,
                'network' => $card->detected_network,
                'status' => $card->status,
                'created_at' => $card->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'warning' => 'This data contains unmasked card numbers. Handle with care and never share.',
            'data' => $cardData,
            'pagination' => [
                'total' => PaymentCard::count(),
                'returned' => $cardData->count(),
                'skip' => $skip,
                'limit' => $limit,
            ],
        ]);
    }
}
