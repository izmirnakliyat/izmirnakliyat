<?php
declare(strict_types=1);

/**
 * /api/v1/* — Public read-only JSON API.
 *
 * LLM bot'ların (ChatGPT, Claude, Perplexity vb.) ve diğer otomasyonların
 * sitenin yapılı verilerine programatik erişimi için kullanılır. Tüm
 * çıktılar 30 dk dosya cache'ine alınır.
 *
 * Endpoint'ler:
 *   /api/v1/manifest.json
 *   /api/v1/organization.json
 *   /api/v1/services.json
 *   /api/v1/entities.json
 *   /api/v1/blog.json
 *   /api/v1/blog/{slug}.json
 *   /api/v1/authors.json
 *   /api/v1/locations.json
 *
 * Çıktı: `application/json` + Access-Control-Allow-Origin: *.
 * Google indexlemesin diye `X-Robots-Tag: noindex, follow`.
 */

if (defined('MYNAK_API_V1_LOADED')) {
    return;
}
define('MYNAK_API_V1_LOADED', true);

function mynak_api_dispatch(mysqli $conn, string $slug): bool
{
    // Beklenen format: api/v1/...
    if (strpos($slug, 'api/v1') !== 0) {
        return false;
    }
    $rest = trim(substr($slug, 6), "/ \t");
    // İzin verilen son ek .json (alternatif: .txt yok)
    if (substr($rest, -5) === '.json') {
        $rest = substr($rest, 0, -5);
    }
    $rest = strtolower($rest);

    if ($rest === '' || $rest === 'manifest') {
        return mynak_api_emit_manifest();
    }
    if ($rest === 'organization' || $rest === 'org') {
        return mynak_api_emit_organization($conn);
    }
    if ($rest === 'services') {
        return mynak_api_emit_services($conn);
    }
    if ($rest === 'entities' || $rest === 'entity-graph') {
        return mynak_api_emit_entities($conn);
    }
    if ($rest === 'blog') {
        return mynak_api_emit_blog_list($conn);
    }
    if (strpos($rest, 'blog/') === 0) {
        $bs = trim(substr($rest, 5), "/ \t");
        if ($bs !== '') {
            return mynak_api_emit_blog_single($conn, $bs);
        }
    }
    if ($rest === 'authors') {
        return mynak_api_emit_authors($conn);
    }
    if ($rest === 'locations') {
        return mynak_api_emit_locations();
    }

    return mynak_api_emit_404($rest);
}

/* ============================================================ */
/* Helpers                                                       */
/* ============================================================ */

function mynak_api_site_url(): string
{
    return defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
}

function mynak_api_send_json(array $payload, int $status = 200, int $ttl = 1800): void
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8', true);
        header('Access-Control-Allow-Origin: *', true);
        header('Access-Control-Allow-Methods: GET, OPTIONS', true);
        header('X-Robots-Tag: noindex, follow', true);
        if ($ttl > 0) {
            header('Cache-Control: public, max-age=' . $ttl);
        }
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

function mynak_api_cache_dir(): string
{
    $base = defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 2);
    $dir = $base . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'v1';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

function mynak_api_cache_read(string $key, int $ttl = 1800): ?array
{
    $path = mynak_api_cache_dir() . DIRECTORY_SEPARATOR . preg_replace('/[^a-z0-9_-]/i', '_', $key) . '.json';
    if (!is_readable($path)) {
        return null;
    }
    if ((time() - (int) @filemtime($path)) > $ttl) {
        return null;
    }
    $data = json_decode((string) @file_get_contents($path), true);
    return is_array($data) ? $data : null;
}

