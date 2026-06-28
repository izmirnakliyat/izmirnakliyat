<?php
declare(strict_types=1);
/** Auto-split from seo_runtime.php — Phase 3 modular SEO runtime. */

/**
 * Yalnızca ?amp= olan sorguları kanonik path’e 301 (eski seo_request_normalize mantığı).
 */
function seo_runtime_strip_amp_query_redirect(): void
{
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    if ($qs === '') {
        return;
    }
    parse_str($qs, $qarr);
    if (!is_array($qarr) || $qarr === []) {
        return;
    }

    // Tüm passive parametreleri çıkar; sayfa-anlamlı parametreler kalırsa redirect yapma.
    $passive = function_exists('seo_rt_passive_tracking_params')
        ? array_map('strtolower', seo_rt_passive_tracking_params())
        : ['amp'];

    $hadPassive = false;
    $filtered = [];
    foreach ($qarr as $k => $v) {
        if (in_array(strtolower((string) $k), $passive, true)) {
            $hadPassive = true;
            continue;
        }
        $filtered[$k] = $v;
    }
    if (!$hadPassive) {
        return;
    }

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (function_exists('mynak_normalize_leaked_windows_request_path')) {
        $path = mynak_normalize_leaked_windows_request_path($path);
    }
    $pathPrefix = function_exists('mynak_url_path_prefix') ? mynak_url_path_prefix() : '';
    if ($pathPrefix !== '' && (strpos($path, $pathPrefix . '/') === 0 || $path === $pathPrefix)) {
        $path = substr($path, strlen($pathPrefix)) ?: '/';
    }
    if ($path !== '/' && $path !== '') {
        $path = rtrim($path, '/') ?: '/';
    }

    $relPathOut = ($path === '/' || $path === '') ? mynak_public_path('') : mynak_public_path(ltrim($path, '/'));
    $newQs = $filtered !== [] ? http_build_query($filtered, '', '&', PHP_QUERY_RFC3986) : '';
    $target = mynak_abs_url_from_public_path($relPathOut) . ($newQs !== '' ? '?' . $newQs : '');

    seo_runtime_trace_record_redirect(seo_runtime_trace_request_url(), $target, 301);
    header('Location: ' . $target, true, 301);
    exit;
}

/**
 * Kök/index.php, iletisim.php vb. doğrudan script isteklerini sitemap ile uyumlu path’e 301.
 */
