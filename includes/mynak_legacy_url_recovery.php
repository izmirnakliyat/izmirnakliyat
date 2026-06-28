<?php
declare(strict_types=1);

/**
 * 404 öncesi: eski WP benzeri sonekler ve hafif slug düzeltme (GSC ölü URL azaltma).
 * Agresif yönlendirme yapmaz: belirsiz eşleşmede false döner.
 */

/**
 * İstek şeması — reverse proxy arkasında HTTPS ile seo_runtime / .htaccess ile uyumlu.
 */
function mynak_request_scheme_for_trace(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

    return $https ? 'https' : 'http';
}

/**
 * @return true yönlendirme yapıldı (exit edilir)
 */
function mynak_fc_try_wp_appendage_redirect(mysqli $conn, string $slug): bool
{
    if (!preg_match('#^([^/]+)/(blog\.php|index\.php|feed)$#i', $slug, $m)) {
        return false;
    }
    $base = $m[1];
    $stmt = $conn->prepare('SELECT slug FROM blog_posts WHERE slug = ? AND durum = 3 LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $base);
    $stmt->execute();
    $rows = mysqli_stmt_fetch_all_assoc($stmt);
    $stmt->close();
    if (empty($rows)) {
        return mynak_fc_try_fuzzy_blog_slug_redirect($conn, $base, true);
    }
    $loc = mynak_abs_url_from_public_path(mynak_public_path((string) $rows[0]['slug']));
    if (function_exists('seo_runtime_trace_record_redirect')) {
        seo_runtime_trace_record_redirect(
            mynak_request_scheme_for_trace() . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? ''),
            $loc,
            301
        );
    }
    header('Location: ' . $loc, true, 301);
    exit;
}

/**
 * Hatalı slug için aday küme: slug ön eki ile DB tarafında sınırlı satır (bellek dostu).
 *
 * @return list<string>
 */
function mynak_fc_blog_slug_candidates_by_prefix(mysqli $conn, string $slug): array
{
    $slug = trim($slug);
    if ($slug === '') {
        return [];
    }
    $ascii = strtolower(preg_replace('/[^a-z0-9\-]+/i', '', $slug));
    if ($ascii === '') {
        $ascii = strtolower(substr($slug, 0, 8));
    }
    $prefix = substr($ascii, 0, 12);
    if ($prefix === '') {
        return [];
    }
    $like = $prefix . '%';
    $stmt = $conn->prepare('SELECT slug FROM blog_posts WHERE durum = 3 AND slug LIKE ? LIMIT 20');
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $rows = mysqli_stmt_fetch_all_assoc($stmt);
    $stmt->close();
    $out = [];
    foreach ($rows as $row) {
        $s = (string) ($row['slug'] ?? '');
        if ($s !== '') {
            $out[] = $s;
        }
    }

    return $out;
}

/**
 * Tek segment; tam eşleşme yoksa önek (tek aday) veya yüksek benzerlik ile 301.
 *
 * @param bool $fromAppendage üst segmentten çağrı (daha kısa slug’a izin)
 */
function mynak_fc_try_fuzzy_blog_slug_redirect(mysqli $conn, string $slug, bool $fromAppendage = false): bool
{
    $slug = trim($slug);
    if ($slug === '' || str_contains($slug, '/')) {
        return false;
    }

    $lower = strtolower($slug);
    $reserved = [
        'blog', 'iletisim', 'galeri', 'teklif-alin', 'teklif-al', 'haberler', 'haberx',
        'robots.txt', 'sitemap.xml', 'sitemap-index.xml', 'image-sitemap.xml', 'video-sitemap.xml',
        'admin', 'ajax', 'assets', 'uploads', 'config', 'includes', 'cache', 'logs', 'scripts', 'cron',
        'wp-content', 'wp-includes', 'tag', 'etiket', 'hizmet', 'llms.txt', 'llms-corpus.txt', 'llms-full-tr.txt',
    ];
    if (in_array($lower, $reserved, true)) {
        return false;
    }

    $minLen = $fromAppendage ? 16 : 24;
    if (strlen($slug) < $minLen) {
        return false;
    }

    $slugList = mynak_fc_blog_slug_candidates_by_prefix($conn, $slug);
    if ($slugList === []) {
        return false;
    }

    $needle = strtolower($slug);
    $prefixMatches = [];
    foreach ($slugList as $cand) {
        $c = strtolower($cand);
        if ($c === $needle) {
            return false;
        }
        if (strlen($needle) >= 32 && str_starts_with($c, $needle) && strlen($c) > strlen($needle)) {
            $prefixMatches[] = $cand;
        }
    }
    if (count($prefixMatches) === 1) {
        $target = $prefixMatches[0];
        $loc = mynak_abs_url_from_public_path(mynak_public_path($target));
        if (function_exists('seo_runtime_trace_record_redirect')) {
            seo_runtime_trace_record_redirect(
                (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? ''),
                $loc,
                301
            );
        }
        header('Location: ' . $loc, true, 301);
        exit;
    }

    $best = '';
    $bestPct = 0.0;
    $secondPct = 0.0;
    foreach ($slugList as $cand) {
        $c = strtolower($cand);
        $pct = 0.0;
        similar_text($needle, $c, $pct);
        if ($pct > $bestPct) {
            $secondPct = $bestPct;
            $bestPct = $pct;
            $best = $cand;
        } elseif ($pct > $secondPct) {
            $secondPct = $pct;
        }
    }

    $threshold = strlen($needle) >= 40 ? 91.0 : 93.0;
    if ($best !== '' && $bestPct >= $threshold && ($bestPct - $secondPct) >= 2.5 && strcasecmp($best, $slug) !== 0) {
        $loc = mynak_abs_url_from_public_path(mynak_public_path($best));
        if (function_exists('seo_runtime_trace_record_redirect')) {
            seo_runtime_trace_record_redirect(
                mynak_request_scheme_for_trace() . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? ''),
                $loc,
                301
            );
        }
        header('Location: ' . $loc, true, 301);
        exit;
    }

    return false;
}