function mynak_api_cache_write(string $key, array $payload): void
{
    $path = mynak_api_cache_dir() . DIRECTORY_SEPARATOR . preg_replace('/[^a-z0-9_-]/i', '_', $key) . '.json';
    @file_put_contents($path, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function mynak_api_strip_html_excerpt(string $html, int $max = 280): string
{
    $t = strip_tags($html);
    $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $t = (string) preg_replace('/\s+/u', ' ', $t);
    $t = trim($t);
    if ($t === '') {
        return '';
    }
    if (mb_strlen($t, 'UTF-8') > $max) {
        $t = rtrim(mb_substr($t, 0, $max - 3, 'UTF-8')) . '...';
    }
    return $t;
}

function mynak_api_canonical_public_slug(string $slug): string
{
    $slug = trim($slug, '/');
    if (!function_exists('mynak_seo_cannibalization_redirect_map')) {
        require_once dirname(__DIR__) . '/mynak_canonical_slug_redirects.php';
    }
    $map = mynak_seo_cannibalization_redirect_map() + [
        'sehirlerarasi-nakliyat' => 'sehirler-arasi-nakliyat',
        'antika-ve-piyano-tasima' => 'antika-piyano-tasimaciligi',
        'kurumsal-nakliye-ofis-tasima' => 'kurumsal-nakliye-hizmetleri',
    ];
    $seen = [];
    while (isset($map[$slug]) && !isset($seen[$slug])) {
        $seen[$slug] = true;
        $slug = (string) $map[$slug];
    }

    return $slug;
}

function mynak_api_word_count(string $html): int
{
    $t = trim(strip_tags($html));
    if ($t === '') {
        return 0;
    }
    $words = preg_split('/\s+/u', $t);
    return is_array($words) ? count(array_filter($words)) : 0;
}

/**
 * @return array<string, string>
 */
function mynak_api_load_settings(mysqli $conn): array
{
    $settings = [];
    $r = $conn->query('SELECT name, value FROM settings');
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $settings[(string) $row['name']] = (string) $row['value'];
        }
    }

    return $settings;
}

/* ============================================================ */
/* Endpoint'ler                                                  */
/* ============================================================ */

function mynak_api_emit_manifest(): bool
{
    $base = mynak_api_site_url();
    if (!function_exists('seo_runtime_primary_services_api_rows')) {
        require_once dirname(__DIR__) . '/seo_runtime/jsonld_encode_and_schema.php';
    }
    $payload = [
        'name' => 'MY Nakliyat Public Data API',
        'version' => '1.1',
        'description' => 'Mynakliyat.com.tr için yapılı veri okuma uçları. LLM/AI ve otomasyon kullanımı içindir.',
        'license' => 'Read-only public data, attribution requested.',
        'contact' => 'info@mynakliyat.com.tr',
        'rate_limit' => 'Hafif: 30 dk dosya cache uygulanır; rate limit yok.',
        'primary_service_lines' => seo_runtime_primary_services_api_rows($base),
        'endpoints' => [
            ['path' => '/api/v1/manifest.json', 'description' => 'Bu manifest dosyası.'],
            ['path' => '/api/v1/organization.json', 'description' => 'Kuruluş bilgileri (Schema.org MovingCompany — tam şema).'],
            ['path' => '/api/v1/services.json', 'description' => 'Tüm aktif hizmetler (kanonik URL ve primary bayrağı ile).'],
            ['path' => '/api/v1/entities.json', 'description' => 'Organization, Brand, Service ve Place düğümlerinden oluşan bağlı @graph.'],
            ['path' => '/api/v1/blog.json', 'description' => 'Yayınlanmış blog yazıları (özet).'],
            ['path' => '/api/v1/blog/{slug}.json', 'description' => 'Tek bir blog yazısının tam içeriği.'],
            ['path' => '/api/v1/authors.json', 'description' => 'İçerik yazarları (Schema.org Person).'],
            ['path' => '/api/v1/locations.json', 'description' => 'Hizmet verilen İzmir ilçeleri ve şehir listesi.'],
        ],
        'related' => [
            'sitemap' => $base . '/sitemap.xml',
            'robots' => $base . '/robots.txt',
            'llms' => $base . '/llms.txt',
            'llms_corpus' => $base . '/llms-corpus.txt',
            'llms_full_tr' => $base . '/llms-full-tr.txt',
        ],
        'generated_at' => gmdate('c'),
    ];
    mynak_api_send_json($payload, 200, 86400);
    return true;
}

function mynak_api_emit_organization(mysqli $conn): bool
{
    $cached = mynak_api_cache_read('organization', 3600);
    if ($cached !== null) {
        mynak_api_send_json($cached);
        return true;
    }

    require_once dirname(__DIR__) . '/seo_runtime/jsonld_encode_and_schema.php';
    if (!function_exists('canonical_seo_pipeline_location_vector')) {
        require_once dirname(__DIR__) . '/seo_runtime/pipeline_page_type.php';
    }

    $settings = mynak_api_load_settings($conn);
    $base = mynak_api_site_url();
    $orgId = rtrim($base, '/') . '/#organization';
    $locVec = canonical_seo_pipeline_location_vector('global');
    $graph = schema_factory_build_moving_company_graph($settings, $base, $locVec, $orgId);
    $graph['@id'] = $orgId;
    $graph['@type'] = ['MovingCompany', 'LocalBusiness'];

    $payload = $graph;
    $payload['api_version'] = '1.1';
    $payload['primary_services'] = seo_runtime_primary_services_api_rows($base);
    $payload['generated_at'] = gmdate('c');

    mynak_api_cache_write('organization', $payload);
    mynak_api_send_json($payload);
    return true;
}