function seo_runtime_legacy_public_php_redirect(): void
{
    if (!defined('SITE_URL')) {
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
    $rel = $path;
    if ($pathPrefix !== '' && (strpos($path, $pathPrefix . '/') === 0 || $path === $pathPrefix)) {
        $rel = substr($path, strlen($pathPrefix)) ?: '/';
    }
    $rel = '/' . trim($rel, '/');
    if ($rel === '//') {
        $rel = '/';
    }

    $baseFile = strtolower(basename($rel));
    $map = [
        'index.php' => '/',
        'iletisim.php' => '/iletisim',
        'galeri.php' => '/galeri',
        'teklif-alin.php' => '/teklif-alin',
    ];
    if (!isset($map[$baseFile])) {
        return;
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return;
    }

    $tail = $map[$baseFile];
    $target = $tail === '/'
        ? mynak_abs_url_from_public_path(mynak_public_path(''))
        : mynak_abs_url_from_public_path(mynak_public_path(ltrim($tail, '/')));

    $queryStr = parse_url($uri, PHP_URL_QUERY);
    $queryStr = is_string($queryStr) ? $queryStr : '';
    if ($queryStr !== '') {
        $target .= '?' . $queryStr;
    }

    $curPath = parse_url($uri, PHP_URL_PATH);
    $curPath = is_string($curPath) ? $curPath : '/';
    $current = $scheme . '://' . $host . $curPath . ($queryStr !== '' ? '?' . $queryStr : '');

    if ($target === $current) {
        return;
    }

    seo_runtime_trace_record_redirect($current, $target, 301);
    header('Location: ' . $target, true, 301);
    exit;
}

/**
 * blog.php?tag= / ?page= → path tabanlı blog URL (kategori= DB ile blog.php başında 301).
 */
function seo_runtime_blog_php_query_redirect(): void
{
    if (!defined('SITE_URL')) {
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
    $rel = $path;
    if ($pathPrefix !== '' && (strpos($path, $pathPrefix . '/') === 0 || $path === $pathPrefix)) {
        $rel = substr($path, strlen($pathPrefix)) ?: '/';
    }
    $rel = '/' . trim($rel, '/');
    if (strcasecmp(basename($rel), 'blog.php') !== 0) {
        return;
    }

    $queryStr = parse_url($uri, PHP_URL_QUERY);
    $queryStr = is_string($queryStr) ? $queryStr : '';
    $q = [];
    if ($queryStr !== '') {
        parse_str($queryStr, $q);
    }
    if (!empty($q['kategori'])) {
        return;
    }

    if (!function_exists('slug_olustur')) {
        require_once dirname(__DIR__) . '/functions.php';
    }

    $page = isset($q['page']) ? max(1, (int) $q['page']) : 1;
    if (!empty($q['tag']) && is_string($q['tag'])) {
        $tagSlug = slug_olustur($q['tag']);
        $suf = 'etiket/' . rawurlencode($tagSlug);
        if ($page > 1) {
            $suf .= '/sayfa/' . $page;
        }
        $target = mynak_abs_url_from_public_path(mynak_blog_href_path($suf));
    } elseif ($page > 1) {
        $target = mynak_abs_url_from_public_path(mynak_blog_href_path('sayfa/' . $page));
    } else {
        $target = mynak_abs_url_from_public_path(mynak_blog_href_path(''));
    }

    $passthrough = $q;
    unset($passthrough['tag'], $passthrough['page'], $passthrough['kategori']);
    if ($passthrough !== []) {
        $target .= '?' . http_build_query($passthrough, '', '&', PHP_QUERY_RFC3986);
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return;
    }

    $curPath = parse_url($uri, PHP_URL_PATH);
    $curPath = is_string($curPath) ? $curPath : '/';
    $current = $scheme . '://' . $host . $curPath . ($queryStr !== '' ? '?' . $queryStr : '');

    if ($target === $current) {
        return;
    }

    seo_runtime_trace_record_redirect($current, $target, 301);
    header('Location: ' . $target, true, 301);
    exit;
}

/**
 * İstek path’i //C:/ veya /C:/xampp/… şeklindeyse tek seferde web path’e 301 (adres çubuğu ve MYNAK_BASE sızıntısı).
 */
function seo_runtime_windows_drive_uri_redirect(): void
{
    if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg' || !defined('SITE_URL')) {
        return;
    }
    if (!function_exists('mynak_normalize_leaked_windows_request_path')
        || !function_exists('mynak_preprocess_leaked_uri_path')
        || !function_exists('mynak_http_request_origin')) {
        return;
    }
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $pathIn = parse_url($uri, PHP_URL_PATH);
    if (!is_string($pathIn) || $pathIn === '') {
        return;
    }
    if (preg_match('#/(admin)(/|$)#i', $pathIn)) {
        return;
    }
    $pre = mynak_preprocess_leaked_uri_path($pathIn);
    if (preg_match('#^/[a-zA-Z]:(/|$)#', $pre) !== 1) {
        return;
    }
    $fixed = mynak_normalize_leaked_windows_request_path($pathIn);
    $preFixed = mynak_preprocess_leaked_uri_path($fixed);
    if (preg_match('#^/[a-zA-Z]:(/|$)#', $preFixed) === 1) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return;
    }
    $pathOut = $fixed === '' ? '/' : ($fixed[0] === '/' ? $fixed : '/' . $fixed);
    $qs = parse_url($uri, PHP_URL_QUERY);
    $qs = is_string($qs) ? $qs : '';
    $frag = parse_url($uri, PHP_URL_FRAGMENT);
    $frag = is_string($frag) && $frag !== '' ? '#' . $frag : '';
    $target = rtrim(mynak_http_request_origin(), '/') . $pathOut . ($qs !== '' ? '?' . $qs : '') . $frag;
    $current = $scheme . '://' . $host . $uri;
    if ($target === $current) {
        return;
    }
    seo_runtime_trace_record_redirect($current, $target, 301);
    header('Location: ' . $target, true, 301);
    exit;
}

/**
 * Erken 301: AMP, kök .php girişleri, blog.php sorgu birleştirme, ?id= / ?key= kaldırma.
 */
function seo_runtime_early_redirects(): void
{
    if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg' || !defined('SITE_URL')) {
        return;
    }

    seo_runtime_windows_drive_uri_redirect();

    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        $path = '/';
    }
    if (function_exists('mynak_normalize_leaked_windows_request_path')) {
        $path = mynak_normalize_leaked_windows_request_path($path);
    }

    if (preg_match('#/(admin)(/|$)#i', $path)) {
        return;
    }

    seo_runtime_strip_amp_query_redirect();
    seo_runtime_legacy_public_php_redirect();
    seo_runtime_blog_php_query_redirect();

    $queryStr = parse_url($uri, PHP_URL_QUERY);
    $queryStr = is_string($queryStr) ? $queryStr : '';
    if ($queryStr === '') {
        return;
    }

    parse_str($queryStr, $qarr);
    if (!isset($qarr['id']) && !isset($qarr['key'])) {
        return;
    }

    unset($qarr['id'], $qarr['key']);
    $newQs = http_build_query($qarr, '', '&', PHP_QUERY_RFC3986);

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return;
    }

    $target = $scheme . '://' . $host . $path . ($newQs !== '' ? '?' . $newQs : '');
    $current = $scheme . '://' . $host . $path . '?' . $queryStr;

    if ($target === $current) {
        return;
    }

    seo_runtime_trace_record_redirect($current, $target, 301);
    header('Location: ' . $target, true, 301);
    exit;
}
