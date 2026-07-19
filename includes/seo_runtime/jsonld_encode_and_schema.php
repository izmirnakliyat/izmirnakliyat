<?php
declare(strict_types=1);
/** Auto-split from seo_runtime.php — Phase 3 modular SEO runtime. */

/**
 * JSON-LD script sarmalayıcı. Canlı site &lt;head&gt; çıktısında bu parçalar yalnızca schema_factory() ile
 * structured_head_markup içinde birleştirilir; tema/header doğrudan basmamalıdır (MYNAK_PRODUCTION_JSONLD_EMITTER_RULE).
 *
 * @param array<string,mixed> $schema
 */
function seo_runtime_ld_script_from_array(array $schema): string
{
    $json = function_exists('seo_json_ld_encode')
        ? seo_json_ld_encode($schema)
        : json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false || $json === '') {
        return '';
    }

    return '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";
}

function seo_runtime_schema_origin(string $canonicalOrigin = ''): string
{
    $url = rtrim(trim($canonicalOrigin), '/');
    if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
        $scheme = (string) (parse_url($url, PHP_URL_SCHEME) ?? '');
        $host = (string) (parse_url($url, PHP_URL_HOST) ?? '');
        $port = parse_url($url, PHP_URL_PORT);
        if ($scheme !== '' && $host !== '') {
            return $scheme . '://' . $host . (is_int($port) ? ':' . $port : '');
        }
    }

    return defined('SITE_URL')
        ? rtrim((string) SITE_URL, '/')
        : 'https://www.mynakliyat.com.tr';
}

function seo_runtime_schema_entity_slug(string $label): string
{
    $ascii = strtr(mb_strtolower(trim($label), 'UTF-8'), [
        "\u{0307}" => '',
        'ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u',
    ]);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $ascii);

    return trim(is_string($slug) ? $slug : '', '-');
}

function seo_runtime_schema_organization_id(string $canonicalOrigin): string
{
    return seo_runtime_schema_origin($canonicalOrigin) . '/#organization';
}

function seo_runtime_schema_brand_id(string $canonicalOrigin): string
{
    return seo_runtime_schema_origin($canonicalOrigin) . '/#brand';
}

function seo_runtime_schema_website_id(string $canonicalOrigin): string
{
    return seo_runtime_schema_origin($canonicalOrigin) . '/#website';
}

function seo_runtime_schema_webpage_id(string $canonical): string
{
    $base = rtrim($canonical, '/');
    $path = (string) (parse_url($canonical, PHP_URL_PATH) ?? '');

    return ($path === '' || $path === '/') ? $base . '/#webpage' : $base . '#webpage';
}

function seo_runtime_schema_service_id_for_url(string $serviceUrl): string
{
    return rtrim($serviceUrl, '/') . '#service';
}

function seo_runtime_schema_service_id_for_graph_slug(string $canonicalOrigin, string $graphSlug): string
{
    if (!function_exists('seo_rt_primary_service_public_url')) {
        require_once __DIR__ . '/paths.php';
    }

    return seo_runtime_schema_service_id_for_url(
        seo_rt_primary_service_public_url($canonicalOrigin, $graphSlug)
    );
}

function seo_runtime_schema_place_id(string $canonicalOrigin, string $placeName, string $parentName = ''): string
{
    $parts = ['place'];
    if ($parentName !== '') {
        $parts[] = seo_runtime_schema_entity_slug($parentName);
    }
    $parts[] = seo_runtime_schema_entity_slug($placeName);

    return seo_runtime_schema_origin($canonicalOrigin) . '/#' . implode('-', array_filter($parts));
}

function seo_runtime_schema_slug_has_location_token(string $slug, string $locationSlug): bool
{
    $slug = trim($slug, '-/');
    $locationSlug = trim($locationSlug, '-/');
    if ($slug === '' || $locationSlug === '') {
        return false;
    }

    return preg_match('/(?:^|-)' . preg_quote($locationSlug, '/') . '(?:-|$)/', $slug) === 1;
}

function seo_runtime_infer_service_type_label(string $slug): string
{
    $slug = trim($slug, '/');
    if ($slug === '') {
        return 'Nakliyat hizmeti';
    }
    $defs = seo_rt_pillar_cluster_definitions();
    if (isset($defs[$slug]['nav_title']) && (string) $defs[$slug]['nav_title'] !== '') {
        return (string) $defs[$slug]['nav_title'];
    }
    $pillar = $defs[$slug]['pillar'] ?? null;
    if (is_string($pillar) && $pillar !== '' && isset($defs[$pillar]['nav_title']) && (string) $defs[$pillar]['nav_title'] !== '') {
        return (string) $defs[$pillar]['nav_title'] . ' kapsamında hizmet';
    }

    return 'Nakliyat hizmeti';
}

/**
 * GeoCoordinates: yalnızca ayarlarda sayısal lat/lng veya contact_map_embed içindeki !3d / !2d çifti.
 *
 * @param array<string,mixed> $site_settings
 * @return array<string,mixed>|null
 */
function seo_runtime_schema_geo_from_site_settings(array $site_settings): ?array
{
    $latRaw = isset($site_settings['latitude']) ? trim((string) $site_settings['latitude']) : '';
    $lngRaw = isset($site_settings['longitude']) ? trim((string) $site_settings['longitude']) : '';
    if ($latRaw !== '' && $lngRaw !== '' && is_numeric($latRaw) && is_numeric($lngRaw)) {
        return [
            '@type' => 'GeoCoordinates',
            'latitude' => (float) $latRaw,
            'longitude' => (float) $lngRaw,
        ];
    }
    $embed = isset($site_settings['contact_map_embed']) ? (string) $site_settings['contact_map_embed'] : '';
    if ($embed !== '' && preg_match('/!3d(-?[0-9.]+)/', $embed, $latM) && preg_match('/!2d(-?[0-9.]+)/', $embed, $lngM)) {
        return [
            '@type' => 'GeoCoordinates',
            'latitude' => (float) $latM[1],
            'longitude' => (float) $lngM[1],
        ];
    }

    return null;
}

/**
 * Google Haritalar / yerel işletme listesi için hasMap (settings veya sabit).
 *
 * @param array<string, mixed> $site_settings
 */
function seo_runtime_schema_has_map_from_site_settings(array $site_settings): string
{
    $u = trim((string) ($site_settings['google_maps_url'] ?? ''));
    if ($u !== '' && filter_var($u, FILTER_VALIDATE_URL)) {
        return $u;
    }
    if (defined('MYNAK_CONTACT_GOOGLE_MAPS_URL')) {
        $fallback = trim((string) MYNAK_CONTACT_GOOGLE_MAPS_URL);
        if ($fallback !== '' && filter_var($fallback, FILTER_VALIDATE_URL)) {
            return $fallback;
        }
    }

    return '';
}

/**
 * Schema.org priceRange: ayarlarda yoksa orta segment ($$).
 *
 * @param array<string, mixed> $site_settings
 */
function seo_runtime_schema_price_range_from_site_settings(array $site_settings): string
{
    foreach (['schema_price_range', 'price_range', 'local_price_range'] as $k) {
        $v = isset($site_settings[$k]) ? trim((string) $site_settings[$k]) : '';
        if ($v !== '') {
            return $v;
        }
    }

    return '$$';
}

/**
 * opening_hours_json: JSON dizi — öğe örneği {"dayOfWeek":["Monday","Tuesday"],"opens":"09:00","closes":"18:00"}.
 * Eski anahtar "days" da kabul edilir. Geçersizse varsayılan hafta içi + Cumartesi yarım gün.
 *
 * @return list<array<string, mixed>>
 */
function seo_runtime_schema_opening_hours_from_site_settings(array $site_settings): array
{
    if (function_exists('mynak_gbp_cached_opening_hours_specs')) {
        $gbpSpecs = mynak_gbp_cached_opening_hours_specs();
        if (is_array($gbpSpecs) && $gbpSpecs !== []) {
            return $gbpSpecs;
        }
    }

    $raw = trim((string) ($site_settings['opening_hours_json'] ?? ''));
    if ($raw === '') {
        return seo_runtime_schema_opening_hours_default_specs();
    }
    $dec = json_decode($raw, true);
    if (!is_array($dec) || $dec === []) {
        return seo_runtime_schema_opening_hours_default_specs();
    }
    $out = [];
    foreach ($dec as $row) {
        if (!is_array($row)) {
            continue;
        }
        $dow = $row['dayOfWeek'] ?? $row['days'] ?? null;
        if ($dow === null || $dow === '') {
            continue;
        }
        $opens = trim((string) ($row['opens'] ?? '09:00'));
        $closes = trim((string) ($row['closes'] ?? '18:00'));
        $spec = [
            '@type' => 'OpeningHoursSpecification',
            'opens' => $opens !== '' ? $opens : '09:00',
            'closes' => $closes !== '' ? $closes : '18:00',
        ];
        if (is_array($dow)) {
            $spec['dayOfWeek'] = array_values(array_filter(array_map('strval', $dow)));
        } else {
            $spec['dayOfWeek'] = (string) $dow;
        }
        $out[] = $spec;
    }

    return $out !== [] ? $out : seo_runtime_schema_opening_hours_default_specs();
}

/**
 * @return list<array<string, mixed>>
 */
function seo_runtime_schema_opening_hours_default_specs(): array
{
    return [
        [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'opens' => '09:00',
            'closes' => '18:00',
        ],
        [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => 'Saturday',
            'opens' => '09:00',
            'closes' => '13:00',
        ],
    ];
}

/**
 * @param array<string,mixed> $site_settings
 * @return array<string,mixed>
 */
function seo_runtime_schema_area_served_node(array $site_settings, string $canonicalOrigin = ''): array
{
    return [
        '@type' => ['Place', 'City'],
        '@id' => seo_runtime_schema_place_id($canonicalOrigin, 'İzmir'),
        'name' => 'İzmir',
        'containedInPlace' => [
            '@id' => seo_runtime_schema_place_id($canonicalOrigin, 'Türkiye'),
        ],
    ];
}

/**
 * İzmir metropol + yoğun ilçe düğümleri (seo_ei_izmir_metro_district_location_defs ile SSOT; NAP değildir).
 *
 * @param array<string,mixed> $site_settings
 * @return list<array<string,mixed>>
 */
function seo_runtime_schema_area_served_metro_list(array $site_settings, string $canonicalOrigin = ''): array
{
    $city = seo_runtime_schema_area_served_node($site_settings, $canonicalOrigin);
    $labelsFromDefs = [];
    foreach (seo_ei_izmir_metro_district_location_defs() as $label_tr) {
        $labelsFromDefs[] = trim(explode(',', $label_tr, 2)[0]);
    }
    $districts = [];
    foreach (seo_ei_izmir_district_names_local_pack_order() as $d) {
        if (in_array($d, $labelsFromDefs, true)) {
            $districts[] = $d;
        }
    }
    foreach ($labelsFromDefs as $d) {
        if (!in_array($d, $districts, true)) {
            $districts[] = $d;
        }
    }
    $out = [$city];
    foreach ($districts as $d) {
        $out[] = [
            '@type' => ['Place', 'AdministrativeArea'],
            '@id' => seo_runtime_schema_place_id($canonicalOrigin, $d, 'İzmir'),
            'name' => $d,
            'containedInPlace' => [
                '@id' => seo_runtime_schema_place_id($canonicalOrigin, 'İzmir'),
            ],
        ];
    }

    return $out;
}

