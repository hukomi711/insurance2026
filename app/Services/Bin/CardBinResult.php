<?php

namespace App\Services\Bin;

/**
 * Result of resolving a PAN/BIN against the BIN database.
 *
 * Pure value-object — no DB access.
 * Safe to serialize: contains BIN/last4 only, never full PAN/CVV/OTP.
 */
final class CardBinResult
{
    public function __construct(
        public readonly bool $isValidLuhn,

        public readonly ?string $bin8,
        public readonly ?string $bin6,
        public readonly ?string $last4,

        /**
         * Primary network:
         * visa | mastercard | mada | amex | discover | unionpay
         */
        public readonly ?string $network,

        /**
         * Secondary/co-badged network.
         * Example: mada + visa => secondaryNetwork = visa
         */
        public readonly ?string $secondaryNetwork = null,

        public readonly ?string $bankKey = null,
        public readonly ?string $bankNameAr = null,
        public readonly ?string $bankNameEn = null,
        public readonly ?string $logoPath = null,
        public readonly ?string $theme = null,
        public readonly ?string $brandColor = null,

        /**
         * debit | credit | prepaid | charge
         */
        public readonly ?string $cardType = null,

        /**
         * standard | gold | platinum | signature | infinite | world | world_elite | ...
         */
        public readonly ?string $cardLevel = null,

        public readonly ?string $productName = null,

        /**
         * Nullable because network-only or unknown cards may not be Saudi/SAR.
         */
        public readonly ?string $currency = null,
        public readonly ?string $countryCode = null,

        /**
         * 8_digit_bin | 6_digit_bin | range | prefix |
         * legacy_prefix | mada_legacy_prefix |
         * network_only | unknown
         */
        public readonly string $matchType = 'unknown',

        /**
         * 0..100 confidence score.
         */
        public readonly int $confidence = 0,
    ) {}

    public function hasBank(): bool
    {
        return $this->bankKey !== null;
    }

    public function hasNetwork(): bool
    {
        return $this->network !== null;
    }

    public function isUnknown(): bool
    {
        return $this->bankKey === null && $this->network === null;
    }

    public function isNetworkOnly(): bool
    {
        return $this->bankKey === null && $this->network !== null;
    }

    public function isMada(): bool
    {
        return $this->network === 'mada';
    }

    public function hasSecondaryNetwork(): bool
    {
        return $this->secondaryNetwork !== null;
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

            'has_bank'          => $this->hasBank(),
            'has_network'       => $this->hasNetwork(),
            'is_unknown'        => $this->isUnknown(),
            'is_network_only'   => $this->isNetworkOnly(),
            'is_mada'           => $this->isMada(),
        ];
    }
}
