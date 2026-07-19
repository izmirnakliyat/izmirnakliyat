<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * includes/breadcrumb_jsonld.php birim testleri.
 */
class BreadcrumbJsonldTest extends TestCase
{
    protected function setUp(): void
    {
        require_once PROJECT_ROOT . '/includes/breadcrumb_jsonld.php';
    }

    // --- mynak_breadcrumb_absolute_from_segments ---

    public function testAbsoluteFromSegmentsEmpty(): void
    {
        $this->assertSame(
            'https://www.mynakliyat.com.tr/',
            mynak_breadcrumb_absolute_from_segments('https://www.mynakliyat.com.tr', [])
        );
    }

    public function testAbsoluteFromSegmentsSingle(): void
    {
        $this->assertSame(
            'https://www.mynakliyat.com.tr/blog',
            mynak_breadcrumb_absolute_from_segments('https://www.mynakliyat.com.tr', ['blog'])
        );
    }

    public function testAbsoluteFromSegmentsMultiple(): void
    {
        $this->assertSame(
            'https://www.mynakliyat.com.tr/blog/kategori/nakliyat',
            mynak_breadcrumb_absolute_from_segments('https://www.mynakliyat.com.tr', ['blog', 'kategori', 'nakliyat'])
        );
    }

    public function testAbsoluteFromSegmentsEncodesSpecialChars(): void
    {
        $url = mynak_breadcrumb_absolute_from_segments('https://example.com', ['türkçe-slug']);
        $this->assertStringContainsString('t%C3%BCrk%C3%A7e-slug', $url);
    }

    public function testAbsoluteFromSegmentsStripsTrailingSlashFromOrigin(): void
    {
        $this->assertSame(
            'https://example.com/test',
            mynak_breadcrumb_absolute_from_segments('https://example.com/', ['test'])
        );
    }

    // --- mynak_breadcrumb_slug_to_title ---

    public function testSlugToTitleBasic(): void
    {
        $this->assertSame('Blog', mynak_breadcrumb_slug_to_title('blog'));
    }

    public function testSlugToTitleTurkishCity(): void
    {
        $this->assertSame('İzmir', mynak_breadcrumb_slug_to_title('izmir'));
        $this->assertSame('İstanbul', mynak_breadcrumb_slug_to_title('istanbul'));
        $this->assertSame('Ankara', mynak_breadcrumb_slug_to_title('ankara'));
    }

    public function testSlugToTitleIzmirDistricts(): void
    {
        $this->assertSame('Karşıyaka', mynak_breadcrumb_slug_to_title('karsiyaka'));
        $this->assertSame('Bayraklı', mynak_breadcrumb_slug_to_title('bayrakli'));
        $this->assertSame('Buca', mynak_breadcrumb_slug_to_title('buca'));
        $this->assertSame('Bornova', mynak_breadcrumb_slug_to_title('bornova'));
    }

    public function testSlugToTitleCompoundSlug(): void
    {
        $result = mynak_breadcrumb_slug_to_title('izmir-evden-eve-nakliyat');
        $this->assertSame('İzmir Evden Eve Nakliyat', $result);
    }

    public function testSlugToTitleWithUnderscores(): void
    {
        $result = mynak_breadcrumb_slug_to_title('sehirlerarasi_nakliyat');
        $this->assertSame('Şehirlerarası Nakliyat', $result);
    }

    public function testSlugToTitleReturnsEmptyForEmpty(): void
    {
        $this->assertSame('', mynak_breadcrumb_slug_to_title(''));
    }

    // --- mynak_breadcrumb_dedupe_adjacent ---

    public function testDedupeAdjacentRemovesDuplicates(): void
    {
        $items = [
            ['name' => 'A', 'url' => '/a'],
            ['name' => 'A', 'url' => '/a'],
            ['name' => 'B', 'url' => '/b'],
        ];
        $result = mynak_breadcrumb_dedupe_adjacent($items);
        $this->assertCount(2, $result);
        $this->assertSame('A', $result[0]['name']);
        $this->assertSame('B', $result[1]['name']);
    }

    public function testDedupeAdjacentKeepsNonAdjacentDuplicates(): void
    {
        $items = [
            ['name' => 'A', 'url' => '/a'],
            ['name' => 'B', 'url' => '/b'],
            ['name' => 'A', 'url' => '/a'],
        ];
        $result = mynak_breadcrumb_dedupe_adjacent($items);
        $this->assertCount(3, $result);
    }

    // --- mynak_breadcrumb_is_home_path ---

    public function testIsHomePathReturnsTrue(): void
    {
        $this->assertTrue(mynak_breadcrumb_is_home_path('/'));
        $this->assertTrue(mynak_breadcrumb_is_home_path(''));
        $this->assertTrue(mynak_breadcrumb_is_home_path('index'));
        $this->assertTrue(mynak_breadcrumb_is_home_path('index.php'));
    }

    public function testIsHomePathReturnsFalse(): void
    {
        $this->assertFalse(mynak_breadcrumb_is_home_path('/blog'));
        $this->assertFalse(mynak_breadcrumb_is_home_path('/iletisim'));
    }

    // --- mynak_breadcrumb_is_excluded_path ---

