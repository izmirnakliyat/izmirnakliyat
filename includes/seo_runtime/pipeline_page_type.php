<?php
declare(strict_types=1);
/** Auto-split from seo_runtime.php — Phase 3 modular SEO runtime. */

/**
 * Ağırlık modeli (iz / dokümantasyon): path sabit; DB yalnızca teyit; router 0 (yönlendirme).
 */
function canonical_page_type_weight_model(): array
{
    return [
        'path' => 1.0,
        'db_confirm' => 0.35,
        'router' => 0.0,
    ];
}

/**
 * @param list<array<string, mixed>> $appliedInputs
 * @return array<string, mixed>
 */
function canonical_page_type_pack_decision(
    string $pageType,
    ?string $fallbackReason,
    string $baseSignalCode,
    array $appliedInputs,
    string $decisiveLayer,
    string $decisiveDetail
): array {
    $wm = canonical_page_type_weight_model();

    return [
        'page_type' => $pageType,
        'fallback_reason' => $fallbackReason,
        'decision_authority' => 'canonical_page_type_resolver',
        'model_version' => 2,
        'weight_model' => $wm,
        'base_signal_code' => $baseSignalCode,
        'influence' => [
            'decisive_layer' => $decisiveLayer,
            'decisive_detail' => $decisiveDetail,
            'post_lock' => true,
            'note' => 'Karar sonrası hiçbir girdi türü değiştiremez; yalnızca resolver öncesi öneri.',
        ],
        'applied_inputs' => $appliedInputs,
    ];
}

/**
 * Tek karar otoritesi — girdi katmanları: path (baz) + DB (teyit) + router (yalnız yönlendirme, ağırlık 0).
 *
 * @param array{relPath?:string, page?:?array, blog?:?array} $ctx
 * @return array<string, mixed>
 */
