<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ServiceGeoContentTest extends TestCase
{
    protected function setUp(): void
    {
        require_once PROJECT_ROOT . '/includes/seo_runtime/faq_extractor.php';
        require_once PROJECT_ROOT . '/includes/seo_runtime/default_service_faqs.php';
        require_once PROJECT_ROOT . '/includes/seo_runtime/location_internal_linking.php';
        require_once PROJECT_ROOT . '/includes/seo_runtime/service_guide_hubs.php';
        require_once PROJECT_ROOT . '/includes/seo_runtime/paths.php';
        require_once PROJECT_ROOT . '/includes/handlers/api_v1.php';
    }

    public function testEveryPublishedServiceDefinitionHasQuickAnswerAndFiveToTenFaqs(): void
    {
        $slugs = [
            'izmir-evden-eve-nakliyat',
            'sehirler-arasi-nakliyat',
            'kurumsal-nakliye-ofis-tasima',
            'kurumsal-nakliye-hizmetleri',
            'parca-esya-tasima',
            'asansorlu-nakliyat',
            'sepetli-vinc-kiralama',
            'mobil-asansor-kiralama',
            'esya-depolama',
            'antika-piyano-tasimaciligi',
            'mobilya-montaj-kurulum',
            'sehir-ici-nakliyat',
        ];

        foreach ($slugs as $slug) {
            $answer = seo_runtime_service_quick_answer($slug);
            $faqs = seo_runtime_default_faq_pairs_for_service_slug($slug);
            $this->assertNotSame('', $answer, $slug);
            $this->assertGreaterThanOrEqual(5, count($faqs), $slug);
            $this->assertLessThanOrEqual(10, count($faqs), $slug);
        }
    }

    public function testVisibleFaqsAreMergedWithoutDuplicateQuestions(): void
    {
        $visible = '<h2>Asansörlü nakliyat nedir ve ne zaman gerekir?</h2>'
            . '<p>Yüksek katlarda dış cephe asansörüyle yapılan taşıma yöntemidir.</p>';
        $pairs = seo_runtime_service_faq_pairs('asansorlu-nakliyat', $visible);
        $questions = array_column($pairs, 'question');
        $normalized = array_map(static fn(string $question): string => mb_strtolower($question, 'UTF-8'), $questions);

        $this->assertGreaterThanOrEqual(5, count($pairs));
        $this->assertSame(count($normalized), count(array_unique($normalized)));
        $this->assertSame('Yüksek katlarda dış cephe asansörüyle yapılan taşıma yöntemidir.', $pairs[0]['answer']);
    }

    public function testGeneratedHtmlUsesSemanticQuickAnswerAndFaqSections(): void
    {
        $quickAnswer = seo_runtime_service_quick_answer_html('izmir-evden-eve-nakliyat');
        $faq = seo_runtime_service_generated_faq_html('izmir-evden-eve-nakliyat');

        $this->assertStringContainsString('class="mynak-answer-box', $quickAnswer);
        $this->assertStringContainsString('Kısa Cevap', $quickAnswer);
        $this->assertStringContainsString('<section class="mynak-service-faq', $faq);
        $this->assertSame(6, substr_count($faq, '<details'));
    }

    public function testDistrictPagesBuildAConnectedContextualLinkGraph(): void
    {
        $labels = mynak_district_page_labels();
        foreach ($labels as $slug => $label) {
            $targets = mynak_related_district_slugs($slug);
            $this->assertCount(6, $targets, $label);
            $this->assertNotContains($slug, $targets);
            foreach ($targets as $target) {
                $this->assertArrayHasKey($target, $labels);
            }
            $html = mynak_location_internal_links_html($slug);
            $this->assertStringContainsString('Yakın Bölgelerde Nakliyat Hizmetleri', $html);
            $this->assertSame(7, substr_count($html, '<a href='));
        }
    }

    public function testCityPairPagesLinkToEverySiblingRouteAndTheIntercityHub(): void
    {
        foreach (mynak_city_pair_page_labels() as $slug => $label) {
            $html = mynak_location_internal_links_html($slug);
            $this->assertStringContainsString('Popüler Taşıma Rotaları', $html, $label);
            $this->assertSame(5, substr_count($html, '<a href='));
            $this->assertStringNotContainsString('href="/' . $slug . '"', $html);
            $this->assertStringContainsString('sehirler-arasi-nakliyat', $html);
        }
    }

    public function testServiceGuideHubsPublishThreeCuratedArticles(): void
    {
        foreach (mynak_service_guide_hub_definitions() as $graphSlug => $definition) {
            $html = mynak_service_guide_hub_html($graphSlug);
            $refs = mynak_service_guide_article_refs('https://www.mynakliyat.com.tr', $graphSlug);
            $this->assertStringContainsString((string) $definition['service_name'], $html);
            $this->assertSame(3, substr_count($html, '<a href='));
            $this->assertCount(3, $refs);
            foreach ($refs as $ref) {
                $this->assertStringEndsWith('#article', $ref['@id']);
            }
        }
    }

    public function testCanonicalServiceAliasesResolveToFinalPublicUrls(): void
    {
        $this->assertSame('kurumsal-nakliye-hizmetleri', seo_rt_public_url_slug_for_graph_slug('izmir-ofis-tasimaciligi'));
        $this->assertSame('kurumsal-nakliye-hizmetleri', mynak_api_canonical_public_slug('kurumsal-nakliye-ofis-tasima'));
        $this->assertSame('sehirler-arasi-nakliyat', mynak_api_canonical_public_slug('sehirlerarasi-nakliyat'));
        $this->assertSame('izmir-evden-eve-nakliyat', mynak_api_canonical_public_slug('izmir-evden-eve-nakliyat-hizmeti'));
    }

    public function testBlogGuideLinksBackToItsPrimaryService(): void
    {
        $blog = [
            'slug' => 'ofis-tasima-oncesi-checklist-islerinizi-aksatmadan-tasinin',
            'baslik' => 'Ofis Taşıma Öncesi Checklist',
        ];
        $context = mynak_blog_service_context($blog);
        $html = mynak_blog_related_service_html($blog);

        $this->assertSame('izmir-ofis-tasimaciligi', $context['graph_slug']);
        $this->assertStringContainsString('kurumsal-nakliye-hizmetleri', $html);
        $this->assertStringContainsString('Bu rehberin ilgili hizmeti', $html);
    }
}