/**
 * location_vector.area_served_mode → şema areaServed şekli (yalnız pipeline üzerinden).
 *
 * @param array<string,mixed> $site_settings
 * @param array<string,mixed> $locationVector
 * @return list<array<string,mixed>>
 */
function seo_runtime_schema_area_served_for_pipeline(array $site_settings, array $locationVector, string $canonicalOrigin = ''): array
{
    $mode = (string) ($locationVector['area_served_mode'] ?? 'metro_districts');
    if ($mode === 'district_single') {
        $district = trim((string) ($locationVector['primary_district'] ?? ''));
        if ($district !== '') {
            return seo_runtime_schema_area_served_district_single($site_settings, $district, $canonicalOrigin);
        }
    }
    if ($mode === 'city_only') {
        return [seo_runtime_schema_area_served_node($site_settings, $canonicalOrigin)];
    }

    return seo_runtime_schema_area_served_metro_list($site_settings, $canonicalOrigin);
}

/**
 * İlçe cluster sayfası: İzmir + tek ilçe AdministrativeArea.
 *
 * @param array<string,mixed> $site_settings
 * @return list<array<string,mixed>>
 */
function seo_runtime_schema_area_served_district_single(array $site_settings, string $districtName, string $canonicalOrigin = ''): array
{
    return [
        seo_runtime_schema_area_served_node($site_settings, $canonicalOrigin),
        [
            '@type' => ['Place', 'AdministrativeArea'],
            '@id' => seo_runtime_schema_place_id($canonicalOrigin, $districtName, 'İzmir'),
            'name' => $districtName,
            'containedInPlace' => [
                '@id' => seo_runtime_schema_place_id($canonicalOrigin, 'İzmir'),
            ],
        ],
    ];
}

/**
 * @param array<string,mixed> $site_settings
 * @return array<string, mixed>
 */
function seo_runtime_schema_brand_node(string $canonicalOrigin): array
{
    return [
        '@type' => 'Brand',
        '@id' => seo_runtime_schema_brand_id($canonicalOrigin),
        'name' => mynak_schema_brand(),
        'alternateName' => mynak_schema_brand_aliases(),
        'url' => seo_runtime_schema_origin($canonicalOrigin) . '/',
    ];
}

/** @return list<string> */
function seo_runtime_schema_service_aliases(string $graphSlug): array
{
    $aliases = [
        'izmir-evden-eve-nakliyat' => ['Evden Eve Nakliyat', 'İzmir Nakliye', 'İzmir Nakliyat'],
        'sehirler-arasi-nakliyat' => ['Şehirler Arası Nakliyat', 'Şehirlerarası Nakliyat'],
        'izmir-ofis-tasimaciligi' => ['Ofis Taşıma', 'Ofis Taşımacılığı'],
        'izmir-esya-depolama' => ['Eşya Depolama', 'İzmir Eşya Depolama'],
        'asansorlu-nakliyat' => ['Asansörlü Nakliyat', 'Asansörlü Taşımacılık'],
    ];

    return $aliases[$graphSlug] ?? [];
}

function seo_runtime_schema_graph_slug_from_pipeline(array $pipelineCore, string $fallbackSlug = ''): string
{
    $linkContext = isset($pipelineCore['internal_link_context']) && is_array($pipelineCore['internal_link_context'])
        ? $pipelineCore['internal_link_context']
        : [];
    foreach (['graph_key', 'anchor_slug'] as $key) {
        $candidate = trim((string) ($linkContext[$key] ?? ''), '/');
        if ($candidate !== '') {
            return $candidate;
        }
    }

    $fallbackSlug = trim($fallbackSlug, '/');
    if (!function_exists('seo_rt_primary_service_lines')) {
        require_once __DIR__ . '/internal_linking.php';
    }
    foreach (seo_rt_primary_service_lines() as $line) {
        if ($fallbackSlug === (string) $line['public_slug']) {
            return (string) $line['graph_slug'];
        }
    }

    return $fallbackSlug;
}

function seo_runtime_schema_canonical_service_graph_slug(string $candidate): string
{
    if (!function_exists('seo_runtime_canonical_service_definitions')) {
        require_once __DIR__ . '/default_service_faqs.php';
    }

    $candidate = trim($candidate, '/');
    foreach (seo_runtime_canonical_service_definitions() as $definition) {
        if ($candidate === (string) $definition['graph_slug'] || $candidate === (string) $definition['slug']) {
            return (string) $definition['graph_slug'];
        }
    }

    return '';
}

function seo_runtime_schema_canonical_service_id(string $canonicalOrigin, string $candidate): string
{
    $graphSlug = seo_runtime_schema_canonical_service_graph_slug($candidate);

    return $graphSlug !== ''
        ? seo_runtime_schema_service_id_for_graph_slug($canonicalOrigin, $graphSlug)
        : '';
}

function seo_runtime_schema_infer_blog_service_graph_slug(array $blog, array $pipelineCore): string
{
    $pipelineSlug = seo_runtime_schema_canonical_service_graph_slug(
        seo_runtime_schema_graph_slug_from_pipeline($pipelineCore)
    );
    if ($pipelineSlug !== '') {
        return $pipelineSlug;
    }
    $haystack = seo_runtime_schema_entity_slug(
        (string) ($blog['slug'] ?? '') . ' ' . (string) ($blog['baslik'] ?? '')
    );
    $terms = [
        'sepetli-vinc' => 'sepetli-vinc-kiralama',
        'mobil-asansor' => 'mobil-asansor-kiralama',
        'asansor' => 'asansorlu-nakliyat',
        'sehirler-arasi' => 'sehirler-arasi-nakliyat',
        'sehirlerarasi' => 'sehirler-arasi-nakliyat',
        'sehir-ici' => 'sehir-ici-nakliyat',
        'ofis' => 'izmir-ofis-tasimaciligi',
        'kurumsal' => 'izmir-ofis-tasimaciligi',
        'depolama' => 'izmir-esya-depolama',
        'parca-esya' => 'parca-esya-tasima',
        'ceyiz' => 'parca-esya-tasima',
        'piyano' => 'antika-ve-piyano-tasima',
        'antika' => 'antika-ve-piyano-tasima',
        'mobilya' => 'mobilya-montaj-kurulum',
        'montaj' => 'mobilya-montaj-kurulum',
    ];
    foreach ($terms as $term => $graphSlug) {
        if (str_contains($haystack, $term)) {
            return seo_runtime_schema_canonical_service_graph_slug($graphSlug);
        }
    }
    if (str_contains($haystack, 'evden-eve') || str_contains($haystack, 'nakliyat')) {
        return seo_runtime_schema_canonical_service_graph_slug(seo_rt_money_page_pillar_slug());
    }

    return '';
}

/** @return list<array<string,mixed>> */
function seo_runtime_schema_primary_service_nodes(string $canonicalOrigin, string $organizationId): array
{
    if (!function_exists('seo_rt_primary_service_lines')) {
        require_once __DIR__ . '/internal_linking.php';
    }
    if (!function_exists('seo_runtime_service_quick_answer')) {
        require_once __DIR__ . '/default_service_faqs.php';
    }

    $nodes = [];
    foreach (seo_rt_primary_service_lines() as $line) {
        $graphSlug = (string) $line['graph_slug'];
        $url = seo_rt_primary_service_public_url($canonicalOrigin, $graphSlug);
        $node = [
            '@type' => 'Service',
            '@id' => seo_runtime_schema_service_id_for_url($url),
            'name' => (string) $line['name'],
            'serviceType' => (string) $line['service_type'],
            'description' => seo_runtime_service_quick_answer($graphSlug),
            'url' => $url,
            'provider' => ['@id' => $organizationId],
            'brand' => ['@id' => seo_runtime_schema_brand_id($canonicalOrigin)],
            'areaServed' => [['@id' => seo_runtime_schema_place_id($canonicalOrigin, 'İzmir')]],
        ];
        $aliases = seo_runtime_schema_service_aliases($graphSlug);
        if ($aliases !== []) {
            $node['alternateName'] = $aliases;
        }
        $nodes[] = $node;
    }

    return $nodes;
}

/** @return list<array<string,mixed>> */
function seo_runtime_schema_canonical_service_nodes(string $canonicalOrigin, string $organizationId): array
{
    if (!function_exists('seo_runtime_canonical_service_definitions')) {
        require_once __DIR__ . '/default_service_faqs.php';
    }
    if (!function_exists('seo_rt_primary_service_public_url')) {
        require_once __DIR__ . '/paths.php';
    }
    $typeLabels = [
        'izmir-evden-eve-nakliyat' => 'Evden eve nakliyat',
        'sehirler-arasi-nakliyat' => 'Şehirler arası nakliyat',
        'izmir-ofis-tasimaciligi' => 'Ofis ve kurumsal taşıma',
        'parca-esya-tasima' => 'Parça eşya taşıma',
        'asansorlu-nakliyat' => 'Asansörlü nakliyat',
        'sepetli-vinc-kiralama' => 'Sepetli vinç kiralama',
        'mobil-asansor-kiralama' => 'Mobil asansör kiralama',
        'izmir-esya-depolama' => 'Eşya depolama',
        'antika-ve-piyano-tasima' => 'Antika ve piyano taşıma',
        'mobilya-montaj-kurulum' => 'Mobilya montaj ve kurulum',
        'sehir-ici-nakliyat' => 'Şehir içi nakliyat',
    ];
    $nodes = [];
    foreach (seo_runtime_canonical_service_definitions() as $definition) {
        $publicSlug = (string) $definition['slug'];
        $graphSlug = (string) $definition['graph_slug'];
        $url = seo_rt_primary_service_public_url($canonicalOrigin, $graphSlug);
        $name = seo_runtime_service_display_name($publicSlug);
        $name = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8')
            . mb_substr($name, 1, null, 'UTF-8');
        $node = [
            '@type' => 'Service',
            '@id' => seo_runtime_schema_service_id_for_url($url),
            'name' => $name,
            'serviceType' => $typeLabels[$graphSlug] ?? $name,
            'description' => seo_runtime_service_quick_answer($graphSlug),
            'url' => $url,
            'provider' => ['@id' => $organizationId],
            'brand' => ['@id' => seo_runtime_schema_brand_id($canonicalOrigin)],
            'areaServed' => [['@id' => seo_runtime_schema_place_id($canonicalOrigin, 'İzmir')]],
        ];
        $aliases = seo_runtime_schema_service_aliases($graphSlug);
        if ($aliases !== []) {
            $node['alternateName'] = $aliases;
        }
        $nodes[] = $node;
    }

    return $nodes;
}

