<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class EntityGraphSchemaTest extends TestCase
{
    private const ORIGIN = 'https://www.mynakliyat.com.tr';

    protected function setUp(): void
    {
        if (!defined('SITE_URL')) {
            define('SITE_URL', self::ORIGIN);
        }
        require_once PROJECT_ROOT . '/includes/seo_runtime.php';
    }

    /** @return array<string,mixed> */
    private function serviceGraph(string $slug, string $graphSlug): array
    {
        $canonical = self::ORIGIN . '/' . $slug;
        $pipeline = [
            'page_type' => 'service',
            'jsonld_type_set' => ['emit_moving_company_inline' => true, 'breadcrumb' => true],
            'internal_link_context' => ['graph_key' => $graphSlug, 'anchor_slug' => $graphSlug],
            'location_vector' => ['area_served_mode' => 'metro_districts'],
        ];
        $flex = [
            'service_name' => 'İzmir Evden Eve Nakliyat',
            'service_slug' => $slug,
            'service_description' => 'Profesyonel nakliyat hizmeti.',
        ];
        $html = schema_factory(
            'service',
            $pipeline,
            $flex,
            $canonical,
            self::ORIGIN,
            [],
            self::ORIGIN . '/#organization',
            ['slug' => $slug],
            null,
            ['relPath' => $slug]
        );

        $this->assertSame(1, substr_count($html, 'application/ld+json'));
        preg_match('#<script[^>]*>(.*)</script>#s', $html, $matches);
        $decoded = json_decode(trim((string) ($matches[1] ?? '')), true);
        $this->assertIsArray($decoded);
        $this->assertSame('https://schema.org', $decoded['@context'] ?? null);
        $this->assertIsArray($decoded['@graph'] ?? null);

        return $decoded;
    }

    /** @param list<array<string,mixed>> $nodes @return array<string,mixed> */
    private function nodeById(array $nodes, string $id): array
    {
        foreach ($nodes as $node) {
            if (($node['@id'] ?? null) === $id) {
                return $node;
            }
        }
        $this->fail('Entity graph node not found: ' . $id);
    }

    public function testTurkishEntityIdsAreStableAsciiSlugs(): void
    {
        $this->assertSame('izmir', seo_runtime_schema_entity_slug('İzmir'));
        $this->assertSame('karsiyaka', seo_runtime_schema_entity_slug('Karşıyaka'));
        $this->assertSame('sirnak', seo_runtime_schema_entity_slug('Şırnak'));
    }

    public function testServiceGraphUsesCanonicalIdsAndConnectedNodes(): void
    {
        $decoded = $this->serviceGraph('izmir-evden-eve-nakliyat', 'izmir-evden-eve-nakliyat');
        $nodes = $decoded['@graph'];
        $organization = $this->nodeById($nodes, self::ORIGIN . '/#organization');
        $service = $this->nodeById($nodes, self::ORIGIN . '/izmir-evden-eve-nakliyat#service');
        $faq = $this->nodeById($nodes, self::ORIGIN . '/izmir-evden-eve-nakliyat#faq');
        $webpage = $this->nodeById($nodes, self::ORIGIN . '/izmir-evden-eve-nakliyat#webpage');

        $this->nodeById($nodes, self::ORIGIN . '/#brand');
        $this->nodeById($nodes, self::ORIGIN . '/#website');
        $this->nodeById($nodes, self::ORIGIN . '/#place-izmir');
        $this->nodeById($nodes, self::ORIGIN . '/#place-izmir-karsiyaka');
        $this->nodeById($nodes, self::ORIGIN . '/izmir-evden-eve-nakliyat#breadcrumb');

        $this->assertContains('https://www.wikidata.org/wiki/Q140273727', $organization['sameAs']);
        $this->assertSame(
            seo_runtime_service_quick_answer('izmir-evden-eve-nakliyat'),
            $service['description']
        );
        $this->assertSame(['@id' => self::ORIGIN . '/#organization'], $service['provider']);
        $this->assertSame(['@id' => self::ORIGIN . '/#brand'], $service['brand']);
        $this->assertContains('İzmir Nakliyat', $service['alternateName']);
        $this->assertSame(['@id' => $service['@id']], $webpage['mainEntity']);
        $this->assertSame(['@id' => $service['@id']], $faq['about']);
        $this->assertCount(4, $service['subjectOf']);
        $this->assertContains(['@id' => self::ORIGIN . '/izmir-evden-eve-nakliyat#faq'], $service['subjectOf']);
        $this->assertContains(
            ['@id' => self::ORIGIN . '/evden-eve-nakliyat-adim-adim-tasinma-rehberi#article'],
            $service['subjectOf']
        );
        $this->assertStringNotContainsString('#mynak-moving-company', json_encode($decoded));
    }

    public function testPrimaryServiceApiRowsUseTheSameQuickAnswerAndEntityId(): void
    {
        $rows = seo_runtime_primary_services_api_rows(self::ORIGIN);
        $row = null;
        foreach ($rows as $candidate) {
            if (($candidate['public_slug'] ?? '') === 'izmir-evden-eve-nakliyat') {
                $row = $candidate;
                break;
            }
        }

        $this->assertIsArray($row);
        $this->assertSame(
            seo_runtime_service_quick_answer('izmir-evden-eve-nakliyat'),
            $row['quick_answer']
        );
        $this->assertSame(
            self::ORIGIN . '/izmir-evden-eve-nakliyat#service',
            $row['entity_id']
        );
    }

    public function testIntercityServiceGraphCreatesAllProvinceEntities(): void
    {
        $decoded = $this->serviceGraph('sehirler-arasi-nakliyat', 'sehirler-arasi-nakliyat');
        $nodes = $decoded['@graph'];
        $cities = array_values(array_filter($nodes, static function (array $node): bool {
            $types = is_array($node['@type'] ?? null) ? $node['@type'] : [($node['@type'] ?? '')];
            return in_array('City', $types, true);
        }));
        $service = $this->nodeById($nodes, self::ORIGIN . '/sehirler-arasi-nakliyat#service');

        $this->assertCount(81, $cities);
        $this->nodeById($nodes, self::ORIGIN . '/#place-izmir');
        $this->nodeById($nodes, self::ORIGIN . '/#place-istanbul');
        $this->nodeById($nodes, self::ORIGIN . '/#place-sanliurfa');
        $this->assertCount(81, $service['areaServed']);
    }

    public function testGeographicEntitiesRetainSpecificTypesAndDeclarePlace(): void
    {
        $decoded = $this->serviceGraph('izmir-evden-eve-nakliyat', 'izmir-evden-eve-nakliyat');
        $nodes = $decoded['@graph'];
        $city = $this->nodeById($nodes, self::ORIGIN . '/#place-izmir');
        $district = $this->nodeById($nodes, self::ORIGIN . '/#place-izmir-karsiyaka');
        $country = $this->nodeById($nodes, self::ORIGIN . '/#place-turkiye');

        $this->assertSame(['Place', 'City'], $city['@type']);
        $this->assertSame(['Place', 'AdministrativeArea'], $district['@type']);
        $this->assertSame(['Place', 'Country'], $country['@type']);
    }

    public function testHomeGraphPublishesPrimaryServiceEntities(): void
    {
        $pipeline = [
            'page_type' => 'home',
            'jsonld_type_set' => ['emit_moving_company_inline' => true, 'website_on_home' => true],
            'internal_link_context' => ['graph_key' => '', 'anchor_slug' => ''],
            'location_vector' => ['area_served_mode' => 'metro_districts'],
        ];
        $html = schema_factory(
            'home',
            $pipeline,
            [],
            self::ORIGIN . '/',
            self::ORIGIN,
            [],
            self::ORIGIN . '/#organization',
            null,
            null,
            ['relPath' => '/']
        );
        preg_match('#<script[^>]*>(.*)</script>#s', $html, $matches);
        $decoded = json_decode(trim((string) ($matches[1] ?? '')), true);
        $nodes = $decoded['@graph'];

        $ids = array_column($nodes, '@id');
        $this->assertContains(self::ORIGIN . '/#webpage', $ids);
        $this->assertContains(self::ORIGIN . '/izmir-evden-eve-nakliyat#service', $ids);
        $this->assertContains(self::ORIGIN . '/sehirler-arasi-nakliyat#service', $ids);
        $this->assertContains(self::ORIGIN . '/kurumsal-nakliye-hizmetleri#service', $ids);
        $this->assertContains(self::ORIGIN . '/esya-depolama#service', $ids);
        $this->assertContains(self::ORIGIN . '/asansorlu-nakliyat#service', $ids);
    }
}
