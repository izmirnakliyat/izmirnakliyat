<?php
declare(strict_types=1);

/**
 * Eski WordPress ?p= ve ?page_id= sorguları → kanonik path 301 (GSC 404 azaltma).
 * config + db yüklendikten sonra çağrılmalıdır.
 */
function mynak_public_try_wp_legacy_query_redirect(mysqli $conn): void
{
    if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
        return;
    }
    if (!defined('SITE_URL') || !function_exists('mynak_abs_url_from_public_path') || !function_exists('mynak_public_path')) {
        return;
    }

    $hasP = isset($_GET['p']) && is_string($_GET['p']) && ctype_digit($_GET['p']) && (int) $_GET['p'] > 0;
    $hasPageId = isset($_GET['page_id']) && is_string($_GET['page_id']) && ctype_digit($_GET['page_id']) && (int) $_GET['page_id'] > 0;
    if (!$hasP && !$hasPageId) {
        return;
    }

    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        $path = '/';
    }
    if (function_exists('mynak_normalize_leaked_windows_request_path')) {
        $path = mynak_normalize_leaked_windows_request_path($path);
    }
    $pathPrefix = function_exists('mynak_url_path_prefix') ? mynak_url_path_prefix() : '';
    if ($pathPrefix !== '' && (strpos($path, $pathPrefix . '/') === 0 || $path === $pathPrefix)) {
        $path = substr($path, strlen($pathPrefix)) ?: '/';
    }
    $norm = '/' . trim((string) $path, '/');
    if ($norm === '//') {
        $norm = '/';
    }
    $bn = strtolower(basename($norm));
    $allowedEntry = $norm === '/' || $bn === '' || $bn === 'index.php' || $bn === 'blog.php' || $bn === 'blog-detay.php' || $bn === 'blog-detay';
    if (!$allowedEntry) {
        return;
    }

    if (!function_exists('mysqli_stmt_fetch_all_assoc')) {
        return;
    }

    $traceFrom = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
        . '://' . ($_SERVER['HTTP_HOST'] ?? '') . $uri;

    if ($hasP) {
        $id = (int) $_GET['p'];
        $stmt = $conn->prepare('SELECT slug FROM blog_posts WHERE id = ? AND durum = 3 LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $rows = mysqli_stmt_fetch_all_assoc($stmt);
            $stmt->close();
            if (!empty($rows[0]['slug'])) {
                $loc = mynak_abs_url_from_public_path(mynak_public_path((string) $rows[0]['slug']));
                if (function_exists('seo_runtime_trace_record_redirect')) {
                    seo_runtime_trace_record_redirect($traceFrom, $loc, 301);
                }
                header('Location: ' . $loc, true, 301);
                exit;
            }
        }
        $stmt2 = $conn->prepare('SELECT slug FROM pages WHERE id = ? AND status = 1 LIMIT 1');
        if ($stmt2) {
            $stmt2->bind_param('i', $id);
            $stmt2->execute();
            $rows2 = mysqli_stmt_fetch_all_assoc($stmt2);
            $stmt2->close();
            if (!empty($rows2[0]['slug'])) {
                $loc = mynak_abs_url_from_public_path(mynak_public_path((string) $rows2[0]['slug']));
                if (function_exists('seo_runtime_trace_record_redirect')) {
                    seo_runtime_trace_record_redirect($traceFrom, $loc, 301);
                }
                header('Location: ' . $loc, true, 301);
                exit;
            }
        }
        $stmt3 = $conn->prepare('SELECT slug FROM services WHERE id = ? AND status = 1 LIMIT 1');
        if ($stmt3) {
            $stmt3->bind_param('i', $id);
            $stmt3->execute();
            $rows3 = mysqli_stmt_fetch_all_assoc($stmt3);
            $stmt3->close();
            if (!empty($rows3[0]['slug'])) {
                $loc = mynak_abs_url_from_public_path(mynak_public_path((string) $rows3[0]['slug']));
                if (function_exists('seo_runtime_trace_record_redirect')) {
                    seo_runtime_trace_record_redirect($traceFrom, $loc, 301);
                }
                header('Location: ' . $loc, true, 301);
                exit;
            }
        }
    }

    if ($hasPageId) {
        $pid = (int) $_GET['page_id'];
        $stmt = $conn->prepare('SELECT slug FROM pages WHERE id = ? AND status = 1 LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $pid);
            $stmt->execute();
            $rows = mysqli_stmt_fetch_all_assoc($stmt);
            $stmt->close();
            if (!empty($rows[0]['slug'])) {
                $loc = mynak_abs_url_from_public_path(mynak_public_path((string) $rows[0]['slug']));
                if (function_exists('seo_runtime_trace_record_redirect')) {
                    seo_runtime_trace_record_redirect($traceFrom, $loc, 301);
                }
                header('Location: ' . $loc, true, 301);
                exit;
            }
        }
    }
}

