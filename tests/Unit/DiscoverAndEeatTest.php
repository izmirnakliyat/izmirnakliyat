<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DiscoverAndEeatTest extends TestCase
{
    private const ORIGIN = 'https://www.mynakliyat.com.tr';

    protected function setUp(): void
    {
        if (!defined('SITE_URL')) {
            define('SITE_URL', self::ORIGIN);
        }
        require_once PROJECT_ROOT . '/includes/seo_runtime/robots_meta.php';
        require_once PROJECT_ROOT . '/includes/mynak_open_graph.php';
        require_once PROJECT_ROOT . '/includes/seo_runtime/author_resolver.php';
        require_once PROJECT_ROOT . '/includes/seo_runtime.php';
    }

    public function testDiscoverDirectiveIsAddedOnlyToIndexablePages(): void
    {
        $this->assertSame(
            'index, follow, max-image-preview:large',
            seo_runtime_apply_discover_robots_directive(null)
        );
        $this->assertSame(
            'index, follow, max-image-preview:large',
            seo_runtime_apply_discover_robots_directive('index, follow')
        );
        $this->assertSame('noindex, follow', seo_runtime_apply_discover_robots_directive('noindex, follow'));
    }

    public function testOpenGraphUsesRealLocalImageMetadata(): void
    {
        $metadata = mynak_open_graph_build([
            'canonical' => self::ORIGIN . '/',
            'page_title' => 'MY Nakliyat',
            'page_type' => 'home',
            'hero_lcp_preload_href' => self::ORIGIN . '/assets/img/hero-background.webp',
        ]);

        $this->assertSame(1920, $metadata['image_width']);
        $this->assertSame(1000, $metadata['image_height']);
        $this->assertSame('image/webp', $metadata['image_type']);
        $this->assertTrue($metadata['large_image']);
    }

    public function testServiceImagePathIsNormalizedToOneUploadsDirectory(): void
    {
        $url = mynak_open_graph_resolve_image(
            [],
            null,
            ['foto' => 'uploads/services/example.webp'],
            ''
        );

        $this->assertSame(self::ORIGIN . '/uploads/services/example.webp', $url);
    }

    public function testUnverifiedAwardClaimIsRemovedFromPublicTitle(): void
    {
        $this->assertSame(
            'İzmir Evden Eve Nakliyat | MY Nakliyat',
            mynak_normalize_public_page_title(
                'İzmir Evden Eve Nakliyat | Güvenilir Marka Ödüllü | MY Nakliyat'
            )
        );
    }

    public function testGenericEditorialIdentitiesResolveToOrganization(): void
    {
        $this->assertTrue(seo_runtime_author_is_organization_identity('MY Nakliyat İçerik Ekibi', 'MY Nakliyat'));
        $this->assertTrue(seo_runtime_author_is_organization_identity('Nakliye Uzmanı', 'MY Nakliyat'));
        $this->assertFalse(seo_runtime_author_is_organization_identity('Ayşe Yılmaz', 'MY Nakliyat'));

        $author = seo_runtime_resolve_blog_author(
            null,
            ['blog_default_author_name' => 'MY Nakliyat İçerik Ekibi'],
            self::ORIGIN . '/#organization',
            'MY Nakliyat',
            self::ORIGIN
        );
        $this->assertSame('Organization', $author['@type']);
        $this->assertSame(self::ORIGIN . '/#organization', $author['@id']);
    }

    public function testAggregateRatingRequiresPlacesDetailsSource(): void
    {
        $this->assertNull(seo_runtime_schema_aggregate_rating_from_gbp_data([
            'source' => 'manual_seed',
            'rating' => 5,
            'user_ratings_total' => 270,
        ]));
        $this->assertNull(seo_runtime_schema_aggregate_rating_from_gbp_data([
            'source' => 'places_details',
            'rating' => 0,
            'user_ratings_total' => 10,
        ]));

        $rating = seo_runtime_schema_aggregate_rating_from_gbp_data([
            'source' => 'places_details',
            'rating' => 4.8,
            'user_ratings_total' => 125,
        ]);
        $this->assertSame('4.8', $rating['ratingValue']);
        $this->assertSame(125, $rating['reviewCount']);
    }

    public function testReviewRequiresVisibleBodyReviewerAndLinksToService(): void
    {
        $GLOBALS['cs'] = [
            'baslik' => 'Bornova Evden Eve Taşıma',
            'icerik' => 'Evden eve nakliyat süreci.',
            'musteri_yorumu' => 'Taşıma planlandığı gibi tamamlandı.',
            'musteri_ad' => 'A. Y.',
            'puan' => null,
        ];
        $node = seo_runtime_schema_case_study_review_node(
            self::ORIGIN . '/musteri-hikayesi',
            self::ORIGIN . '/#organization'
        );
        $this->assertIsArray($node);
        $this->assertSame(self::ORIGIN . '/izmir-evden-eve-nakliyat#service', $node['itemReviewed']['@id']);
        $this->assertArrayNotHasKey('reviewRating', $node);

        $GLOBALS['cs']['musteri_ad'] = '';
        $this->assertNull(seo_runtime_schema_case_study_review_node(
            self::ORIGIN . '/musteri-hikayesi',
            self::ORIGIN . '/#organization'
        ));
        unset($GLOBALS['cs']);
    }
}
