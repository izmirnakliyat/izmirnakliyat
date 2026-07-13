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

    private function renderLlms(string $uri): string
    {
        $path = PROJECT_ROOT . '/llms.php';
        $code = 'define("SITE_URL", "https://www.mynakliyat.com.tr");'
            . ' $_SERVER["REQUEST_URI"] = ' . var_export($uri, true) . ';'
            . ' ob_start(); require ' . var_export($path, true) . '; echo ob_get_clean();';
        exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($code), $output, $status);
        $this->assertSame(0, $status, implode("\n", $output));

        return implode("\n", $output);
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
            $wordCount = count(preg_split('/\s+/u', trim($answer)) ?: []);
            $this->assertNotSame('', $answer, $slug);
            $this->assertGreaterThanOrEqual(40, $wordCount, $slug);
            $this->assertLessThanOrEqual(70, $wordCount, $slug);
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
        $faq = seo_runtime_service_generated_faq_html(
            'izmir-evden-eve-nakliyat',
            '<h2>İzmir evden eve nakliyat fiyatı nasıl belirlenir?</h2><p>Eski içerik cevabı.</p>'
        );

        $this->assertStringContainsString('class="mynak-answer-box', $quickAnswer);
        $this->assertStringContainsString('Kısa Cevap', $quickAnswer);
        $this->assertStringContainsString('<section class="mynak-service-faq', $faq);
        $this->assertSame(6, substr_count($faq, '<details'));
    }

    public function testPublishedFaqSetIsSharedByVisibleAndMachineReadableOutputs(): void
    {
        $pairs = seo_runtime_service_published_faq_pairs('antika-piyano-tasimaciligi');
        $html = seo_runtime_service_generated_faq_html('antika-piyano-tasimaciligi');

        $this->assertCount(6, $pairs);
        $this->assertCount(6, seo_runtime_service_published_faq_pairs('antika-ve-piyano-tasima'));
        $this->assertSame(6, substr_count($html, '<details'));
        foreach ($pairs as $pair) {
            $this->assertStringContainsString(
                htmlspecialchars($pair['question'], ENT_QUOTES, 'UTF-8'),
                $html
            );
        }
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

    public function testEachCanonicalServicePublishesThreeToFiveCuratedGuides(): void
    {
        $definitions = mynak_service_guide_hub_definitions();
        foreach (seo_runtime_canonical_service_definitions() as $service) {
            $graphSlug = mynak_service_guide_graph_slug($service['slug']);
            $this->assertArrayHasKey($graphSlug, $definitions, $service['slug']);
            $guides = $definitions[$graphSlug]['guides'];
            $html = mynak_service_guide_hub_html($service['slug']);
            $refs = mynak_service_guide_article_refs('https://www.mynakliyat.com.tr', $service['slug']);
            $this->assertGreaterThanOrEqual(3, count($guides), $service['slug']);
            $this->assertLessThanOrEqual(5, count($guides), $service['slug']);
            $this->assertSame(count($guides), substr_count($html, '<a href='));
            $this->assertCount(count($guides), $refs);
            $guideSlugs = array_column($guides, 'slug');
            $this->assertSame($guideSlugs, array_values(array_unique($guideSlugs)), $service['slug']);
            foreach ($refs as $ref) {
                $this->assertStringEndsWith('#article', $ref['@id']);
            }
        }
    }

    public function testFourTopicHubsUseThreeToFiveUniqueGuides(): void
    {
        $hubs = mynak_topic_hub_definitions();
        $this->assertSame(
            ['tasinma-rehberi', 'nakliyat-rehberi', 'paketleme-rehberi', 'nakliyat-fiyat-rehberi'],
            array_keys($hubs)
        );
        foreach ($hubs as $key => $hub) {
            $guideSlugs = array_column($hub['guides'], 'slug');
            $this->assertGreaterThanOrEqual(3, count($guideSlugs), $key);
            $this->assertLessThanOrEqual(5, count($guideSlugs), $key);
            $this->assertSame($guideSlugs, array_values(array_unique($guideSlugs)), $key);
        }
    }

    public function testTopicHubLinksRelatedGuidesWithoutSelfLink(): void
    {
        $blog = ['slug' => 'evden-eve-nakliyat-adim-adim-tasinma-rehberi'];
        $html = mynak_blog_topic_hub_html($blog);

        $this->assertStringContainsString('Taşınma Rehberi', $html);
        $this->assertStringNotContainsString('/evden-eve-nakliyat-adim-adim-tasinma-rehberi', $html);
        $this->assertSame(3, substr_count($html, '<li'));
    }

    public function testLlmsIndexPublishesStableEntityReferences(): void
    {
        $output = $this->renderLlms('/llms.txt');

        foreach (['/#organization', '/#brand', '/#website', 'Q140273727', '/api/v1/entities.json'] as $reference) {
            $this->assertStringContainsString($reference, $output);
        }
    }

    public function testLlmsCorpusUsesDistinctMachineReadableHeading(): void
    {
        $output = $this->renderLlms('/llms-corpus.txt');

        $this->assertStringStartsWith('# LLMS CORPUS', $output);
        $this->assertStringContainsString('## MY Nakliyat İçerik Corpus’u', $output);
    }

    public function testCanonicalServiceDefinitionsContainNoAliasUrls(): void
    {
        $definitions = seo_runtime_canonical_service_definitions();
        $slugs = array_column($definitions, 'slug');

        $this->assertCount(11, $slugs);
        $this->assertSame($slugs, array_values(array_unique($slugs)));
        $this->assertNotContains('sehirlerarasi-nakliyat', $slugs);
        $this->assertNotContains('izmir-evden-eve-nakliyat-hizmeti', $slugs);
        $this->assertNotContains('kurumsal-nakliye-ofis-tasima', $slugs);
    }

    public function testCanonicalServiceAliasesResolveToFinalPublicUrls(): void
    {
        $this->assertSame('kurumsal-nakliye-hizmetleri', seo_rt_public_url_slug_for_graph_slug('izmir-ofis-tasimaciligi'));
        $this->assertSame('kurumsal-nakliye-hizmetleri', mynak_api_canonical_public_slug('kurumsal-nakliye-ofis-tasima'));
        $this->assertSame('sehirler-arasi-nakliyat', mynak_api_canonical_public_slug('sehirlerarasi-nakliyat'));
        $this->assertSame('izmir-evden-eve-nakliyat', mynak_api_canonical_public_slug('izmir-evden-eve-nakliyat-hizmeti'));
    }

    public function testSpecializedGuidesLinkToTheirCanonicalServices(): void
    {
        $mobile = mynak_blog_service_context([
            'slug' => 'izmir-mobil-asansor-kiralama-fiyatlari',
            'baslik' => 'İzmir Mobil Asansör Kiralama Fiyatları',
        ]);
        $ceyiz = mynak_blog_service_context([
            'slug' => 'izmir-ceyiz-tasima-rehberi',
            'baslik' => 'İzmir Çeyiz Taşıma Rehberi',
        ]);

        $this->assertSame('mobil-asansor-kiralama', $mobile['graph_slug']);
        $this->assertSame('mobil-asansor-kiralama', $mobile['service_slug']);
        $this->assertSame('parca-esya-tasima', $ceyiz['graph_slug']);
        $this->assertSame('parca-esya-tasima', $ceyiz['service_slug']);
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
