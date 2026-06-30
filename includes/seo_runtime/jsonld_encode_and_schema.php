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
function seo_runtime_schema_area_served_node(array $site_settings): array
{
    return [
        '@type' => 'City',
        'name' => 'İzmir',
        'containedInPlace' => [
            '@type' => 'AdministrativeArea',
            'name' => 'İzmir',
            'containedInPlace' => [
                '@type' => 'Country',
                'name' => 'Türkiye',
            ],
        ],
    ];
}

/**
 * İzmir metropol + yoğun ilçe düğümleri (seo_ei_izmir_metro_district_location_defs ile SSOT; NAP değildir).
 *
 * @param array<string,mixed> $site_settings
 * @return list<array<string,mixed>>
 */
function seo_runtime_schema_area_served_metro_list(array $site_settings): array
{
    $city = seo_runtime_schema_area_served_node($site_settings);
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
    $countryTr = [
        '@type' => 'Country',
        'name' => 'Türkiye',
    ];
    $cityShell = [
        '@type' => 'City',
        'name' => 'İzmir',
        'containedInPlace' => $countryTr,
    ];
    foreach ($districts as $d) {
        $out[] = [
            '@type' => 'AdministrativeArea',
            'name' => $d,
            'containedInPlace' => $cityShell,
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
function seo_runtime_schema_area_served_for_pipeline(array $site_settings, array $locationVector): array
{
    $mode = (string) ($locationVector['area_served_mode'] ?? 'metro_districts');
    if ($mode === 'district_single') {
        $district = trim((string) ($locationVector['primary_district'] ?? ''));
        if ($district !== '') {
            return seo_runtime_schema_area_served_district_single($site_settings, $district);
        }
    }
    if ($mode === 'city_only') {
        return [seo_runtime_schema_area_served_node($site_settings)];
    }

    return seo_runtime_schema_area_served_metro_list($site_settings);
}

/**
 * İlçe cluster sayfası: İzmir + tek ilçe AdministrativeArea.
 *
 * @param array<string,mixed> $site_settings
 * @return list<array<string,mixed>>
 */
function seo_runtime_schema_area_served_district_single(array $site_settings, string $districtName): array
{
    $countryTr = [
        '@type' => 'Country',
        'name' => 'Türkiye',
    ];
    $cityShell = [
        '@type' => 'City',
        'name' => 'İzmir',
        'containedInPlace' => $countryTr,
    ];

    return [
        seo_runtime_schema_area_served_node($site_settings),
        [
            '@type' => 'AdministrativeArea',
            'name' => $districtName,
            'containedInPlace' => $cityShell,
        ],
    ];
}

/**
 * @param array<string,mixed> $site_settings
 * @return array<string, mixed>
 */
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
        '@type' => ['MovingCompany', 'LocalBusiness'],
        'name' => $brand,
        'url' => $canonical_origin . '/',
        'description' => !empty($site_settings['site_description'])
            ? $site_settings['site_description']
            : (!empty($site_settings['short_description'])
                ? $site_settings['short_description']
                : 'MY Nakliyat ® Evden eve nakliyat, Ofis taşıma, Eşya Depolama, Parça eşya taşıma & Şehirler arası nakliyatı sağlayan Güvenilir Marka ödüllü İzmir nakliyat firmasıdır.'),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => !empty($site_settings['address'])
                ? $site_settings['address']
                : (defined('MYNAK_CONTACT_ADDRESS_DISPLAY') ? MYNAK_CONTACT_ADDRESS_DISPLAY : 'İzmir'),
            'addressLocality' => 'İzmir',
            'addressRegion' => 'İzmir',
            'addressCountry' => 'TR',
        ],
        'areaServed' => seo_runtime_schema_area_served_for_pipeline($site_settings, $locationVector),
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

    // ---- E-E-A-T zenginlestirme: Brand + Award + foundingDate + slogan + knowsAbout ----
    // AI Overview / ChatGPT / Perplexity, "guvenilir / odullu / uzman" sinyallerini bu alanlardan okur.
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
 * Marka guven katmani — schema.org alanlari ile "Guvenilir / Odullu / Uzman" sinyalleri.
 * Tum degerler ya site_settings'tan ya da defansif default'lardan gelir; uydurma yok.
 *
 * @return array<string, mixed>
 */
function seo_runtime_schema_brand_trust_layer(string $brand, string $canonical_origin): array
{
    $out = [];

    // Brand objesi (sadece adla, logo MovingCompany.logo'da zaten var)
    $out['brand'] = [
        '@type' => 'Brand',
        'name' => $brand,
    ];

    // Slogan (kullanici beyani: 'Guvenilir Marka Odullu Nakliye Firmasi')
    $out['slogan'] = 'Güvenilir Marka Ödüllü Nakliye Firması';

    // Award listesi — Hakkimizda > "Kalite Belgeleri ve Odüller" ile birebir senkron.
    // Yeni odul eklendikce: (1) bu liste, (2) Hakkimizda sayfa icerigi,
    // (3) llms-full-tr.txt Bolum 6, (4) sayfa.php trust badge — DORDU birlikte guncellenir.
    $out['award'] = [
        '2024 — ISO 9001 Belgeli İlk Nakliye Firması',
        '2023 — En İyi Şehirler Arası Nakliyat Firması Ödülü',
        '2022 — En Çok Tercih Edilen Kurumsal Nakliyat Firması',
        '2018, 2020, 2022 — Güvenilir Marka Ödülleri',
        '2016 — Türkiye Altın Marka Ödülü',
        '2014 — Yılın Lider Taşımacılık Markası',
    ];

    // foundingDate — marka faaliyet başlangıcı (kanonik: 2001).
    $out['foundingDate'] = '2001';

    // knowsAbout — uzmanlik alanlari (AI'lar bunu "domain authority" sinyali olarak kullanir).
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

    // makesOffer (kisa ozet — Service nodelarinda detayli)
    $out['makesOffer'] = [
        ['@type' => 'Offer', 'name' => 'Sigortalı evden eve nakliyat'],
        ['@type' => 'Offer', 'name' => 'Asansörlü taşımacılık'],
        ['@type' => 'Offer', 'name' => 'Şehirler arası taşımacılık'],
        ['@type' => 'Offer', 'name' => 'Eşya depolama'],
    ];

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

    $offers = [];
    $pos = 1;
    foreach (seo_rt_primary_service_lines() as $line) {
        $offers[] = [
            '@type' => 'Offer',
            'position' => $pos,
            'itemOffered' => [
                '@type' => 'Service',
                'name' => (string) $line['name'],
                'serviceType' => (string) $line['service_type'],
                'url' => seo_rt_primary_service_public_url($canonical_origin, (string) $line['graph_slug']),
                'areaServed' => [
                    '@type' => 'City',
                    'name' => 'İzmir',
                ],
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
        $node = seo_runtime_schema_speakable_webpage($canonical, ['h1', '.page-banner h1']);
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

    $rows = [];
    foreach (seo_rt_primary_service_lines() as $line) {
        $rows[] = [
            'name' => (string) $line['name'],
            'service_type' => (string) $line['service_type'],
            'graph_slug' => (string) $line['graph_slug'],
            'public_slug' => (string) $line['public_slug'],
            'url' => seo_rt_primary_service_public_url($canonical_origin, (string) $line['graph_slug']),
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
 * GBP rating → AggregateRating (şema). Iki kaynak: 1) cache/gbp_data.json, 2) settings tablosu.
 * Yapay zeka kaynak gosterimi (AI Overview, ChatGPT, Perplexity) icin kritik.
 *
 * @return array<string, mixed>|null
 */
function seo_runtime_schema_aggregate_rating_from_gbp_cache(): ?array
{
    $rating = null;
    $count = 0;

    // 1) Cache (Places API otomatik veya manuel seed)
    if (function_exists('mynak_gbp_cache_file_path')) {
        $path = mynak_gbp_cache_file_path();
        if (is_readable($path)) {
            $j = json_decode((string) file_get_contents($path), true);
            if (is_array($j) && isset($j['rating']) && is_numeric($j['rating'])) {
                $rating = (string) $j['rating'];
                $count = (int) ($j['user_ratings_total'] ?? 0);
            }
        }
    }

    // 2) Fallback: settings tablosu (cache yoksa)
    if ($rating === null && isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
        $res = @$GLOBALS['conn']->query(
            "SELECT name, value FROM settings WHERE name IN ('google_place_rating','google_total_reviews')"
        );
        if ($res) {
            $tmp = [];
            while ($row = $res->fetch_assoc()) {
                $tmp[$row['name']] = (string) $row['value'];
            }
            if (isset($tmp['google_place_rating']) && is_numeric($tmp['google_place_rating'])) {
                $rating = $tmp['google_place_rating'];
                $count = (int) ($tmp['google_total_reviews'] ?? 0);
            }
        }
    }

    if ($rating === null || $count < 1) {
        return null;
    }

    return [
        '@type' => 'AggregateRating',
        'ratingValue' => $rating,
        'reviewCount' => $count,
        'bestRating' => '5',
        'worstRating' => '1',
    ];
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
        $moving_company_at_id = rtrim($canonical_origin, '/') . '/#mynak-moving-company';
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
            $defsForCat = seo_rt_pillar_cluster_definitions();
            $intentLabel = $slug !== '' ? seo_ei_primary_intent_label_for_slug($slug) : '';
            $svc = [
                '@context' => 'https://schema.org',
                '@type' => 'Service',
                'name' => $name,
                'serviceType' => $intentLabel !== '' ? $intentLabel : seo_runtime_infer_service_type_label($slug),
                'provider' => ['@id' => $moving_company_at_id],
                'areaServed' => seo_runtime_schema_area_served_for_pipeline($site_settings, $locVec),
                'audience' => [
                    '@type' => 'BusinessAudience',
                    'audienceType' => 'corporate_clients',
                    'name' => 'Kurumsal müşteriler ve yüksek değerli konut taşımacılığı ihtiyacı olan hane halkı',
                ],
            ];
            if ($slug !== '') {
                $svc['url'] = rtrim($canonical_origin, '/') . '/' . rawurlencode($slug);
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
                'availability' => 'https://schema.org/InStock',
                'businessFunction' => 'https://schema.org/Sell',
                'priceCurrency' => 'TRY',
                'price' => '0',
                'description' => 'Ücretsiz ekspertiz ve yazılı teklif; sigorta kapsamı ve sözleşme maddeleri keşif sonrası net olarak paylaşılır.',
                'url' => rtrim($canonical_origin, '/') . '/teklif-alin',
                'seller' => ['@id' => $moving_company_at_id],
            ];

            // Service.brand — MovingCompany ile aynı işletme referansı (AI Overview için tutarlılık).
            $svc['brand'] = ['@id' => $moving_company_at_id];

            // Service.serviceOutput — hizmetin somut çıktısı (yazılı teklif + sigorta + sözleşme).
            $svc['serviceOutput'] = [
                '@type' => 'Thing',
                'name' => 'Yazılı teklif, sigortalı taşıma ve sözleşmeli hizmet çıktısı',
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
            $faqPairs = [];
            if (function_exists('seo_runtime_extract_faq_pairs_from_html') && is_array($page) && !empty($page['content'])) {
                $faqPairs = seo_runtime_extract_faq_pairs_from_html((string) $page['content']);
            }
            if ($faqPairs === []) {
                if (!function_exists('seo_runtime_default_faq_pairs_for_service_slug')) {
                    require_once __DIR__ . '/default_service_faqs.php';
                }
                $faqPairs = seo_runtime_default_faq_pairs_for_service_slug($slug);
            }
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
            if ($datePub === '') {
                $datePub = date('Y-m-d');
            }
            if ($dateMod === '') {
                $dateMod = $datePub;
            }
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'BlogPosting',
                'headline' => $headline,
                'description' => (string) ($bp['description'] ?? ''),
                'mainEntityOfPage' => [
                    '@type' => 'WebPage',
                    '@id' => $postUrl !== '' ? $postUrl : $origin,
                ],
                'author' => $authorNode,
                'datePublished' => $datePub,
                'dateModified' => $dateMod,
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
            $img = (string) ($bp['image_url'] ?? '');
            $imgW = (int) ($bp['image_width'] ?? 1200);
            $imgH = (int) ($bp['image_height'] ?? 675);
            if ($imgW < 1) {
                $imgW = 1200;
            }
            if ($imgH < 1) {
                $imgH = 675;
            }
            if ($img !== '') {
                $schema['image'] = [
                    '@type' => 'ImageObject',
                    'url' => $img,
                    'width' => $imgW,
                    'height' => $imgH,
                ];
            } else {
                $schema['image'] = [
                    '@type' => 'ImageObject',
                    'url' => $origin . 'uploads/logo/my-nakliyat-logo.webp',
                    'width' => 1200,
                    'height' => 675,
                ];
            }
            if ($postUrl !== '') {
                $schema['url'] = $postUrl;
            }

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
                'name' => $contactName,
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
                'name' => $titleDisp,
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
                        '@type' => 'Service',
                        'name' => (string) $line['name'],
                        'serviceType' => (string) $line['service_type'],
                        'url' => seo_rt_primary_service_public_url($canonical_origin, (string) $line['graph_slug']),
                        'provider' => ['@id' => $moving_company_at_id],
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

/**
 * Üretim JSON-LD tek fabrika (yalnız bu yol &lt;script type="application/ld+json"&gt; üretir).
 * full_head_context: MovingCompany + BreadcrumbList + WebSite (home) + sayfa tipi parçası.
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
        $moving_company_at_id = rtrim($canonical_origin, '/') . '/#mynak-moving-company';
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
    $parts = [];

    if (!empty($j['emit_moving_company_inline'])) {
        $parts[] = seo_runtime_ld_script_from_array(schema_factory_build_moving_company_graph(
            $site_settings,
            $canonical_origin,
            $locVec,
            $moving_company_at_id
        ));
    }

    if (!empty($j['breadcrumb']) && $relPath !== '') {
        require_once dirname(__DIR__) . '/breadcrumb_jsonld.php';
        $crumbs = mynak_breadcrumb_build_items($relPath, $canonical, $canonical_origin);
        $bcGraph = mynak_breadcrumb_schema_from_items($crumbs);
        if ($bcGraph !== null) {
            $parts[] = seo_runtime_ld_script_from_array($bcGraph);
        }
    }

    if (!empty($j['website_on_home']) && $page_type === 'home') {
        $parts[] = seo_runtime_ld_script_from_array(seo_runtime_schema_website_home_graph(
            $canonical_origin,
            $site_settings,
            $moving_company_at_id
        ));
    }

    if ($pageFragment !== '') {
        $parts[] = $pageFragment;
    }

    return implode('', $parts);
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
        $moving_company_at_id = rtrim($canonical_origin, '/') . '/#mynak-moving-company';
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