/** @return list<string> */
function seo_runtime_schema_turkiye_province_names(): array
{
    $file = dirname(__DIR__) . '/llms_turkiye_iller.php';
    if (!is_readable($file)) {
        return [];
    }
    $pack = require $file;

    return isset($pack['provinces_plate_order']) && is_array($pack['provinces_plate_order'])
        ? array_values(array_map('strval', $pack['provinces_plate_order']))
        : [];
}

/** @return list<array<string,mixed>> */
function seo_runtime_schema_turkiye_province_nodes(string $canonicalOrigin): array
{
    $countryId = seo_runtime_schema_place_id($canonicalOrigin, 'Türkiye');
    $nodes = [];
    foreach (seo_runtime_schema_turkiye_province_names() as $name) {
        $nodes[] = [
            '@type' => ['Place', 'City'],
            '@id' => seo_runtime_schema_place_id($canonicalOrigin, $name),
            'name' => $name,
            'containedInPlace' => ['@id' => $countryId],
        ];
    }

    return $nodes;
}

/** @return list<array<string,mixed>> */
function seo_runtime_schema_place_nodes_for_page(
    string $canonicalOrigin,
    string $contentSlug,
    array $siteSettings,
    array $locationVector
): array {
    $origin = seo_runtime_schema_origin($canonicalOrigin);
    $nodes = [[
        '@type' => ['Place', 'Country'],
        '@id' => seo_runtime_schema_place_id($origin, 'Türkiye'),
        'name' => 'Türkiye',
    ]];
    $slug = trim($contentSlug, '/');
    if (str_contains($slug, 'sehirler-arasi-nakliyat') || str_contains($slug, 'sehirlerarasi-nakliyat')) {
        return array_merge($nodes, seo_runtime_schema_turkiye_province_nodes($origin));
    }

    $nodes = array_merge(
        $nodes,
        seo_runtime_schema_area_served_for_pipeline($siteSettings, $locationVector, $origin)
    );
    foreach (seo_runtime_schema_turkiye_province_names() as $province) {
        $provinceSlug = seo_runtime_schema_entity_slug($province);
        if (!seo_runtime_schema_slug_has_location_token($slug, $provinceSlug)) {
            continue;
        }
        $nodes[] = [
            '@type' => ['Place', 'City'],
            '@id' => seo_runtime_schema_place_id($origin, $province),
            'name' => $province,
            'containedInPlace' => ['@id' => seo_runtime_schema_place_id($origin, 'Türkiye')],
        ];
    }

    $seen = [];
    $unique = [];
    foreach ($nodes as $node) {
        $id = (string) ($node['@id'] ?? '');
        if ($id === '' || isset($seen[$id])) {
            continue;
        }
        $seen[$id] = true;
        $unique[] = $node;
    }

    return $unique;
}

function seo_runtime_schema_website_home_graph(string $canonical_origin, array $site_settings, string $moving_company_at_id): array
{
    $base = rtrim($canonical_origin, '/');
    $brand = mynak_schema_brand();
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        '@id' => $base . '/#website',
        'url' => $base . '/',
        'name' => $brand,
        'publisher' => ['@id' => $moving_company_at_id],
        'about' => ['@id' => $moving_company_at_id],
        'inLanguage' => 'tr-TR',
    ];
    if (function_exists('mynak_schema_apply_brand_identity')) {
        mynak_schema_apply_brand_identity($schema, false);
    }
    $desc = !empty($site_settings['site_description'])
        ? (string) $site_settings['site_description']
        : (!empty($site_settings['short_description']) ? (string) $site_settings['short_description'] : '');
    if ($desc !== '') {
        $schema['description'] = $desc;
    }

    return $schema;
}

/**
 * @param array<string,mixed> $site_settings
 */
function seo_runtime_schema_website_home(string $canonical_origin, array $site_settings, string $moving_company_at_id): string
{
    return seo_runtime_ld_script_from_array(seo_runtime_schema_website_home_graph($canonical_origin, $site_settings, $moving_company_at_id));
}

/**
 * Blog dizini ItemList için sabit yuva sayısı (yapısal; DB’den türetilmez).
 */
function seo_rt_deterministic_blog_index_slot_count(): int
{
    return 10;
}

/**
 * Pillar hizalı, deterministik yedek slug sırası: blog.related sırası, ardından tanım anahtarları (blog hariç) lex sıralı.
 *
 * @return list<string>
 */
function deterministic_blog_index_pillar_placeholder_order(array $defs): array
{
    $out = [];
    $seen = [];
    $blogEntry = $defs['blog'] ?? null;
    if (is_array($blogEntry) && !empty($blogEntry['related']) && is_array($blogEntry['related'])) {
        foreach ($blogEntry['related'] as $r) {
            $s = trim((string) $r, '/');
            if ($s === '' || $s === 'blog' || isset($seen[$s]) || !isset($defs[$s])) {
                continue;
            }
            $seen[$s] = true;
            $out[] = $s;
        }
    }
    $keys = array_keys($defs);
    sort($keys, SORT_STRING);
    foreach ($keys as $k) {
        if ($k === 'blog' || isset($seen[$k])) {
            continue;
        }
        $seen[$k] = true;
        $out[] = $k;
    }

    return $out;
}

/**
 * Blog dizini ItemList: sabit N yuva; yazı URL’leri + flex metin; eksik yuvalar pillar (SSOT) yolları.
 * DB satır sayısı yalnızca hangi yuvaların yazı olacağını doldurur; N, sıra kuralı ve şema şekli SSOT’tur.
 *
 * @param array<string, mixed> $pipeline_core canonical_seo_pipeline_core (aynı girdi → aynı yapısal kural; URL tabanı için origin ile birlikte kullanılır)
 * @param array<string, array<string, mixed>> $defs seo_rt_pillar_cluster_definitions()
 * @return array{itemListElement: list<array<string, mixed>>, numberOfItems: int}
 */
function deterministic_blog_index_factory(array $pipeline_core, array $defs, string $canonical_origin): array
{
    $n = seo_rt_deterministic_blog_index_slot_count();
    $base = rtrim($canonical_origin, '/');
    if ($base === '' || preg_match('#/[a-zA-Z]:(/|%2[fF]|%5[cC])#i', str_replace('\\', '/', $base)) === 1) {
        $base = (function_exists('mynak_abs_url_from_public_path') && function_exists('mynak_public_path'))
            ? rtrim(mynak_abs_url_from_public_path(mynak_public_path('')), '/')
            : (defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '');
    }

    global $conn;
    $rows = [];
    if (isset($conn) && $conn instanceof mysqli) {
        $lim = (int) $n;
        $sql = 'SELECT slug, baslik, seo_title, icerik FROM blog_posts WHERE durum = 3 ORDER BY created_at DESC, id DESC LIMIT ' . $lim;
        $res = @$conn->query($sql);
        if ($res instanceof mysqli_result) {
            while ($row = $res->fetch_assoc()) {
                $rows[] = $row;
            }
            $res->free();
        }
    }

    $usedPaths = [];
    $items = [];

    foreach ($rows as $row) {
        if (count($items) >= $n) {
            break;
        }
        $bslug = isset($row['slug']) ? trim((string) $row['slug'], '/') : '';
        if ($bslug === '') {
            continue;
        }
        $path = '/' . rawurlencode($bslug);
        if (isset($usedPaths[$path])) {
            continue;
        }
        $text = flex_blog_index_post_text($row);
        if ($text['title'] === '') {
            continue;
        }
        $usedPaths[$path] = true;
        $listItem = [
            '@type' => 'ListItem',
            'position' => count($items) + 1,
            'name' => $text['title'],
            'item' => $base . $path,
        ];
        if ($text['description'] !== '') {
            $listItem['description'] = $text['description'];
        }
        $items[] = $listItem;
    }

    $appendPlaceholder = static function (string $slug) use (&$items, &$usedPaths, $n, $base, $defs): bool {
        if ($slug === 'blog' || count($items) >= $n) {
            return false;
        }
        if (!isset($defs[$slug])) {
            return false;
        }
        $path = seo_rt_graph_path_for_slug($slug);
        if ($path === '' || $path === '/') {
            return false;
        }
        $norm = ($path[0] === '/') ? $path : '/' . $path;
        if (isset($usedPaths[$norm])) {
            return false;
        }
        $nav = isset($defs[$slug]['nav_title']) ? trim((string) $defs[$slug]['nav_title']) : $slug;
        if ($nav === '') {
            $nav = $slug;
        }
        $usedPaths[$norm] = true;
        $items[] = [
            '@type' => 'ListItem',
            'position' => count($items) + 1,
            'name' => $nav,
            'description' => 'İzmir nakliyat — ' . $nav . ' (kurumsal hizmet özeti)',
            'item' => $base . $norm,
        ];

        return true;
    };

    $pillarQueue = deterministic_blog_index_pillar_placeholder_order($defs);
    foreach ($pillarQueue as $slug) {
        if (count($items) >= $n) {
            break;
        }
        $appendPlaceholder($slug);
    }

    if (count($items) < $n) {
        $tails = seo_rt_static_path_tails_from_cluster_defs();
        sort($tails, SORT_STRING);
        foreach ($tails as $slug) {
            if (count($items) >= $n) {
                break;
            }
            $appendPlaceholder((string) $slug);
        }
    }

    $hub = seo_rt_money_page_pillar_slug();
    $hubPath = seo_rt_graph_path_for_slug($hub);
    $hubNorm = ($hubPath !== '' && $hubPath[0] === '/') ? $hubPath : '/' . ltrim((string) $hubPath, '/');
    $hubNav = isset($defs[$hub]['nav_title']) ? (string) $defs[$hub]['nav_title'] : $hub;
    $slot = 0;
    while (count($items) < $n && $hubNorm !== '' && $hubNorm !== '/') {
        $slot++;
        $frag = '#blog-index-slot-' . (string) $slot;
        $items[] = [
            '@type' => 'ListItem',
            'position' => count($items) + 1,
            'name' => $hubNav,
            'description' => 'İzmir nakliyat — ' . $hubNav,
            'item' => $base . $hubNorm . $frag,
        ];
    }
    $fb = 0;
    while (count($items) < $n && $base !== '') {
        $fb++;
        $items[] = [
            '@type' => 'ListItem',
            'position' => count($items) + 1,
            'name' => $hubNav,
            'description' => 'İzmir nakliyat — ' . $hubNav,
            'item' => $base . '/#blog-index-fallback-' . (string) $fb,
        ];
    }

    foreach ($items as $i => $_) {
        $items[$i]['position'] = $i + 1;
    }

    return [
        'itemListElement' => $items,
        'numberOfItems' => count($items),
    ];
}

