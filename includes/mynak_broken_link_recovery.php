<?php
declare(strict_types=1);

/**
 * Broken internal link recovery — son 404 oncesi agresif slug normalize ve DB lookup.
 * Semrush: 1.020 kirik ic link.
 *
 * Strateji:
 * 1. Slug sonunda trailing dash veya tekrarlayan tire varsa normalize et
 * 2. WordPress ?p=ID / ?page_id=ID query → blog_posts.id lookup
 * 3. Slug'dan .html/.htm uzantisi cikart
 * 4. Slug parcala (prefix/suffix) ve DB'de partial LIKE ara
 * 5. Levenshtein mesafesi ile en yakin canlı slug bul (kisa sluglar icin)
 */

/**
 * Slug normalize: yaygın URL hatalarını düzelt.
 * @return string|null düzeltilmiş slug (null = düzeltilemez)
 */
function mynak_normalize_broken_slug(string $slug): ?string
{
    if ($slug === '') {
        return null;
    }

    $original = $slug;

    // Trailing dash veya tekrarlayan tire
    $slug = preg_replace('#-{2,}#', '-', $slug);
    $slug = trim($slug, '-');

    // .html / .htm uzantısı kaldır
    if (preg_match('#^(.+)\.(html?|php|asp)$#i', $slug, $m)) {
        $slug = $m[1];
    }

    // Sonda /index, /index.html kaldır
    $slug = preg_replace('#/index(\.html?)?$#i', '', $slug);

    // URL-encoded Türkçe karakterleri decode et
    if (str_contains($slug, '%')) {
        $decoded = rawurldecode($slug);
        // Sadece ASCII+Türkçe karakterler kalmalı
        if (preg_match('#^[\p{L}\p{N}\-/]+$#u', $decoded)) {
            $slug = $decoded;
        }
    }

    // Türkçe → ASCII transliteration (i̇ → i, ş → s, ü → u, etc.)
    $trMap = [
        'ı' => 'i', 'İ' => 'i', 'ş' => 's', 'Ş' => 's',
        'ğ' => 'g', 'Ğ' => 'g', 'ü' => 'u', 'Ü' => 'u',
        'ö' => 'o', 'Ö' => 'o', 'ç' => 'c', 'Ç' => 'c',
    ];
    $slugAscii = strtr(mb_strtolower($slug, 'UTF-8'), $trMap);

    if ($slugAscii !== $original && $slugAscii !== '') {
        return $slugAscii;
    }

    if ($slug !== $original && $slug !== '') {
        return $slug;
    }

    return null;
}

/**
 * DB'de slug'ın kısa versiyonunu (ilk N segment) ara.
 */
function mynak_try_partial_slug_match(mysqli $conn, string $slug): ?string
{
    if ($slug === '' || str_contains($slug, '/')) {
        return null;
    }

    // Sadece tek segment, tire içeren slug'lar
    $parts = explode('-', $slug);
    if (count($parts) < 3) {
        return null;
    }

    // İlk 3-4 segmentle prefix arama (en kısa -> en uzun)
    $prefixLens = [count($parts) - 1, count($parts) - 2];
    foreach ($prefixLens as $len) {
        if ($len < 3) {
            continue;
        }
        $prefix = implode('-', array_slice($parts, 0, $len));
        if (strlen($prefix) < 10) {
            continue;
        }
        $like = $prefix . '%';
        $stmt = $conn->prepare(
            'SELECT slug FROM blog_posts WHERE durum = 3 AND slug LIKE ? LIMIT 3'
        );
        if (!$stmt) {
            continue;
        }
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $rows = mysqli_stmt_fetch_all_assoc($stmt);
        $stmt->close();

        if (count($rows) === 1) {
            return (string) $rows[0]['slug'];
        }
    }

    // Services tablosunda da dene
    $prefix = implode('-', array_slice($parts, 0, min(count($parts) - 1, 4)));
    if (strlen($prefix) >= 8) {
        $like = $prefix . '%';
        $stmt = $conn->prepare(
            'SELECT slug FROM services WHERE status = 1 AND slug LIKE ? LIMIT 3'
        );
        if ($stmt) {
            $stmt->bind_param('s', $like);
            $stmt->execute();
            $rows = mysqli_stmt_fetch_all_assoc($stmt);
            $stmt->close();
            if (count($rows) === 1) {
                return (string) $rows[0]['slug'];
            }
        }
    }

    return null;
}

