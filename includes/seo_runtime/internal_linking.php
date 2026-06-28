<?php
declare(strict_types=1);
/** Auto-split from seo_runtime.php — Phase 3 modular SEO runtime. */

/**
 * Pillar + cluster + nav_title (+ isteğe bağlı path). Tek kaynak; related içindeki her slug burada anahtar olmalı.
 * 'pillar' null: sayfanın kendisi pillar.
 *
 * @return array<string, array{pillar: ?string, related: list<string>, nav_title: string, path?: string}>
 */
function seo_rt_pillar_cluster_definitions(): array
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }

    $map = [
        'izmir-evden-eve-nakliyat' => [
            'pillar' => null,
            'related' => ['asansorlu-nakliyat', 'sehirler-arasi-nakliyat', 'sehirici-nakliyat', 'parca-esya-tasima', 'izmir-ofis-tasimaciligi', 'teklif-alin', 'fiyat', 'belgelerimiz'],
            'nav_title' => 'İzmir Evden Eve Nakliyat',
        ],
        'asansorlu-nakliyat' => [
            'pillar' => seo_rt_money_page_pillar_slug(),
            'related' => ['izmir-evden-eve-nakliyat', 'parca-esya-tasima', 'sehirici-nakliyat', 'teklif-alin', 'belgelerimiz'],
            'nav_title' => 'Asansörlü Nakliyat',
        ],
        'sehirler-arasi-nakliyat' => [
            'pillar' => seo_rt_money_page_pillar_slug(),
            'related' => ['izmir-evden-eve-nakliyat', 'sehirici-nakliyat', 'teklif-alin', 'belgelerimiz', 'blog'],
            'nav_title' => 'Şehirler Arası Nakliyat',
        ],
        'sehirici-nakliyat' => [
            'pillar' => seo_rt_money_page_pillar_slug(),
            'related' => ['izmir-evden-eve-nakliyat', 'sehirler-arasi-nakliyat', 'izmir-ofis-tasimaciligi', 'teklif-alin', 'blog', 'belgelerimiz'],
            'nav_title' => 'Şehir İçi Nakliyat',
        ],
        'parca-esya-tasima' => [
            'pillar' => seo_rt_money_page_pillar_slug(),
            'related' => ['izmir-ceyiz-tasima-nakliyat', 'izmir-esya-depolama', 'antika-ve-piyano-tasima', 'teklif-alin'],
            'nav_title' => 'Parça Eşya Taşıma',
        ],
        'izmir-ceyiz-tasima-nakliyat' => [
            'pillar' => seo_rt_money_page_pillar_slug(),
            'related' => ['parca-esya-tasima', 'izmir-esya-depolama', 'antika-ve-piyano-tasima', 'teklif-alin'],
            'nav_title' => 'İzmir Çeyiz Taşıma',
        ],
        'antika-ve-piyano-tasima' => [
            'pillar' => seo_rt_money_page_pillar_slug(),
            'related' => ['parca-esya-tasima', 'izmir-ceyiz-tasima-nakliyat', 'izmir-esya-depolama', 'teklif-alin'],
            'nav_title' => 'Antika ve Piyano Taşıma',
        ],
        'izmir-esya-depolama' => [
            'pillar' => seo_rt_money_page_pillar_slug(),
            'related' => ['parca-esya-tasima', 'sehirler-arasi-nakliyat', 'teklif-alin', 'belgelerimiz'],
            'nav_title' => 'İzmir Eşya Depolama',
        ],
        'izmir-ofis-tasimaciligi' => [
            'pillar' => seo_rt_money_page_pillar_slug(),
            'related' => ['sehirici-nakliyat', 'sehirler-arasi-nakliyat', 'teklif-alin', 'belgelerimiz'],
            'nav_title' => 'İzmir Ofis Taşımacılığı',
        ],
        'hakkimizda' => [
            'pillar' => null,
            'related' => ['belgelerimiz', 'ekibimiz', 'basinda-biz', 'teklif-alin', 'blog'],
            'nav_title' => 'Hakkımızda',
        ],
        'ekibimiz' => [
            'pillar' => 'hakkimizda',
            'related' => ['hakkimizda', 'belgelerimiz', 'teklif-alin', 'blog'],
            'nav_title' => 'Ekibimiz',
        ],
        'belgelerimiz' => [
            'pillar' => 'hakkimizda',
            'related' => ['hakkimizda', 'teklif-alin', 'izmir-evden-eve-nakliyat', 'blog'],
            'nav_title' => 'Belgelerimiz',
        ],
        'teklif-alin' => [
            'pillar' => null,
            'related' => ['fiyat', 'izmir-evden-eve-nakliyat', 'sehirler-arasi-nakliyat', 'iletisim', 'belgelerimiz'],
            'nav_title' => 'Teklif Alın',
        ],
        'fiyat' => [
            'pillar' => null,
            'related' => ['teklif-alin', 'izmir-evden-eve-nakliyat', 'sehirler-arasi-nakliyat', 'asansorlu-nakliyat', 'parca-esya-tasima', 'belgelerimiz'],
            'nav_title' => 'Nakliyat Fiyatları',
        ],
        'iletisim' => [
            'pillar' => null,
            'related' => ['teklif-alin', 'izmir-evden-eve-nakliyat', 'belgelerimiz', 'blog'],
            'nav_title' => 'İletişim',
        ],
        'basinda-biz' => [
            'pillar' => 'hakkimizda',
            'related' => ['hakkimizda', 'blog', 'belgelerimiz', 'teklif-alin'],
            'nav_title' => 'Basında Biz',
        ],
        'blog' => [
            'pillar' => null,
            'related' => ['izmir-evden-eve-nakliyat', 'teklif-alin', 'iletisim'],
            'nav_title' => 'Blog',
            'path' => '/blog/',
        ],
    ];

    return $map;
}

