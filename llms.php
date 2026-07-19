<?php
declare(strict_types=1);

/**
 * /llms.txt — CANONICAL canlı LLM/SEO dışa aktarımı (pillar + intent; DB ayarları).
 * Statik /llms-full*.txt ve llms-full-tr.php gövdesi: politikaya özet ayna; yapısal doğruluk burada.
 */
if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg' && isset($_SERVER['SCRIPT_FILENAME']) && strtolower(basename((string) $_SERVER['SCRIPT_FILENAME'])) === 'llms.php') {
    require_once __DIR__ . '/bootstrap.php';
    require_once __DIR__ . '/config/site_url_define.php';
    $loc = rtrim((string) SITE_URL, '/') . (!empty($_GET['full']) ? '/llms-corpus.txt' : '/llms.txt');
    header('Location: ' . $loc, true, 301);
    exit;
}

if (!defined('SITE_URL')) {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/db.php';
}

header('Content-Type: text/plain; charset=UTF-8');

$site_settings = [];
if (isset($conn) && $conn instanceof mysqli) {
    $settings_result = $conn->query('SELECT name, value FROM settings');
    if ($settings_result && $settings_result->num_rows > 0) {
        while ($row = $settings_result->fetch_assoc()) {
            $site_settings[$row['name']] = $row['value'];
        }
    }
}

$site_url = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
$defs = seo_rt_pillar_cluster_definitions();

$llms_pipeline = canonical_seo_pipeline_core([
    'relPath' => seo_runtime_compute_rel_path_from_request_uri($_SERVER['REQUEST_URI'] ?? '/llms.txt'),
    'get' => isset($_GET) && is_array($_GET) ? $_GET : [],
    'page' => null,
    'blog' => null,
    'site_settings' => $site_settings,
]);
$llms_ctx = isset($llms_pipeline['llms_export_context']) && is_array($llms_pipeline['llms_export_context'])
    ? $llms_pipeline['llms_export_context'] : [];
$layout = isset($llms_ctx['section_layout']) && is_array($llms_ctx['section_layout']) ? $llms_ctx['section_layout'] : [];

echo "# LLMS — CANONICAL (canlı); /llms-full-tr.* = NON-CANONICAL politika özeti.\n\n";
echo '# canonical_seo_pipeline.page_type=' . (string) ($llms_pipeline['page_type'] ?? '') . "\n";
echo '# canonical_seo_pipeline.llms_export_context.grouping=' . (string) ($llms_ctx['grouping'] ?? '') . "\n\n";
echo "# LLMS — İzmir nakliyat site grafiği + niyet haritası\n\n";
echo 'Web: ' . $site_url . "\n";
echo "Sütun tanımı: seo_rt_pillar_cluster_definitions()\n";
echo "Varlık/niyet katmanı: includes/seo_entity_intent_graph.php (seo_ei_graph_bundle)\n";
echo 'Para (pillar hub) slug SSOT: ' . seo_rt_money_page_pillar_slug() . " — tüm hizmet sütunları bu düğüme supports_pillar_hub ile bağlanır.\n";
echo "Yerel bağlam: İzmir metropol + ilçe düğümleri (EI locations + MovingCompany areaServed listesi); şehir hiyerarşisi TR.\n\n";