function canonical_page_type_decide(array $ctx): array
{
    $wm = canonical_page_type_weight_model();
    $wPath = (float) $wm['path'];
    $wDb = (float) $wm['db_confirm'];
    $wRouter = (float) $wm['router'];

    $applied = [];

    $relRaw = (string) ($ctx['relPath'] ?? '');
    $qpos = strpos($relRaw, '?');
    if ($qpos !== false) {
        $relRaw = substr($relRaw, 0, $qpos);
    }
    $trim = trim($relRaw, '/');
    if ($trim === 'index' || $trim === 'index.php') {
        $trim = '';
    }
    if (preg_match('#^([^/]+)\.php$#', $trim, $m)) {
        $trim = $m[1];
    }

    $parts = $trim === '' ? [] : explode('/', $trim);
    $first = $parts[0] ?? '';
    $segment = $first;
    $segmentKey = $segment !== '' ? mb_strtolower($segment, 'UTF-8') : '';

    $page = isset($ctx['page']) && is_array($ctx['page']) ? $ctx['page'] : null;
    $blog = isset($ctx['blog']) && is_array($ctx['blog']) ? $ctx['blog'] : null;

    $applied[] = [
        'layer' => 'path',
        'role' => 'normalize',
        'relPath_raw' => (string) ($ctx['relPath'] ?? ''),
        'trim' => $trim,
        'weight' => $wPath,
    ];

    $clusterSlugs = array_fill_keys(
        array_map(static function ($k): string {
            return mb_strtolower((string) $k, 'UTF-8');
        }, array_keys(seo_rt_pillar_cluster_definitions())),
        true
    );

    $baseCode = 'content_root';
    if ($trim === '') {
        $baseCode = 'home';
    } elseif ($trim === 'iletisim') {
        $baseCode = 'contact';
    } elseif ($first === 'hakkimizda' && count($parts) === 1) {
        $baseCode = 'about_path';
    } elseif ($first === 'blog') {
        $baseCode = 'blog_route';
    } elseif (count($parts) === 1) {
        $baseCode = 'content_root';
    } else {
        $baseCode = 'multi_segment';
    }

    $applied[] = [
        'layer' => 'path',
        'role' => 'base_signal',
        'code' => $baseCode,
        'segment' => $segmentKey !== '' ? $segmentKey : null,
        'weight' => $wPath,
    ];

    $applied[] = [
        'layer' => 'router',
        'role' => 'routing_only',
        'weight' => $wRouter,
        'note' => 'slug-router şablon seçer; sınıflandırma otoritesi taşımaz',
    ];

    if ($baseCode === 'home') {
        return canonical_page_type_pack_decision('home', null, $baseCode, $applied, 'path', 'empty_rel_path');
    }
    if ($baseCode === 'contact') {
        return canonical_page_type_pack_decision('contact', null, $baseCode, $applied, 'path', 'iletisim');
    }
    if ($baseCode === 'about_path') {
        return canonical_page_type_pack_decision('about', null, $baseCode, $applied, 'path', 'hakkimizda_segment');
    }
    if ($baseCode === 'blog_route') {
        return canonical_page_type_pack_decision('blog', null, $baseCode, $applied, 'path', 'blog_prefix');
    }
    if ($trim === 'shorts') {
        return canonical_page_type_pack_decision('shorts_hub', null, 'shorts_hub', $applied, 'path', 'shorts_hub');
    }
    if (preg_match('#^video/[A-Za-z0-9_-]{11}$#', $trim)) {
        return canonical_page_type_pack_decision('video_watch', null, 'video_watch', $applied, 'path', 'video_watch_slug');
    }

    if ($baseCode === 'content_root' && count($parts) === 1 && $segmentKey !== '') {
        if (in_array($segmentKey, ['llms.txt', 'llms-corpus.txt', 'llms.php'], true)) {
            $applied[] = [
                'layer' => 'path',
                'role' => 'llms_export_route',
                'segment' => $segmentKey,
                'weight' => $wPath,
            ];

            return canonical_page_type_pack_decision('llms_export', null, 'llms_export', $applied, 'path', 'llms_endpoint');
        }
    }

    // İlçe cluster slug'ları — blog_posts rotasında da Service şeması (blog_post değil).
    if ($baseCode === 'content_root' && count($parts) === 1 && $segmentKey !== '') {
        $faz2File = dirname(__DIR__) . '/mynak_faz2_ilce_seo.php';
        if (is_file($faz2File)) {
            require_once $faz2File;
            if (function_exists('mynak_faz2_is_ilce_slug') && mynak_faz2_is_ilce_slug($segmentKey)) {
                $applied[] = [
                    'layer' => 'path',
                    'role' => 'ilce_cluster_service_fallback',
                    'slug' => $segmentKey,
                    'weight' => $wPath,
                ];

                return canonical_page_type_pack_decision('service', null, $baseCode, $applied, 'path', 'ilce_cluster_slug');
            }
        }
    }

    $blogSlug = ($blog !== null && !empty($blog['slug'])) ? trim((string) $blog['slug'], '/') : '';
    $blogSlugKey = $blogSlug !== '' ? mb_strtolower($blogSlug, 'UTF-8') : '';

    $blogPathEligible = ($baseCode === 'content_root')
        && $segmentKey !== ''
        && $blogSlugKey !== ''
        && $blogSlugKey === $segmentKey
        && !isset($clusterSlugs[$segmentKey]);

    if ($blog !== null && $blogSlugKey !== '' && !$blogPathEligible) {
        $applied[] = [
            'layer' => 'db',
            'role' => 'blog_record_not_applied',
            'reason' => 'path_does_not_allow_blog_post_confirm',
            'base_code' => $baseCode,
            'weight' => $wDb,
        ];
    }

    if ($blogPathEligible && $blog !== null) {
        $applied[] = [
            'layer' => 'db',
            'role' => 'blog_confirm',
            'slug' => $blogSlugKey,
            'weight' => $wDb,
        ];

        return canonical_page_type_pack_decision('blog_post', null, $baseCode, $applied, 'db_confirm', 'blog_slug_matches_path_segment');
    }

    if ($page !== null && !empty($page['type']) && (string) $page['type'] === 'service') {
        $applied[] = [
            'layer' => 'db',
            'role' => 'service_type_confirm',
            'weight' => $wDb,
        ];

        return canonical_page_type_pack_decision('service', null, $baseCode, $applied, 'db_confirm', 'page.type=service');
    }

    // DB'de page.type yanlış/boş olsa da cluster hizmet slug'ları service olarak sınıflansın.
    // Bu sayede Service + FAQPage şeması kritik servis URL'lerinde düşmez.
    if (
        $baseCode === 'content_root'
        && count($parts) === 1
        && $segmentKey !== ''
        && isset($clusterSlugs[$segmentKey])
    ) {
        $applied[] = [
            'layer' => 'path',
            'role' => 'service_slug_fallback',
            'slug' => $segmentKey,
            'weight' => $wPath,
        ];

        return canonical_page_type_pack_decision('service', null, $baseCode, $applied, 'path', 'cluster_slug_service_fallback');
    }

    if ($page !== null && !empty($page['id'])) {
        $applied[] = [
            'layer' => 'db',
            'role' => 'static_page_id',
            'weight' => $wDb,
        ];

        return canonical_page_type_pack_decision('global', 'static_page', $baseCode, $applied, 'db_confirm', 'page.id');
    }

    if ($baseCode === 'multi_segment') {
        $applied[] = [
            'layer' => 'path',
            'role' => 'multi_segment_fallback',
            'weight' => $wPath,
        ];

        return canonical_page_type_pack_decision('global', 'unclassified', $baseCode, $applied, 'path', 'multi_segment');
    }

    $applied[] = [
        'layer' => 'path',
        'role' => 'no_matching_db_confirm',
        'weight' => $wPath,
    ];

    return canonical_page_type_pack_decision('global', 'unclassified', $baseCode, $applied, 'path', 'content_root_no_confirm');
}

