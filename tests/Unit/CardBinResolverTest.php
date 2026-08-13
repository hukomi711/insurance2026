<?php

namespace Tests\Unit;

use App\Services\Bin\CardBinResolver;
use Database\Seeders\BankBinSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Tests for CardBinResolver.
 *
 * Covers:
 *  - Luhn validation
 *  - Network detection (rule-based)
 *  - BIN lookup priority (8 → 6 → range → prefix)
 *  - mada co-badged behavior
 *  - Unknown PAN fallback
 */
class CardBinResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed only the bank/BIN data needed for resolution tests.
        $this->seed(BankBinSeeder::class);
    }

    private function resolver(): CardBinResolver
    {
        return app(CardBinResolver::class);
    }

    // ── Luhn ─────────────────────────────────────────────────────

    public function test_luhn_accepts_valid_visa(): void
    {
        $this->assertTrue($this->resolver()->isValidLuhn('4242424242424242'));
    }

    public function test_luhn_rejects_invalid(): void
    {
        $this->assertFalse($this->resolver()->isValidLuhn('4242424242424241'));
    }

    public function test_luhn_strips_non_digits(): void
    {
        $this->assertTrue($this->resolver()->isValidLuhn('4242 4242-4242 4242'));
    }

    // ── Network detection ────────────────────────────────────────

    public function test_network_visa(): void
    {
        $this->assertEquals('visa', $this->resolver()->detectNetwork('4847831304739458'));
    }

    public function test_network_mastercard_5_series(): void
    {
        $this->assertEquals('mastercard', $this->resolver()->detectNetwork('5294000000000000'));
    }

    public function test_network_mastercard_2_series(): void
    {
        // 222100 is the lower bound of the 2-series MC range
        $this->assertEquals('mastercard', $this->resolver()->detectNetwork('2221001234567890'));
    }

    public function test_network_amex(): void
    {
        $this->assertEquals('amex', $this->resolver()->detectNetwork('371449635398431'));
    }

    public function test_network_unknown_for_short_number(): void
    {
        $this->assertNull($this->resolver()->detectNetwork(''));
    }

    // ── BIN lookup ───────────────────────────────────────────────

    public function test_resolves_rajhi_via_4847_prefix(): void
    {
        $r = $this->resolver()->resolve('4847831304739458');

        $this->assertEquals('rajhi', $r->bankKey);
        $this->assertEquals('mada', $r->network);            // 4847 is mada in seed
        $this->assertEquals('visa', $r->secondaryNetwork);   // co-badged with Visa
        $this->assertGreaterThanOrEqual(50, $r->confidence);
    }

    public function test_resolves_snb_via_5294_prefix(): void
    {
        $r = $this->resolver()->resolve('5294000000000007');

        $this->assertEquals('ahli', $r->bankKey);
        $this->assertEquals('mastercard', $r->network);
    }

    public function test_unknown_pan_returns_network_only(): void
    {
        // Visa prefix (network detectable) but BIN doesn't map to any seeded bank
        $r = $this->resolver()->resolve('4999990000000000');

        $this->assertNull($r->bankKey);
        $this->assertEquals('network_only', $r->matchType);
        $this->assertLessThan(50, $r->confidence);
    }

    public function test_short_pan_returns_unknown(): void
    {
        $r = $this->resolver()->resolve('1234');

        $this->assertEquals('unknown', $r->matchType);
        $this->assertEquals(0, $r->confidence);
        $this->assertFalse($r->isValidLuhn);
    }

    public function test_resolves_riyad_via_full_6_digit_bin(): void
    {
        // 421141 is a Riyad 6-digit prefix in the seed
        $r = $this->resolver()->resolve('4211410000000000');

        $this->assertEquals('riyad', $r->bankKey);
        $this->assertContains($r->matchType, ['6_digit_bin', 'range']);
    }

    #[DataProvider('overlappingPrefixProvider')]
    public function test_longest_matching_prefix_wins_regardless_of_config_order(
        string $specificPrefix,
        string $expectedBank,
    ): void {
        $originalConfig = config('bank_bins');
        $orders = [$originalConfig, array_reverse($originalConfig, true)];

        foreach ($orders as $configOrder) {
            config(['bank_bins' => $configOrder]);

            $this->assertSame(
                $expectedBank,
                $this->resolver()->resolveConfiguredBankKey($specificPrefix.'0000000000'),
            );
        }

        config(['bank_bins' => $originalConfig]);

        $resolved = $this->resolver()->resolve(str_pad($specificPrefix, 16, '0'));
        $this->assertSame($expectedBank, $resolved->bankKey);
    }

    public static function overlappingPrefixProvider(): array
    {
        return [
            'SABB 4228 vs SNB 422820' => ['422820', 'ahli'],
            'SABB 4228 vs SNB 422821' => ['422821', 'ahli'],
            'SABB 4228 vs Riyad 422817' => ['422817', 'riyad'],
            'BSF 4406 vs Rajhi 440647' => ['440647', 'rajhi'],
            'STC 4201 vs SABB 420132' => ['420132', 'sabb'],
            'ENBD 4106 vs Rajhi 410621' => ['410621', 'rajhi'],
            'ENBD 4106 vs SABB 410685' => ['410685', 'sabb'],
        ];
    }
}
