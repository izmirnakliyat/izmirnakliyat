<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SitemapCanonicalTest extends TestCase
{
    protected function setUp(): void
    {
        require_once PROJECT_ROOT . '/includes/sitemap_build.php';
    }

    public function testRedirectSourcesAreExcludedThroughTheCanonicalMap(): void
    {
        $redirectSources = [
            'kurumsal-nakliye-ofis-tasima',
            'izmir-ev-tasima-firmalari',
            'izmir-evden-eve-tasimacilik-fiyatlari',
            'sehirler-arasi-nakliyat-fiyatlari-2026',
            'izmir-evden-eve-nakliyat-platformu',
            'izmir-evden-eve-nakliye-fiyatlari-2025',
            'en-iyi-izmir-evden-eve-nakliyat-firmalari',
            'izmir-evden-eve-nakliyat-fiyatlari-2025',
            'profesyonel-ve-ozenli-sehir-ici-nakliyat',
            'izmir-ev-tasima-fiyatlari',
            'my-nakliyat-evden-eve-nakliyat',
            'izmir-nakliyat-firmalari',
            'izmir-evden-eve-nakliyat-fiyatlari',
            'antika-ve-piyano-tasima',
            'evden-eve-nakliyat',
            'sehirlerarasi-nakliyat',
        ];

        foreach ($redirectSources as $slug) {
            $this->assertTrue(sitemap_build_is_redirect_source_slug($slug), $slug);
        }
        $this->assertFalse(sitemap_build_is_redirect_source_slug('izmir-evden-eve-nakliyat'));
        $this->assertFalse(sitemap_build_is_redirect_source_slug('sehirler-arasi-nakliyat'));
        $this->assertFalse(sitemap_build_is_redirect_source_slug('antika-piyano-tasimaciligi'));
    }
}
