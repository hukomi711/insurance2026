<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Traits\ResolvesRealIP;
use App\Services\GeoLocationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CountryRestriction Middleware
 *
 * يقيّد وصول صفحات الويب (SPA) حسب الموقع الجغرافي:
 * - زوار السعودية: جميع الصفحات متاحة
 * - زوار خارج السعودية: المدونة فقط
 * - صفحة الدخول ولوحة التحكم: متاحة فقط لـ IP المالك
 *
 * Fail-Open: إذا فشل تحديد الموقع → يُسمح بالوصول
 */
class CountryRestriction
{
    use ResolvesRealIP;

    /**
     * مسارات تقنية مسموح بها لجميع الدول (لا تحتاج تحقق)
     * ملاحظة: api/* لها middleware خاص (ApiGeoRestriction)
     *          admin/* لها middleware خاص (AdminIpRestriction)
     */
    protected array $technicalPaths = [
        'sanctum/*',
        'broadcasting/*',
        '_ignition/*',
        'livewire/*',
        'up',
        // لوحة التحكم وتسجيل الدخول: الـ SPA shell فقط (لا بيانات حساسة)
        // الحماية الحقيقية عبر: Vue Router auth guard + Sanctum middleware على API
        'login',
        'dashboard',
        'dashboard/*',
    ];

    /**
     * المسارات المسموح بها للزوار من خارج السعودية (المدونة فقط)
     */
    protected array $allowedForForeigners = [
        'blog',
        'blog/*',
        'ar/blog',
        'ar/blog/*',
        'en/blog',
        'en/blog/*',
    ];

    /**
     * مسارات الملفات الثابتة (لا تحتاج تحقق)
     */
    protected array $staticAssets = [
        'assets/*',
        'build/*',
        'css/*',
        'js/*',
        'images/*',
        'img/*',
        'fonts/*',
        'Fonts/*',
        'favicon.ico',
        'robots.txt',
        'sitemap.xml',
    ];

    /**
     * مسارات الإدارة التي تتطلب IP المالك
     * ملاحظة: لوحة التحكم (dashboard) محمية عبر Vue Router auth guard
     * + Sanctum middleware على API endpoints، لذلك لا نحظرها هنا
     * حتى يتمكن المتصفح من تحميل الـ SPA shell أولاً.
     */
    protected array $adminWebPaths = [
        // 'dashboard' and 'dashboard/*' removed — SPA handles auth client-side
    ];

    public function __construct(
        protected GeoLocationService $geoService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // التقييد الجغرافي معطّل → سماح كامل
        if (! $this->geoService->isEnabled()) {
            return $next($request);
        }

        // السماح للمستخدمين المسجلين (الإداريين)
        // التحقق من Sanctum auth (API) أو session flag (web SPA)
        if ($request->user() || session('admin_authenticated')) {
            return $next($request);
        }

        // السماح للملفات الثابتة
        foreach ($this->staticAssets as $path) {
            if ($request->is($path)) {
                return $next($request);
            }
        }

        // السماح للمسارات التقنية
        foreach ($this->technicalPaths as $path) {
            if ($request->is($path)) {
                return $next($request);
            }
        }

        // ── حماية مسارات الإدارة بـ IP المالك ──
        $ip = $this->getRealIP($request);

        // ── IP المالك: وصول كامل لجميع الصفحات بدون أي قيد جغرافي ──
        if ($this->geoService->isAdminIp($ip)) {
            return $next($request);
        }

        // ── التحقق من الموقع الجغرافي ──
        $location = $this->geoService->getLocation($ip);

        // Fail-Open: لا نستطيع تحديد الموقع → نسمح
        if ($location === null) {
            return $next($request);
        }

        // السعودية (أو دولة مسموح بها) → جميع الصفحات
        $allowedCountries = $this->geoService->getAllowedCountries();
        if (in_array($location['country_code'] ?? '', $allowedCountries, true)) {
            return $next($request);
        }

        // خارج السعودية: التحقق إذا كان المسار مسموحاً (المدونة)
        foreach ($this->allowedForForeigners as $path) {
            if ($request->is($path)) {
                return $next($request);
            }
        }

        // حظر → تحويل للمدونة
        return $this->redirectToBlog($request, $location);
    }

    /**
     * تحويل الزائر لصفحة المدونة مع رسالة توضيحية
     */
    protected function redirectToBlog(Request $request, array $location): Response
    {
        $segment = $request->segment(1);
        $locale = in_array($segment, ['ar', 'en']) ? $segment : 'ar';

        $countryAr = $this->geoService->getArabicCountryName($location['country_code'] ?? null);

        session([
            'country_restricted' => true,
            'user_country' => $countryAr ?: ($location['country'] ?? 'Unknown'),
            'restriction_message' => $locale === 'ar'
                ? 'عذراً، هذه الخدمة متاحة فقط للمستخدمين في المملكة العربية السعودية. يمكنك تصفح مقالات المدونة.'
                : 'Sorry, this service is only available for users in Saudi Arabia. You can browse our blog articles.',
        ]);

        return redirect("/{$locale}/blog");
    }
}
