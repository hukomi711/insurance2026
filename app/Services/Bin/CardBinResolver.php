<?php

namespace App\Services\Bin;

use App\Models\CardBinRange;
use App\Models\IssuerBank;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves a PAN (card number) to its issuer bank, network, type and level.
 *
 * Resolution pipeline:
 *   1. Normalize PAN → digits only.
 *   2. Validate length (10..19) per ISO/IEC 7812.
 *   3. Run Luhn check (cosmetic — the result still resolves on failure
 *      so admin tools never blank out invalid-but-existing entries).
 *   4. Detect network from rule-based prefixes (Visa/MC/Amex/Discover/...).
 *   5. Look up BIN in card_bin_ranges, in this priority order:
 *        a) bin_length=8 exact (highest confidence)
 *        b) bin_length=6 exact
 *        c) any other range that contains the bin8
 *   6. If no match, fall back to legacy config/bank_bins.php prefix table
 *      (issuer bank by prefix + mada list).
 *   7. If still nothing, return network-only result with low confidence.
 *
 * The result is cached per BIN for 1 hour to keep admin reports fast.
 */
class CardBinResolver
{
    /** Mada BIN list from legacy config. */
    private const MADA_CONFIG_KEY = 'bank_bins._mada_bins';

    /** Cache TTL for BIN lookups (seconds). Lookups are pure read-only. */
    private const CACHE_TTL = 3600;

    public function resolve(?string $pan): CardBinResult
    {
        $digits = preg_replace('/\D/', '', (string) $pan) ?? '';

        if ($digits === '' || strlen($digits) < 10 || strlen($digits) > 19) {
            return new CardBinResult(
                isValidLuhn: false,
                bin8: null,
                bin6: null,
                last4: null,
                network: null,
                matchType: 'unknown',
                confidence: 0,
            );
        }

        $bin8 = substr($digits, 0, 8);
        $bin6 = substr($digits, 0, 6);
        $last4 = substr($digits, -4);

        $luhn = $this->isValidLuhn($digits);

        // Cache by 8-digit BIN — independent of full PAN
        $cacheKey = "bin:resolve:{$bin8}";

        /** @var CardBinResult $result */
        $result = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($bin8, $bin6, $digits) {
            return $this->lookup($bin8, $bin6, $digits);
        });