echo "## Public JSON API (LLM/AI için programatik erişim — read-only, attribution requested)\n\n";
echo '- Manifest: ' . $site_url . "/api/v1/manifest.json\n";
echo '- Hizmetler: ' . $site_url . "/api/v1/services.json\n";
echo '- Blog (özet, sayfalı): ' . $site_url . "/api/v1/blog.json\n";
echo '- Tek blog yazısı: ' . $site_url . "/api/v1/blog/{slug}.json  (örn: /api/v1/blog/izmir-evden-eve-nakliyat-rehberi.json)\n";
echo '- Yazarlar (Person): ' . $site_url . "/api/v1/authors.json\n";
echo '- Kuruluş (MovingCompany): ' . $site_url . "/api/v1/organization.json\n";
echo '- Konumlar: ' . $site_url . "/api/v1/locations.json\n";
echo "\n";
echo "## Markdown Content Negotiation\n\n";
echo "Aynı URL'lere 'Accept: text/markdown' başlığı veya '?format=markdown' parametresiyle erişilirse içerik\n";
echo "LLM-friendly markdown olarak servis edilir. Örnek:\n";
echo '  curl -H "Accept: text/markdown" ' . $site_url . "/izmir-evden-eve-nakliyat-hizmeti\n";
echo '  ' . $site_url . "/{slug}?format=markdown\n";
echo "X-Robots-Tag: noindex,follow — Google indexlemez, LLM/AI okuyabilir.\n\n";

/*
 * Coğrafi kapsam + hizmet envanteri (AI/LLM okunabilir).
 * SSOT: includes/llms_izmir_data.php (İzmir ilçeleri + hizmet tipleri),
 *       includes/llms_turkiye_iller.php (81 il). llms-full-tr.txt ile aynı kaynak → tutarlı.
 * Yalnızca /llms.txt metnine yazılır; hiçbir render/HTML/URL etkilenmez.
 */
$izmirGeoData = @include __DIR__ . '/includes/llms_izmir_data.php';
$trIllerData = @include __DIR__ . '/includes/llms_turkiye_iller.php';
$izmirDistricts = (is_array($izmirGeoData) && isset($izmirGeoData['districts']) && is_array($izmirGeoData['districts']))
    ? $izmirGeoData['districts'] : [];
$serviceLeafTypes = (is_array($izmirGeoData) && isset($izmirGeoData['service_leaf_types']) && is_array($izmirGeoData['service_leaf_types']))
    ? $izmirGeoData['service_leaf_types'] : [];
$trProvinces = (is_array($trIllerData) && isset($trIllerData['provinces_plate_order']) && is_array($trIllerData['provinces_plate_order']))
    ? $trIllerData['provinces_plate_order'] : [];

if (!empty($serviceLeafTypes)) {
    echo "## Hizmet envanteri (birincil hizmet tipleri)\n\n";
    foreach ($serviceLeafTypes as $svc) {
        echo '- ' . (string) $svc . "\n";
    }
    echo 'Yapılandırılmış hizmet listesi (canlı): ' . $site_url . "/api/v1/services.json\n\n";
}

if (!empty($izmirDistricts)) {
    echo '## İzmir ilçe kapsamı (' . count($izmirDistricts) . " ilçe — evden eve + tüm hizmetler)\n\n";
    echo "MY Nakliyat, İzmir Büyükşehir'in tüm ilçelerinde evden eve nakliyat, asansörlü taşımacılık, eşya depolama ve ofis taşıma hizmeti verir.\n";
    echo "İlçe hizmet sayfası deseni: " . $site_url . "/{ilçe-slug}-evden-eve-nakliyat\n";
    echo 'Yapılandırılmış konum verisi (canlı): ' . $site_url . "/api/v1/locations.json\n\n";
    foreach ($izmirDistricts as $d) {
        echo '- ' . (string) $d . ", İzmir\n";
    }
    echo "\n";
}

if (!empty($trProvinces)) {
    $sehirlerarasiPath = function_exists('seo_rt_graph_path_for_slug')
        ? seo_rt_graph_path_for_slug('sehirler-arasi-nakliyat') : '/sehirlerarasi-nakliyat';
    if ($sehirlerarasiPath === '' || ($sehirlerarasiPath[0] ?? '') !== '/') {
        $sehirlerarasiPath = '/' . ltrim($sehirlerarasiPath, '/');
    }
    echo '## Türkiye şehirler arası kapsam (' . count($trProvinces) . " il)\n\n";
    echo 'İzmir merkezli şehirler arası nakliyat, 81 ilin tamamına hizmet verir: ' . $site_url . $sehirlerarasiPath . "\n";
    echo 'Kapsanan iller (plaka sırası): ' . implode(', ', array_map('strval', $trProvinces)) . "\n\n";
}

