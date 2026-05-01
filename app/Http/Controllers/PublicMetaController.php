<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Serves env-driven public meta files: robots.txt, sitemap.xml,
 * .well-known/security.txt. Everything resolves from config('app.url')
 * so deployments to new domains do not require code changes.
 */
class PublicMetaController extends Controller
{
    public function robots(): Response
    {
        $base = rtrim(config('app.url'), '/');

        $body = <<<TXT
User-agent: *
Disallow: /dashboard
Disallow: /login
Disallow: /insurance/
Disallow: /api/

Sitemap: {$base}/sitemap.xml
TXT;

        return response($body, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function sitemap(): Response
    {
        $base = rtrim(config('app.url'), '/');

        $paths = [
            ['/',         'weekly',  '1.0'],
            ['/motorapp', 'weekly',  '0.9'],
            ['/about',    'monthly', '0.6'],
            ['/contact',  'monthly', '0.6'],
            ['/faq',      'monthly', '0.6'],
            ['/blog',     'weekly',  '0.7'],
        ];

        $urls = '';
        foreach ($paths as [$path, $freq, $priority]) {
            $loc = htmlspecialchars($base . $path, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $urls .= "  <url>\n    <loc>{$loc}</loc>\n    <changefreq>{$freq}</changefreq>\n    <priority>{$priority}</priority>\n  </url>\n";
        }

        $body = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
              . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
              . $urls
              . "</urlset>\n";

        return response($body, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function securityTxt(): Response
    {
        $base = rtrim(config('app.url'), '/');
        $emailDomain = env('SUPPORT_EMAIL_DOMAIN', parse_url($base, PHP_URL_HOST) ?: 'localhost');
        $expires = now()->addYear()->setTime(23, 59, 59)->toIso8601ZuluString('millisecond');

        $body = <<<TXT
Contact: mailto:security@{$emailDomain}
Expires: {$expires}
Preferred-Languages: ar, en
Canonical: {$base}/.well-known/security.txt
TXT;

        return response($body, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