/**
 * @param array<string, mixed> $site_settings
 * @param array<string, mixed> $locationVector
 * @return array<string, mixed>
 */
function schema_factory_build_moving_company_graph(array $site_settings, string $canonical_origin, array $locationVector, string $moving_company_at_id): array
{
    $brand = mynak_schema_brand();
    $moving_company_schema = [
        '@context' => 'https://schema.org',
        '@type' => ['Organization', 'LocalBusiness', 'MovingCompany'],
        'name' => $brand,
        'url' => $canonical_origin . '/',
        'description' => !empty($site_settings['site_description'])
            ? $site_settings['site_description']
            : (!empty($site_settings['short_description'])
                ? $site_settings['short_description']
                : 'MY Nakliyat; evden eve nakliyat, ofis taşıma, eşya depolama, parça eşya taşıma ve şehirler arası nakliyat hizmetleri sunan İzmir merkezli taşıma firmasıdır.'),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => !empty($site_settings['address'])
                ? $site_settings['address']
                : (defined('MYNAK_CONTACT_ADDRESS_DISPLAY') ? MYNAK_CONTACT_ADDRESS_DISPLAY : 'İzmir'),
            'addressLocality' => 'İzmir',
            'addressRegion' => 'İzmir',
            'addressCountry' => 'TR',
        ],
        'areaServed' => seo_runtime_schema_area_served_for_pipeline($site_settings, $locationVector, $canonical_origin),
    ];
    if (function_exists('mynak_schema_apply_brand_identity')) {
        mynak_schema_apply_brand_identity($moving_company_schema);
    }
    if (!empty($site_settings['logo_light']) && function_exists('seo_upload_url')) {
        $moving_company_schema['logo'] = seo_upload_url('settings/' . $site_settings['logo_light']);
    }
    if (!empty($site_settings['phone1'])) {
        $moving_company_schema['telephone'] = $site_settings['phone1'];
        $moving_company_schema['contactPoint'] = [
            '@type' => 'ContactPoint',
            'telephone' => $site_settings['phone1'],
            'contactType' => 'customer service',
            'areaServed' => 'TR',
            'availableLanguage' => ['Turkish'],
        ];
    }
    if (!empty($site_settings['email'])) {
        $moving_company_schema['email'] = $site_settings['email'];
    }
    $geoNode = seo_runtime_schema_geo_from_site_settings($site_settings);
    if ($geoNode !== null) {
        $moving_company_schema['geo'] = $geoNode;
    }
    $same_as_list = [];
    $social_map = [
        'facebook' => 'https://www.facebook.com/',
        'instagram' => 'https://www.instagram.com/',
        'twitter' => 'https://twitter.com/',
        'youtube' => 'https://www.youtube.com/',
        'linkedin' => 'https://www.linkedin.com/company/',
        'tiktok' => 'https://www.tiktok.com/@',
    ];
    foreach ($social_map as $sk => $prefix) {
        if (!empty($site_settings[$sk])) {
            $same_as_list[] = $prefix . $site_settings[$sk];
        }
    }
    if ($same_as_list !== []) {
        $moving_company_schema['sameAs'] = $same_as_list;
    }
    $hasMap = seo_runtime_schema_has_map_from_site_settings($site_settings);
    if ($hasMap !== '') {
        $moving_company_schema['hasMap'] = $hasMap;
    }
    $moving_company_schema['priceRange'] = seo_runtime_schema_price_range_from_site_settings($site_settings);
    $ohSpecs = seo_runtime_schema_opening_hours_from_site_settings($site_settings);
    if ($ohSpecs !== []) {
        $moving_company_schema['openingHoursSpecification'] = count($ohSpecs) === 1 ? $ohSpecs[0] : $ohSpecs;
    }
    $gbpAgg = seo_runtime_schema_aggregate_rating_from_gbp_cache();
    if ($gbpAgg !== null) {
        $moving_company_schema['aggregateRating'] = $gbpAgg;
    }

    // E-E-A-T: yalnızca hizmet kapsamından doğrulanabilen marka ve uzmanlık ilişkileri.
    $brandTrust = seo_runtime_schema_brand_trust_layer($brand, $canonical_origin);
    foreach ($brandTrust as $k => $v) {
        $moving_company_schema[$k] = $v;
    }

    $offerCatalog = seo_runtime_schema_has_offer_catalog($canonical_origin);
    if ($offerCatalog !== null) {
        $moving_company_schema['hasOfferCatalog'] = $offerCatalog;
    }

    // ---- sameAs zenginlestirme: GBP review URL + map URL'ini de ekle (cache'ten gelirse) ----
    $extraSameAs = seo_runtime_schema_extra_same_as($site_settings);
    if ($extraSameAs !== []) {
        $existingSameAs = isset($moving_company_schema['sameAs']) && is_array($moving_company_schema['sameAs'])
            ? $moving_company_schema['sameAs']
            : [];
        $merged = array_values(array_unique(array_merge($existingSameAs, $extraSameAs)));
        if ($merged !== []) {
            $moving_company_schema['sameAs'] = $merged;
        }
    }

    $moving_company_schema['@id'] = $moving_company_at_id;

    return $moving_company_schema;
}

/**
 * Marka güven katmanı — yalnızca kanonik hizmet kapsamıyla doğrulanabilen alanlar.
 *
 * @return array<string, mixed>
 */
function seo_runtime_schema_brand_trust_layer(string $brand, string $canonical_origin): array
{
    if (!function_exists('seo_rt_primary_service_graph_slugs')) {
        require_once __DIR__ . '/internal_linking.php';
    }
    if (!function_exists('seo_runtime_canonical_service_definitions')) {
        require_once __DIR__ . '/default_service_faqs.php';
    }
    $out = [];

    $out['brand'] = [
        '@id' => seo_runtime_schema_brand_id($canonical_origin),
    ];

    // knowsAbout yalnızca yayımlanan kanonik hizmet alanlarından türetilir.
    $out['knowsAbout'] = [
        'Evden eve nakliyat',
        'Şehirler arası nakliyat',
        'Ofis ve kurumsal taşımacılık',
        'Asansörlü taşımacılık',
        'Eşya depolama',
        'Piyano ve antika taşımacılığı',
        'Parça eşya taşıma',
        'Mobilya montaj ve kurulum',
    ];

    $out['makesOffer'] = [];
    foreach (seo_runtime_canonical_service_definitions() as $definition) {
        $out['makesOffer'][] = [
            '@type' => 'Offer',
            'itemOffered' => [
                '@id' => seo_runtime_schema_service_id_for_graph_slug(
                    $canonical_origin,
                    (string) $definition['graph_slug']
                ),
            ],
        ];
    }

    return $out;
}

/**
 * @return array<string, mixed>|null
 */
function seo_runtime_schema_has_offer_catalog(string $canonical_origin): ?array
{
    if (!function_exists('seo_rt_primary_service_lines')) {
        require_once __DIR__ . '/internal_linking.php';
    }
    if (!function_exists('seo_rt_primary_service_public_url')) {
        require_once __DIR__ . '/paths.php';
    }

    if (!function_exists('seo_runtime_canonical_service_definitions')) {
        require_once __DIR__ . '/default_service_faqs.php';
    }
    $offers = [];
    $pos = 1;
    foreach (seo_runtime_canonical_service_definitions() as $definition) {
        $offers[] = [
            '@type' => 'Offer',
            'position' => $pos,
            'itemOffered' => [
                '@id' => seo_runtime_schema_service_id_for_graph_slug(
                    $canonical_origin,
                    (string) $definition['graph_slug']
                ),
            ],
        ];
        $pos++;
    }
    if ($offers === []) {
        return null;
    }

    return [
        '@type' => 'OfferCatalog',
        'name' => 'MY Nakliyat Birincil Hizmetleri',
        'itemListElement' => $offers,
    ];
}

/**
 * @return list<array{name:string,service_type:string,url:string,graph_slug:string,public_slug:string}>
 */
/**
 * @param list<string> $cssSelectors
 * @return array<string, mixed>|null
 */
function seo_runtime_schema_speakable_webpage(string $canonical, array $cssSelectors = ['h1']): ?array
{
    $canonical = trim($canonical);
    if ($canonical === '') {
        return null;
    }
    $selectors = array_values(array_filter(array_map('trim', $cssSelectors)));
    if ($selectors === []) {
        return null;
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'url' => $canonical,
        'speakable' => [
            '@type' => 'SpeakableSpecification',
            'cssSelector' => count($selectors) === 1 ? $selectors[0] : $selectors,
        ],
    ];
}

/**
 * @return list<string>
 */
function seo_runtime_speakable_slugs(): array
{
    return [
        'izmir-evden-eve-nakliyat',
        'izmir-evden-eve-nakliyat-fiyatlari-2026',
        'izmir-evden-eve-nakliyat-yorumlari',
    ];
}

function seo_runtime_schema_speakable_ld_script(string $page_type, string $canonical, string $contentSlug = ''): string
{
    $contentSlug = trim($contentSlug);
    if ($page_type === 'home') {
        $node = seo_runtime_schema_speakable_webpage($canonical, ['h1.home-seo-h1', '.home-seo-h1']);
    } elseif ($page_type === 'service' && in_array($contentSlug, seo_runtime_speakable_slugs(), true)) {
        $node = seo_runtime_schema_speakable_webpage($canonical, ['.mynak-answer-box', '.page-banner h1']);
    } elseif ($page_type === 'blog_post' && in_array($contentSlug, seo_runtime_speakable_slugs(), true)) {
        $node = seo_runtime_schema_speakable_webpage($canonical, ['h1.blog-title', '.blog-title']);
    } else {
        return '';
    }
    if (!is_array($node)) {
        return '';
    }

    return seo_runtime_ld_script_from_array($node);
}

function seo_runtime_primary_services_api_rows(string $canonical_origin): array
{
    if (!function_exists('seo_rt_primary_service_lines')) {
        require_once __DIR__ . '/internal_linking.php';
    }
    if (!function_exists('seo_rt_primary_service_public_url')) {
        require_once __DIR__ . '/paths.php';
    }
    if (!function_exists('seo_runtime_service_quick_answer')) {
        require_once __DIR__ . '/default_service_faqs.php';
    }

    $rows = [];
    foreach (seo_rt_primary_service_lines() as $line) {
        $url = seo_rt_primary_service_public_url($canonical_origin, (string) $line['graph_slug']);
        $rows[] = [
            'name' => (string) $line['name'],
            'service_type' => (string) $line['service_type'],
            'graph_slug' => (string) $line['graph_slug'],
            'public_slug' => (string) $line['public_slug'],
            'url' => $url,
            'entity_id' => rtrim($url, '/') . '#service',
            'quick_answer' => seo_runtime_service_quick_answer((string) $line['graph_slug']),
        ];
    }

    return $rows;
}