    public function testIsExcludedPathAdmin(): void
    {
        $this->assertTrue(mynak_breadcrumb_is_excluded_path('/admin/dashboard'));
        $this->assertTrue(mynak_breadcrumb_is_excluded_path('/admin/'));
    }

    public function testIsExcludedPathPublic(): void
    {
        $this->assertFalse(mynak_breadcrumb_is_excluded_path('/blog'));
        $this->assertFalse(mynak_breadcrumb_is_excluded_path('/'));
    }

    // --- mynak_breadcrumb_path_segments ---

    public function testPathSegmentsEmpty(): void
    {
        $this->assertSame([], mynak_breadcrumb_path_segments('/'));
        $this->assertSame([], mynak_breadcrumb_path_segments(''));
    }

    public function testPathSegmentsSingle(): void
    {
        $this->assertSame(['blog'], mynak_breadcrumb_path_segments('/blog'));
    }

    public function testPathSegmentsMultiple(): void
    {
        $this->assertSame(['blog', 'kategori', 'test'], mynak_breadcrumb_path_segments('/blog/kategori/test'));
    }

    // --- mynak_breadcrumb_build_generic ---

    public function testBuildGenericSingleSegment(): void
    {
        $result = mynak_breadcrumb_build_generic(
            ['iletisim'],
            'https://www.mynakliyat.com.tr',
            'https://www.mynakliyat.com.tr/iletisim'
        );
        $this->assertCount(2, $result);
        $this->assertSame('Ana Sayfa', $result[0]['name']);
        $this->assertSame('İletişim', $result[1]['name']);
    }

    public function testBuildGenericMultipleSegments(): void
    {
        $result = mynak_breadcrumb_build_generic(
            ['hizmetleri', 'evden-eve-nakliyat'],
            'https://www.mynakliyat.com.tr',
            'https://www.mynakliyat.com.tr/hizmetleri/evden-eve-nakliyat'
        );
        $this->assertCount(3, $result);
        $this->assertSame('Ana Sayfa', $result[0]['name']);
        $this->assertSame('Hizmetleri', $result[1]['name']);
    }

    // --- mynak_breadcrumb_schema_from_items ---

    public function testSchemaFromItemsReturnsNullForEmpty(): void
    {
        $this->assertNull(mynak_breadcrumb_schema_from_items([]));
    }

    public function testSchemaFromItemsReturnsNullForSingleItem(): void
    {
        $items = [['name' => 'Home', 'url' => 'https://example.com/']];
        $this->assertNull(mynak_breadcrumb_schema_from_items($items));
    }

    public function testSchemaFromItemsReturnsBreadcrumbListSchema(): void
    {
        $items = [
            ['name' => 'Ana Sayfa', 'url' => 'https://www.mynakliyat.com.tr/'],
            ['name' => 'Blog', 'url' => 'https://www.mynakliyat.com.tr/blog'],
        ];
        $result = mynak_breadcrumb_schema_from_items($items);
        $this->assertNotNull($result);
        $this->assertSame('https://schema.org', $result['@context']);
        $this->assertSame('BreadcrumbList', $result['@type']);
        $this->assertCount(2, $result['itemListElement']);
        $this->assertSame(1, $result['itemListElement'][0]['position']);
        $this->assertSame('Ana Sayfa', $result['itemListElement'][0]['name']);
        $this->assertSame(2, $result['itemListElement'][1]['position']);
    }

    public function testSchemaFromItemsReturnsNullForInvalidUrl(): void
    {
        $items = [
            ['name' => 'A', 'url' => 'not-a-url'],
            ['name' => 'B', 'url' => 'also-not-url'],
        ];
        $this->assertNull(mynak_breadcrumb_schema_from_items($items));
    }

    // --- mynak_breadcrumb_build_items ---

    public function testBuildItemsReturnsEmptyForHomePath(): void
    {
        $result = mynak_breadcrumb_build_items('/', 'https://www.mynakliyat.com.tr/', 'https://www.mynakliyat.com.tr');
        $this->assertSame([], $result);
    }

    public function testBuildItemsReturnsEmptyForAdmin(): void
    {
        $result = mynak_breadcrumb_build_items('/admin/dashboard', 'https://www.mynakliyat.com.tr/admin/dashboard', 'https://www.mynakliyat.com.tr');
        $this->assertSame([], $result);
    }

    public function testBuildItemsBuildsForPublicPage(): void
    {
        $result = mynak_breadcrumb_build_items(
            '/galeri',
            'https://www.mynakliyat.com.tr/galeri',
            'https://www.mynakliyat.com.tr'
        );
        $this->assertNotEmpty($result);
        $this->assertSame('Ana Sayfa', $result[0]['name']);
    }

    public function testVideoWatchBreadcrumbUsesTheExistingGalleryParent(): void
    {
        $result = mynak_breadcrumb_build_items(
            '/video/spulgA_gx7o',
            'https://www.mynakliyat.com.tr/video/spulgA_gx7o',
            'https://www.mynakliyat.com.tr'
        );

        $this->assertCount(3, $result);
        $this->assertSame('Video', $result[1]['name']);
        $this->assertSame('https://www.mynakliyat.com.tr/video-galeri', $result[1]['url']);
        $this->assertSame('https://www.mynakliyat.com.tr/video/spulgA_gx7o', $result[2]['url']);
    }
}
