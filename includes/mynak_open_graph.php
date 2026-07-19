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
 * @return array{image: string, image_width: int, image_height: int, image_type: string, image_alt: string, large_image: bool, type: 'article'|'website', site_name: string, title: string, description: string, url: string, twitter_card: 'summary_large_image'}
 */
function mynak_open_graph_build(array $ctx): array
{
    $site = isset($ctx['site_settings']) && is_array($ctx['site_settings']) ? $ctx['site_settings'] : [];
    $blog = isset($ctx['blog']) && is_array($ctx['blog']) ? $ctx['blog'] : null;
    $page = isset($ctx['page']) && is_array($ctx['page']) ? $ctx['page'] : null;
    $hero = isset($ctx['hero_lcp_preload_href']) ? trim((string) $ctx['hero_lcp_preload_href']) : '';
    $canonical = isset($ctx['canonical']) ? trim((string) $ctx['canonical']) : '';
    $title = isset($ctx['page_title']) ? trim((string) $ctx['page_title']) : '';
    $desc = isset($ctx['seo_description']) ? trim((string) $ctx['seo_description']) : '';
    $pageType = isset($ctx['page_type']) ? (string) $ctx['page_type'] : '';

    $siteName = defined('MYNAK_BRAND_NAME') ? (string) MYNAK_BRAND_NAME : 'MY Nakliyat';

    $image = mynak_open_graph_resolve_image($site, $blog, $page, $hero);

    if ($image === '' && function_exists('seo_default_placeholder_image_url')) {
        $image = seo_default_placeholder_image_url();
    }

    $ogType = $pageType === 'blog_post' ? 'article' : 'website';
    $imageMetadata = mynak_open_graph_image_metadata($image);

    return [
        'image' => $image,
        'image_width' => $imageMetadata['width'],
        'image_height' => $imageMetadata['height'],
        'image_type' => $imageMetadata['type'],
        'image_alt' => $title !== '' ? $title : $siteName,
        'large_image' => $imageMetadata['width'] >= 1200,
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
 * @param array<string, mixed>|null $page
 */
function mynak_open_graph_resolve_image(array $site, ?array $blog, ?array $page, string $heroLcp): string
{
    if ($blog !== null && !empty($blog['kapak_foto']) && is_string($blog['kapak_foto']) && trim($blog['kapak_foto']) !== '' && function_exists('blog_kapak_full_url')) {
        return blog_kapak_full_url($blog['kapak_foto']);
    }

    if ($page !== null && !empty($page['foto']) && is_string($page['foto'])) {
        $photo = trim($page['foto']);
        if (filter_var($photo, FILTER_VALIDATE_URL)) {
            return $photo;
        }
        if ($photo !== '' && defined('SITE_URL')) {
            $relative = ltrim($photo, '/');
            if (!str_contains($relative, '/')) {
                $relative = 'uploads/services/' . $relative;
            }

            return rtrim((string) SITE_URL, '/') . '/' . $relative;
        }
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

/** @return array{width:int,height:int,type:string} */
function mynak_open_graph_image_metadata(string $imageUrl): array
{
    $empty = ['width' => 0, 'height' => 0, 'type' => ''];
    $path = rawurldecode((string) parse_url($imageUrl, PHP_URL_PATH));
    if ($path === '') {
        return $empty;
    }
    $relativePath = ltrim($path, '/');
    $roots = [];
    if (defined('PROJECT_ROOT')) {
        $roots[] = rtrim((string) PROJECT_ROOT, '/');
    }
    if (!empty($_SERVER['DOCUMENT_ROOT']) && is_string($_SERVER['DOCUMENT_ROOT'])) {
        $roots[] = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
    }
    foreach (array_unique($roots) as $root) {
        $file = $root . '/' . $relativePath;
        if (!is_readable($file)) {
            continue;
        }
        $info = @getimagesize($file);
        if (is_array($info)) {
            return [
                'width' => (int) ($info[0] ?? 0),
                'height' => (int) ($info[1] ?? 0),
                'type' => (string) ($info['mime'] ?? ''),
            ];
        }
    }
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $types = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'avif' => 'image/avif',
        'gif' => 'image/gif',
    ];
    $empty['type'] = $types[$extension] ?? '';

    return $empty;
}