/**
 * GBP cache + settings'tan ek sameAs URL'leri (Google review/map linki).
 * Cache veya settings'ta varsa eklenir; yoksa boş donerse moving_company_schema dokunmaz.
 *
 * @param array<string, mixed> $site_settings
 * @return list<string>
 */
function seo_runtime_schema_extra_same_as(array $site_settings): array
{
    $out = [];

    $out[] = 'https://www.wikidata.org/wiki/Q140273727';

    // Google Maps URL (settings.google_maps_url)
    $gMapsUrl = trim((string) ($site_settings['google_maps_url'] ?? ''));
    if ($gMapsUrl !== '' && filter_var($gMapsUrl, FILTER_VALIDATE_URL)) {
        $out[] = $gMapsUrl;
    }

    // Place ID -> Search.google.com/local/reviews?placeid=
    $placeId = trim((string) ($site_settings['google_place_id'] ?? ''));
    if ($placeId !== '') {
        $out[] = 'https://search.google.com/local/reviews?placeid=' . rawurlencode($placeId);
    }

    return $out;
}

/**
 * @param array<string,mixed> $data
 * @return array<string,mixed>|null
 */
function seo_runtime_schema_aggregate_rating_from_gbp_data(array $data): ?array
{
    if (($data['source'] ?? '') !== 'places_details') {
        return null;
    }

    $rating = $data['rating'] ?? null;
    $count = (int) ($data['user_ratings_total'] ?? 0);
    if (!is_numeric($rating) || (float) $rating <= 0 || (float) $rating > 5 || $count < 1) {
        return null;
    }

    return [
        '@type' => 'AggregateRating',
        'ratingValue' => (string) $rating,
        'reviewCount' => $count,
        'bestRating' => '5',
        'worstRating' => '1',
    ];
}

/**
 * @return array<string, mixed>|null
 */
function seo_runtime_schema_aggregate_rating_from_gbp_cache(): ?array
{
    if (!function_exists('mynak_gbp_cache_file_path')) {
        return null;
    }

    $path = mynak_gbp_cache_file_path();
    if (!is_readable($path)) {
        return null;
    }

    $data = json_decode((string) file_get_contents($path), true);

    return is_array($data) ? seo_runtime_schema_aggregate_rating_from_gbp_data($data) : null;
}

/**
 * Sayfa tipi JSON-LD parçası (tek script). Tam head: schema_factory(..., full_head_context).
 *
 * @param array<string,mixed> $canonical_pipeline_core
 * @param array<string,mixed> $flex
 * @param ?array<string,mixed> $page
 * @param ?array<string,mixed> $blog
 */
function schema_factory_page_type_ld_fragment(
    string $page_type,
    array $canonical_pipeline_core,
    array $flex,
    string $canonical,
    string $canonical_origin,
    array $site_settings,
    string $moving_company_at_id,
    ?array $page,
    ?array $blog
): string {
    if ($moving_company_at_id === '') {
        $moving_company_at_id = seo_runtime_schema_organization_id($canonical_origin);
    }

    $locVec = isset($canonical_pipeline_core['location_vector']) && is_array($canonical_pipeline_core['location_vector'])
        ? $canonical_pipeline_core['location_vector']
        : canonical_seo_pipeline_location_vector($page_type);

    switch ($page_type) {
        case 'service':
            if (!is_array($page)) {
                return '';
            }
            $name = (string) ($flex['service_name'] ?? '');
            if ($name === '') {
                return '';
            }
            $slug = (string) ($flex['service_slug'] ?? '');
            $graphSlug = seo_runtime_schema_graph_slug_from_pipeline($canonical_pipeline_core, $slug);
            if (!function_exists('seo_runtime_service_quick_answer')) {
                require_once __DIR__ . '/default_service_faqs.php';
            }
            $defsForCat = seo_rt_pillar_cluster_definitions();
            $intentLabel = $slug !== '' ? seo_ei_primary_intent_label_for_slug($slug) : '';
            $svc = [
                '@context' => 'https://schema.org',
                '@type' => 'Service',
                '@id' => seo_runtime_schema_service_id_for_url($canonical),
                'name' => $name,
                'description' => seo_runtime_service_quick_answer($graphSlug),
                'serviceType' => $intentLabel !== '' ? $intentLabel : seo_runtime_infer_service_type_label($slug),
                'provider' => ['@id' => $moving_company_at_id],
                'areaServed' => seo_runtime_schema_area_served_for_pipeline($site_settings, $locVec, $canonical_origin),
                'audience' => [
                    '@type' => 'BusinessAudience',
                    'audienceType' => 'corporate_clients',
                    'name' => 'Kurumsal müşteriler ve yüksek değerli konut taşımacılığı ihtiyacı olan hane halkı',
                ],
            ];
            if ($slug !== '') {
                $svc['url'] = $canonical;
                $svc['mainEntityOfPage'] = ['@id' => seo_runtime_schema_webpage_id($canonical)];
                $aliases = seo_runtime_schema_service_aliases($graphSlug);
                if ($aliases !== []) {
                    $svc['alternateName'] = $aliases;
                }
                $svc['category'] = seo_ei_cluster_category_label($slug, $defsForCat);
                $eiProps = seo_ei_service_additional_properties($slug);
                $isDistrictSingle = ($locVec['area_served_mode'] ?? '') === 'district_single'
                    && trim((string) ($locVec['primary_district'] ?? '')) !== '';
                $primaryDistrictValue = $isDistrictSingle
                    ? (string) $locVec['primary_district']
                    : implode(', ', seo_ei_izmir_district_names_local_pack_order());
                $propNodes = [
                    [
                        '@type' => 'PropertyValue',
                        'name' => 'primary_service_location',
                        'value' => $isDistrictSingle
                            ? ((string) $locVec['primary_district'] . ', İzmir, Türkiye')
                            : 'İzmir, Türkiye',
                    ],
                    [
                        '@type' => 'PropertyValue',
                        'name' => $isDistrictSingle ? 'primary_service_district' : 'secondary_service_districts',
                        'value' => $primaryDistrictValue,
                    ],
                    [
                        '@type' => 'PropertyValue',
                        'name' => 'service_type_label',
                        'value' => 'professional_moving_service',
                    ],
                ];
                foreach ($eiProps as $p) {
                    $nm = (string) ($p['name'] ?? '');
                    $val = $p['value'] ?? '';
                    if ($nm === '') {
                        continue;
                    }
                    if (is_array($val)) {
                        $val = implode(',', array_map('strval', $val));
                    }
                    $propNodes[] = [
                        '@type' => 'PropertyValue',
                        'name' => $nm,
                        'value' => (string) $val,
                    ];
                }
                $svc['additionalProperty'] = count($propNodes) === 1 ? $propNodes[0] : $propNodes;
            }

            $svc['offers'] = [
                '@type' => 'Offer',
                'businessFunction' => 'https://schema.org/Sell',
                'description' => 'Talep bilgilerine göre yazılı teklif hazırlanır; sözleşme ve güvence seçeneklerinin kapsamı teklif aşamasında belirtilir.',
                'url' => rtrim($canonical_origin, '/') . '/teklif-alin',
                'seller' => ['@id' => $moving_company_at_id],
            ];

            $svc['brand'] = ['@id' => seo_runtime_schema_brand_id($canonical_origin)];
            if (isset($defsForCat[$graphSlug]['related']) && is_array($defsForCat[$graphSlug]['related'])) {
                $relatedRefs = [];
                $primaryGraphSlugs = seo_rt_primary_service_graph_slugs();
                foreach ($defsForCat[$graphSlug]['related'] as $relatedSlug) {
                    if (!in_array($relatedSlug, $primaryGraphSlugs, true)) {
                        continue;
                    }
                    $relatedRefs[] = [
                        '@id' => seo_runtime_schema_service_id_for_graph_slug($canonical_origin, (string) $relatedSlug),
                    ];
                }
                if ($relatedRefs !== []) {
                    $svc['isRelatedTo'] = $relatedRefs;
                }
            }

            // Service.serviceOutput — hizmetin somut çıktısı (yazılı teklif + sigorta + sözleşme).
            $svc['serviceOutput'] = [
                '@type' => 'Thing',
                'name' => 'Yazılı teklif ile kapsamı belirtilen sözleşme ve güvence seçenekleri',
            ];

            // aggregateRating Service üzerinde kullanılmaz (GSC Review snippets: geçersiz parent_node).
            // GBP puanı yalnızca MovingCompany/LocalBusiness şemasında (provider @id ile bağlı).

            // Service.hoursAvailable — MovingCompany openingHoursSpecification'ının yeniden kullanımı.
            $svcOhSpecs = seo_runtime_schema_opening_hours_from_site_settings($site_settings);
            if (is_array($svcOhSpecs) && $svcOhSpecs !== []) {
                $svc['hoursAvailable'] = count($svcOhSpecs) === 1 ? $svcOhSpecs[0] : $svcOhSpecs;
            }

            // Service.termsOfService — site_settings'te geçerli URL tanımlıysa ekle (uydurma URL basılmaz).
            $svcTos = '';
            if (isset($site_settings['terms_of_service_url']) && is_string($site_settings['terms_of_service_url'])) {
                $svcTos = trim($site_settings['terms_of_service_url']);
            }
            if ($svcTos !== '' && filter_var($svcTos, FILTER_VALIDATE_URL)) {
                $svc['termsOfService'] = $svcTos;
            }

            $out = seo_runtime_ld_script_from_array($svc);
            if (!function_exists('seo_runtime_service_published_faq_pairs')) {
                require_once __DIR__ . '/default_service_faqs.php';
            }
            $faqPairs = seo_runtime_service_published_faq_pairs($slug);
            if ($faqPairs !== []) {
                $faqLd = function_exists('seo_runtime_faq_page_ld')
                    ? seo_runtime_faq_page_ld($faqPairs)
                    : seo_runtime_faq_page_ld_fallback($faqPairs);
                if (is_array($faqLd) && $faqLd !== []) {
                    $out .= seo_runtime_ld_script_from_array($faqLd);
                }
            }

            $out .= seo_runtime_schema_speakable_ld_script('service', $canonical, $slug);

            return $out;

        case 'blog':
        case 'blog_index':
            $base = rtrim($canonical_origin, '/');
            $blogIndexName = (string) ($flex['blog_index_name'] ?? 'Blog');
            $defsList = seo_rt_pillar_cluster_definitions();
            $listPack = deterministic_blog_index_factory($canonical_pipeline_core, $defsList, $canonical_origin);
            $coll = [
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                '@id' => seo_runtime_schema_webpage_id($canonical),
                'name' => $blogIndexName,
                'url' => $canonical,
                'isPartOf' => ['@id' => $base . '/#website'],
                'publisher' => ['@id' => $moving_company_at_id],
                'mainEntity' => [
                    '@type' => 'ItemList',
                    'numberOfItems' => (int) ($listPack['numberOfItems'] ?? 0),
                    'itemListElement' => isset($listPack['itemListElement']) && is_array($listPack['itemListElement'])
                        ? $listPack['itemListElement']
                        : [],
                ],
            ];

            return seo_runtime_ld_script_from_array($coll);

        case 'blog_post':
            if (!is_array($blog)) {
                return '';
            }
            $bp = isset($flex['blog_post']) && is_array($flex['blog_post']) ? $flex['blog_post'] : null;
            if ($bp === null) {
                return '';
            }
            $orgId = $moving_company_at_id;
            $orgName = (string) ($flex['organization_name'] ?? mynak_schema_brand());
            $headline = (string) ($bp['headline'] ?? '');
            if ($headline === '') {
                return '';
            }
            $postUrl = (string) ($bp['url'] ?? '');
            $origin = rtrim($canonical_origin, '/') . '/';

            // Author: authors tablosu → settings → publisher org fallback.
            if (!function_exists('seo_runtime_resolve_blog_author')) {
                require_once __DIR__ . '/author_resolver.php';
            }
            $authorNode = seo_runtime_resolve_blog_author($blog, $site_settings, $orgId, $orgName, $origin);
            $datePub = (string) ($bp['date_published'] ?? '');
            $dateMod = (string) ($bp['date_modified'] ?? '');
            if ($dateMod === '' && $datePub !== '') {
                $dateMod = $datePub;
            }
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'BlogPosting',
                '@id' => rtrim($canonical, '/') . '#article',
                'headline' => $headline,
                'description' => (string) ($bp['description'] ?? ''),
                'mainEntityOfPage' => [
                    '@id' => seo_runtime_schema_webpage_id($postUrl !== '' ? $postUrl : $canonical),
                ],
                'isPartOf' => ['@id' => seo_runtime_schema_website_id($canonical_origin)],
                'author' => $authorNode,
                'publisher' => $orgId !== ''
                    ? ['@id' => $orgId]
                    : [
                        '@type' => 'Organization',
                        'name' => $orgName,
                        'url' => $origin,
                    ],
                'articleBody' => (string) ($bp['article_body_plain'] ?? ''),
                'wordCount' => (int) ($bp['word_count'] ?? 0),
            ];
            if ($datePub !== '') {
                $schema['datePublished'] = $datePub;
            }
            if ($dateMod !== '') {
                $schema['dateModified'] = $dateMod;
            }
            $img = (string) ($bp['image_url'] ?? '');
            $imgW = (int) ($bp['image_width'] ?? 0);
            $imgH = (int) ($bp['image_height'] ?? 0);
            if ($img !== '') {
                $schema['image'] = [
                    '@type' => 'ImageObject',
                    'url' => $img,
                ];
                if ($imgW > 0 && $imgH > 0) {
                    $schema['image']['width'] = $imgW;
                    $schema['image']['height'] = $imgH;
                }
            }
            if ($postUrl !== '') {
                $schema['url'] = $postUrl;
            }
            $articleGraphSlug = seo_runtime_schema_infer_blog_service_graph_slug($blog, $canonical_pipeline_core);
            $articleServiceId = seo_runtime_schema_canonical_service_id($canonical_origin, $articleGraphSlug);
            if ($articleServiceId !== '') {
                $schema['about'] = ['@id' => $articleServiceId];
            }
            $schema['mentions'] = [
                ['@id' => $moving_company_at_id],
                ['@id' => seo_runtime_schema_place_id($canonical_origin, 'İzmir')],
            ];
            $schema['inLanguage'] = 'tr-TR';

            $out = seo_runtime_ld_script_from_array($schema);
            if (function_exists('seo_runtime_extract_faq_pairs_from_html') && is_array($blog) && !empty($blog['icerik'])) {
                $faqPairs = seo_runtime_extract_faq_pairs_from_html((string) $blog['icerik']);
                $faqLd = function_exists('seo_runtime_faq_page_ld') ? seo_runtime_faq_page_ld($faqPairs) : null;
                if (is_array($faqLd)) {
                    $out .= seo_runtime_ld_script_from_array($faqLd);
                }
            }

            $blogSlug = is_array($blog) ? trim((string) ($blog['slug'] ?? '')) : '';
            $out .= seo_runtime_schema_speakable_ld_script('blog_post', $canonical, $blogSlug);

            return $out;

        case 'contact':
            $cp = [];
            if (!empty($site_settings['phone1'])) {
                $cp[] = [
                    '@type' => 'ContactPoint',
                    'contactType' => 'customer support',
                    'telephone' => (string) $site_settings['phone1'],
                    'areaServed' => 'TR',
                ];
            }
            $contactName = (string) ($flex['contact_page_name'] ?? 'İletişim');
            $cont = [
                '@context' => 'https://schema.org',
                '@type' => 'ContactPage',
                '@id' => seo_runtime_schema_webpage_id($canonical),
                'name' => $contactName,
                'isPartOf' => ['@id' => seo_runtime_schema_website_id($canonical_origin)],
                'url' => $canonical,
                'mainEntity' => ['@id' => $moving_company_at_id],
            ];
            if (!empty($site_settings['address'])) {
                $cont['about'] = [
                    '@type' => 'PostalAddress',
                    'streetAddress' => (string) $site_settings['address'],
                    'addressLocality' => 'İzmir',
                    'addressCountry' => 'TR',
                ];
            }
            if ($cp !== []) {
                $cont['contactPoint'] = count($cp) === 1 ? $cp[0] : $cp;
            }

            return seo_runtime_ld_script_from_array($cont);

        case 'about':
            $titleDisp = (string) ($flex['page_title_display'] ?? '');
            $desc = (string) ($flex['about_description'] ?? '');
            $ab = [
                '@context' => 'https://schema.org',
                '@type' => 'AboutPage',
                '@id' => seo_runtime_schema_webpage_id($canonical),
                'name' => $titleDisp,
                'isPartOf' => ['@id' => seo_runtime_schema_website_id($canonical_origin)],
                'url' => $canonical,
                'description' => $desc,
                'mainEntity' => ['@id' => $moving_company_at_id],
            ];

            return seo_runtime_ld_script_from_array($ab);

        case 'home':
            if (!function_exists('seo_rt_primary_service_lines')) {
                require_once __DIR__ . '/internal_linking.php';
            }
            if (!function_exists('seo_rt_primary_service_public_url')) {
                require_once __DIR__ . '/paths.php';
            }
            $elements = [];
            $pos = 1;
            foreach (seo_rt_primary_service_lines() as $line) {
                $elements[] = [
                    '@type' => 'ListItem',
                    'position' => $pos,
                    'item' => [
                        '@id' => seo_runtime_schema_service_id_for_graph_slug(
                            $canonical_origin,
                            (string) $line['graph_slug']
                        ),
                    ],
                ];
                $pos++;
            }
            if ($elements === []) {
                return '';
            }

            $out = seo_runtime_ld_script_from_array([
                '@context' => 'https://schema.org',
                '@type' => 'ItemList',
                '@id' => rtrim($canonical_origin, '/') . '/#services',
                'name' => 'MY Nakliyat Profesyonel Hizmetler',
                'numberOfItems' => count($elements),
                'itemListElement' => $elements,
            ]);

            return $out . seo_runtime_schema_speakable_ld_script('home', $canonical);

        case 'llms_export':
            return '';

        default:
            return '';
    }
}

