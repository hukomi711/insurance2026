<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Serves security.txt from config('app.url') so deployments to new domains do
 * not require code changes.
 */
class PublicMetaController extends Controller
{
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
        $body = <<<TXT
    User-agent: *
    Disallow: /
    TXT;

        return response($body, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');
    }

    public function robots(): Response
    {
        return $this->robotsTxt();
    }

    public function sitemapXml(): Response
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
</urlset>
XML;

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');
    }

    public function sitemap(): Response
    {
        return $this->sitemapXml();
    }
}
