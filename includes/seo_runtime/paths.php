<?php
declare(strict_types=1);
/** Auto-split from seo_runtime.php — Phase 3 modular SEO runtime. */

/**
 * İstek URI → relPath (seo_runtime_document_head ile aynı kurallar; tek kaynak).
 */
function seo_runtime_compute_rel_path_from_request_uri(string $requestUri): string
{
    $qpos = strpos($requestUri, '?');
    $clean_path = $qpos !== false ? substr($requestUri, 0, $qpos) : $requestUri;

    if (function_exists('mynak_preprocess_leaked_uri_path')) {
        $clean_path = mynak_preprocess_leaked_uri_path($clean_path);
    }
    if (function_exists('mynak_normalize_leaked_windows_request_path')) {
        $clean_path = mynak_normalize_leaked_windows_request_path($clean_path);
    }

    if ($clean_path !== '/' && substr($clean_path, -1) === '/') {
        $clean_path = rtrim($clean_path, '/');
    }

    $pathPrefix = (defined('SITE_URL') && function_exists('mynak_url_path_prefix'))
        ? mynak_url_path_prefix()
        : '';
    $pathPrefix = ($pathPrefix !== '' && $pathPrefix !== '/') ? $pathPrefix : '';
    $relPath = $clean_path;
    if ($pathPrefix !== '' && (strpos($relPath, $pathPrefix . '/') === 0 || $relPath === $pathPrefix)) {
        $relPath = substr($relPath, strlen($pathPrefix)) ?: '/';
    }
    if ($relPath !== '/' && $relPath !== '') {
        $relPath = rtrim($relPath, '/');
    }
    if ($relPath === '/index' || $relPath === '/index.php') {
        $relPath = '/';
    }

    return $relPath;
}

/**
 * İstek fazı: normalize + erken 301 (tüm SEO öncesi yönlendirmeler bu girişten).
 */
function seo_runtime_pipeline_request_normalize(): void
{
    seo_runtime_trace_bootstrap_request();
    seo_runtime_early_redirects();
}

/**
 * İç URL normalleştirme — şablonların doğrudan functions yerine pipeline üzerinden kullanması için.
 */
function seo_runtime_normalize_internal_url(string $pathOrUrl): string
{
    if (!function_exists('normalize_internal_link_url')) {
        return $pathOrUrl;
    }
    return normalize_internal_link_url($pathOrUrl);
}

/**
 * Yayında düz /{slug}. Çok segmentli kanonik (/kategori/konu) açıldığında bu fonksiyon genişletilir.
 */
function seo_runtime_canonical_path_for_slug(string $slug): string
{
    $slug = trim($slug, '/');
    if ($slug === '') {
        return '/';
    }
    return '/' . $slug;
}

/**
 * Para (authority hub) sütunu — iç bağlantı, EI grafiği ve LLMS çıktıları için tek kaynak.
 */
function seo_rt_money_page_pillar_slug(): string
{
    return 'izmir-evden-eve-nakliyat';
}

/**
 * Küme / EI graf anahtarı → canlı services.slug (iç link href SSOT; pages öncelikli URL’ler hariç).
 */
function seo_rt_public_url_slug_for_graph_slug(string $graphSlug): string
{
    $graphSlug = trim($graphSlug, '/');
    static $map = [
        // Şehirlerarası kanonik URL = pages id=16 (tireli); iç linkler doğrudan ona gitsin.
        'sehirler-arasi-nakliyat' => 'sehirler-arasi-nakliyat',
        'izmir-ofis-tasimaciligi' => 'kurumsal-nakliye-hizmetleri',
        'izmir-esya-depolama' => 'esya-depolama',
        'antika-ve-piyano-tasima' => 'antika-piyano-tasimaciligi',
    ];

    return $map[$graphSlug] ?? $graphSlug;
}

function seo_rt_primary_service_public_url(string $canonical_origin, string $graphSlug): string
{
    return rtrim($canonical_origin, '/') . '/' . seo_rt_public_url_slug_for_graph_slug($graphSlug);
}

/** @return list<string> */
function seo_rt_system_path_prefixes(): array
{
    return ['admin', 'config', 'includes', 'logs', 'cache', 'scripts', 'cron'];
}

/** Ana sayfa: yalnızca bu parametreler “pasif” kabul edilir (noindex tetiklemez). */
/** @return list<string> */
function seo_rt_passive_tracking_params(): array
{
    return [
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
        'gclid', 'fbclid', 'msclkid', 'yclid', 'dclid', 'gbraid', 'wbraid',
        // Genel referer/kaynak takip parametreleri (UTM benzeri davranış; noindex tetiklemesin)
        'from', 'ref', 'referrer', 'referer', 'source', 'src',
        'mc_cid', 'mc_eid', 'igshid', 'mibextid', '_ga', '_gl',
        // AMP eski WP eklenti artığı (?amp, ?amp=1) — index'i parçalamamalı
        'amp', 'amphtml', 'noamp',
        // E-posta paylaşım ve diğer tracking
        'feature', 'medium', 'campaign', 'pk_campaign', 'pk_kwd', 'pk_source',
        // Eski WP feed/print parametreleri (içerik fragmanı yok)
        'replytocom', 'redirect_to', 'preview', 'preview_id', 'preview_nonce',
    ];
}

/**
 * Bu parametre adları (herhangi bir sayfada) noindex,follow tetikler.
 * @return list<string>
 */
function seo_rt_noindex_query_params(): array
{
    return [
        'sort', 'orderby', 'order', 'filter', 'filters', 'facet', 'facets',
        'q', 'query', 'search', 'arama', 'min', 'max', 'price', 'view', 'layout',
        'print', 'replytocom', 'share', 'callback', 'format',
    ];
}

/** @return list<string> */
function seo_rt_sitemap_blocked_prefixes(): array
{
    return [
        'admin', 'config', 'includes', 'logs', 'cache', 'scripts', 'cron',
        'ajax', 'uploads/temp', 'test_', 'debug_', 'fix_', 'dry_run_',
        'generate_', 'reset_', 'scan_', 'compare_', 'check_', 'clear_logs',
        'view_logs', 'slug-router',
    ];
}