/**
 * Eski /blog-detay.php?id=N veya ?slug=X istegi → kanonik slug 301.
 * Bu, GSC "Tarandı - şu anda dizine eklenmiş değil" raporundaki yüzlerce blog-detay.php?id= URL'ini
 * /blog'a değil, gerçek slug'a yönlendirir — Google iki URL'i kanonik bir slug altında birleştirir.
 */
function mynak_public_try_blog_detay_legacy_redirect(mysqli $conn): void
{
    if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
        return;
    }
    if (!defined('SITE_URL') || !function_exists('mynak_abs_url_from_public_path') || !function_exists('mynak_public_path')) {
        return;
    }
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        return;
    }
    if (function_exists('mynak_normalize_leaked_windows_request_path')) {
        $path = mynak_normalize_leaked_windows_request_path($path);
    }
    $pathPrefix = function_exists('mynak_url_path_prefix') ? mynak_url_path_prefix() : '';
    if ($pathPrefix !== '' && (strpos($path, $pathPrefix . '/') === 0 || $path === $pathPrefix)) {
        $path = substr($path, strlen($pathPrefix)) ?: '/';
    }
    $norm = '/' . trim((string) $path, '/');
    $bn = strtolower(basename($norm));
    if ($bn !== 'blog-detay.php' && $bn !== 'blog-detay') {
        return;
    }

    if (!function_exists('mysqli_stmt_fetch_all_assoc')) {
        return;
    }

    $homeBlog = mynak_abs_url_from_public_path(mynak_public_path('blog'));

    if (isset($_GET['id']) && is_string($_GET['id']) && ctype_digit($_GET['id']) && (int) $_GET['id'] > 0) {
        $id = (int) $_GET['id'];
        $stmt = $conn->prepare('SELECT slug FROM blog_posts WHERE id = ? AND durum = 3 LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $rows = mysqli_stmt_fetch_all_assoc($stmt);
                if (!empty($rows[0]['slug'])) {
                    $stmt->close();
                    header('Location: ' . mynak_abs_url_from_public_path(mynak_public_path((string) $rows[0]['slug'])), true, 301);
                    exit;
                }
            }
            $stmt->close();
        }
        // ID yok/silinmiş → /blog'a 301
        header('Location: ' . $homeBlog, true, 301);
        exit;
    }

    if (isset($_GET['slug']) && is_string($_GET['slug']) && $_GET['slug'] !== '') {
        $slug = (string) $_GET['slug'];
        $stmt = $conn->prepare('SELECT slug FROM blog_posts WHERE slug = ? AND durum = 3 LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $slug);
            if ($stmt->execute()) {
                $rows = mysqli_stmt_fetch_all_assoc($stmt);
                if (!empty($rows[0]['slug'])) {
                    $stmt->close();
                    header('Location: ' . mynak_abs_url_from_public_path(mynak_public_path((string) $rows[0]['slug'])), true, 301);
                    exit;
                }
            }
            $stmt->close();
        }
        header('Location: ' . $homeBlog, true, 301);
        exit;
    }

    // blog-detay.php çıplak → /blog
    header('Location: ' . $homeBlog, true, 301);
    exit;
}
