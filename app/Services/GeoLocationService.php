<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GeoLocationService — خدمة تحديد الموقع الجغرافي
 *
 * تحدد موقع الزائر عبر IP مع:
 * - مزود أساسي (ip-api.com) ومزود احتياطي (ipapi.co)
 * - كاش 24 ساعة لتقليل استهلاك API
 * - دعم country_code للتحقق الموثوق من الدولة
 * - Fail-Open: السماح بالدخول عند فشل تحديد الموقع
 */
class GeoLocationService
{
    /**
     * خريطة أسماء المدن السعودية بالعربية
     */
    protected array $arabicCityNames = [
        'Riyadh' => 'الرياض',
        'Jeddah' => 'جدة',
        'Mecca' => 'مكة المكرمة',
        'Medina' => 'المدينة المنورة',
        'Dammam' => 'الدمام',
        'Khobar' => 'الخبر',
        'Dhahran' => 'الظهران',
        'Jubail' => 'الجبيل',
        'Tabuk' => 'تبوك',
        'Abha' => 'أبها',
        'Taif' => 'الطائف',
        'Hail' => 'حائل',
        'Buraydah' => 'بريدة',
        'Khamis Mushait' => 'خميس مشيط',
        'Najran' => 'نجران',
        'Yanbu' => 'ينبع',
        'Sakaka' => 'سكاكا',
        'Arar' => 'عرعر',
        'Jizan' => 'جازان',
        'Al Bahah' => 'الباحة',
        'Al Qatif' => 'القطيف',
        'Al Ahsa' => 'الأحساء',
        'Hofuf' => 'الهفوف',
        'Qassim' => 'القصيم',
        'Unaizah' => 'عنيزة',
    ];

    /**
     * خريطة أسماء الدول بالعربية (الأكثر شيوعاً)
     */
    protected array $arabicCountryNames = [
        'SA' => 'المملكة العربية السعودية',
        'AE' => 'الإمارات العربية المتحدة',
        'KW' => 'الكويت',
        'BH' => 'البحرين',
        'QA' => 'قطر',
        'OM' => 'عُمان',
        'EG' => 'مصر',
        'JO' => 'الأردن',
        'LB' => 'لبنان',
        'IQ' => 'العراق',
        'SY' => 'سوريا',
        'YE' => 'اليمن',
        'SD' => 'السودان',
        'LY' => 'ليبيا',
        'TN' => 'تونس',
        'DZ' => 'الجزائر',
        'MA' => 'المغرب',
        'PS' => 'فلسطين',
        'TR' => 'تركيا',
        'PK' => 'باكستان',
        'IN' => 'الهند',
        'BD' => 'بنغلاديش',
        'PH' => 'الفلبين',
        'ID' => 'إندونيسيا',
        'US' => 'الولايات المتحدة',
        'GB' => 'المملكة المتحدة',
        'FR' => 'فرنسا',
        'DE' => 'ألمانيا',
        'CN' => 'الصين',
        'RU' => 'روسيا',
    ];

    // ─────────────────────────────────────────────────────────────
    //  Core: getLocation
    // ─────────────────────────────────────────────────────────────

    /**
     * الحصول على معلومات الموقع الجغرافي حسب IP
     *
     * @return array{country: ?string, country_code: ?string, region: ?string, city: ?string, lat: ?float, lon: ?float}|null
     */
    public function getLocation(string $ip): ?array
    {
        $ip = trim($ip);

        // localhost → بيانات افتراضية (تطوير)
        if ($ip === 'localhost') {
            return $this->getDefaultLocation();
        }

        // IP غير صالح → لا تتصل بالمزود
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            Log::info('GeoLocation: invalid IP skipped', ['ip' => $ip]);

            return null;
        }

        // IPs المحلية → بيانات افتراضية (تطوير)
        if ($this->isLocalIp($ip)) {
            return $this->getDefaultLocation();
        }

