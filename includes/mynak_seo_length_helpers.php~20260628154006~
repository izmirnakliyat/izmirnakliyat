<?php
declare(strict_types=1);

/**
 * Dashboard SEO kartı ile aynı uzunluk kuralları (admin/includes/dashboard_seo_metrics.php).
 */
function mynak_seo_dashboard_title_class(int $len): string
{
    if ($len === 0) {
        return 'missing';
    }
    if ($len < 30) {
        return 'short';
    }
    if ($len > 60) {
        return 'long';
    }

    return 'ok';
}

function mynak_seo_dashboard_meta_class(int $len): string
{
    if ($len === 0) {
        return 'missing';
    }
    if ($len < 120) {
        return 'short';
    }
    if ($len > 160) {
        return 'long';
    }

    return 'ok';
}

function mynak_seo_trim_at_word(string $text, int $maxLen): string
{
    $text = trim($text);
    if ($text === '' || mb_strlen($text) <= $maxLen) {
        return $text;
    }
    $cut = mb_substr($text, 0, $maxLen);
    $pos = mb_strrpos($cut, ' ');
    if ($pos !== false && $pos >= (int) floor($maxLen * 0.65)) {
        return trim(mb_substr($cut, 0, $pos));
    }

    return trim($cut);
}

/**
 * Sayfa seo_title — gereksiz site soneklerini kaldırır, 30–60 karaktere indirir.
 */
function mynak_seo_fix_page_title(string $seoTitle, string $pageTitle, string $slug = ''): string
{
    $t = trim($seoTitle);
    if ($t === '') {
        $t = trim($pageTitle);
    }
    $patterns = [
        '/\s*-\s*İzmir Evden Eve Nakliyat.*$/iu',
        '/\s*\|\s*MY Nakliyat.*$/iu',
        '/\s*-\s*MY Nakliyat.*$/iu',
        '/\s*®\s*Resmi Sitesi.*$/iu',
    ];
    foreach ($patterns as $p) {
        $t = trim((string) preg_replace($p, '', $t));
    }
    $t = preg_replace('/\s+/u', ' ', $t) ?? $t;

    if (mynak_seo_dashboard_title_class(mb_strlen($t)) === 'ok') {
        return $t;
    }

    if (preg_match('/^(.+?)\s+Evden\s+Eve\s+Nakliyat/i', $t, $m)) {
        $loc = trim($m[1], " \t\n\r\0\x0B-|");
        $t = $loc . ' Evden Eve Nakliyat | MY Nakliyat';
    } elseif ($slug !== '' && preg_match('/^([a-z0-9-]+)-evden-eve-nakliyat/i', $slug, $sm)) {
        $loc = str_replace('-', ' ', $sm[1]);
        $loc = mb_convert_case($loc, MB_CASE_TITLE, 'UTF-8');
        $t = $loc . ' Evden Eve Nakliyat | MY Nakliyat';
    }

    if (mynak_seo_dashboard_title_class(mb_strlen($t)) === 'short' && mb_strlen($t) > 0) {
        $t .= ' | İzmir';
    }

    return mynak_seo_trim_at_word($t, 60);
}

/**
 * Meta description — 120–160 karakter bandına getirir.
 */
function mynak_seo_fix_page_meta(string $meta, string $pageTitle): string
{
    $m = trim(strip_tags($meta));
    if ($m === '') {
        $base = trim($pageTitle);
        $m = $base !== ''
            ? $base . ' — sigortalı evden eve nakliyat, ücretsiz ekspertiz ve yazılı sözleşme. MY Nakliyat İzmir.'
            : 'İzmir evden eve nakliyat — sigortalı taşıma, ücretsiz ekspertiz ve profesyonel ekip. MY Nakliyat.';
    }

    $class = mynak_seo_dashboard_meta_class(mb_strlen($m));
    if ($class === 'long') {
        return mynak_seo_trim_at_word($m, 160);
    }
    if ($class === 'short') {
        $suffix = ' MY Nakliyat ile güvenli ve sigortalı taşımacılık. Ücretsiz ekspertiz için hemen teklif alın.';
        $m = trim($m . $suffix);
        if (mynak_seo_dashboard_meta_class(mb_strlen($m)) === 'long') {
            return mynak_seo_trim_at_word($m, 160);
        }
    }

    return $m;
}

/**
 * @return array{seo_title: string, meta_description: string, changed: bool}
 */