/**
 * Yapısal SEO pipeline (SSOT çekirdek): aynı $ctx ile her zaman aynı page_type / jsonld_type_set / iç bağlam / LLMS iskeleti.
 * İçerik alanları (başlık, gövde, pazarlama metni) bu fonksiyonda okunmaz; flex_content_resolver ayrıdır.
 *
 * @param array{relPath?:string, get?:array, page?:?array, blog?:?array, site_settings?:array} $ctx
 * @return array{
 *   page_type:string,
 *   jsonld_type_set:array,
 *   internal_link_context:array,
 *   llms_export_context:array,
 *   authority_input_seed:array,
 *   intent_vector:array,
 *   location_vector:array,
 *   decision:array
 * }
 */
function canonical_seo_pipeline_core(array $ctx): array
{
    $decision = canonical_page_type_decide($ctx);
    $page_type = (string) ($decision['page_type'] ?? 'global');

    $jsonld_type_set = canonical_seo_pipeline_jsonld_type_set($page_type);
    $internal_link_context = canonical_seo_pipeline_internal_link_context($decision, $ctx);
    $llms_export_context = canonical_seo_pipeline_llms_export_context($page_type, $internal_link_context);
    $authority_input_seed = canonical_seo_pipeline_authority_input_seed($page_type, $internal_link_context);
    $intent_vector = canonical_seo_pipeline_intent_vector($page_type, $internal_link_context);
    $location_vector = canonical_seo_pipeline_location_vector($page_type, $ctx);

    return [
        'page_type' => $page_type,
        'jsonld_type_set' => $jsonld_type_set,
        'internal_link_context' => $internal_link_context,
        'llms_export_context' => $llms_export_context,
        'authority_input_seed' => $authority_input_seed,
        'intent_vector' => $intent_vector,
        'location_vector' => $location_vector,
        'decision' => $decision,
    ];
}

/**
 * @param array{relPath?:string, get?:array, page?:?array, blog?:?array, site_settings?:array} $ctx
 */
function canonical_seo_pipeline_resolve(array $ctx): array
{
    return canonical_seo_pipeline_core($ctx);
}

/**
 * URL + DB payload → karar; slug/ başlık yalnızca veri (yapı seçimi yok).
 *
 * @param array{relPath?:string, get?:array, page?:?array, blog?:?array} $ctx
 * @param array<string, mixed> $decision
 */
function canonical_seo_pipeline_graph_slug_from_ctx(array $decision, array $ctx): string
{
    $pt = (string) ($decision['page_type'] ?? 'global');
    $page = isset($ctx['page']) && is_array($ctx['page']) ? $ctx['page'] : null;
    $blog = isset($ctx['blog']) && is_array($ctx['blog']) ? $ctx['blog'] : null;
    if ($page !== null && !empty($page['slug']) && ($pt === 'service' || $pt === 'global')) {
        return trim((string) $page['slug'], '/');
    }
    if ($blog !== null && !empty($blog['slug']) && $pt === 'blog_post') {
        return trim((string) $blog['slug'], '/');
    }

    return '';
}

/**
 * @return array<string, mixed>
 */
function canonical_seo_pipeline_jsonld_type_set(string $page_type): array
{
    $out = [
        'emit_moving_company_inline' => $page_type !== 'llms_export',
        'breadcrumb' => $page_type !== 'llms_export',
        'website_on_home' => $page_type === 'home',
        'page_schema_types' => [],
    ];
    switch ($page_type) {
        case 'service':
            $out['page_schema_types'] = ['Service'];
            break;
        case 'blog':
            $out['page_schema_types'] = ['CollectionPage', 'ItemList'];
            break;
        case 'blog_post':
            $out['page_schema_types'] = ['BlogPosting'];
            break;
        case 'contact':
            $out['page_schema_types'] = ['ContactPage'];
            break;
        case 'about':
            $out['page_schema_types'] = ['AboutPage'];
            break;
        case 'home':
            $out['page_schema_types'] = ['ItemList', 'VideoObject'];
            break;
        case 'video_watch':
            $out['page_schema_types'] = ['WebPage', 'VideoObject'];
            break;
        case 'shorts_hub':
            $out['page_schema_types'] = ['CollectionPage', 'ItemList'];
            break;
    }

    return $out;
}