        // كاش النتيجة لمدة 24 ساعة (cache key hashed للخصوصية)
        return Cache::remember($this->cacheKey($ip), now()->addDay(), function () use ($ip) {
            // المزود الأساسي: ip-api.com
            $result = $this->fetchFromPrimaryApi($ip);

            // المزود الاحتياطي: ipapi.co
            if ($result === null) {
                Log::info('GeoLocation: primary API failed, trying fallback', [
                    'ip_hash' => hash('sha256', $ip),
                ]);
                $result = $this->fetchFromFallbackApi($ip);
            }

            return $result;
        });
    }

    // ─────────────────────────────────────────────────────────────
    //  Convenience: Saudi check & Admin IP check
    // ─────────────────────────────────────────────────────────────

    /**
     * هل الزائر من السعودية؟
     *
     * Fail-Open: إذا فشل تحديد الموقع أو كان كود البلد فارغاً/غير معروف
     * يُعتبر سعودي (لتجنّب حظر زوار حقيقيين على نطاقات IP غير مفهرسة في MaxMind).
     */
    public function isSaudiArabia(string $ip): bool
    {
        return $this->isAllowedCountry($ip);
    }

    /**
     * هل الزائر من إحدى الدول المسموح بها؟
     *
     * Fail-Open: إذا فشل تحديد الموقع أو كان كود البلد فارغاً/غير معروف
     * يُعتبر مسموحاً (لتجنّب حظر زوار حقيقيين على نطاقات IP غير مفهرسة).
     */
    public function isAllowedCountry(string $ip): bool
    {
        $location = $this->getLocation($ip);

        // Fail-Open: لا نعرف الموقع → نسمح
        if ($location === null) {
            return true;
        }

        $countryCode = strtoupper(trim((string) ($location['country_code'] ?? '')));

        // Fail-Open: كود البلد فارغ أو unknown → نسمح
        if ($countryCode === '' || $countryCode === 'UNKNOWN') {
            return true;
        }

        return in_array($countryCode, $this->getAllowedCountries(), true);
    }

    /**
     * هل IP ضمن قائمة IPs المسموح بها للإدارة؟
     *
     * Fail-Closed: إذا لم تُحدد قائمة → يُحظر الجميع
     */
    public function isAdminIp(string $ip): bool
    {
        $allowedIps = config('services.geo.admin_ips', '');

        if (empty($allowedIps)) {
            return false;
        }

        $list = array_map('trim', explode(',', $allowedIps));

        foreach ($list as $entry) {
            if ($entry === '') {
                continue;
            }

            // دعم CIDR notation (مثل 1.2.3.0/24)
            if (str_contains($entry, '/')) {
                if ($this->ipInCidr($ip, $entry)) {
                    return true;
                }
            } elseif ($ip === $entry) {
                return true;
            }
        }

        return false;
    }

    /**
     * هل التقييد الجغرافي مُفعّل؟
     */
    public function isEnabled(): bool
    {
        return (bool) config('services.geo.enabled', true);
    }

    /**
     * الحصول على قائمة الدول المسموح بها
     *
     * @return string[] قائمة أكواد ISO 3166-1 alpha-2
     */
    public function getAllowedCountries(): array
    {
        $raw = (string) config('services.geo.allowed_countries', 'SA');

        return collect(explode(',', $raw))
            ->map(fn ($code) => strtoupper(trim((string) $code)))
            ->filter(fn ($code) => preg_match('/^[A-Z]{2}$/', $code) === 1)
            ->values()
            ->all();
    }

    // ─────────────────────────────────────────────────────────────
    //  Cache management
    // ─────────────────────────────────────────────────────────────

    /**
     * حذف كاش موقع IP معين
     */
    public function clearLocationCache(string $ip): void
    {
        Cache::forget($this->cacheKey($ip));
    }

    /**
     * بناء cache key مجزأ (hashed) للخصوصية وتجنّب رموز IPv6.
     */
    protected function cacheKey(string $ip): string
    {
        return 'geo_location:' . hash('sha256', trim($ip));
    }

    // ─────────────────────────────────────────────────────────────
    //  Primary API: ip-api.com
    // ─────────────────────────────────────────────────────────────

    /**
     * جلب بيانات الموقع من ip-api.com (المزود الأساسي)
     */
    protected function fetchFromPrimaryApi(string $ip): ?array
    {
        try {
            $apiKey = config('services.ip_api.key', '');

            if ($apiKey) {
                $url = "https://pro.ip-api.com/json/{$ip}";
                $params = [
                    'fields' => 'status,country,countryCode,regionName,city,lat,lon',
                    'lang' => 'en',
                    'key' => $apiKey,
                ];
            } else {
                // Free tier: HTTP only, 45 requests/minute
                $url = "http://ip-api.com/json/{$ip}";
                $params = [
                    'fields' => 'status,country,countryCode,regionName,city,lat,lon',
                    'lang' => 'en',
                ];
            }

            $response = Http::timeout(5)->get($url, $params);

            if ($response->successful() && $response->json('status') === 'success') {
                $data = $response->json();

                return [
                    'country' => $data['country'] ?? null,
                    'country_code' => $data['countryCode'] ?? null,
                    'region' => $data['regionName'] ?? null,
                    'city' => $data['city'] ?? null,
                    'lat' => $data['lat'] ?? null,
                    'lon' => $data['lon'] ?? null,
                ];
            }

            Log::info('GeoLocation primary API non-success response', [
                'ip' => $ip,
                'status' => $response->json('status'),
                'code' => $response->status(),
            ]);
        } catch (\Exception $e) {
            Log::warning('GeoLocation primary API failed', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────
    //  Fallback API: ipapi.co
    // ─────────────────────────────────────────────────────────────

    /**
     * جلب بيانات الموقع من ipapi.co (المزود الاحتياطي)
     * Free: 1,000 requests/day, HTTPS, no key needed
     */
    protected function fetchFromFallbackApi(string $ip): ?array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders(['User-Agent' => 'TaminkomInsurance/1.0'])
                ->get("https://ipapi.co/{$ip}/json/");

            if ($response->successful() && ! $response->json('error')) {
                $data = $response->json();

                return [
                    'country' => $data['country_name'] ?? null,
                    'country_code' => $data['country_code'] ?? null,
                    'region' => $data['region'] ?? null,
                    'city' => $data['city'] ?? null,
                    'lat' => $data['latitude'] ?? null,
                    'lon' => $data['longitude'] ?? null,
                ];
            }

            Log::info('GeoLocation fallback API non-success response', [
                'ip' => $ip,
                'code' => $response->status(),
                'error' => $response->json('reason') ?? 'unknown',
            ]);
        } catch (\Exception $e) {
            Log::warning('GeoLocation fallback API failed', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────
    //  Name translation helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * تحويل اسم المدينة الإنجليزي إلى العربي
     */
    public function getArabicCityName(?string $englishName): ?string
    {
        if (! $englishName) {
            return null;
        }

        return $this->arabicCityNames[$englishName] ?? $englishName;
    }

    /**
     * تحويل كود الدولة إلى الاسم العربي
     */
    public function getArabicCountryName(?string $countryCode): ?string
    {
        if (! $countryCode) {
            return null;
        }

        return $this->arabicCountryNames[strtoupper($countryCode)] ?? $countryCode;
    }

    // ─────────────────────────────────────────────────────────────
    //  Internal helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * التحقق إذا كان IP محلي (بيئة تطوير)
     *
     * يدعم IPv4 و IPv6 عبر CIDR الدقيقة:
     *   127.0.0.0/8, 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16,
     *   ::1/128, fc00::/7 (Unique Local), fe80::/10 (Link-Local).
     */
    protected function isLocalIp(string $ip): bool
    {
        $ip = trim($ip);

        if ($ip === 'localhost') {
            return true;
        }

        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        return $this->ipInCidr($ip, '127.0.0.0/8')
            || $this->ipInCidr($ip, '10.0.0.0/8')
            || $this->ipInCidr($ip, '172.16.0.0/12')
            || $this->ipInCidr($ip, '192.168.0.0/16')
            || $this->ipInCidr($ip, '::1/128')
            || $this->ipInCidr($ip, 'fc00::/7')
            || $this->ipInCidr($ip, 'fe80::/10');
    }

    /**
     * الموقع الافتراضي للتطوير المحلي (الرياض)
     */
    protected function getDefaultLocation(): array
    {
        return [
            'country' => 'Saudi Arabia',
            'country_code' => 'SA',
            'region' => 'Riyadh Region',
            'city' => 'Riyadh',
            'lat' => 24.7136,
            'lon' => 46.6753,
        ];
    }

    /**
     * التحقق إذا كان IP ضمن نطاق CIDR (يدعم IPv4 و IPv6)
     *
     * يستخدم inet_pton() للمقارنة الثنائية بدلاً من ip2long()
     * مما يدعم عناوين IPv6 (128-bit) بالإضافة إلى IPv4 (32-bit).
     */
    protected function ipInCidr(string $ip, string $cidr): bool
    {
        $ip = trim($ip);
        $cidr = trim($cidr);
        if ($cidr === '') {
            return false;
        }

        // عنوان مفرد بدون CIDR → مقارنة مباشرة
        if (! str_contains($cidr, '/')) {
            return hash_equals($cidr, $ip);
        }

        [$subnet, $prefixStr] = array_map('trim', explode('/', $cidr, 2));
        if ($subnet === '' || $prefixStr === '') {
            return false;
        }

        $prefix = (int) $prefixStr;

        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);

        if ($ipBin === false || $subnetBin === false) {
            return false;
        }

        // يجب أن يكونا من نفس العائلة (4 bytes = IPv4, 16 bytes = IPv6)
        if (strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        $len = strlen($ipBin); // 4 أو 16
        $maxBits = $len * 8;   // 32 أو 128

        if ($prefix < 0 || $prefix > $maxBits) {
            return false;
        }

        $fullBytes = intdiv($prefix, 8);
        $remainingBits = $prefix % 8;

        // مقارنة البايتات الكاملة
        if ($fullBytes > 0) {
            if (substr($ipBin, 0, $fullBytes) !== substr($subnetBin, 0, $fullBytes)) {
                return false;
            }
        }

        // مقارنة البتات المتبقية في البايت التالي
        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;

        return (ord($ipBin[$fullBytes]) & $mask) === (ord($subnetBin[$fullBytes]) & $mask);
    }
}