/**
 * WordPress query string parametrelerinden redirect hedefi bul.
 * ?p=ID → blog_posts.id lookup; ?page_id=ID → pages.id lookup
 */
function mynak_try_wp_query_param_redirect(mysqli $conn): ?string
{
    $p = $_GET['p'] ?? null;
    $pageId = $_GET['page_id'] ?? null;

    if ($p !== null && ctype_digit((string) $p)) {
        $id = (int) $p;
        $stmt = $conn->prepare('SELECT slug FROM blog_posts WHERE id = ? AND durum = 3 LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $rows = mysqli_stmt_fetch_all_assoc($stmt);
            $stmt->close();
            if (!empty($rows[0]['slug'])) {
                return (string) $rows[0]['slug'];
            }
        }
    }

    if ($pageId !== null && ctype_digit((string) $pageId)) {
        $id = (int) $pageId;
        $stmt = $conn->prepare('SELECT slug FROM pages WHERE id = ? AND status = 1 LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $rows = mysqli_stmt_fetch_all_assoc($stmt);
            $stmt->close();
            if (!empty($rows[0]['slug'])) {
                return (string) $rows[0]['slug'];
            }
        }
        // Ayrıca services tablosunda da bak
        $stmt = $conn->prepare('SELECT slug FROM services WHERE id = ? AND status = 1 LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $rows = mysqli_stmt_fetch_all_assoc($stmt);
            $stmt->close();
            if (!empty($rows[0]['slug'])) {
                return (string) $rows[0]['slug'];
            }
        }
    }

    return null;
}

/**
 * Son seans: normalize + partial match → 301 veya null.
 */
function mynak_fc_try_broken_link_recovery(mysqli $conn, string $slug): bool
{
    // 1. WordPress query parametreleri (?p=ID, ?page_id=ID)
    $wpTarget = mynak_try_wp_query_param_redirect($conn);
    if ($wpTarget !== null) {
        $loc = mynak_abs_url_from_public_path(mynak_public_path($wpTarget));
        header('Location: ' . $loc, true, 301);
        exit;
    }

    // 2. Slug normalization (tire düzeltme, .html kaldırma, vb.)
    $normalized = mynak_normalize_broken_slug($slug);
    if ($normalized !== null && $normalized !== $slug) {
        // Normalize edilmiş slug DB'de var mı?
        $stmt = $conn->prepare(
            'SELECT slug FROM blog_posts WHERE slug = ? AND durum = 3 '
            . 'UNION SELECT slug FROM services WHERE slug = ? AND status = 1 '
            . 'UNION SELECT slug FROM pages WHERE slug = ? AND status = 1 LIMIT 1'
        );
        if ($stmt) {
            $stmt->bind_param('sss', $normalized, $normalized, $normalized);
            $stmt->execute();
            $rows = mysqli_stmt_fetch_all_assoc($stmt);
            $stmt->close();
            if (!empty($rows[0]['slug'])) {
                $loc = mynak_abs_url_from_public_path(mynak_public_path((string) $rows[0]['slug']));
                header('Location: ' . $loc, true, 301);
                exit;
            }
        }
    }

    // 3. Partial slug match (prefix araması)
    if (!str_contains($slug, '/')) {
        $partial = mynak_try_partial_slug_match($conn, $slug);
        if ($partial !== null) {
            $loc = mynak_abs_url_from_public_path(mynak_public_path($partial));
            header('Location: ' . $loc, true, 301);
            exit;
        }
    }

    return false;
}