/**
 * @param array<string, mixed> $decision
 * @param array{relPath?:string, get?:array, page?:?array, blog?:?array} $ctx
 * @return array<string, mixed>
 */
function canonical_seo_pipeline_internal_link_context(array $decision, array $ctx): array
{
    $anchor = canonical_seo_pipeline_graph_slug_from_ctx($decision, $ctx);
    $graph_key = $anchor !== '' ? seo_ei_graph_key_for_url_slug($anchor) : '';

    return [
        'mode' => 'pillar_cluster_graph',
        'authority_ordering' => false,
        'anchor_slug' => $anchor,
        'graph_key' => $graph_key,
        'eligible_page_types' => ['service', 'global'],
    ];
}

/**
 * @return array<string, mixed>
 */
function canonical_seo_pipeline_llms_export_context(string $page_type, array $internal_link_context): array
{
    if ($page_type === 'llms_export') {
        return [
            'grouping' => 'full_site_graph',
            'section_layout' => [
                'brand_perception',
                'triple_dominance',
                'pillar_urls',
                'intent_map',
                'canonical_dataset',
                'authority_json',
                'trust_signals_block',
            ],
            'graph_anchor_slug' => (string) ($internal_link_context['graph_key'] ?? ''),
        ];
    }

    return [
        'grouping' => 'n_a',
        'section_layout' => [],
        'graph_anchor_slug' => (string) ($internal_link_context['graph_key'] ?? ''),
    ];
}

/**
 * @return array<string, mixed>
 */
function canonical_seo_pipeline_authority_input_seed(string $page_type, array $internal_link_context): array
{
    return [
        'page_type' => $page_type,
        'anchor_slug' => (string) ($internal_link_context['anchor_slug'] ?? ''),
        'graph_key' => (string) ($internal_link_context['graph_key'] ?? ''),
    ];
}

/**
 * @return array<string, mixed>
 */
function canonical_seo_pipeline_intent_vector(string $page_type, array $internal_link_context): array
{
    $anchor = (string) ($internal_link_context['anchor_slug'] ?? '');
    $spec = $anchor !== '' ? seo_ei_service_spec_for_pillar_slug($anchor) : null;
    $pk = is_array($spec) ? (string) ($spec['primary_intent'] ?? '') : '';

    return [
        'page_type' => $page_type,
        'graph_key' => (string) ($internal_link_context['graph_key'] ?? ''),
        'anchor_slug' => $anchor,
        'primary_intent_key' => $pk,
    ];
}

/**
 * @param array{relPath?:string, get?:array, page?:?array, blog?:?array, site_settings?:array} $ctx
 * @return array<string, mixed>
 */
function canonical_seo_pipeline_location_vector(string $page_type, array $ctx = []): array
{
    $vector = [
        'area_served_mode' => 'metro_districts',
        'district_ssot' => 'seo_ei_izmir_metro_district_location_defs',
        'metro_center' => 'İzmir',
        'country' => 'TR',
    ];

    $page = isset($ctx['page']) && is_array($ctx['page']) ? $ctx['page'] : null;
    $blog = isset($ctx['blog']) && is_array($ctx['blog']) ? $ctx['blog'] : null;
    $locSlug = '';
    if ($page !== null && !empty($page['slug'])) {
        $locSlug = (string) $page['slug'];
    } elseif ($blog !== null && !empty($blog['slug'])) {
        $locSlug = (string) $blog['slug'];
    }
    if ($locSlug !== '') {
        $faz2File = dirname(__DIR__) . '/mynak_faz2_ilce_seo.php';
        if (is_file($faz2File)) {
            require_once $faz2File;
            if (function_exists('mynak_faz2_enrich_location_vector')) {
                $vector = mynak_faz2_enrich_location_vector($vector, $locSlug);
            }
        }
    }

    return $vector;
}

/**
 * @param array{relPath?:string, get?:array, page?:?array, blog?:?array} $ctx
 * @param ?string $fallbackReasonOut global: static_page | unclassified
 * @param ?array<string, mixed> $traceOut tam karar kaydı (ağırlık + applied_inputs)
 */
function canonical_page_type_resolver(array $ctx, ?string &$fallbackReasonOut = null, ?array &$traceOut = null): string
{
    $pipeline = canonical_seo_pipeline_resolve($ctx);
    $decision = $pipeline['decision'];
    $fallbackReasonOut = isset($decision['fallback_reason']) && is_string($decision['fallback_reason'])
        ? $decision['fallback_reason']
        : null;
    if ($traceOut !== null) {
        $traceOut = $decision;
    }
    if (function_exists('seo_runtime_trace_record_page_type_decision')) {
        seo_runtime_trace_record_page_type_decision($decision);
    }

    return (string) ($pipeline['page_type'] ?? 'global');
}
