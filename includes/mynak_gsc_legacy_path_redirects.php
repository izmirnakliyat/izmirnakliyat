<?php
declare(strict_types=1);

/**
 * GSC 404 drilldown — kalan legacy path / kısa slug 301 ve çöp URL 410.
 */

/**
 * @return array<string, string> eski slug => hedef slug (blog, hizmet veya sayfa)
 */
function mynak_gsc_legacy_static_slug_map(): array
{
    return [
        'cesme-nakliyat' => 'cesme-evden-eve-nakliyat',
        'sehirlerarasi-nakliye-' => 'sehirlerarasi-nakliyat',
        'tasinirken-ambalaj-' => 'tasinirken-ambalaj-ve-paketlemenin-onemi',
        'garantili-evdeneve-nakliyat' => 'izmir-evden-eve-nakliyat',
        'nakliye' => 'blog',
    ];
}

/**
 * WP Yoast / eski XML site haritaları → güncel indeks.
 *
 * @return array<string, string> istek slug => hedef slug (special handler veya path)
 */
function mynak_gsc_legacy_sitemap_slug_map(): array
{
    return [
        'category-sitemap.xml' => 'sitemap-index.xml',
        'post-sitemap.xml' => 'sitemap-index.xml',
        'page-sitemap.xml' => 'sitemap-index.xml',
        'author-sitemap.xml' => 'sitemap-index.xml',
        'sitemap_index.xml' => 'sitemap-index.xml',
    ];
}

function mynak_fc_slug_is_template_junk(string $slug): bool
{
    if ($slug === '') {
        return false;
    }
    $lower = strtolower(rawurldecode($slug));

    return str_contains($lower, '${')
        || str_contains($lower, '%7b%24')
        || $lower === '${item.slug}'
        || str_contains($lower, 'item.slug');
}

/**
 * Slug hizmet veya sayfa tablosunda yayında mı?
 */
function mynak_fc_resolve_live_content_slug(mysqli $conn, string $slug): ?string
{
    if ($slug === '' || !function_exists('mysqli_stmt_fetch_all_assoc')) {
        return null;
    }
    $stmt = $conn->prepare(
        'SELECT slug FROM services WHERE slug = ? AND status = 1 '
        . 'UNION SELECT slug FROM pages WHERE slug = ? AND status = 1 LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('ss', $slug, $slug);
    $stmt->execute();
    $rows = mysqli_stmt_fetch_all_assoc($stmt);
    $stmt->close();
    if (empty($rows[0]['slug'])) {
        return null;
    }

    return (string) $rows[0]['slug'];
}

/**
 * Blog yazısı yayında mı?
 */
function mynak_fc_resolve_live_blog_slug(mysqli $conn, string $slug): ?string
{
    if ($slug === '' || !function_exists('mysqli_stmt_fetch_all_assoc')) {
        return null;
    }
    $stmt = $conn->prepare('SELECT slug FROM blog_posts WHERE slug = ? AND durum = 3 LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $rows = mysqli_stmt_fetch_all_assoc($stmt);
    $stmt->close();
    if (empty($rows[0]['slug'])) {
        return null;
    }

    return (string) $rows[0]['slug'];
}

function mynak_fc_send_legacy_redirect(string $targetPath, int $code = 301): void
{
    $loc = mynak_abs_url_from_public_path(mynak_public_path(ltrim($targetPath, '/')));
    if (function_exists('seo_runtime_trace_record_redirect')) {
        seo_runtime_trace_record_redirect(
            (function_exists('mynak_request_scheme_for_trace') ? mynak_request_scheme_for_trace() : 'https')
                . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? ''),
            $loc,
            $code
        );
    }
    header('Location: ' . $loc, true, $code);
    exit;
}

/**
 * Eşleşme varsa yönlendirir veya 410 döner.
 */
function mynak_fc_try_gsc_legacy_path_redirect(mysqli $conn, string $slug): void
{
    if ($slug === '') {
        return;
    }

    if (mynak_fc_slug_is_template_junk($slug)) {
        http_response_code(410);
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Robots-Tag: noindex, nofollow', true);
        echo '<!doctype html><html lang="tr"><head><meta charset="UTF-8"><title>410</title></head>'
            . '<body><p>Geçersiz adres.</p></body></html>';
        exit;
    }

    $sitemapMap = mynak_gsc_legacy_sitemap_slug_map();
    $slugKey = strtolower($slug);
    if (isset($sitemapMap[$slugKey])) {
        mynak_fc_send_legacy_redirect('/' . $sitemapMap[$slugKey]);
    }

    $staticMap = mynak_gsc_legacy_static_slug_map();
    if (isset($staticMap[$slug])) {
        $target = $staticMap[$slug];
        if ($target === 'blog') {
            mynak_fc_send_legacy_redirect(mynak_blog_href_path(''));
        }
        $live = mynak_fc_resolve_live_blog_slug($conn, $target)
            ?? mynak_fc_resolve_live_content_slug($conn, $target);
        if ($live !== null) {
            mynak_fc_send_legacy_redirect(mynak_public_path($live));
        }
    }

    // /blog/kategori/{slug} — kategori yok ama hizmet/sayfa slug'ı varsa (ör. esya-depolama)
    if (preg_match('#^blog/kategori/([^/]+)/?$#u', $slug, $m)) {
        $catSlug = $m[1];
        $stmt = $conn->prepare('SELECT id FROM blog_categories WHERE slug = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $catSlug);
            $stmt->execute();
            $rows = mysqli_stmt_fetch_all_assoc($stmt);
            $stmt->close();
            if (empty($rows)) {
                $live = mynak_fc_resolve_live_content_slug($conn, $catSlug);
                if ($live !== null) {
                    mynak_fc_send_legacy_redirect(mynak_public_path($live));
                }
                mynak_fc_send_legacy_redirect(mynak_blog_href_path(''));
            }
        }
    }
}
