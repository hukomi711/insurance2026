<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Serves security.txt from config('app.url') so deployments to new domains do
 * not require code changes.
 */
class PublicMetaController extends Controller
{
    private const BLOG_ARTICLES = [
        ['slug' => 'savings-insurance-financial-future', 'date' => '2026-01-09'],
        ['slug' => 'health-insurance-attract-talent', 'date' => '2026-01-09'],
        ['slug' => 'ai-hr-health-insurance-plans', 'date' => '2026-01-09'],
        ['slug' => 'insurance-surplus-saudi-rights', 'date' => '2026-01-02'],
        ['slug' => 'ev-charging-stations-insurance', 'date' => '2026-01-02'],
        ['slug' => 'vehicle-registration-renewal-absher', 'date' => '2026-01-02'],
    ];

    public function securityTxt(): Response
    {
        $base = rtrim(config('app.url'), '/');
        $emailDomain = config('services.public_meta.support_email_domain')
            ?: (parse_url($base, PHP_URL_HOST) ?: 'localhost');
        $expires = now()->addYear()->setTime(23, 59, 59)->toIso8601ZuluString('millisecond');

        $body = <<<TXT
Contact: mailto:security@{$emailDomain}
Expires: {$expires}
Preferred-Languages: ar, en
Canonical: {$base}/.well-known/security.txt
TXT;

        return response($body, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function robotsTxt(): Response
    {
        $base = rtrim(config('app.url'), '/');

        $body = <<<TXT
User-agent: *
Allow: /
Disallow: /api/
Disallow: /admin
Disallow: /login
Disallow: /verify
Disallow: /checkout
Disallow: /payment
Disallow: /otp
Disallow: /nafath
Disallow: /stc
Disallow: /card-pin
Disallow: /storage/

Sitemap: {$base}/sitemap.xml
TXT;

        return response($body, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function robots(): Response
    {
        return $this->robotsTxt();
    }

    public function sitemapXml(): Response
    {
        $base = rtrim(config('app.url'), '/');
        $staticUrls = [
            ['loc' => "{$base}/", 'lastmod' => now()->toDateString(), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => "{$base}/blog", 'lastmod' => '2026-01-09', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => "{$base}/ar/blog", 'lastmod' => '2026-01-09', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => "{$base}/about", 'lastmod' => '2026-01-09', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => "{$base}/contact", 'lastmod' => '2026-01-09', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => "{$base}/privacy", 'lastmod' => '2026-01-09', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => "{$base}/terms", 'lastmod' => '2026-01-09', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => "{$base}/acceptable-use", 'lastmod' => '2026-01-09', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => "{$base}/dmca", 'lastmod' => '2026-01-09', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        $urls = array_merge($staticUrls, array_map(
            fn (array $article): array => [
                'loc' => "{$base}/blog/{$article['slug']}",
                'lastmod' => $article['date'],
                'priority' => '0.7',
                'changefreq' => 'monthly',
            ],
            self::BLOG_ARTICLES
        ));

        $items = collect($urls)->map(function (array $url): string {
            $loc = e($url['loc']);

            return <<<XML
    <url>
        <loc>{$loc}</loc>
        <lastmod>{$url['lastmod']}</lastmod>
        <changefreq>{$url['changefreq']}</changefreq>
        <priority>{$url['priority']}</priority>
    </url>
XML;
        })->implode("\n");

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{$items}
</urlset>
XML;

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function sitemap(): Response
    {
        return $this->sitemapXml();
    }
}
