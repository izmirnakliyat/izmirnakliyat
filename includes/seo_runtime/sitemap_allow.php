<?php
declare(strict_types=1);
/** Auto-split from seo_runtime.php — Phase 3 modular SEO runtime. */

function seo_runtime_sitemap_allow_loc(string $loc, string $siteUrl): bool
{
    $siteUrl = rtrim($siteUrl, '/');
    if (str_contains($loc, '?')) {
        return false;
    }
    $base = parse_url($siteUrl);
    $locp = parse_url($loc);
    if (!is_array($base) || !is_array($locp) || empty($base['host']) || empty($locp['host'])) {
        return false;
    }
    if (strtolower((string) $locp['host']) !== strtolower((string) $base['host'])) {
        return false;
    }
    if ($loc !== $siteUrl && $loc !== $siteUrl . '/' && strpos($loc, $siteUrl . '/') !== 0) {
        return false;
    }

    $path = (string) (parse_url($loc, PHP_URL_PATH) ?? '/');
    $path = '/' . trim($path, '/');
    if ($path === '//') {
        $path = '/';
    }
    $p = trim($path, '/');
    if ($p === '') {
        return true;
    }
    foreach (seo_rt_sitemap_blocked_prefixes() as $prefix) {
        if ($p === $prefix || strpos($p, $prefix . '/') === 0) {
            return false;
        }
    }

    return true;
}
