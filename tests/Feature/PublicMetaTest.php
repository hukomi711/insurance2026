<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicMetaTest extends TestCase
{
    public function test_robots_txt_disables_indexing_globally(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');
        $response->assertSee('User-agent: *', false);
        $response->assertSee('Disallow: /', false);
    }

    public function test_sitemap_xml_route_is_not_exposed(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertNotFound();
    }
}
