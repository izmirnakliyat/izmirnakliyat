<?php
declare(strict_types=1);

/**
 * Open Graph + Twitter Card: tek kaynaktan mutlak URL ve tip.
 *
 * @param array{
 *   site_settings?: array<string, string>,
 *   blog?: array<string, mixed>|null,
 *   page?: array<string, mixed>|null,
 *   hero_lcp_preload_href?: string,
 *   canonical?: string,
 *   page_title?: string,
 *   seo_description?: string,
 *   page_type?: string
 * } $ctx
 * @return array{image: string, type: 'article'|'website', site_name: string, title: string, description: string, url: string, twitter_card: 'summary_large_image'}
 */
function mynak_open_graph_build(array $ctx): array
{
    $site = isset($ctx['site_settings']) && is_array($ctx['site_settings']) ? $ctx['site_settings'] : [];
    $blog = isset($ctx['blog']) && is_array($ctx['blog']) ? $ctx['blog'] : null;
    $hero = isset($ctx['hero_lcp_preload_href']) ? trim((string) $ctx['hero_lcp_preload_href']) : '';
    $canonical = isset($ctx['canonical']) ? trim((string) $ctx['canonical']) : '';
    $title = isset($ctx['page_title']) ? trim((string) $ctx['page_title']) : '';
    $desc = isset($ctx['seo_description']) ? trim((string) $ctx['seo_description']) : '';
    $pageType = isset($ctx['page_type']) ? (string) $ctx['page_type'] : '';

    $siteName = defined('MYNAK_BRAND_NAME') ? (string) MYNAK_BRAND_NAME : 'MY Nakliyat';

    $image = mynak_open_graph_resolve_image($site, $blog, $hero);

    if ($image === '' && function_exists('seo_default_placeholder_image_url')) {
        $image = seo_default_placeholder_image_url();
    }

    $ogType = $pageType === 'blog_post' ? 'article' : 'website';

    return [
        'image' => $image,
        'type' => $ogType,
        'site_name' => $siteName,
        'title' => $title,
        'description' => $desc,
        'url' => $canonical,
        'twitter_card' => 'summary_large_image',
    ];
}

/**
 * @param array<string, string> $site
 * @param array<string, mixed>|null $blog
 */
function mynak_open_graph_resolve_image(array $site, ?array $blog, string $heroLcp): string
{
    if ($blog !== null && !empty($blog['kapak_foto']) && is_string($blog['kapak_foto']) && trim($blog['kapak_foto']) !== '' && function_exists('blog_kapak_full_url')) {
        return blog_kapak_full_url($blog['kapak_foto']);
    }

    if ($heroLcp !== '' && (str_starts_with($heroLcp, 'http://') || str_starts_with($heroLcp, 'https://'))) {
        return $heroLcp;
    }

    foreach (['logo_light', 'logo', 'favicon'] as $k) {
        if (empty($site[$k]) || !is_string($site[$k]) || trim($site[$k]) === '') {
            continue;
        }
        $file = ltrim($site[$k], '/');
        $rel = 'settings/' . $file;
        if (function_exists('seo_upload_file_exists') && seo_upload_file_exists($rel) && function_exists('seo_upload_url')) {
            return seo_upload_url($rel);
        }
    }

    return '';
}
