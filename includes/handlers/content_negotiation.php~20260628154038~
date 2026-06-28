<?php
declare(strict_types=1);

/**
 * Content negotiation: `Accept: text/markdown` istekleri için LLM-friendly
 * markdown çıktısı sunar.
 *
 * Tetikleyiciler:
 *   - HTTP_ACCEPT içeriği `text/markdown` (öncelikli)
 *   - URL ?format=markdown veya ?format=md
 *
 * Sayfa türleri:
 *   - Blog yazısı (blog_posts.slug)
 *   - Hizmet sayfası (services.slug)
 *
 * Çıktı: `text/markdown; charset=utf-8`. X-Robots-Tag varsayılan olarak
 * `noindex, follow` (Google bu varyantı index etmesin, LLM erişebilsin).
 */

if (defined('MYNAK_CONTENT_NEGOTIATION_LOADED')) {
    return;
}
define('MYNAK_CONTENT_NEGOTIATION_LOADED', true);

function mynak_cn_wants_markdown(): bool
{
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
    if (strpos($accept, 'text/markdown') !== false) {
        return true;
    }
    $format = strtolower((string) ($_GET['format'] ?? ''));
    if ($format === 'markdown' || $format === 'md') {
        return true;
    }
    return false;
}

/**
 * Markdown response yazar ve true döner. Çıktıyı yazıp tüketici tarafa
 * `exit` etmesini bırakır.
 */
function mynak_cn_emit_markdown(string $title, string $contentMd, array $meta = []): bool
{
    if (headers_sent()) {
        return false;
    }
    if (!function_exists('mynak_html_to_markdown')) {
        require_once dirname(__DIR__) . '/markdown/html_to_markdown.php';
    }
    header('Content-Type: text/markdown; charset=utf-8', true);
    header('X-Robots-Tag: noindex, follow', true);
    header('Cache-Control: public, max-age=600');
    header('Vary: Accept');

    echo "# " . trim($title) . "\n\n";
    if (!empty($meta['description'])) {
        echo "_" . trim((string) $meta['description']) . "_\n\n";
    }
    if (!empty($meta['url'])) {
        echo "Source: " . (string) $meta['url'] . "\n\n";
    }
    if (!empty($meta['author'])) {
        echo "Author: " . (string) $meta['author'] . "\n\n";
    }
    if (!empty($meta['date_published'])) {
        echo "Published: " . (string) $meta['date_published'] . "\n";
    }
    if (!empty($meta['date_modified'])) {
        echo "Updated: " . (string) $meta['date_modified'] . "\n";
    }
    if (!empty($meta['category'])) {
        echo "Category: " . (string) $meta['category'] . "\n";
    }
    if (!empty($meta['tags'])) {
        echo "Tags: " . (string) $meta['tags'] . "\n";
    }
    echo "\n---\n\n";
    echo $contentMd;
    echo "\n";
    return true;
}

/**
 * Blog yazısı için markdown sun.
 */
function mynak_cn_try_emit_blog_markdown(mysqli $conn, string $slug): bool
{
    if (!mynak_cn_wants_markdown() || $slug === '') {
        return false;
    }

    $stmt = $conn->prepare('SELECT bp.*, bc.ad AS kategori_ad, bc.slug AS kategori_slug, a.name AS author_name FROM blog_posts bp LEFT JOIN blog_categories bc ON bp.kategori_id = bc.id LEFT JOIN authors a ON bp.author_id = a.id WHERE bp.slug = ? AND bp.durum = 3 LIMIT 1');
    if (!($stmt instanceof mysqli_stmt)) {
        return false;
    }
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res instanceof mysqli_result ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!is_array($row)) {
        return false;
    }

    if (!function_exists('mynak_html_to_markdown')) {
        require_once dirname(__DIR__) . '/markdown/html_to_markdown.php';
    }

    $rawHtml = (string) ($row['icerik'] ?? '');
    if (function_exists('mynak_blok_isle') && isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
        $rawHtml = mynak_blok_isle($GLOBALS['conn'], $rawHtml);
    }
    $contentMd = mynak_html_to_markdown($rawHtml);

    $siteUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
    $url = $siteUrl !== '' ? $siteUrl . '/' . ltrim((string) $row['slug'], '/') : '';

    return mynak_cn_emit_markdown(
        (string) ($row['seo_title'] ?? $row['baslik'] ?? ''),
        $contentMd,
        [
            'description' => (string) ($row['meta_description'] ?? ''),
            'url' => $url,
            'author' => (string) ($row['author_name'] ?? ''),
            'category' => (string) ($row['kategori_ad'] ?? ''),
            'tags' => (string) ($row['etiketler'] ?? ''),
            'date_published' => (string) ($row['created_at'] ?? ''),
            'date_modified' => (string) ($row['updated_at'] ?? ''),
        ]
    );
}

/**
 * Hizmet sayfası için markdown sun.
 */
function mynak_cn_try_emit_service_markdown(mysqli $conn, string $slug): bool
{
    if (!mynak_cn_wants_markdown() || $slug === '') {
        return false;
    }

    $stmt = $conn->prepare('SELECT * FROM services WHERE slug = ? AND status = 1 LIMIT 1');
    if (!($stmt instanceof mysqli_stmt)) {
        return false;
    }
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res instanceof mysqli_result ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!is_array($row)) {
        return false;
    }

    if (!function_exists('mynak_html_to_markdown')) {
        require_once dirname(__DIR__) . '/markdown/html_to_markdown.php';
    }

    $rawHtml = (string) ($row['icerik'] ?? '');
    if (function_exists('mynak_blok_isle') && isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
        $rawHtml = mynak_blok_isle($GLOBALS['conn'], $rawHtml);
    }
    $contentMd = mynak_html_to_markdown($rawHtml);

    $siteUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
    $url = $siteUrl !== '' ? $siteUrl . '/' . ltrim((string) $row['slug'], '/') : '';

    return mynak_cn_emit_markdown(
        (string) ($row['seo_title'] ?? $row['ana_baslik'] ?? ''),
        $contentMd,
        [
            'description' => (string) ($row['meta_description'] ?? $row['aciklama'] ?? ''),
            'url' => $url,
        ]
    );
}