/**
 * Birincil (para) hizmet sütunları — AI/Google entity SSOT (5 hizmet).
 *
 * @return list<string>
 */
function seo_rt_primary_service_graph_slugs(): array
{
    return [
        'izmir-evden-eve-nakliyat',
        'sehirler-arasi-nakliyat',
        'izmir-ofis-tasimaciligi',
        'izmir-esya-depolama',
        'asansorlu-nakliyat',
    ];
}

/**
 * @return array<string, string>
 */
function seo_rt_primary_service_type_labels(): array
{
    return [
        'izmir-evden-eve-nakliyat' => 'Evden eve nakliyat',
        'sehirler-arasi-nakliyat' => 'Şehirler arası nakliyat',
        'izmir-ofis-tasimaciligi' => 'Ofis taşıma',
        'izmir-esya-depolama' => 'Eşya depolama',
        'asansorlu-nakliyat' => 'Asansörlü taşımacılık',
    ];
}

/**
 * Schema, API ve ana sayfa ItemList için birincil hizmet satırları.
 *
 * @return list<array{graph_slug:string,public_slug:string,name:string,service_type:string}>
 */
function seo_rt_primary_service_lines(): array
{
    static $lines = null;
    if (is_array($lines)) {
        return $lines;
    }

    $defs = seo_rt_pillar_cluster_definitions();
    $types = seo_rt_primary_service_type_labels();
    $lines = [];
    foreach (seo_rt_primary_service_graph_slugs() as $graphSlug) {
        $publicSlug = seo_rt_public_url_slug_for_graph_slug($graphSlug);
        $lines[] = [
            'graph_slug' => $graphSlug,
            'public_slug' => $publicSlug,
            'name' => (string) ($defs[$graphSlug]['nav_title'] ?? $graphSlug),
            'service_type' => (string) ($types[$graphSlug] ?? $graphSlug),
        ];
    }

    return $lines;
}

function seo_runtime_primary_services_links_html(string $currentSlug = ''): string
{
    $mynak_current_content_slug = trim($currentSlug);
    ob_start();
    $partial = dirname(__DIR__) . '/partials/mynak_primary_services_links.php';
    if (is_readable($partial)) {
        include $partial;
    }

    return (string) ob_get_clean();
}

/**
 * slug-router çakışma kuyruğu: yalnızca küme tanımından türetilmiş slug kümesi (duplike liste yok).
 *
 * @return list<string>
 */