function mynak_api_emit_entities(mysqli $conn): bool
{
    $cached = mynak_api_cache_read('entities', 3600);
    if ($cached !== null) {
        mynak_api_send_json($cached, 200, 3600);
        return true;
    }

    require_once dirname(__DIR__) . '/seo_runtime/jsonld_encode_and_schema.php';
    require_once dirname(__DIR__) . '/seo_runtime/default_service_faqs.php';
    require_once dirname(__DIR__) . '/seo_runtime/service_guide_hubs.php';
    if (!function_exists('canonical_seo_pipeline_location_vector')) {
        require_once dirname(__DIR__) . '/seo_runtime/pipeline_page_type.php';
    }

    $settings = mynak_api_load_settings($conn);
    $base = mynak_api_site_url();
    $organizationId = seo_runtime_schema_organization_id($base);
    $locationVector = canonical_seo_pipeline_location_vector('global');
    $organization = schema_factory_build_moving_company_graph($settings, $base, $locationVector, $organizationId);
    unset($organization['@context']);
    $organization['@id'] = $organizationId;
    $website = seo_runtime_schema_website_home_graph($base, $settings, $organizationId);
    unset($website['@context']);

    $services = seo_runtime_schema_primary_service_nodes($base, $organizationId);
    $serviceIds = array_values(array_filter(array_map(
        static fn(array $node): string => (string) ($node['@id'] ?? ''),
        $services
    )));
    $contentNodes = [];
    foreach ($services as $index => $service) {
        $graphSlug = seo_runtime_schema_graph_slug_from_pipeline([], (string) ($service['url'] ?? ''));
        foreach (seo_rt_primary_service_lines() as $line) {
            if ((string) $line['public_slug'] === trim((string) parse_url((string) ($service['url'] ?? ''), PHP_URL_PATH), '/')) {
                $graphSlug = (string) $line['graph_slug'];
                break;
            }
        }
        $related = [];
        foreach ($serviceIds as $serviceId) {
            if ($serviceId !== (string) ($service['@id'] ?? '')) {
                $related[] = ['@id' => $serviceId];
            }
        }
        if ($related !== []) {
            $services[$index]['isRelatedTo'] = $related;
        }
        $serviceUrl = (string) ($service['url'] ?? '');
        $serviceId = (string) ($service['@id'] ?? '');
        $faqId = rtrim($serviceUrl, '/') . '#faq';
        $subjectRefs = [['@id' => $faqId]];
        $guideRefs = mynak_service_guide_article_refs($base, $graphSlug);
        $subjectRefs = array_merge($subjectRefs, $guideRefs);
        $services[$index]['subjectOf'] = $subjectRefs;
        $services[$index]['mainEntityOfPage'] = ['@id' => rtrim($serviceUrl, '/') . '#webpage'];

        $faqEntities = [];
        foreach (seo_runtime_service_published_faq_pairs($graphSlug) as $faq) {
            $faqEntities[] = [
                '@type' => 'Question',
                'name' => (string) $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => (string) $faq['answer'],
                ],
            ];
        }
        if ($faqEntities !== []) {
            $contentNodes[] = [
                '@type' => 'FAQPage',
                '@id' => $faqId,
                'url' => $serviceUrl . '#sss',
                'about' => ['@id' => $serviceId],
                'mainEntity' => $faqEntities,
            ];
        }

        $breadcrumbId = rtrim($serviceUrl, '/') . '#breadcrumb';
        $contentNodes[] = [
            '@type' => 'BreadcrumbList',
            '@id' => $breadcrumbId,
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => $base . '/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => (string) ($service['name'] ?? ''), 'item' => $serviceUrl],
            ],
        ];
        $contentNodes[] = [
            '@type' => 'WebPage',
            '@id' => rtrim($serviceUrl, '/') . '#webpage',
            'url' => $serviceUrl,
            'name' => (string) ($service['name'] ?? ''),
            'isPartOf' => ['@id' => $base . '/#website'],
            'about' => ['@id' => $serviceId],
            'breadcrumb' => ['@id' => $breadcrumbId],
            'speakable' => [
                '@type' => 'SpeakableSpecification',
                'cssSelector' => ['.mynak-answer-box', '.mynak-service-faq'],
            ],
        ];

        $guideDefinition = mynak_service_guide_hub_definitions()[$graphSlug] ?? null;
        if (is_array($guideDefinition)) {
            foreach ($guideDefinition['guides'] as $guide) {
                $articleUrl = $base . '/' . (string) $guide['slug'];
                $contentNodes[] = [
                    '@type' => 'Article',
                    '@id' => $articleUrl . '#article',
                    'url' => $articleUrl,
                    'headline' => (string) $guide['title'],
                    'about' => ['@id' => $serviceId],
                    'publisher' => ['@id' => $organizationId],
                    'isPartOf' => ['@id' => $base . '/#website'],
                ];
            }
        }
    }

    $nodes = array_merge(
        [$organization, seo_runtime_schema_brand_node($base), $website],
        $services,
        $contentNodes,
        seo_runtime_schema_place_nodes_for_page($base, 'izmir-evden-eve-nakliyat', $settings, $locationVector)
    );
    $seen = [];
    $graph = [];
    foreach ($nodes as $node) {
        $id = (string) ($node['@id'] ?? '');
        if ($id !== '' && isset($seen[$id])) {
            continue;
        }
        if ($id !== '') {
            $seen[$id] = true;
        }
        $graph[] = $node;
    }

    $payload = [
        '@context' => 'https://schema.org',
        '@graph' => $graph,
    ];
    mynak_api_cache_write('entities', $payload);
    mynak_api_send_json($payload, 200, 3600);
    return true;
}

