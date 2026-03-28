<?php

namespace Tests\Unit;

use App\Services\CarrierDetectionService;
use Tests\TestCase;

/**
 * Tests for CarrierDetectionService — Saudi phone carrier detection.
 */
class CarrierDetectionServiceTest extends TestCase
{
    // ── STC ──────────────────────────────────────────────────────

    public function test_stc_with_966_prefix(): void
    {
        $this->assertEquals('STC', CarrierDetectionService::detect('966500123456'));
    }

    public function test_stc_with_0_prefix(): void
    {
        $this->assertEquals('STC', CarrierDetectionService::detect('0550123456'));
    }

    public function test_stc_without_prefix(): void
    {
        $this->assertEquals('STC', CarrierDetectionService::detect('530123456'));
    }

    public function test_stc_with_plus_966(): void
    {
        $this->assertEquals('STC', CarrierDetectionService::detect('+966555123456'));
    }

    // ── Mobily ───────────────────────────────────────────────────

    public function test_mobily_540_range(): void
    {
        $this->assertEquals('Mobily', CarrierDetectionService::detect('0540123456'));
    }

    public function test_mobily_560_range(): void
    {
        $this->assertEquals('Mobily', CarrierDetectionService::detect('966560123456'));
    }

    public function test_mobily_590_range(): void
    {
        $this->assertEquals('Mobily', CarrierDetectionService::detect('0590123456'));
    }

    // ── Zain ────────────────────────────────────────────────────

    public function test_zain_510_range(): void
    {
        $this->assertEquals('Zain', CarrierDetectionService::detect('0510123456'));
    }

    public function test_zain_520_range(): void
    {
        $this->assertEquals('Zain', CarrierDetectionService::detect('966520123456'));
    }

    // ── Edge cases ──────────────────────────────────────────────

    public function test_null_input_returns_null(): void
    {
        $this->assertNull(CarrierDetectionService::detect(null));
    }

    public function test_empty_string_returns_null(): void
    {
        $this->assertNull(CarrierDetectionService::detect(''));
    }

    public function test_too_short_returns_null(): void
    {
        $this->assertNull(CarrierDetectionService::detect('12345'));
    }

    public function test_unknown_prefix_returns_unknown(): void
    {
        $this->assertEquals('Unknown', CarrierDetectionService::detect('0600123456'));
    }

    public function test_strips_non_digits(): void
    {
        $this->assertEquals('STC', CarrierDetectionService::detect('+966-50-012-3456'));
    }
}
