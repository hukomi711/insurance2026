<?php

namespace Database\Seeders;

use App\Models\CardBinRange;
use App\Models\IssuerBank;
use Illuminate\Database\Seeder;

/**
 * Seeds issuer_banks + card_bin_ranges from config/bank_bins.php.
 *
 * Each prefix becomes a single BIN range (bin_start = bin_end = padded prefix).
 * mada bins are stored as separate ranges with primary_network=mada and
 * secondary_network kept null (resolver detects co-badged Visa/MC at runtime).
 *
 * Confidence scale:
 *   - 8-digit exact: 90 (none in current config — left for future imports)
 *   - 6-digit prefix: 75
 *   - 4-digit prefix: 50
 */
class BankBinSeeder extends Seeder
{
    /** @var array<string, array{name:string,name_ar:string,logo:string,theme:?string,color:?string}> */
    private const BANK_META = [
        'rajhi'  => ['logo' => '/images/banks/alrajhi.png',              'theme' => 'rajhi-blue',  'color' => '#003b71'],
        'ahli'   => ['logo' => '/images/banks/SNB.png',                   'theme' => 'snb-green',   'color' => '#0e7c3a'],
        'inma'   => ['logo' => '/images/banks/alinma.png',                'theme' => 'inma-purple', 'color' => '#6a1b9a'],
        'sabb'   => ['logo' => '/images/banks/SABB.png',                  'theme' => 'sabb-red',    'color' => '#c8102e'],
        'jazira' => ['logo' => '/images/banks/Bank_Aljazira.png',         'theme' => 'jazira-red',  'color' => '#a31616'],
        'riyad'  => ['logo' => '/images/banks/Riyad_Bank.png',            'theme' => 'riyad-blue',  'color' => '#0c2c5a'],
        'bilad'  => ['logo' => '/images/banks/Bank_Albilad.png',          'theme' => 'bilad-gold',  'color' => '#a17b1f'],
        'anb'    => ['logo' => '/images/banks/anb.png',                   'theme' => 'anb-blue',    'color' => '#08385a'],
        'saib'   => ['logo' => '/images/banks/Saudi_Investment.png',      'theme' => 'saib-blue',   'color' => '#1f3a5f'],
        'bsf'    => ['logo' => '/images/banks/Saudi_Fransi_Capital.webp', 'theme' => 'bsf-green',   'color' => '#0a6240'],
        'gib'    => ['logo' => null,                                      'theme' => 'gib-navy',    'color' => '#0a2a52'],
        'stc'    => ['logo' => null,                                      'theme' => 'stc-purple',  'color' => '#4f008c'],
        'enbd'   => ['logo' => null,                                      'theme' => 'enbd-red',    'color' => '#d31f3c'],
        'barraq' => ['logo' => null,                                      'theme' => 'barraq',      'color' => '#1a1a1a'],
    ];

    public function run(): void
    {
        $config = config('bank_bins', []);
        $madaBins = collect($config['_mada_bins'] ?? [])->map(fn ($b) => (string) $b)->all();

        // 1. Issuer banks
        foreach ($config as $key => $bank) {
            if ($key === '_mada_bins') {
                continue;
            }

            $meta = self::BANK_META[$key] ?? [];
            IssuerBank::updateOrCreate(
                ['key' => $key],
                [
                    'name_ar'      => $bank['name_ar'] ?? $key,
                    'name_en'      => $bank['name'] ?? ucfirst($key),
                    'logo_path'    => $meta['logo'] ?? null,
                    'theme'        => $meta['theme'] ?? null,
                    'brand_color'  => $meta['color'] ?? null,
                    'country_code' => 'SA',
                    'is_active'    => true,
                ]
            );
        }

        // 2. BIN ranges from prefixes
        foreach ($config as $key => $bank) {
            if ($key === '_mada_bins') {
                continue;
            }

            foreach (($bank['prefixes'] ?? []) as $prefix) {
                $this->seedPrefix($key, (string) $prefix, $madaBins);
            }
        }

        // 3. Pure-mada BINs that don't appear under a specific bank yet
        foreach ($madaBins as $prefix) {
            $exists = CardBinRange::query()
                ->where('bin_length', strlen((string) $prefix))
                ->where('bin_start', $this->padBinNumeric((string) $prefix))
                ->exists();
            if ($exists) {
                continue;
            }
            $this->seedPrefix(null, (string) $prefix, $madaBins, isMadaOnly: true);
        }
    }

    private function seedPrefix(?string $bankKey, string $prefix, array $madaBins, bool $isMadaOnly = false): void
    {
        $prefix = preg_replace('/\D/', '', $prefix);
        $len = strlen($prefix);

        if ($len !== 4 && $len !== 6 && $len !== 8) {
            // Skip unsupported lengths
            return;
        }

        // Convert prefix to numeric range covering all PANs that start with it.
        // We always store as 8-digit numeric range (bin_start..bin_end inclusive)
        // so a 6-digit prefix "484783" becomes 48478300..48478399.
        $padTo = 8;
        $start = (int) str_pad($prefix, $padTo, '0', STR_PAD_RIGHT);
        $end   = (int) str_pad($prefix, $padTo, '9', STR_PAD_RIGHT);

        $confidence = match ($len) {
            8 => 90,
            6 => 75,
            4 => 50,
            default => 40,
        };

        $isMada = in_array($prefix, $madaBins, true);
        $primary = $isMada ? 'mada' : $this->guessNetworkFromPrefix($prefix);
        $secondary = $isMada ? $this->guessNetworkFromPrefix($prefix) : null;

        CardBinRange::updateOrCreate(
            [
                'bin_start'  => $start,
                'bin_end'    => $end,
                'bin_length' => $len,
            ],
            [
                'issuer_bank_key'   => $bankKey,
                'primary_network'   => $primary,
                'secondary_network' => $secondary !== $primary ? $secondary : null,
                'card_type'         => $isMada ? 'debit' : null,
                'card_level'        => null,
                'country_code'      => 'SA',
                'currency'          => 'SAR',
                'confidence'        => $confidence,
                'source'            => $isMadaOnly ? 'mada-config' : 'bank-bins-config',
                'is_active'         => true,
            ]
        );
    }

    private function padBinNumeric(string $prefix): int
    {
        $prefix = preg_replace('/\D/', '', $prefix);

        return (int) str_pad($prefix, 8, '0', STR_PAD_RIGHT);
    }

    private function guessNetworkFromPrefix(string $prefix): ?string
    {
        if ($prefix === '') {
            return null;
        }
        if ($prefix[0] === '4') {
            return 'visa';
        }
        if (preg_match('/^5[1-5]/', $prefix) === 1) {
            return 'mastercard';
        }
        $first6 = (int) substr(str_pad($prefix, 6, '0'), 0, 6);
        if ($first6 >= 222100 && $first6 <= 272099) {
            return 'mastercard';
        }
        if (preg_match('/^3[47]/', $prefix) === 1) {
            return 'amex';
        }
        if ($prefix[0] === '6') {
            return 'discover';
        }
        if (str_starts_with($prefix, '9')) {
            return 'unionpay';
        }

        return null;
    }
}