function seo_rt_static_path_tails_from_cluster_defs(): array
{
    $defs = seo_rt_pillar_cluster_definitions();
    $out = [];
    foreach ($defs as $slug => $entry) {
        $out[] = $slug;
        if (!empty($entry['pillar']) && is_string($entry['pillar'])) {
            $out[] = $entry['pillar'];
        }
        foreach ($entry['related'] ?? [] as $r) {
            $out[] = (string) $r;
        }
    }

    return array_values(array_unique(array_filter($out, static function ($s): bool {
        return is_string($s) && $s !== '';
    })));
}

function seo_rt_graph_path_for_slug(string $slug): string
{
    $defs = seo_rt_pillar_cluster_definitions();
    $entry = $defs[$slug] ?? null;
    if (is_array($entry) && !empty($entry['path']) && is_string($entry['path'])) {
        $p = $entry['path'];
        $out = ($p !== '' && $p[0] === '/') ? $p : '/' . $p;
    } else {
        $pubSlug = seo_rt_public_url_slug_for_graph_slug($slug);
        $out = seo_runtime_canonical_path_for_slug($pubSlug);
    }
    seo_runtime_trace_record_graph_path($slug, $out);
    return $out;
}

/**
 * İç link anchor metni: hedef konu adı + sayfa/üst konu bağlamı (generic CTA yok).
 */
function seo_rt_resolve_link_topic_name(string $targetSlug): string
{
    $defs = seo_rt_pillar_cluster_definitions();
    if (isset($defs[$targetSlug]['nav_title']) && (string) $defs[$targetSlug]['nav_title'] !== '') {
        return (string) $defs[$targetSlug]['nav_title'];
    }

    return $targetSlug;
}

function seo_rt_cluster_key_for_slug(string $slug, array $defs): string
{
    if (!isset($defs[$slug])) {
        return '_external';
    }
    $pillar = $defs[$slug]['pillar'] ?? null;
    if ($pillar === null || $pillar === '') {
        return $slug;
    }
    return (string) $pillar;
}

/**
 * @param ?string $uplinkPillarSlug Üst konu (ilk sıradaki pillar) varsa; yoksa null
 * @param string $sourcePageTitle Mevcut sayfa başlığı (küme içi ifadeleri kişiselleştirmek için)
 */
function seo_rt_context_link_anchor_for_target(
    string $targetSlug,
    string $sourceSlug,
    ?string $uplinkPillarSlug,
    string $sourcePageTitle = ''
): string {
    if (function_exists('seo_rt_anchor_pool_pick')) {
        $poolPick = seo_rt_anchor_pool_pick($targetSlug, $sourceSlug);
        if (is_string($poolPick) && $poolPick !== '') {
            return $poolPick;
        }
    }
    $defs = seo_rt_pillar_cluster_definitions();
    $topic = seo_rt_resolve_link_topic_name($targetSlug);
    $sourcePageTitle = trim($sourcePageTitle);
    $titleStem = '';
    if ($sourcePageTitle !== '') {
        $titleStem = $sourcePageTitle;
        if (function_exists('mb_strlen') && function_exists('mb_substr') && mb_strlen($titleStem, 'UTF-8') > 42) {
            $titleStem = mb_substr($titleStem, 0, 42, 'UTF-8') . '…';
        } elseif (strlen($titleStem) > 42) {
            $titleStem = substr($titleStem, 0, 42) . '…';
        }
    }

    if ($targetSlug === 'blog') {
        return 'Nakliyat blogu: güncel yazılar ve uzman rehberleri';
    }
    if ($targetSlug === 'teklif-alin') {
        return 'Keşif ve taşıma planı talebi — form üzerinden iletişim';
    }
    if ($targetSlug === 'fiyat') {
        return 'İzmir nakliyat fiyat aralıkları ve ücret rehberi';
    }
    if ($targetSlug === 'iletisim') {
        return 'İletişim bilgileri, adres ve telefon';
    }
    if ($targetSlug === 'belgelerimiz') {
        return 'Kurumsal güven: resmi belgeler ve yetki bilgileri';
    }
    if ($targetSlug === 'ekibimiz') {
        return 'Ekibimiz ve çalışma anlayışımız';
    }
    if ($targetSlug === 'hakkimizda') {
        return 'Kurumsal güven: firmamız ve profesyonel taşımacılık yaklaşımımız';
    }
    if ($targetSlug === 'basinda-biz') {
        return 'Basında biz ve medya yansımaları';
    }

    $hub = seo_rt_money_page_pillar_slug();
    if ($targetSlug === $hub && $sourceSlug !== $hub) {
        $anchors = [
            'İzmir evden eve nakliyat — kurumsal hizmet',
            'Profesyonel ve güvenilir nakliyat firması İzmir',
            'Güvenilir evden eve nakliyat İzmir',
        ];
        $idx = abs(crc32($sourceSlug)) % count($anchors);

        return $anchors[$idx];
    }

    if ($uplinkPillarSlug !== null && $uplinkPillarSlug !== '' && $targetSlug === $uplinkPillarSlug) {
        if ($titleStem !== '') {
            return $titleStem . ' için üst konu: ' . $topic . ' kapsam ve süreç özeti';
        }
        return $topic . ' hizmet kapsamı ve süreç özeti';
    }

    $ckS = seo_rt_cluster_key_for_slug($sourceSlug, $defs);
    $ckT = seo_rt_cluster_key_for_slug($targetSlug, $defs);
    if ($ckS !== '_external' && $ckT !== '_external' && $ckS === $ckT && $targetSlug !== $sourceSlug) {
        if ($titleStem !== '') {
            return $titleStem . ' ile ilişkili ' . $topic . ' sürecini inceleyin';
        }
        return $topic . ' sürecini ve koşullarını inceleyin';
    }

    if ($titleStem !== '') {
        return $titleStem . ' sayfasından ' . $topic . ' hizmetine geçin';
    }
    return $topic . ' hizmeti hakkında bilgi alın';
}

