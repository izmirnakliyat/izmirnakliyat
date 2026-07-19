<?php
declare(strict_types=1);
/** Auto-split from seo_runtime.php — Phase 3 modular SEO runtime. */

/**
 * Meta robots tek karar noktası (preset → kurallar).
 *
 * Dönüş:
 * - string → <meta name="robots"> içeriği
 * - null → indexlenebilir; etiket üretilmez (allow_indexing true ve kural yok)
 *
 * Öncelik sırası:
 * 0) meta_robots_preset dolu → uygula; ancak seo_fallback_content veya allow_indexing=false iken
 *    “index” önerisi yutulur (noindex,follow).
 * 1) seo_fallback_content → noindex,follow
 * 2) allow_indexing yok → noindex,follow
 * 3) İlk path segmenti sistem klasörü → noindex,nofollow
 * 4) Ana sayfa (index.php) ve GET’te pasif olmayan parametre → noindex,follow
 * 5) Kara liste parametre veya id/key → noindex,follow
 * 6) null
 *
 * @param array{relPath?:string, get?:array, allow_indexing?:bool, is_front_home?:bool, meta_robots_preset?:?string, seo_fallback_content?:bool} $ctx
 */
function seo_runtime_document_meta_robots(array $ctx): ?string
{
    $relPath = (string) ($ctx['relPath'] ?? '/');
    $get = isset($ctx['get']) && is_array($ctx['get']) ? $ctx['get'] : [];
    $allow = !empty($ctx['allow_indexing']);
    $isFrontHome = !empty($ctx['is_front_home']);

    $presetRaw = $ctx['meta_robots_preset'] ?? null;
    $presetMr = is_string($presetRaw) && $presetRaw !== '' ? $presetRaw : null;

    if ($presetMr !== null) {
        if (!empty($ctx['seo_fallback_content'])) {
            return 'noindex, follow';
        }
        if (!$allow && stripos($presetMr, 'noindex') === false) {
            return 'noindex, follow';
        }

        return $presetMr;
    }

    if (!empty($ctx['seo_fallback_content'])) {
        return 'noindex, follow';
    }

    if (!$allow) {
        return 'noindex, follow';
    }

    $trim = trim($relPath, '/');
    $first = strtolower(explode('/', $trim)[0] ?? '');
    if (in_array($first, seo_rt_system_path_prefixes(), true)) {
        return 'noindex, nofollow';
    }

    $passive = seo_rt_passive_tracking_params();

    if ($isFrontHome && $get !== []) {
        foreach (array_keys($get) as $k) {
            if (!in_array(strtolower((string) $k), $passive, true)) {
                return 'noindex, follow';
            }
        }
    }

    foreach (array_keys($get) as $k) {
        if (in_array(strtolower((string) $k), seo_rt_noindex_query_params(), true)) {
            return 'noindex, follow';
        }
    }

    if (isset($get['id']) || isset($get['key'])) {
        return 'noindex, follow';
    }

    return null;
}

function seo_runtime_apply_discover_robots_directive(?string $robots): string
{
    $robots = trim((string) $robots);
    if ($robots === '') {
        return 'index, follow, max-image-preview:large';
    }
    if (stripos($robots, 'noindex') !== false || stripos($robots, 'max-image-preview:') !== false) {
        return $robots;
    }

    return rtrim($robots, ', ') . ', max-image-preview:large';
}

function seo_rt_canonical_path_key(string $url): string
{
    $p = parse_url($url);
    if (!is_array($p) || empty($p['host'])) {
        return '';
    }
    $h = strtolower((string) $p['host']);
    if (strncmp($h, 'www.', 4) === 0) {
        $h = substr($h, 4);
    }
    $path = isset($p['path']) ? $p['path'] : '/';
    $path = '/' . trim($path, '/');
    if ($path !== '/') {
        $path = rtrim($path, '/');
    }
    return $h . $path;
}