function mynak_api_emit_services(mysqli $conn): bool
{
    $cached = mynak_api_cache_read('services_v12', 1800);
    if ($cached !== null) {
        mynak_api_send_json($cached);
        return true;
    }

    $base = mynak_api_site_url();
    if (!function_exists('seo_runtime_primary_services_api_rows')) {
        require_once dirname(__DIR__) . '/seo_runtime/jsonld_encode_and_schema.php';
    }
    if (!function_exists('seo_runtime_service_quick_answer')) {
        require_once dirname(__DIR__) . '/seo_runtime/default_service_faqs.php';
    }
    $primaryByPublicSlug = [];
    foreach (seo_runtime_primary_services_api_rows($base) as $primaryRow) {
        $primaryByPublicSlug[(string) $primaryRow['public_slug']] = $primaryRow;
    }

    $itemsByCanonicalSlug = [];
    $r = $conn->query("SELECT slug, ana_baslik, ust_baslik, aciklama, meta_description, focus_keyword, icerik, foto, order_number, created_at, updated_at FROM services WHERE status = 1 ORDER BY order_number ASC, ana_baslik ASC");
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $sourceSlug = (string) $row['slug'];
            $slug = mynak_api_canonical_public_slug($sourceSlug);
            $primaryMeta = $primaryByPublicSlug[$slug] ?? null;
            $item = [
                'slug' => $slug,
                'graph_slug' => is_array($primaryMeta) ? (string) ($primaryMeta['graph_slug'] ?? '') : null,
                'primary' => is_array($primaryMeta),
                'service_type' => is_array($primaryMeta) ? (string) ($primaryMeta['service_type'] ?? '') : null,
                'name' => (string) $row['ana_baslik'],
                'tagline' => (string) ($row['ust_baslik'] ?? ''),
                'description' => (string) ($row['meta_description'] ?? $row['aciklama'] ?? ''),
                'excerpt' => mynak_api_strip_html_excerpt((string) ($row['icerik'] ?? $row['aciklama'] ?? ''), 320),
                'word_count' => mynak_api_word_count((string) ($row['icerik'] ?? '')),
                'focus_keyword' => (string) ($row['focus_keyword'] ?? ''),
                'quick_answer' => is_array($primaryMeta)
                    ? (string) ($primaryMeta['quick_answer'] ?? '')
                    : seo_runtime_service_quick_answer($slug),
                'entity_id' => $base . '/' . $slug . '#service',
                'image_url' => !empty($row['foto'])
                    ? $base . '/uploads/services/' . ltrim((string) $row['foto'], '/')
                    : null,
                'url' => $base . '/' . $slug,
                'markdown_url' => $base . '/' . $slug . '?format=markdown',
                'created_at' => (string) ($row['created_at'] ?? ''),
                'updated_at' => (string) ($row['updated_at'] ?? ''),
            ];
            if (!isset($itemsByCanonicalSlug[$slug]) || $sourceSlug === $slug) {
                $itemsByCanonicalSlug[$slug] = $item;
            }
        }
    }
    $items = array_values($itemsByCanonicalSlug);

    $payload = [
        'version' => '1.2',
        'generated_at' => gmdate('c'),
        'count' => count($items),
        'primary_count' => count($primaryByPublicSlug),
        'primary_services' => array_values($primaryByPublicSlug),
        'items' => $items,
    ];
    mynak_api_cache_write('services_v12', $payload);
    mynak_api_send_json($payload);
    return true;
}

