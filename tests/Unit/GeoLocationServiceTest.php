<?php

namespace Tests\Unit;

use App\Services\GeoLocationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests for GeoLocationService — pure logic tests (no HTTP calls).
 *
 * Covers: isAdminIp, isEnabled, getAllowedCountries, isLocalIp,
 *         Arabic name translation, ipInCidr.
 */
class GeoLocationServiceTest extends TestCase
{
    private GeoLocationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->service = new GeoLocationService();
    }

    // ── isAdminIp ───────────────────────────────────────────────

    public function test_admin_ip_matches_exact(): void
    {
        config(['services.geo.admin_ips' => '1.2.3.4,5.6.7.8']);

        $this->assertTrue($this->service->isAdminIp('1.2.3.4'));
        $this->assertTrue($this->service->isAdminIp('5.6.7.8'));
    }

    public function test_admin_ip_rejects_non_listed(): void
    {
        config(['services.geo.admin_ips' => '1.2.3.4']);

        $this->assertFalse($this->service->isAdminIp('9.9.9.9'));
    }

    public function test_admin_ip_empty_config_rejects_all(): void
    {
        config(['services.geo.admin_ips' => '']);

        $this->assertFalse($this->service->isAdminIp('1.2.3.4'));
    }

    public function test_admin_ip_cidr_range(): void
    {
        config(['services.geo.admin_ips' => '10.0.0.0/24']);

        $this->assertTrue($this->service->isAdminIp('10.0.0.1'));
        $this->assertTrue($this->service->isAdminIp('10.0.0.255'));
        $this->assertFalse($this->service->isAdminIp('10.0.1.1'));
    }

    public function test_admin_ip_trims_whitespace(): void
    {
        config(['services.geo.admin_ips' => ' 1.2.3.4 , 5.6.7.8 ']);

        $this->assertTrue($this->service->isAdminIp('1.2.3.4'));
        $this->assertTrue($this->service->isAdminIp('5.6.7.8'));
    }

    // ── isEnabled ───────────────────────────────────────────────

    public function test_enabled_by_default(): void
    {
        config(['services.geo.enabled' => true]);
        $this->assertTrue($this->service->isEnabled());
    }

    public function test_disabled_when_config_false(): void
    {
        config(['services.geo.enabled' => false]);
        $this->assertFalse($this->service->isEnabled());
    }

    // ── getAllowedCountries ─────────────────────────────────────

    public function test_default_allowed_country_is_sa(): void
    {
        config(['services.geo.allowed_countries' => 'SA']);
        $this->assertEquals(['SA'], $this->service->getAllowedCountries());
    }

    public function test_multiple_allowed_countries(): void
    {
        config(['services.geo.allowed_countries' => 'SA,AE,KW']);
        $this->assertEquals(['SA', 'AE', 'KW'], $this->service->getAllowedCountries());
    }

    public function test_allowed_location_reuses_resolved_payload(): void
    {
        config(['services.geo.allowed_countries' => 'SA,AE']);

        $this->assertTrue($this->service->isAllowedLocation(['country_code' => 'AE']));
        $this->assertFalse($this->service->isAllowedLocation(['country_code' => 'US']));
        $this->assertTrue($this->service->isAllowedLocation(null));
    }

    public function test_failed_provider_lookup_is_cached_briefly(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 'fail', 'error' => true], 503),
        ]);

        $this->assertNull($this->service->getLocation('8.8.8.8'));
        $this->assertNull($this->service->getLocation('8.8.8.8'));

        Http::assertSentCount(2);
    }

    // ── Arabic name translation ─────────────────────────────────

    public function test_arabic_city_name_known(): void
    {
        $this->assertEquals('الرياض', $this->service->getArabicCityName('Riyadh'));
    }

    public function test_arabic_city_name_unknown_returns_original(): void
    {
        $this->assertEquals('NewCity', $this->service->getArabicCityName('NewCity'));
    }

    public function test_arabic_city_name_null(): void
    {
        $this->assertNull($this->service->getArabicCityName(null));
    }

    public function test_arabic_country_name_known(): void
    {
        $this->assertEquals('المملكة العربية السعودية', $this->service->getArabicCountryName('SA'));
    }

    public function test_arabic_country_name_lowercase_input(): void
    {
        $this->assertEquals('المملكة العربية السعودية', $this->service->getArabicCountryName('sa'));
    }

    public function test_arabic_country_name_unknown_returns_code(): void
    {
        $this->assertEquals('ZZ', $this->service->getArabicCountryName('ZZ'));
    }

    public function test_arabic_country_name_null(): void
    {
        $this->assertNull($this->service->getArabicCountryName(null));
    }

    // ── Local IP detection ──────────────────────────────────────

    public function test_local_ip_returns_default_riyadh(): void
    {
        $location = $this->service->getLocation('127.0.0.1');

        $this->assertEquals('SA', $location['country_code']);
        $this->assertEquals('Riyadh', $location['city']);
    }

    public function test_private_ip_192_168_returns_default(): void
    {
        $location = $this->service->getLocation('192.168.1.100');

        $this->assertEquals('SA', $location['country_code']);
    }

    public function test_private_ip_10_returns_default(): void
    {
        $location = $this->service->getLocation('10.0.0.1');

        $this->assertEquals('SA', $location['country_code']);
    }
}
