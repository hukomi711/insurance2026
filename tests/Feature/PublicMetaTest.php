<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicMetaTest extends TestCase
{
    public function test_robots_txt_allows_public_pages_and_blocks_sensitive_paths(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSee('Allow: /', false);
        $response->assertSee('Disallow: /api/', false);
        $response->assertSee('Disallow: /admin', false);
        $response->assertSee('Sitemap: '.rtrim(config('app.url'), '/').'/sitemap.xml', false);
    }

    public function test_sitemap_xml_lists_public_blog_urls(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', false);
        $response->assertSee('<loc>'.rtrim(config('app.url'), '/').'/blog</loc>', false);
        $response->assertSee('<loc>'.rtrim(config('app.url'), '/').'/blog/vehicle-registration-renewal-absher</loc>', false);
    }
}
