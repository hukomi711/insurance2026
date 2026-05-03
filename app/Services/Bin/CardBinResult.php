<?php

namespace App\Services\Bin;

/**
 * Result of resolving a PAN against the BIN database.
 *
 * Pure value-object — no DB access.
 */
final class CardBinResult
{
    public function __construct(
        public readonly bool    $isValidLuhn,
        public readonly ?string $bin8,
        public readonly ?string $bin6,
        public readonly ?string $last4,
        public readonly ?string $network,        // primary network: visa | mastercard | mada | amex | discover | unionpay
        public readonly ?string $secondaryNetwork = null, // co-badged: e.g. mada+visa → 'visa'
        public readonly ?string $bankKey = null,
        public readonly ?string $bankNameAr = null,
        public readonly ?string $bankNameEn = null,
        public readonly ?string $logoPath = null,
        public readonly ?string $theme = null,
        public readonly ?string $brandColor = null,
        public readonly ?string $cardType = null,    // debit | credit | prepaid | charge
        public readonly ?string $cardLevel = null,   // standard | gold | platinum | ...
        public readonly ?string $productName = null,
        public readonly string  $currency = 'SAR',
        public readonly string  $countryCode = 'SA',
        public readonly string  $matchType = 'unknown', // 8_digit_bin | 6_digit_bin | range | prefix | network_only | unknown
        public readonly int     $confidence = 0,
    ) {}

    public function isUnknown(): bool
    {
        return $this->bankKey === null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'is_valid_luhn'     => $this->isValidLuhn,
            'bin_8'             => $this->bin8,
            'bin_6'             => $this->bin6,
            'last4'             => $this->last4,
            'network'           => $this->network,
            'secondary_network' => $this->secondaryNetwork,
            'bank_key'          => $this->bankKey,
            'bank_name_ar'      => $this->bankNameAr,
            'bank_name_en'      => $this->bankNameEn,
            'logo_path'         => $this->logoPath,
            'theme'             => $this->theme,
            'brand_color'       => $this->brandColor,
            'card_type'         => $this->cardType,
            'card_level'        => $this->cardLevel,
            'product_name'      => $this->productName,
            'currency'          => $this->currency,
            'country_code'      => $this->countryCode,
            'match_type'        => $this->matchType,
            'confidence'        => $this->confidence,
        ];
    }
}