        // Re-emit with per-PAN fields (luhn + last4) — these are not cacheable.
        return new CardBinResult(
            isValidLuhn: $luhn,
            bin8: $bin8,
            bin6: $bin6,
            last4: $last4,
            network: $result->network,
            secondaryNetwork: $result->secondaryNetwork,
            bankKey: $result->bankKey,
            bankNameAr: $result->bankNameAr,
            bankNameEn: $result->bankNameEn,
            logoPath: $result->logoPath,
            theme: $result->theme,
            brandColor: $result->brandColor,
            cardType: $result->cardType,
            cardLevel: $result->cardLevel,
            productName: $result->productName,
            currency: $result->currency,
            countryCode: $result->countryCode,
            matchType: $result->matchType,
            confidence: $result->confidence,
        );
    }

    /**
     * Internal lookup (cacheable — does not depend on full PAN).
     */
    private function lookup(string $bin8, string $bin6, string $fullPan): CardBinResult
    {
        $bin8Int = (int) $bin8;
        $bin6Padded = (int) str_pad($bin6, 8, '0', STR_PAD_RIGHT);

        // 1. 8-digit exact range match
        $row = CardBinRange::query()
            ->where('is_active', true)
            ->where('bin_length', 8)
            ->where('bin_start', '<=', $bin8Int)
            ->where('bin_end', '>=', $bin8Int)
            ->orderByDesc('confidence')
            ->first();
        if ($row !== null) {
            return $this->buildResult($row, $this->detectNetwork($fullPan), $bin8, $bin6, $fullPan, '8_digit_bin');
        }

        // 2. 6-digit exact range match
        $row = CardBinRange::query()
            ->where('is_active', true)
            ->where('bin_length', 6)
            ->where('bin_start', '<=', $bin6Padded)
            ->where('bin_end', '>=', $bin6Padded)
            ->orderByDesc('confidence')
            ->first();
        if ($row !== null) {
            return $this->buildResult($row, $this->detectNetwork($fullPan), $bin8, $bin6, $fullPan, '6_digit_bin');
        }

        // 3. Any other range that contains bin8 (4-digit prefix from seed)
        $row = CardBinRange::query()
            ->where('is_active', true)
            ->where('bin_start', '<=', $bin8Int)
            ->where('bin_end', '>=', $bin8Int)
            ->orderByDesc('bin_length')
            ->orderByDesc('confidence')
            ->first();
        if ($row !== null) {
            return $this->buildResult($row, $this->detectNetwork($fullPan), $bin8, $bin6, $fullPan, $row->bin_length === 4 ? 'prefix' : 'range');
        }

        // 4. Legacy config/bank_bins.php prefix fallback (issuer bank by prefix)
        $network = $this->detectNetwork($fullPan);
        $isMada = $this->isMadaBin($bin6) || $this->isMadaBin(substr($bin8, 0, 4));

        $legacyBankKey = $this->legacyBankKeyForBin($bin8, $bin6);
        if ($legacyBankKey !== null) {
            $bank = IssuerBank::query()->where('key', $legacyBankKey)->first();

            return new CardBinResult(
                isValidLuhn: $this->isValidLuhn($fullPan),
                bin8: $bin8,
                bin6: $bin6,
                last4: substr($fullPan, -4),
                network: $isMada ? 'mada' : $network,
                secondaryNetwork: $isMada ? $network : null,
                bankKey: $legacyBankKey,
                bankNameAr: $bank?->name_ar,
                bankNameEn: $bank?->name_en,
                logoPath: $bank?->logo_path,
                theme: $bank?->theme,
                brandColor: $bank?->brand_color,
                cardType: $isMada ? 'debit' : null,
                currency: 'SAR',
                countryCode: 'SA',
                matchType: 'legacy_prefix',
                confidence: 50,
            );
        }

        // 5. Network-only fallback (rule-based)
        return new CardBinResult(
            isValidLuhn: $this->isValidLuhn($fullPan),
            bin8: $bin8,
            bin6: $bin6,
            last4: substr($fullPan, -4),
            network: $isMada ? 'mada' : $network,
            secondaryNetwork: $isMada ? $network : null,
            cardType: $isMada ? 'debit' : null,
            currency: $isMada ? 'SAR' : null,
            countryCode: $isMada ? 'SA' : null,
            matchType: $isMada ? 'mada_legacy_prefix' : ($network ? 'network_only' : 'unknown'),
            confidence: $isMada ? 45 : ($network ? 30 : 0),
        );
    }

    /**
     * Legacy fallback: scan config/bank_bins.php for a bank whose prefix list
     * matches the supplied BIN. Returns the bank key (e.g. 'rajhi') or null.
     */
    private function legacyBankKeyForBin(string $bin8, string $bin6): ?string
    {
        $config = (array) config('bank_bins', []);

        foreach ($config as $bankKey => $bankEntry) {
            if ($bankKey === '_mada_bins' || ! is_array($bankEntry)) {
                continue;
            }

            // Config structure: ['name' => ..., 'name_ar' => ..., 'prefixes' => [...]].
            // Legacy structure: bankKey => [prefix, prefix, ...].
            $prefixes = isset($bankEntry['prefixes']) && is_array($bankEntry['prefixes'])
                ? $bankEntry['prefixes']
                : $bankEntry;

            foreach ($prefixes as $prefix) {
                if (! is_scalar($prefix)) {
                    continue;
                }
                $prefix = preg_replace('/\D/', '', (string) $prefix) ?? '';
                if ($prefix === '') {
                    continue;
                }

                if (str_starts_with($bin8, $prefix) || str_starts_with($bin6, $prefix)) {
                    return (string) $bankKey;
                }
            }
        }

        return null;
    }

    private function buildResult(
        CardBinRange $row,
        ?string $detectedNetwork,
        string $bin8,
        string $bin6,
        string $fullPan,
        string $matchType,
    ): CardBinResult {
        $bank = $row->issuer_bank_key
            ? IssuerBank::query()->where('key', $row->issuer_bank_key)->first()
            : null;

        // For mada rows, prefer the detected network from PAN as the secondary
        // (mada is co-badged with Visa/MC).
        $primary = $row->primary_network ?: $detectedNetwork;
        $secondary = $row->secondary_network;
        if ($primary === 'mada' && $secondary === null && $detectedNetwork && $detectedNetwork !== 'mada') {
            $secondary = $detectedNetwork;
        }

        return new CardBinResult(
            isValidLuhn: $this->isValidLuhn($fullPan),
            bin8: $bin8,
            bin6: $bin6,
            last4: substr($fullPan, -4),
            network: $primary,
            secondaryNetwork: $secondary,
            bankKey: $row->issuer_bank_key,
            bankNameAr: $bank?->name_ar,
            bankNameEn: $bank?->name_en,
            logoPath: $bank?->logo_path,
            theme: $bank?->theme,
            brandColor: $bank?->brand_color,
            cardType: $row->card_type,
            cardLevel: $row->card_level,
            productName: $row->product_name,
            currency: $row->currency ?: 'SAR',
            countryCode: $row->country_code ?: 'SA',
            matchType: $matchType,
            confidence: (int) $row->confidence,
        );
    }

    /**
     * Public for unit testing.
     */
    public function isValidLuhn(string $digits): bool
    {
        $digits = preg_replace('/\D/', '', $digits) ?? '';
        $len = strlen($digits);
        if ($len < 10) {
            return false;
        }
        $sum = 0;
        $alt = false;
        for ($i = $len - 1; $i >= 0; $i--) {
            $n = (int) $digits[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alt = ! $alt;
        }

        return $sum % 10 === 0;
    }

    /**
     * Public for unit testing.
     */
    public function detectNetwork(string $pan): ?string
    {
        $pan = preg_replace('/\D/', '', $pan) ?? '';
        if ($pan === '') {
            return null;
        }

        // Visa
        if ($pan[0] === '4') {
            return 'visa';
        }

        // Mastercard: 51-55
        if (preg_match('/^5[1-5]/', $pan) === 1) {
            return 'mastercard';
        }

        // Mastercard: 222100-272099
        $first6 = (int) substr(str_pad($pan, 6, '0'), 0, 6);
        if ($first6 >= 222100 && $first6 <= 272099) {
            return 'mastercard';
        }

        // American Express
        if (preg_match('/^3[47]/', $pan) === 1) {
            return 'amex';
        }

        // UnionPay before Discover — UnionPay commonly starts with 62
        if (preg_match('/^(62|81)/', $pan) === 1) {
            return 'unionpay';
        }

        // Discover: precise ranges (6011, 65, 644-649, 622126-622925)
        if (
            preg_match('/^6011/', $pan) === 1
            || preg_match('/^65/', $pan) === 1
            || preg_match('/^64[4-9]/', $pan) === 1
            || ($first6 >= 622126 && $first6 <= 622925)
        ) {
            return 'discover';
        }

        return null;
    }

    private function isMadaBin(string $bin): bool
    {
        $list = (array) config(self::MADA_CONFIG_KEY, []);

        return in_array($bin, $list, true);
    }
}