function mynak_seo_fix_page_row(array $row): array
{
    $seoTitle = trim((string) ($row['seo_title'] ?? ''));
    $pageTitle = trim((string) ($row['title'] ?? ''));
    $meta = trim((string) ($row['meta_description'] ?? ''));
    $slug = trim((string) ($row['slug'] ?? ''));

    $effectiveTitle = $seoTitle !== '' ? $seoTitle : $pageTitle;
    $titleClass = mynak_seo_dashboard_title_class(mb_strlen($effectiveTitle));
    $metaClass = mynak_seo_dashboard_meta_class(mb_strlen($meta));

    $newTitle = $seoTitle;
    $newMeta = $meta;
    if ($titleClass !== 'ok') {
        $newTitle = mynak_seo_fix_page_title($seoTitle, $pageTitle, $slug);
    }
    if ($metaClass !== 'ok') {
        $newMeta = mynak_seo_fix_page_meta($meta, $pageTitle !== '' ? $pageTitle : $newTitle);
    }

    return [
        'seo_title' => $newTitle,
        'meta_description' => $newMeta,
        'changed' => ($newTitle !== $seoTitle || $newMeta !== $meta),
    ];
}

/**
 * Blog satırı (baslik → title alanı ile sayfa mantığı).
 *
 * @param array{baslik?: string, seo_title?: string, meta_description?: string, slug?: string} $row
 * @return array{seo_title: string, meta_description: string, changed: bool}
 */
function mynak_seo_fix_blog_row(array $row): array
{
    return mynak_seo_fix_page_row([
        'seo_title' => (string) ($row['seo_title'] ?? ''),
        'title' => (string) ($row['baslik'] ?? ''),
        'meta_description' => (string) ($row['meta_description'] ?? ''),
        'slug' => (string) ($row['slug'] ?? ''),
    ]);
}

/**
 * Dashboard kurallarına göre toplu düzeltme (sayfalar + yayında blog).
 *
 * @return array{
 *   pages_fixed: int,
 *   blogs_fixed: int,
 *   preview: list<array{type: string, slug: string, seo_title: string, meta_len: int}>
 * }
 */
function mynak_seo_apply_bulk_title_meta_fixes(mysqli $conn, bool $fixPages = true, bool $fixBlogs = true): array
{
    $out = ['pages_fixed' => 0, 'blogs_fixed' => 0, 'preview' => []];

    if ($fixPages) {
        $res = $conn->query('SELECT id, slug, title, seo_title, meta_description FROM pages WHERE status = 1 ORDER BY id');
        $stmt = $conn->prepare('UPDATE pages SET seo_title = ?, meta_description = ? WHERE id = ?');
        if ($res && $stmt) {
            while ($row = $res->fetch_assoc()) {
                $fix = mynak_seo_fix_page_row($row);
                if (!$fix['changed']) {
                    continue;
                }
                $newTitle = $fix['seo_title'];
                $newMeta = $fix['meta_description'];
                $id = (int) $row['id'];
                $stmt->bind_param('ssi', $newTitle, $newMeta, $id);
                if ($stmt->execute()) {
                    $out['pages_fixed']++;
                    if (count($out['preview']) < 40) {
                        $out['preview'][] = [
                            'type' => 'sayfa',
                            'slug' => (string) $row['slug'],
                            'seo_title' => $newTitle,
                            'meta_len' => mb_strlen($newMeta),
                        ];
                    }
                }
            }
            $stmt->close();
            $res->free();
        }
    }

    if ($fixBlogs) {
        if (!defined('MYNAK_BLOG_STATUS_PUBLISHED')) {
            require_once __DIR__ . '/blog_post_status.php';
        }
        $published = (int) MYNAK_BLOG_STATUS_PUBLISHED;
        $res = $conn->query(
            "SELECT id, slug, baslik, seo_title, meta_description FROM blog_posts WHERE durum = {$published} ORDER BY id"
        );
        $stmt = $conn->prepare('UPDATE blog_posts SET seo_title = ?, meta_description = ? WHERE id = ?');
        if ($res && $stmt) {
            while ($row = $res->fetch_assoc()) {
                $fix = mynak_seo_fix_blog_row($row);
                if (!$fix['changed']) {
                    continue;
                }
                $newTitle = $fix['seo_title'];
                $newMeta = $fix['meta_description'];
                $id = (int) $row['id'];
                $stmt->bind_param('ssi', $newTitle, $newMeta, $id);
                if ($stmt->execute()) {
                    $out['blogs_fixed']++;
                    if (count($out['preview']) < 40) {
                        $out['preview'][] = [
                            'type' => 'blog',
                            'slug' => (string) $row['slug'],
                            'seo_title' => $newTitle,
                            'meta_len' => mb_strlen($newMeta),
                        ];
                    }
                }
            }
            $stmt->close();
            $res->free();
        }
    }

    return $out;
}