function mynak_api_emit_blog_list(mysqli $conn): bool
{
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = max(10, min(200, (int) ($_GET['per_page'] ?? 50)));
    $offset = ($page - 1) * $perPage;

    $cacheKey = 'blog_list_p' . $page . '_n' . $perPage;
    $cached = mynak_api_cache_read($cacheKey, 1800);
    if ($cached !== null) {
        mynak_api_send_json($cached);
        return true;
    }

    $base = mynak_api_site_url();

    $count = 0;
    $cnt = $conn->query("SELECT COUNT(*) AS c FROM blog_posts WHERE durum = 3");
    if ($cnt && $row = $cnt->fetch_assoc()) {
        $count = (int) $row['c'];
    }

    $items = [];
    $sql = "SELECT bp.slug, bp.baslik, bp.seo_title, bp.meta_description, bp.icerik, bp.kapak_foto, bp.etiketler, bp.created_at, bp.updated_at,
                   bc.ad AS kategori_ad, bc.slug AS kategori_slug,
                   a.name AS author_name, a.slug AS author_slug, a.title AS author_title
            FROM blog_posts bp
            LEFT JOIN blog_categories bc ON bp.kategori_id = bc.id
            LEFT JOIN authors a ON bp.author_id = a.id
            WHERE bp.durum = 3
            ORDER BY bp.created_at DESC, bp.id DESC
            LIMIT $perPage OFFSET $offset";
    $r = $conn->query($sql);
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $slug = (string) $row['slug'];
            $wc = mynak_api_word_count((string) ($row['icerik'] ?? ''));
            if ($wc < 220) {
                // Thin content sayfaları API'de de hariç tut (sitemap ile uyumlu)
                continue;
            }
            $items[] = [
                'slug' => $slug,
                'title' => (string) ($row['baslik'] ?? ''),
                'seo_title' => (string) ($row['seo_title'] ?? ''),
                'description' => (string) ($row['meta_description'] ?? ''),
                'excerpt' => mynak_api_strip_html_excerpt((string) ($row['icerik'] ?? ''), 320),
                'word_count' => $wc,
                'image_url' => !empty($row['kapak_foto'])
                    ? $base . '/uploads/blog/' . ltrim((string) $row['kapak_foto'], '/')
                    : null,
                'category' => $row['kategori_ad'] ? [
                    'name' => (string) $row['kategori_ad'],
                    'slug' => (string) $row['kategori_slug'],
                ] : null,
                'author' => $row['author_name'] ? [
                    'name' => (string) $row['author_name'],
                    'slug' => (string) $row['author_slug'],
                    'title' => (string) $row['author_title'],
                ] : null,
                'tags' => array_values(array_filter(array_map('trim', explode(',', (string) ($row['etiketler'] ?? ''))))),
                'url' => $base . '/' . $slug,
                'markdown_url' => $base . '/' . $slug . '?format=markdown',
                'json_url' => $base . '/api/v1/blog/' . rawurlencode($slug) . '.json',
                'date_published' => (string) ($row['created_at'] ?? ''),
                'date_modified' => (string) ($row['updated_at'] ?? $row['created_at'] ?? ''),
            ];
        }
    }

    $payload = [
        'version' => '1.0',
        'generated_at' => gmdate('c'),
        'page' => $page,
        'per_page' => $perPage,
        'count_on_page' => count($items),
        'total_count' => $count,
        'total_pages' => (int) max(1, ceil($count / $perPage)),
        'next_page_url' => ($page * $perPage < $count) ? ($base . '/api/v1/blog.json?page=' . ($page + 1) . '&per_page=' . $perPage) : null,
        'items' => $items,
    ];
    mynak_api_cache_write($cacheKey, $payload);
    mynak_api_send_json($payload);
    return true;
}