$b = null;
$payload = null;

foreach ($layout as $section) {
    if ($section === 'brand_perception') {
        echo "## Brand perception overlay (AI-only; sınıflandırma ve şema türü seçimi değiştirmez)\n\n";
        $blk = seo_ei_brand_perception_llms_block();
        $enc = json_encode($blk, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        echo $enc !== false ? $enc : '{}';
        echo "\n\n";
        continue;
    }
    if ($section === 'triple_dominance') {
        echo "## Triple dominance — Local Pack + AI Overview + organik (answer-ready)\n\n";
        $blocks = seo_ei_triple_dominance_answer_blocks($defs, $site_settings);
        $clarity = seo_ei_entity_clarity_manifest($defs);
        $enc = json_encode(
            ['answer_ready_blocks' => $blocks, 'entity_clarity' => $clarity],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
        echo $enc !== false ? $enc : '{}';
        echo "\n\n";
        continue;
    }
    if ($section === 'pillar_urls') {
        echo "## Site küme sayfaları (URL)\n\n";
        $clusterSlugs = array_keys($defs);
        sort($clusterSlugs, SORT_STRING);
        foreach ($clusterSlugs as $cslug) {
            $nav = isset($defs[$cslug]['nav_title']) ? (string) $defs[$cslug]['nav_title'] : $cslug;
            $path = seo_rt_graph_path_for_slug($cslug);
            if ($path === '' || ($path[0] ?? '') !== '/') {
                $path = '/' . ltrim($path, '/');
            }
            echo '- [' . $nav . '](' . $site_url . $path . ")\n";
        }
        echo "\n";
        continue;
    }
    if ($section === 'intent_map') {
        echo "## Hizmet → niyet eşlemesi (okunabilir)\n\n";
        if ($b === null) {
            $b = seo_ei_graph_bundle();
        }
        foreach ($b['services'] as $pSlug => $spec) {
            $title = isset($defs[$pSlug]['nav_title']) ? (string) $defs[$pSlug]['nav_title'] : $pSlug;
            $pi = (string) ($spec['primary_intent'] ?? '');
            $pil = $pi !== '' && isset($b['intents'][$pi]['label_tr']) ? (string) $b['intents'][$pi]['label_tr'] : $pi;
            $sec = isset($spec['secondary_intents']) && is_array($spec['secondary_intents']) ? implode(', ', $spec['secondary_intents']) : '';
            $inf = isset($spec['informational_intents']) && is_array($spec['informational_intents']) ? implode(', ', $spec['informational_intents']) : '';
            echo '- ' . $title . " (`{$pSlug}`)\n";
            echo '  - entity_id: ' . ($spec['entity_id'] ?? '') . "\n";
            echo '  - primary_intent: ' . $pi . ' — ' . $pil . "\n";
            if ($sec !== '') {
                echo '  - secondary_intents: ' . $sec . "\n";
            }
            if ($inf !== '') {
                echo '  - informational_intents: ' . $inf . "\n";
            }
            echo "\n";
        }
        continue;
    }
    if ($section === 'canonical_dataset') {
        echo "## Canonical dataset (AI / LLM)\n\n";
        if ($payload === null) {
            $payload = seo_ei_llm_export_payload($defs, $site_settings);
        }
        echo 'Zorunlu para hizmet kapsamı: ' . (string) ($payload['mandatory_service_coverage']['coverage_percent'] ?? 0) . "% (graf içi)\n";
        echo "Dönüşüm haritası: payload.conversion_paths (transactional | informational | trust_reputation | local_discovery).\n";
        echo "DB services.slug → graf anahtarı: payload.services_slug_alias_to_graph_key\n\n";

        echo "## Entity graph (JSON)\n\n";
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        echo $json !== false ? $json : '{}';
        echo "\n\n";
        continue;
    }
    if ($section === 'trust_signals_block') {
        echo "## trust_signals_block\n\n";
        if (function_exists('seo_llms_trust_signals_readable_block')) {
            $trustBlk = seo_llms_trust_signals_readable_block($site_settings);
            $encTrust = json_encode($trustBlk, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            echo $encTrust !== false ? $encTrust : '{}';
            echo "\n";
        }
        continue;
    }
    if ($section === 'authority_json') {
        echo "## Authority + içerik sinyalleri (okunabilir)\n\n";
        echo "Katman: includes/seo_authority_signal_layer.php (seo_as_*)\n\n";

        if (function_exists('seo_as_llm_authority_export')) {
            $authPayload = seo_as_llm_authority_export($defs, $site_settings);
            $ts = $authPayload['trust_summary'] ?? [];
            echo 'Güven (EEAT-light) toplam skor: ' . (int) ($ts['trust_signal_score'] ?? 0) . "/100\n";
            echo '- İletişim sayfası tanımı: ' . (!empty($ts['has_contact_page']) ? 'var' : 'yok') . "\n";
            echo '- Hakkımızda tanımı: ' . (!empty($ts['has_about_page']) ? 'var' : 'yok') . "\n";
            echo '- Hizmet alanı netliği: ' . (!empty($ts['has_service_area_clarity']) ? 'var' : 'zayıf') . "\n";
            echo '- Kuruluş şeması doluluk: ' . (int) ($ts['organization_schema_completeness'] ?? 0) . "/100\n\n";

            echo "### Hizmet bazlı otorite + derinlik (özet)\n\n";
            foreach ($authPayload['services_authority'] ?? [] as $pSlug => $row) {
                $title = isset($row['nav_title']) ? (string) $row['nav_title'] : $pSlug;
                echo '- ' . $title . ' (`' . $pSlug . "`)\n";
                echo '  - authority_score: ' . (int) ($row['authority_score'] ?? 0);
                echo ', content_depth_score: ' . (int) ($row['content_depth_score'] ?? 0);
                echo ', trust_signal_score: ' . (int) ($row['trust_signal_score'] ?? 0) . "\n";
                echo '  - is_pillar_service: ' . (!empty($row['is_pillar_service']) ? 'true' : 'false');
                echo ', authority_multiplier: ' . (string) ($row['authority_multiplier'] ?? '1');
                echo ', priority_indexing_weight: ' . (int) ($row['priority_indexing_weight'] ?? 0);
                echo ', tier: ' . (string) ($row['service_tier'] ?? '') . "\n\n";
            }

            echo "### En yüksek otorite (ilk 8)\n\n";
            foreach ($authPayload['top_authority_pages'] ?? [] as $row) {
                $ps = isset($row['slug']) ? (string) $row['slug'] : '';
                $nt = isset($row['nav_title']) ? (string) $row['nav_title'] : $ps;
                echo '- ' . $nt . ' — authority ' . (int) ($row['authority_score'] ?? 0) . ', depth ' . (int) ($row['content_depth_score'] ?? 0) . "\n";
            }
            echo "\n";

            echo "### İçerik derinliği sıralaması (ilk 8, hizmet)\n\n";
            foreach ($authPayload['content_depth_ranking'] ?? [] as $row) {
                $ps = isset($row['slug']) ? (string) $row['slug'] : '';
                echo '- `' . $ps . '` — depth ' . (int) ($row['content_depth_score'] ?? 0) . "\n";
            }
            echo "\n";

            echo "## Authority layer (JSON)\n\n";
            $authJson = json_encode($authPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            echo $authJson !== false ? $authJson : '{}';
            echo "\n";
        } else {
            echo "(Authority katmanı yüklenemedi: seo_as_llm_authority_export tanımsız.)\n";
        }
    }
}