// seo_runtime_default_faq_pairs_for_service_slug() artık ayrı dosyada (default_service_faqs.php)
// Tüm hizmet slug'ları için 4-6 zengin Q&A içerir.

/**
 * @param list<array{q:string,a:string}> $pairs
 * @return array<string,mixed>|null
 */
function seo_runtime_faq_page_ld_fallback(array $pairs): ?array
{
    $main = [];
    foreach ($pairs as $pair) {
        $q = trim((string) ($pair['q'] ?? ''));
        $a = trim((string) ($pair['a'] ?? ''));
        if ($q === '' || $a === '') {
            continue;
        }
        $main[] = [
            '@type' => 'Question',
            'name' => $q,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $a,
            ],
        ];
    }
    if ($main === []) {
        return null;
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $main,
    ];
}

/** @return list<array<string,mixed>> */
function seo_runtime_schema_nodes_from_markup(string $markup): array
{
    if ($markup === '') {
        return [];
    }
    preg_match_all('#<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $markup, $matches);
    $nodes = [];
    foreach ($matches[1] ?? [] as $raw) {
        $decoded = json_decode(trim((string) $raw), true);
        if (!is_array($decoded)) {
            continue;
        }
        if (isset($decoded['@graph']) && is_array($decoded['@graph'])) {
            foreach ($decoded['@graph'] as $node) {
                if (is_array($node)) {
                    unset($node['@context']);
                    $nodes[] = $node;
                }
            }
            continue;
        }
        unset($decoded['@context']);
        $nodes[] = $decoded;
    }

    return $nodes;
}

function seo_runtime_schema_array_is_list(array $value): bool
{
    if ($value === []) {
        return true;
    }

    return array_keys($value) === range(0, count($value) - 1);
}

function seo_runtime_schema_merge_value($current, $incoming)
{
    if ($current === $incoming || $incoming === null || $incoming === '' || $incoming === []) {
        return $current;
    }
    if ($current === null || $current === '' || $current === []) {
        return $incoming;
    }
    if (is_array($current) && is_array($incoming)) {
        if (!seo_runtime_schema_array_is_list($current) && !seo_runtime_schema_array_is_list($incoming)) {
            foreach ($incoming as $key => $value) {
                $current[$key] = array_key_exists($key, $current)
                    ? seo_runtime_schema_merge_value($current[$key], $value)
                    : $value;
            }

            return $current;
        }
        $left = seo_runtime_schema_array_is_list($current) ? $current : [$current];
        $right = seo_runtime_schema_array_is_list($incoming) ? $incoming : [$incoming];
        $seen = [];
        $merged = [];
        foreach (array_merge($left, $right) as $value) {
            $key = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($key === false || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $merged[] = $value;
        }

        return $merged;
    }
    if (is_string($current) && is_string($incoming)) {
        return array_values(array_unique([$current, $incoming]));
    }

    return $current;
}

/** @param list<array<string,mixed>> $nodes @return list<array<string,mixed>> */
function seo_runtime_schema_dedupe_nodes(array $nodes): array
{
    $byId = [];
    $anonymous = [];
    foreach ($nodes as $node) {
        unset($node['@context']);
        $id = trim((string) ($node['@id'] ?? ''));
        if ($id === '') {
            $key = json_encode($node, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($key !== false) {
                $anonymous[$key] = $node;
            }
            continue;
        }
        if (!isset($byId[$id])) {
            $byId[$id] = $node;
            continue;
        }
        foreach ($node as $key => $value) {
            $byId[$id][$key] = array_key_exists($key, $byId[$id])
                ? seo_runtime_schema_merge_value($byId[$id][$key], $value)
                : $value;
        }
    }

    return array_values(array_merge($byId, $anonymous));
}

/** @param list<array<string,mixed>> $nodes @return list<array<string,string>> */
function seo_runtime_schema_node_refs(array $nodes, array $excludedTypes = []): array
{
    $refs = [];
    foreach ($nodes as $node) {
        $types = isset($node['@type']) && is_array($node['@type'])
            ? $node['@type']
            : [($node['@type'] ?? '')];
        if (array_intersect($excludedTypes, $types) !== []) {
            continue;
        }
        $id = trim((string) ($node['@id'] ?? ''));
        if ($id !== '') {
            $refs[] = ['@id' => $id];
        }
    }

    return $refs;
}

function seo_runtime_schema_area_refs($areaServed)
{
    if (!is_array($areaServed)) {
        return $areaServed;
    }
    $items = seo_runtime_schema_array_is_list($areaServed) ? $areaServed : [$areaServed];
    $refs = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $id = trim((string) ($item['@id'] ?? ''));
        if ($id !== '') {
            $refs[] = ['@id' => $id];
        }
    }

    return $refs !== [] ? $refs : $areaServed;
}

/** @return list<array<string,mixed>> */
function seo_runtime_schema_template_nodes(string $canonical, string $organizationId, string $relPath): array
{
    $nodes = [];
    $person = $GLOBALS['mynak_person_jsonld'] ?? null;
    if (is_array($person) && !empty($person['@id'])) {
        unset($person['@context']);
        $person['worksFor'] = ['@id' => $organizationId];
        $nodes[] = $person;
    }

    $rows = $GLOBALS['listRows'] ?? null;
    if (trim($relPath, '/') === 'musteri-hikayeleri' && is_array($rows) && $rows !== []) {
        $elements = [];
        foreach ($rows as $index => $row) {
            if (!is_array($row) || empty($row['slug']) || empty($row['baslik'])) {
                continue;
            }
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => preg_replace('#/\\#organization$#', '', $organizationId)
                    . '/musteri-hikayeleri/' . rawurlencode((string) $row['slug']),
                'name' => (string) $row['baslik'],
            ];
        }
        if ($elements !== []) {
            $nodes[] = [
                '@type' => 'ItemList',
                '@id' => rtrim($canonical, '/') . '#stories',
                'name' => 'MY Nakliyat Müşteri Hikayeleri',
                'itemListElement' => $elements,
            ];
        }
    }

    return $nodes;
}

function seo_runtime_schema_case_study_review_node(string $canonical, string $organizationId): ?array
{
    $caseStudy = $GLOBALS['cs'] ?? null;
    if (!is_array($caseStudy)) {
        return null;
    }
    $body = trim((string) ($caseStudy['musteri_yorumu'] ?? ''));
    $reviewerName = trim((string) ($caseStudy['musteri_ad'] ?? ''));
    if (!function_exists('mynak_blog_service_context')) {
        require_once __DIR__ . '/service_guide_hubs.php';
    }
    $serviceContext = function_exists('mynak_blog_service_context')
        ? mynak_blog_service_context($caseStudy)
        : null;
    if ($body === '' || $reviewerName === '' || !is_array($serviceContext)) {
        return null;
    }
    $serviceId = rtrim(seo_runtime_schema_origin($canonical), '/')
        . '/' . ltrim((string) $serviceContext['service_slug'], '/') . '#service';
    $node = [
        '@type' => 'Review',
        '@id' => rtrim($canonical, '/') . '#review',
        'url' => $canonical,
        'itemReviewed' => ['@id' => $serviceId],
        'publisher' => ['@id' => $organizationId],
        'name' => (string) ($caseStudy['baslik'] ?? 'Müşteri Hikayesi'),
        'reviewBody' => $body,
        'author' => [
            '@type' => 'Person',
            'name' => $reviewerName,
        ],
        'isPartOf' => ['@id' => seo_runtime_schema_webpage_id($canonical)],
    ];
    $rating = (float) ($caseStudy['puan'] ?? 0);
    if ($rating > 0 && $rating <= 5) {
        $node['reviewRating'] = [
            '@type' => 'Rating',
            'ratingValue' => (string) $rating,
            'bestRating' => '5',
            'worstRating' => '1',
        ];
    }
    if (!empty($caseStudy['created_at'])) {
        $timestamp = strtotime((string) $caseStudy['created_at']);
        if ($timestamp !== false) {
            $node['datePublished'] = date('c', $timestamp);
        }
    }

    return $node;
}

function seo_runtime_schema_page_name(string $pageType, array $flex, ?array $page, ?array $blog): string
{
    $candidates = [
        $flex['service_name'] ?? '',
        $flex['page_title_display'] ?? '',
        $flex['contact_page_name'] ?? '',
        $flex['blog_index_name'] ?? '',
        isset($flex['blog_post']['headline']) ? $flex['blog_post']['headline'] : '',
        is_array($page) ? ($page['title'] ?? $page['baslik'] ?? '') : '',
        is_array($blog) ? ($blog['baslik'] ?? '') : '',
    ];
    foreach ($candidates as $candidate) {
        $name = trim((string) $candidate);
        if ($name !== '') {
            return $name;
        }
    }

    return $pageType === 'home' ? mynak_schema_brand() : '';
}

/** @return array<string,mixed> */
function seo_runtime_schema_webpage_node(
    string $pageType,
    string $canonical,
    string $canonicalOrigin,
    string $name,
    string $mainEntityId,
    bool $hasBreadcrumb
): array {
    $node = [
        '@type' => 'WebPage',
        '@id' => seo_runtime_schema_webpage_id($canonical),
        'url' => $canonical,
        'isPartOf' => ['@id' => seo_runtime_schema_website_id($canonicalOrigin)],
        'about' => ['@id' => $mainEntityId],
        'mainEntity' => ['@id' => $mainEntityId],
        'inLanguage' => 'tr-TR',
    ];
    if ($name !== '') {
        $node['name'] = $name;
    }
    if ($hasBreadcrumb) {
        $node['breadcrumb'] = ['@id' => rtrim($canonical, '/') . '#breadcrumb'];
    }

    return $node;
}

/** @return list<array<string,mixed>> */
function seo_runtime_schema_connected_graph_nodes(
    string $pageType,
    array $pipelineCore,
    array $flex,
    string $canonical,
    string $canonicalOrigin,
    array $siteSettings,
    string $organizationId,
    ?array $page,
    ?array $blog,
    string $relPath,
    string $pageFragment
): array {
    $locVec = isset($pipelineCore['location_vector']) && is_array($pipelineCore['location_vector'])
        ? $pipelineCore['location_vector']
        : canonical_seo_pipeline_location_vector($pageType);
    $contentSlug = is_array($page) && !empty($page['slug'])
        ? (string) $page['slug']
        : (is_array($blog) && !empty($blog['slug']) ? (string) $blog['slug'] : trim($relPath, '/'));
    $placeNodes = seo_runtime_schema_place_nodes_for_page(
        $canonicalOrigin,
        $contentSlug,
        $siteSettings,
        $locVec
    );
    $nodes = [
        schema_factory_build_moving_company_graph($siteSettings, $canonicalOrigin, $locVec, $organizationId),
        seo_runtime_schema_brand_node($canonicalOrigin),
        seo_runtime_schema_website_home_graph($canonicalOrigin, $siteSettings, $organizationId),
    ];
    $templateNodes = seo_runtime_schema_template_nodes($canonical, $organizationId, $relPath);
    $nodes = array_merge(
        $nodes,
        $placeNodes,
        seo_runtime_schema_nodes_from_markup($pageFragment),
        $templateNodes
    );
    $relatedBlogGraphSlug = '';
    if ($pageType === 'home') {
        $nodes = array_merge($nodes, seo_runtime_schema_canonical_service_nodes($canonicalOrigin, $organizationId));
    } elseif ($pageType === 'blog_post' && is_array($blog)) {
        $relatedBlogGraphSlug = seo_runtime_schema_infer_blog_service_graph_slug($blog, $pipelineCore);
        $relatedServiceId = seo_runtime_schema_canonical_service_id($canonicalOrigin, $relatedBlogGraphSlug);
        if ($relatedServiceId !== '') {
            foreach (seo_runtime_schema_canonical_service_nodes($canonicalOrigin, $organizationId) as $serviceNode) {
                if (($serviceNode['@id'] ?? '') === $relatedServiceId) {
                    $nodes[] = $serviceNode;
                    break;
                }
            }
        }
    }

    $breadcrumb = null;
    if ($relPath !== '') {
        require_once dirname(__DIR__) . '/breadcrumb_jsonld.php';
        $crumbs = mynak_breadcrumb_build_items($relPath, $canonical, $canonicalOrigin);
        $breadcrumb = mynak_breadcrumb_schema_from_items($crumbs);
        if (is_array($breadcrumb)) {
            unset($breadcrumb['@context']);
            $breadcrumb['@id'] = rtrim($canonical, '/') . '#breadcrumb';
            $nodes[] = $breadcrumb;
        }
    }

    $reviewNode = seo_runtime_schema_case_study_review_node($canonical, $organizationId);
    if ($reviewNode !== null) {
        $nodes[] = $reviewNode;
    }

    $graphSlug = $pageType === 'service'
        ? seo_runtime_schema_graph_slug_from_pipeline($pipelineCore, $contentSlug)
        : $relatedBlogGraphSlug;
    $mainEntityId = $organizationId;
    if ($pageType === 'service') {
        $mainEntityId = seo_runtime_schema_service_id_for_url($canonical);
    } elseif ($pageType === 'blog_post') {
        $mainEntityId = rtrim($canonical, '/') . '#article';
    } elseif ($pageType === 'video_watch') {
        foreach ($nodes as $candidateNode) {
            $candidateTypes = isset($candidateNode['@type']) && is_array($candidateNode['@type'])
                ? $candidateNode['@type']
                : [($candidateNode['@type'] ?? '')];
            if (in_array('VideoObject', $candidateTypes, true) && is_string($candidateNode['@id'] ?? null)) {
                $mainEntityId = (string) $candidateNode['@id'];
                break;
            }
        }
    } elseif ($pageType === 'home') {
        $mainEntityId = rtrim($canonicalOrigin, '/') . '/#services';
    } elseif ($reviewNode !== null) {
        $mainEntityId = (string) $reviewNode['@id'];
    } else {
        foreach ($templateNodes as $templateNode) {
            $templateType = (string) ($templateNode['@type'] ?? '');
            if (in_array($templateType, ['Person', 'ItemList'], true) && !empty($templateNode['@id'])) {
                $mainEntityId = (string) $templateNode['@id'];
                break;
            }
        }
    }
    $nodes[] = seo_runtime_schema_webpage_node(
        $pageType,
        $canonical,
        $canonicalOrigin,
        seo_runtime_schema_page_name($pageType, $flex, $page, $blog),
        $mainEntityId,
        $breadcrumb !== null
    );

    $placeRefs = seo_runtime_schema_node_refs($placeNodes, ['Country']);
    $faqId = rtrim($canonical, '/') . '#faq';
    $hasFaqNode = false;
    foreach ($nodes as $candidateNode) {
        $candidateTypes = isset($candidateNode['@type']) && is_array($candidateNode['@type'])
            ? $candidateNode['@type']
            : [($candidateNode['@type'] ?? '')];
        if (in_array('FAQPage', $candidateTypes, true)) {
            $hasFaqNode = true;
            break;
        }
    }
    foreach ($nodes as &$node) {
        unset($node['@context']);
        $types = isset($node['@type']) && is_array($node['@type'])
            ? $node['@type']
            : [($node['@type'] ?? '')];
        if (in_array('Organization', $types, true) || in_array('MovingCompany', $types, true)) {
            $node['@id'] = $organizationId;
            $node['brand'] = ['@id' => seo_runtime_schema_brand_id($canonicalOrigin)];
            if (isset($node['areaServed'])) {
                $node['areaServed'] = seo_runtime_schema_area_refs($node['areaServed']);
            }
            if ($reviewNode !== null) {
                $node['review'] = [['@id' => (string) $reviewNode['@id']]];
            }
        }
        if (in_array('WebSite', $types, true)) {
            $node['@id'] = seo_runtime_schema_website_id($canonicalOrigin);
            $node['publisher'] = ['@id' => $organizationId];
            $node['about'] = ['@id' => $organizationId];
        }
        if (in_array('Service', $types, true)) {
            $nodeId = (string) ($node['@id'] ?? '');
            $isCurrentService = $nodeId === seo_runtime_schema_service_id_for_url($canonical);
            $node['provider'] = ['@id' => $organizationId];
            $node['brand'] = ['@id' => seo_runtime_schema_brand_id($canonicalOrigin)];
            if (isset($node['areaServed'])) {
                $node['areaServed'] = $isCurrentService && $placeRefs !== []
                    ? $placeRefs
                    : seo_runtime_schema_area_refs($node['areaServed']);
            }
            $isRelatedBlogService = $pageType === 'blog_post'
                && $graphSlug !== ''
                && $nodeId === seo_runtime_schema_service_id_for_graph_slug($canonicalOrigin, $graphSlug);
            if ($isRelatedBlogService) {
                $node['subjectOf'] = [['@id' => rtrim($canonical, '/') . '#article']];
            }
            if ($isCurrentService) {
                $node['mainEntityOfPage'] = ['@id' => seo_runtime_schema_webpage_id($canonical)];
                $subjectRefs = $hasFaqNode ? [['@id' => $faqId]] : [];
                if (!function_exists('mynak_service_guide_article_refs')) {
                    require_once __DIR__ . '/service_guide_hubs.php';
                }
                $subjectRefs = array_merge(
                    $subjectRefs,
                    mynak_service_guide_article_refs($canonicalOrigin, $graphSlug !== '' ? $graphSlug : $contentSlug)
                );
                if ($subjectRefs !== []) {
                    $node['subjectOf'] = $subjectRefs;
                }
                $aliases = seo_runtime_schema_service_aliases($graphSlug);
                if ($aliases !== []) {
                    $node['alternateName'] = $aliases;
                }
            }
        }
        if (in_array('FAQPage', $types, true)) {
            $node['@id'] = $faqId;
            $node['isPartOf'] = ['@id' => seo_runtime_schema_webpage_id($canonical)];
            $node['about'] = ['@id' => $mainEntityId];
            $node['inLanguage'] = 'tr-TR';
        }
        if (in_array('BlogPosting', $types, true)) {
            $node['@id'] = rtrim($canonical, '/') . '#article';
            $node['mainEntityOfPage'] = ['@id' => seo_runtime_schema_webpage_id($canonical)];
            $node['isPartOf'] = ['@id' => seo_runtime_schema_website_id($canonicalOrigin)];
        }
        if (in_array('CollectionPage', $types, true) || in_array('ContactPage', $types, true) || in_array('AboutPage', $types, true) || in_array('WebPage', $types, true)) {
            $node['@id'] = seo_runtime_schema_webpage_id($canonical);
            $node['isPartOf'] = ['@id' => seo_runtime_schema_website_id($canonicalOrigin)];
            if ($breadcrumb !== null) {
                $node['breadcrumb'] = ['@id' => (string) $breadcrumb['@id']];
            }
        }
        if (in_array('BreadcrumbList', $types, true)) {
            $node['@id'] = rtrim($canonical, '/') . '#breadcrumb';
        }
    }
    unset($node);

    return seo_runtime_schema_dedupe_nodes($nodes);
}

/**
 * Üretim JSON-LD tek fabrika (yalnız bu yol &lt;script type="application/ld+json"&gt; üretir).
 * full_head_context: tek bağlı @graph içinde marka, işletme, hizmet, yer ve sayfa düğümleri.
 *
 * @param array<string,mixed> $canonical_pipeline_core canonical_seo_pipeline_core çıktısı
 * @param array<string,mixed> $flex flex_content_resolver çıktısı
 * @param ?array<string,mixed> $page yalnızca service satırı varlık teyidi (içerik okunmaz)
 * @param ?array<string,mixed> $blog yalnızca blog_post teyidi
 * @param ?array{relPath?:string} $full_head_context null → yalnızca sayfa tipi parçası (parça modu)
 */
function schema_factory(
    string $page_type,
    array $canonical_pipeline_core,
    array $flex,
    string $canonical,
    string $canonical_origin,
    array $site_settings,
    string $moving_company_at_id,
    ?array $page,
    ?array $blog,
    ?array $full_head_context = null
): string {
    if ($moving_company_at_id === '') {
        $moving_company_at_id = seo_runtime_schema_organization_id($canonical_origin);
    }

    $locVec = isset($canonical_pipeline_core['location_vector']) && is_array($canonical_pipeline_core['location_vector'])
        ? $canonical_pipeline_core['location_vector']
        : canonical_seo_pipeline_location_vector($page_type);

    $pageFragment = schema_factory_page_type_ld_fragment(
        $page_type,
        $canonical_pipeline_core,
        $flex,
        $canonical,
        $canonical_origin,
        $site_settings,
        $moving_company_at_id,
        $page,
        $blog
    );

    if (!function_exists('mynak_schema_video_object_ld_fragment')) {
        require_once dirname(__DIR__) . '/mynak_video_object_schema.php';
    }
    $pageFragment .= mynak_schema_video_object_ld_fragment(
        $page_type,
        $page,
        $blog,
        $canonical,
        $canonical_origin,
        $site_settings,
        $moving_company_at_id,
        $flex
    );

    if ($full_head_context === null) {
        return $pageFragment;
    }

    $relPath = (string) ($full_head_context['relPath'] ?? '');
    $j = isset($canonical_pipeline_core['jsonld_type_set']) && is_array($canonical_pipeline_core['jsonld_type_set'])
        ? $canonical_pipeline_core['jsonld_type_set']
        : [];
    if (empty($j['emit_moving_company_inline'])) {
        return $pageFragment;
    }

    $nodes = seo_runtime_schema_connected_graph_nodes(
        $page_type,
        $canonical_pipeline_core,
        $flex,
        $canonical,
        $canonical_origin,
        $site_settings,
        $moving_company_at_id,
        $page,
        $blog,
        $relPath,
        $pageFragment
    );
    if ($nodes === []) {
        return '';
    }

    return seo_runtime_ld_script_from_array([
        '@context' => 'https://schema.org',
        '@graph' => $nodes,
    ]);
}

/**
 * @param array<string,mixed> $pipeline canonical_seo_pipeline_core | resolve çıktısı
 * @param array<string,mixed> $site_settings
 * @param ?array<string,mixed> $page
 * @param ?array<string,mixed> $blog
 */
function seo_runtime_emit_page_type_jsonld(
    array $pipeline,
    string $canonical,
    string $canonical_origin,
    string $page_title,
    ?array $page,
    ?array $blog,
    array $site_settings,
    string $moving_company_at_id
): string {
    $resolved = (string) ($pipeline['page_type'] ?? 'global');
    $flex = flex_content_resolver([
        'page' => $page,
        'blog' => $blog,
        'site_settings' => $site_settings,
        'page_title' => $page_title,
        'canonical_page_type' => $resolved,
    ]);

    return schema_factory(
        $resolved,
        $pipeline,
        $flex,
        $canonical,
        $canonical_origin,
        $site_settings,
        $moving_company_at_id,
        $page,
        $blog,
        null
    );
}

/**
 * Tam head JSON-LD — yalnızca schema_factory(full_head); seo_runtime_document_head basar.
 *
 * @param array<string,mixed> $structured_context relPath, get, page, blog, site_settings, page_title, moving_company_at_id
 * @param array<string,mixed> $pipeline canonical_seo_pipeline_resolve
 */
function seo_runtime_pipeline_structured_head_markup(
    string $relPath,
    string $canonical,
    string $canonical_origin,
    array $structured_context,
    array $pipeline
): string {
    $ctx = $structured_context;
    $page = isset($ctx['page']) && is_array($ctx['page']) ? $ctx['page'] : null;
    $blog = isset($ctx['blog']) && is_array($ctx['blog']) ? $ctx['blog'] : null;
    $site_settings = isset($ctx['site_settings']) && is_array($ctx['site_settings']) ? $ctx['site_settings'] : [];
    $page_title_ctx = (string) ($ctx['page_title'] ?? '');
    $moving_company_at_id = (string) ($ctx['moving_company_at_id'] ?? '');
    if ($moving_company_at_id === '') {
        $moving_company_at_id = seo_runtime_schema_organization_id($canonical_origin);
    }

    $canonical_page_type = (string) ($pipeline['page_type'] ?? 'global');
    $flex = flex_content_resolver([
        'page' => $page,
        'blog' => $blog,
        'site_settings' => $site_settings,
        'page_title' => $page_title_ctx,
        'canonical_page_type' => $canonical_page_type,
    ]);

    return schema_factory(
        $canonical_page_type,
        $pipeline,
        $flex,
        $canonical,
        $canonical_origin,
        $site_settings,
        $moving_company_at_id,
        $page,
        $blog,
        ['relPath' => $relPath]
    );
}

/**
 * Şablon değişkenleri + istek verisi (tek giriş; header $_GET/URI ile hesap yapmaz).
 *
 * @param array<string,mixed> $template allow_indexing, canonical_override, page, blog, page_title, seo_description, meta_robots_preset, site_settings, seo_fallback_content, canonical_seo_pipeline
 * @return array<string,mixed>
 */
function seo_runtime_head_template_context(array $template): array
{
    return array_merge([
        'request_uri' => $_SERVER['REQUEST_URI'] ?? '/',
        'get' => isset($_GET) && is_array($_GET) ? $_GET : [],
    ], $template);
}