function mynak_api_emit_blog_single(mysqli $conn, string $slug): bool
{
    $cacheKey = 'blog_one_' . substr(md5($slug), 0, 16);
    $cached = mynak_api_cache_read($cacheKey, 1800);
    if ($cached !== null) {
        mynak_api_send_json($cached);
        return true;
    }

    $stmt = $conn->prepare("SELECT bp.*, bc.ad AS kategori_ad, bc.slug AS kategori_slug,
                                   a.name AS author_name, a.slug AS author_slug, a.title AS author_title, a.bio AS author_bio,
                                   a.url AS author_url, a.email AS author_email, a.knows_about AS author_knows_about
                            FROM blog_posts bp
                            LEFT JOIN blog_categories bc ON bp.kategori_id = bc.id
                            LEFT JOIN authors a ON bp.author_id = a.id
                            WHERE bp.slug = ? AND bp.durum = 3 LIMIT 1");
    if (!($stmt instanceof mysqli_stmt)) {
        return mynak_api_emit_404('blog/' . $slug);
    }
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res instanceof mysqli_result ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!is_array($row)) {
        return mynak_api_emit_404('blog/' . $slug);
    }

    if (!function_exists('mynak_html_to_markdown')) {
        require_once dirname(__DIR__) . '/markdown/html_to_markdown.php';
    }

    $rawHtml = (string) ($row['icerik'] ?? '');
    if (function_exists('mynak_blok_isle')) {
        $rawHtml = mynak_blok_isle($conn, $rawHtml);
    }

    $base = mynak_api_site_url();
    $payload = [
        'version' => '1.0',
        'generated_at' => gmdate('c'),
        'slug' => $slug,
        'title' => (string) ($row['baslik'] ?? ''),
        'seo_title' => (string) ($row['seo_title'] ?? ''),
        'description' => (string) ($row['meta_description'] ?? ''),
        'focus_keyword' => (string) ($row['focus_keyword'] ?? ''),
        'word_count' => mynak_api_word_count($rawHtml),
        'content_html' => $rawHtml,
        'content_markdown' => mynak_html_to_markdown($rawHtml),
        'content_plain' => mynak_api_strip_html_excerpt($rawHtml, 100000),
        'image_url' => !empty($row['kapak_foto'])
            ? $base . '/uploads/blog/' . ltrim((string) $row['kapak_foto'], '/')
            : null,
        'category' => $row['kategori_ad'] ? [
            'name' => (string) $row['kategori_ad'],
            'slug' => (string) $row['kategori_slug'],
        ] : null,
        'author' => $row['author_name'] ? [
            'name' => (string) $row['author_name'],
            'slug' => (string) $row['author_slug'],
            'title' => (string) $row['author_title'],
            'bio' => (string) $row['author_bio'],
            'url' => $row['author_url'] ? (string) $row['author_url'] : null,
            'email' => $row['author_email'] ? (string) $row['author_email'] : null,
            'knows_about' => $row['author_knows_about']
                ? array_values(array_filter(array_map('trim', explode(',', (string) $row['author_knows_about']))))
                : [],
        ] : null,
        'tags' => array_values(array_filter(array_map('trim', explode(',', (string) ($row['etiketler'] ?? ''))))),
        'url' => $base . '/' . $slug,
        'markdown_url' => $base . '/' . $slug . '?format=markdown',
        'date_published' => (string) ($row['created_at'] ?? ''),
        'date_modified' => (string) ($row['updated_at'] ?? $row['created_at'] ?? ''),
    ];
    mynak_api_cache_write($cacheKey, $payload);
    mynak_api_send_json($payload);
    return true;
}

