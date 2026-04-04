<?php

namespace Tests\Unit;

use App\Rules\SaudiNationalId;
use PHPUnit\Framework\TestCase;

class SaudiNationalIdRuleTest extends TestCase
{
    private SaudiNationalId $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new SaudiNationalId;
    }

    private function passes(string $value): bool
    {
        $failed = false;
        $this->rule->validate('national_id', $value, function () use (&$failed) {
            $failed = true;
        });

        return ! $failed;
    }

    // ─── Valid IDs ────────────────────────────────────────────────

    public function test_valid_saudi_national_id(): void
    {
        // 1000000008 — Luhn valid
        $this->assertTrue($this->passes('1000000008'));
    }

    public function test_valid_iqama_number(): void
    {
        // 2000000006 — Luhn valid
        $this->assertTrue($this->passes('2000000006'));
    }

    // ─── Invalid: format ─────────────────────────────────────────

    public function test_rejects_non_numeric(): void
    {
        $this->assertFalse($this->passes('abcdefghij'));
    }

    public function test_rejects_too_short(): void
    {
        $this->assertFalse($this->passes('100000000'));
    }

    public function test_rejects_too_long(): void
    {
        $this->assertFalse($this->passes('10000000060'));
    }

    public function test_rejects_wrong_prefix(): void
    {
        $this->assertFalse($this->passes('3000000000'));
    }

    // ─── Invalid: checksum ───────────────────────────────────────

    public function test_rejects_bad_luhn_checksum(): void
    {
        // 1000000001 — wrong check digit (should be 6)
        $this->assertFalse($this->passes('1000000001'));
    }

    public function test_rejects_another_bad_checksum(): void
    {
        $this->assertFalse($this->passes('2000000009'));
    }
}