/**
 * Sütun grafiğinde slug eşleştirmesi için tire-parçaları (tanım dışı mantık yok).
 *
 * @return list<string>
 */
function seo_rt_internal_nav_token_parts(string $slug): array
{
    $slug = trim($slug, '/');
    if ($slug === '') {
        return [];
    }
    $parts = preg_split('/-+/u', $slug, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $out = [];
    foreach ($parts as $p) {
        $p = mb_strtolower((string) $p, 'UTF-8');
        if ($p !== '' && strlen($p) >= 2) {
            $out[$p] = true;
        }
    }

    return array_keys($out);
}

/**
 * Sütun köküne uzaklık (üst pillar zinciri adımı); döngü korumalı.
 */
function seo_rt_nav_pillar_depth(string $nodeKey, array $defs): int
{
    $depth = 0;
    $cur = $nodeKey;
    $seen = [];
    while ($depth < 64 && isset($defs[$cur])) {
        if (isset($seen[$cur])) {
            break;
        }
        $seen[$cur] = true;
        $p = $defs[$cur]['pillar'] ?? null;
        if (!is_string($p) || $p === '') {
            return $depth;
        }
        $depth++;
        $cur = $p;
    }

    return $depth;
}

/**
 * Beraberlik: önce daha sığ sütun (kök yakını), sonra tanım haritası sırası (deterministik; alfabetik/uzunluk yok).
 *
 * @param list<string> $candidateKeys
 */
function seo_rt_nav_pick_anchor_by_column_proximity(array $candidateKeys, array $defs): ?string
{
    $candidateKeys = array_values(array_unique(array_filter($candidateKeys, static function ($k) {
        return is_string($k) && $k !== '';
    })));
    if ($candidateKeys === []) {
        return null;
    }

    $orderIndex = array_flip(array_keys($defs));

    usort($candidateKeys, static function (string $a, string $b) use ($defs, $orderIndex): int {
        $da = seo_rt_nav_pillar_depth($a, $defs);
        $db = seo_rt_nav_pillar_depth($b, $defs);
        if ($da !== $db) {
            return $da <=> $db;
        }
        $ia = $orderIndex[$a] ?? PHP_INT_MAX;
        $ib = $orderIndex[$b] ?? PHP_INT_MAX;

        return $ia <=> $ib;
    });

    return $candidateKeys[0];
}

/**
 * Varlık+niyet katmanı: related[] birleşimi (pillar + zorunlu niyet/yer/bilgi düğümleri).
 *
 * @param array<string, mixed> $cfg
 * @return array<string, mixed>
 */
function seo_rt_internal_nav_apply_entity_layer(string $slug, array $cfg, array $defs): array
{
    if (($cfg['resolution_source'] ?? '') === 'unresolved') {
        return $cfg;
    }
    $lookup = $slug;
    if (!isset($defs[$slug]) && !empty($cfg['anchor_node']) && is_string($cfg['anchor_node'])) {
        $lookup = $cfg['anchor_node'];
    }
    $cfg['related'] = seo_ei_merge_related_with_intents(
        $slug,
        isset($cfg['related']) && is_array($cfg['related']) ? array_values($cfg['related']) : [],
        isset($cfg['pillar']) && is_string($cfg['pillar']) ? $cfg['pillar'] : null,
        $defs,
        (float) ($cfg['resolution_weight'] ?? 0.0),
        $lookup
    );

    return $cfg;
}

/**
 * İç gezinme: seo_rt_pillar_cluster_definitions() + entity/intent zenginleştirmesi; ağırlık defined(1.0) > inferred_reference(0.7) > inferred_semantic(0.4).
 *
 * @return array{
 *   pillar: ?string,
 *   related: list<string>,
 *   anchor_node: ?string,
 *   resolution_source: 'defined'|'inferred_reference'|'inferred_semantic'|'unresolved',
 *   resolution_weight: float,
 *   fallback_chain_used: list<string>
 * }
 */
function seo_rt_internal_nav_config_for_slug(string $slug): array
{
    $slug = trim((string) $slug, '/');
    $defs = seo_rt_pillar_cluster_definitions();

    $baseUnresolved = static function (string $chainLast) use ($slug): array {
        $chain = ['unresolved', $chainLast];
        if ($slug !== '') {
            $chain[] = $slug;
        }

        return [
            'pillar' => null,
            'related' => [],
            'anchor_node' => null,
            'resolution_source' => 'unresolved',
            'resolution_weight' => 0.0,
            'fallback_chain_used' => $chain,
        ];
    };

    if ($slug === '') {
        return $baseUnresolved('empty_slug');
    }

    if (isset($defs[$slug])) {
        $e = $defs[$slug];
        $pillar = isset($e['pillar']) && is_string($e['pillar']) && $e['pillar'] !== '' ? $e['pillar'] : null;
        $related = isset($e['related']) && is_array($e['related']) ? array_values($e['related']) : [];

        return seo_rt_internal_nav_apply_entity_layer($slug, [
            'pillar' => $pillar,
            'related' => $related,
            'anchor_node' => $slug,
            'resolution_source' => 'defined',
            'resolution_weight' => 1.0,
            'fallback_chain_used' => ['defined'],
        ], $defs);
    }

    $referenceHosts = [];
    foreach ($defs as $nodeKey => $entry) {
        $rel = isset($entry['related']) && is_array($entry['related']) ? $entry['related'] : [];
        foreach ($rel as $r) {
            if ((string) $r === $slug) {
                $referenceHosts[] = (string) $nodeKey;
                break;
            }
        }
    }

    if ($referenceHosts !== []) {
        $anchor = seo_rt_nav_pick_anchor_by_column_proximity($referenceHosts, $defs);
        if ($anchor === null || !isset($defs[$anchor])) {
            return $baseUnresolved('reference_pick_failed');
        }
        $e = $defs[$anchor];
        $pillar = isset($e['pillar']) && is_string($e['pillar']) && $e['pillar'] !== '' ? $e['pillar'] : null;
        $related = isset($e['related']) && is_array($e['related']) ? array_values($e['related']) : [];

        return seo_rt_internal_nav_apply_entity_layer($slug, [
            'pillar' => $pillar,
            'related' => $related,
            'anchor_node' => $anchor,
            'resolution_source' => 'inferred_reference',
            'resolution_weight' => 0.7,
            'fallback_chain_used' => ['inferred_reference', $anchor],
        ], $defs);
    }

    $partsU = seo_rt_internal_nav_token_parts($slug);
    if ($partsU === []) {
        return $baseUnresolved('no_tokens');
    }

    $maxScore = -1;
    $semanticCandidates = [];
    foreach (array_keys($defs) as $k) {
        $score = count(array_intersect($partsU, seo_rt_internal_nav_token_parts($k)));
        if ($score < 1) {
            continue;
        }
        if ($score > $maxScore) {
            $maxScore = $score;
            $semanticCandidates = [$k];
        } elseif ($score === $maxScore) {
            $semanticCandidates[] = $k;
        }
    }

    $bestKey = seo_rt_nav_pick_anchor_by_column_proximity($semanticCandidates, $defs);
    if ($bestKey === null) {
        return $baseUnresolved('no_semantic_match');
    }

    $e = $defs[$bestKey];
    $pillar = isset($e['pillar']) && is_string($e['pillar']) && $e['pillar'] !== '' ? $e['pillar'] : null;
    $related = isset($e['related']) && is_array($e['related']) ? array_values($e['related']) : [];

    return seo_rt_internal_nav_apply_entity_layer($slug, [
        'pillar' => $pillar,
        'related' => $related,
        'anchor_node' => $bestKey,
        'resolution_source' => 'inferred_semantic',
        'resolution_weight' => 0.4,
        'fallback_chain_used' => ['inferred_semantic', $bestKey],
    ], $defs);
}

/**
 * Hizmet / sayfa şablonu: seo_rt_internal_nav_config_for_slug() üzerinden tek grafik katmanı.
 * Sayfa türü / otorite girdisi: yalnızca $pipeline (canonical_seo_pipeline_resolve).
 */
function seo_runtime_cluster_context_links_html(array $page, array $pipeline): string
{
    $slug = isset($page['slug']) ? trim((string) $page['slug']) : '';
    if ($slug === '') {
        return '';
    }

    $pt = (string) ($pipeline['page_type'] ?? 'global');
    $ili = isset($pipeline['internal_link_context']) && is_array($pipeline['internal_link_context'])
        ? $pipeline['internal_link_context'] : [];
    $eli = isset($ili['eligible_page_types']) && is_array($ili['eligible_page_types'])
        ? $ili['eligible_page_types'] : ['service', 'global'];
    if (!in_array($pt, $eli, true)) {
        return '';
    }

    if (!function_exists('normalize_internal_link_url')) {
        return '';
    }

    $defs = seo_rt_pillar_cluster_definitions();
    $navCfg = seo_rt_internal_nav_config_for_slug($slug);
    $resolution = $navCfg['resolution_source'];
    $graphMiss = $resolution === 'unresolved';
    $resWeight = (float) ($navCfg['resolution_weight'] ?? 0.0);
    $fbChain = isset($navCfg['fallback_chain_used']) && is_array($navCfg['fallback_chain_used'])
        ? $navCfg['fallback_chain_used'] : [];
    $traceResolutionBase = [
        'resolution_source' => $resolution,
        'resolution_weight' => $resWeight,
        'fallback_chain_used' => $fbChain,
        'çözünürlük_kaynağı' => $resolution,
        'çözünürlük_w_' => $resWeight,
        'yedek_zincir_kullanıldı' => $fbChain,
    ];

    if ($graphMiss) {
        seo_runtime_trace_record_internal_links($slug, [], 0, true, [], array_merge($traceResolutionBase, [
            'resolved_pillar' => null,
            'anchor_node' => null,
            'çözümlenmiş_sütun' => null,
            'neighborhood_same_cluster' => 0,
            'neighborhood_cross_cluster' => 0,
        ]));
        return '';
    }

    $pillarSlug = $navCfg['pillar'];
    if ($pillarSlug === $slug) {
        $pillarSlug = null;
    }

    $related = $navCfg['related'];

    $ordered = [];
    if ($pillarSlug !== null && $pillarSlug !== '') {
        $ordered[] = $pillarSlug;
    }
    foreach ($related as $rel) {
        if ($rel === $slug || in_array($rel, $ordered, true)) {
            continue;
        }
        $ordered[] = $rel;
        if (count($ordered) >= 6) {
            break;
        }
    }

    if ($ordered === [] && $pillarSlug !== null && $pillarSlug !== '' && $pillarSlug !== $slug) {
        $ordered = [$pillarSlug];
    }

    $ordered = array_slice(array_values(array_unique($ordered)), 0, 6);

    if ($ordered === []) {
        seo_runtime_trace_record_internal_links($slug, [], 0, false, [], array_merge($traceResolutionBase, [
            'resolved_pillar' => $pillarSlug,
            'anchor_node' => $navCfg['anchor_node'],
            'çözümlenmiş_sütun' => $pillarSlug,
            'neighborhood_same_cluster' => 0,
            'neighborhood_cross_cluster' => 0,
        ]));
        return '';
    }

    if (isset($defs[$slug])) {
        $sourceCluster = seo_rt_cluster_key_for_slug($slug, $defs);
    } elseif ($navCfg['anchor_node'] !== null && isset($defs[$navCfg['anchor_node']])) {
        $sourceCluster = seo_rt_cluster_key_for_slug($navCfg['anchor_node'], $defs);
    } else {
        $sourceCluster = '_external';
    }

    $items = [];
    $renderedSlugs = [];
    $sameC = 0;
    $crossC = 0;
    $uplink = ($pillarSlug !== null && $pillarSlug !== '') ? $pillarSlug : null;
    $pageTitleForAnchor = isset($page['title']) ? trim((string) $page['title']) : '';
    foreach ($ordered as $s) {
        $entry = $defs[$s] ?? null;
        if (!is_array($entry) || empty($entry['nav_title'])) {
            continue;
        }
        $tCluster = isset($defs[$s]) ? seo_rt_cluster_key_for_slug($s, $defs) : '_external';
        if ($sourceCluster !== '_external' && $tCluster === $sourceCluster) {
            $sameC++;
        } else {
            $crossC++;
        }
        $anchor = seo_rt_context_link_anchor_for_target($s, $slug, $uplink, $pageTitleForAnchor);
        $shortLabel = trim((string) ($entry['nav_title'] ?? ''));
        if ($shortLabel === '') {
            $shortLabel = seo_rt_resolve_link_topic_name($s);
        }
        $items[] = [
            'title' => $anchor,
            'short_label' => $shortLabel,
            'href' => seo_runtime_normalize_internal_url(seo_rt_graph_path_for_slug($s)),
        ];
        $renderedSlugs[] = $s;
    }

    if ($items === []) {
        seo_runtime_trace_record_internal_links($slug, $ordered, 0, false, [], array_merge($traceResolutionBase, [
            'resolved_pillar' => $pillarSlug,
            'anchor_node' => $navCfg['anchor_node'],
            'çözümlenmiş_sütun' => $pillarSlug,
            'neighborhood_same_cluster' => 0,
            'neighborhood_cross_cluster' => 0,
        ]));
        return '';
    }

    seo_runtime_trace_record_internal_links($slug, $ordered, count($items), false, $renderedSlugs, array_merge($traceResolutionBase, [
        'resolved_pillar' => $pillarSlug,
        'anchor_node' => $navCfg['anchor_node'],
        'çözümlenmiş_sütun' => $pillarSlug,
        'neighborhood_same_cluster' => $sameC,
        'neighborhood_cross_cluster' => $crossC,
    ]));

    $html = '<section class="seo-context-links" aria-labelledby="seo-context-heading">';
    $html .= '<div class="container"><div class="seo-context-links__inner">';
    $html .= '<header class="seo-context-links__header">';
    $html .= '<span class="seo-context-links__eyebrow">Önerilen bağlantılar</span>';
    $html .= '<h2 id="seo-context-heading" class="seo-context-links__title">İlgili sayfalar</h2>';
    $html .= '<p class="seo-context-links__lead">Bu sayfayla birlikte sıkça bakılan hizmet ve içerikler; tek tıkla ilerleyin.</p>';
    $html .= '</header>';
    $html .= '<ul class="seo-context-links__grid list-unstyled mb-0">';
    foreach ($items as $it) {
        $href = htmlspecialchars($it['href'], ENT_QUOTES, 'UTF-8');
        $short = htmlspecialchars($it['short_label'], ENT_QUOTES, 'UTF-8');
        $tip = htmlspecialchars($it['title'], ENT_QUOTES, 'UTF-8');
        $html .= '<li class="seo-context-links__item"><a class="seo-context-links__card" href="' . $href
            . '" title="' . $tip . '"><span class="seo-context-links__card-text">' . $short
            . '</span><span class="seo-context-links__card-arrow" aria-hidden="true">'
            . '<i class="fas fa-arrow-right"></i></span></a></li>';
    }
    $html .= '</ul></div></div></section>';

    return $html;
}