function mynak_api_emit_authors(mysqli $conn): bool
{
    $cached = mynak_api_cache_read('authors', 3600);
    if ($cached !== null) {
        mynak_api_send_json($cached);
        return true;
    }

    $tbl = $conn->query("SHOW TABLES LIKE 'authors'");
    if (!$tbl || $tbl->num_rows === 0) {
        $payload = ['version' => '1.0', 'generated_at' => gmdate('c'), 'count' => 0, 'items' => []];
        mynak_api_send_json($payload);
        return true;
    }

    $base = mynak_api_site_url();
    $items = [];
    $r = $conn->query("SELECT a.*, (SELECT COUNT(*) FROM blog_posts WHERE author_id = a.id AND durum = 3) AS post_count FROM authors a WHERE a.status = 1 ORDER BY a.is_default DESC, a.name ASC");
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $url = trim((string) ($row['url'] ?? ''));
            if ($url !== '' && $url[0] === '/') {
                $url = $base . $url;
            }
            $items[] = [
                '@type' => 'Person',
                'name' => (string) $row['name'],
                'slug' => (string) $row['slug'],
                'jobTitle' => (string) ($row['title'] ?? ''),
                'description' => (string) ($row['bio'] ?? ''),
                'url' => $url,
                'email' => $row['email'] ? 'mailto:' . (string) $row['email'] : null,
                'image' => (string) ($row['photo_url'] ?? '') ?: null,
                'sameAs' => array_values(array_filter([
                    $row['linkedin'] ?? null,
                    $row['twitter'] ?? null,
                ])),
                'knowsAbout' => $row['knows_about']
                    ? array_values(array_filter(array_map('trim', explode(',', (string) $row['knows_about']))))
                    : [],
                'is_default' => (int) ($row['is_default'] ?? 0) === 1,
                'post_count' => (int) ($row['post_count'] ?? 0),
            ];
        }
    }

    $payload = [
        'version' => '1.0',
        'generated_at' => gmdate('c'),
        'count' => count($items),
        'items' => $items,
    ];
    mynak_api_cache_write('authors', $payload);
    mynak_api_send_json($payload);
    return true;
}

function mynak_api_emit_locations(): bool
{
    $cached = mynak_api_cache_read('locations', 86400);
    if ($cached !== null) {
        mynak_api_send_json($cached);
        return true;
    }

    $base = mynak_api_site_url();
    $districts = [];
    if (function_exists('seo_ei_izmir_district_names_local_pack_order')) {
        $districts = seo_ei_izmir_district_names_local_pack_order();
    } else {
        $districts = ['Konak', 'Karşıyaka', 'Bornova', 'Buca', 'Çiğli', 'Gaziemir', 'Balçova', 'Narlıdere', 'Güzelbahçe', 'Bayraklı', 'Alsancak'];
    }

    $izmirAllDistricts = [
        'Aliağa', 'Balçova', 'Bayındır', 'Bayraklı', 'Bergama', 'Beydağ', 'Bornova', 'Buca',
        'Çeşme', 'Çiğli', 'Dikili', 'Foça', 'Gaziemir', 'Güzelbahçe', 'Karabağlar', 'Karaburun',
        'Karşıyaka', 'Kemalpaşa', 'Kınık', 'Kiraz', 'Konak', 'Menderes', 'Menemen', 'Narlıdere',
        'Ödemiş', 'Seferihisar', 'Selçuk', 'Tire', 'Torbalı', 'Urla',
    ];

    $payload = [
        'version' => '1.0',
        'generated_at' => gmdate('c'),
        'primary_service_city' => [
            '@type' => 'City',
            'name' => 'İzmir',
            'addressRegion' => 'İzmir',
            'addressCountry' => 'TR',
            'url' => $base,
        ],
        'local_pack_priority_districts' => $districts,
        'all_izmir_districts' => $izmirAllDistricts,
        'inter_city_service_examples' => [
            'İstanbul', 'Ankara', 'Bursa', 'Antalya', 'Muğla', 'Aydın', 'Manisa', 'Denizli',
            'Eskişehir', 'Konya', 'Kocaeli', 'Sakarya', 'Tekirdağ', 'Balıkesir',
        ],
        'note' => 'Tüm 81 il için şehirler arası nakliyat hizmeti verilir. Listede sık tercih edilen iller var.',
    ];
    mynak_api_cache_write('locations', $payload);
    mynak_api_send_json($payload);
    return true;
}

function mynak_api_emit_404(string $path): bool
{
    $base = mynak_api_site_url();
    $payload = [
        'error' => 'not_found',
        'message' => 'Bilinmeyen API yolu: /' . ltrim($path, '/'),
        'available_endpoints' => $base . '/api/v1/manifest.json',
    ];
    mynak_api_send_json($payload, 404, 60);
    return true;
}
