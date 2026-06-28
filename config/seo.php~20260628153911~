<?php
/**
 * Tek kaynak SEO / varlık URL'leri (Semrush, GSC, Ahrefs ile uyumlu mutlak yollar).
 * config.php içinde SITE_URL tanmlandıktan sonra yüklenir.
 */
declare(strict_types=1);

if (!defined('SITE_URL')) {
    return;
}

$__seo_origin = rtrim((string) SITE_URL, '/');

if (!defined('SEO_ORIGIN')) {
    define('SEO_ORIGIN', $__seo_origin);
}
if (!defined('ASSET_PATH')) {
    define('ASSET_PATH', $__seo_origin . '/assets/');
}
if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', $__seo_origin . '/uploads/');
}
if (!defined('PUBLIC_JS_PATH')) {
    define('PUBLIC_JS_PATH', $__seo_origin . '/js/');
}

/**
 * SITE_URL içindeki path öneki (örn. XAMPP: /mynakliyat). Canlı kökte boş.
 */
function mynak_url_path_prefix(): string
{
    $raw = parse_url((string) SITE_URL, PHP_URL_PATH);
    $out = '';
    if (function_exists('mynak_sanitize_parsed_url_path')) {
        $out = mynak_sanitize_parsed_url_path(is_string($raw) ? $raw : null);
    } elseif (is_string($raw) && $raw !== '' && $raw !== '/') {
        $out = rtrim(str_replace('\\', '/', $raw), '/');
    }
    $check = str_replace('\\', '/', rawurldecode($out));
    if ($check !== '' && function_exists('mynak_infer_web_path_prefix_from_filesystem')) {
        $cl = strtolower($check);
        if (preg_match('#^/[a-zA-Z](?:\:|%3A)(/|$)#', $check) === 1
            || str_contains($cl, 'xampp')
            || str_contains($cl, '/htdocs/')
            || str_contains($cl, '/www/')) {
            return rtrim(mynak_infer_web_path_prefix_from_filesystem(), '/');
        }
    }
    $prefCheck = $out === '' ? '' : ($out[0] === '/' ? $out : '/' . $out);
    if ($prefCheck !== '' && function_exists('mynak_path_has_windows_filesystem_leak')
        && mynak_path_has_windows_filesystem_leak($prefCheck)
        && function_exists('mynak_infer_web_path_prefix_from_filesystem')) {
        return rtrim(mynak_infer_web_path_prefix_from_filesystem(), '/');
    }

    return $out;
}

/**
 * Tarayıcıda kullanılacak kök-relative path: canlı /slug, yerel /mynakliyat/slug.
 */
function mynak_public_path(string $path = ''): string
{
    $path = trim(str_replace('\\', '/', $path), '/');
    $base = mynak_url_path_prefix();
    $baseCheck = str_replace('\\', '/', $base);
    if ($baseCheck !== '' && preg_match('#^/[a-zA-Z]:(/|$)#', $baseCheck) === 1 && function_exists('mynak_infer_web_path_prefix_from_filesystem')) {
        $base = rtrim(mynak_infer_web_path_prefix_from_filesystem(), '/');
    }
    $baseTrim = trim(str_replace('\\', '/', $base), '/');
    if ($baseTrim !== '' && $path !== '') {
        for ($i = 0; $i < 16; $i++) {
            if (str_starts_with($path, $baseTrim . '/')) {
                $path = substr($path, strlen($baseTrim) + 1);
                continue;
            }
            if ($path === $baseTrim) {
                $path = '';
                break;
            }
            break;
        }
    }
    if ($path === '') {
        $out = $base === '' ? '/' : $base . '/';
    } else {
        $out = ($base === '' ? '' : $base) . '/' . $path;
    }
    $verify = str_replace('\\', '/', rawurldecode($out));
    if ($verify !== '' && function_exists('mynak_infer_web_path_prefix_from_filesystem')) {
        $vl = strtolower($verify);
        if (preg_match('#/[a-zA-Z](?:\:|%3A)(/|$)#', $verify) === 1
            || str_contains($vl, 'xampp')
            || str_contains($vl, '/htdocs/')
            || str_contains($vl, '/www/')) {
            $cleanBase = rtrim(mynak_infer_web_path_prefix_from_filesystem(), '/');
            if ($path === '') {
                return $cleanBase === '' ? '/' : $cleanBase . '/';
            }

            return ($cleanBase === '' ? '' : $cleanBase) . '/' . $path;
        }
    }

    return $out;
}

/**
 * Geçerli istek: şema + host (path yok). Location ve tam URL birleştirme — SITE_URL içindeki hatalı path kullanılmaz.
 */
function mynak_http_request_origin(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    $host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : 'localhost';

    return ($https ? 'https' : 'http') . '://' . $host;
}

/**
 * localhost menü href: /C:/xampp/… veya içinde xampp/htdocs geçen path’leri web yoluna çevirir.
 */
function mynak_scrub_leak_from_href(string $href): string
{
    if ($href === '' || !function_exists('mynak_should_scrub_windows_path_hrefs') || !mynak_should_scrub_windows_path_hrefs()) {
        return $href;
    }
    $trim = trim($href);
    $isHttp = (bool) preg_match('#^https?://#i', $trim);
    $path = $trim;
    $qsfrag = '';
    if ($isHttp) {
        $p = parse_url($trim);
        if (!is_array($p) || empty($p['host'])) {
            return $href;
        }
        $h = strtolower(preg_replace('/:\d+$/', '', (string) $p['host']));
        $req = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
        if ($h !== $req) {
            return $href;
        }
        $path = isset($p['path']) ? (string) $p['path'] : '/';
        $qsfrag = (isset($p['query']) && $p['query'] !== '') ? '?' . $p['query'] : '';
        $qsfrag .= isset($p['fragment']) && $p['fragment'] !== '' ? '#' . $p['fragment'] : '';
    }
    $dec = str_replace('\\', '/', rawurldecode($path));
    if (!function_exists('mynak_path_has_windows_filesystem_leak') || !mynak_path_has_windows_filesystem_leak($dec)) {
        return $href;
    }
    $fixed = function_exists('mynak_fix_uri_path_windows_leak') ? mynak_fix_uri_path_windows_leak($dec) : $dec;
    $fixed = ($fixed === '' || $fixed === '/') ? '/' : ($fixed[0] === '/' ? $fixed : '/' . $fixed);
    if ($isHttp && function_exists('mynak_http_request_origin')) {
        return rtrim(mynak_http_request_origin(), '/') . $fixed . $qsfrag;
    }
    if (function_exists('mynak_web_url_from_site_path')) {
        return mynak_web_url_from_site_path($fixed . $qsfrag);
    }

    return $fixed . $qsfrag;
}

/**
 * @param string $publicPath mynak_public_path çıktısı; / ile başlamalı
 */
function mynak_abs_url_from_public_path(string $publicPath): string
{
    if ($publicPath === '') {
        $publicPath = '/';
    }
    $publicPath = str_replace('\\', '/', $publicPath);
    if ($publicPath === '' || $publicPath[0] !== '/') {
        $publicPath = '/' . ltrim($publicPath, '/');
    }

    return rtrim(mynak_http_request_origin(), '/') . $publicPath;
}

/**
 * Blog bölümü kök-relative path. $suffix örn. "", "kategori/foo", "sayfa/2", "kategori/foo/sayfa/2".
 * GSC tutarlılığı: trailing slash YOK (.htaccess tüm trailing slash'ı 301 kaldırır).
 */
function mynak_blog_href_path(string $suffix = ''): string
{
    $suffix = trim(str_replace('\\', '/', $suffix), '/');
    $inner = $suffix === '' ? 'blog' : 'blog/' . $suffix;
    $p = mynak_public_path($inner);
    if ($p === '/' || $p === '') {
        return $p;
    }
    return rtrim($p, '/');
}

/**
 * normalize_internal_link_url sonrası href hâlâ Windows dosya yolu içeriyor mu (tam URL veya path).
 */
function mynak_href_still_has_filesystem_leak(string $href): bool
{
    $s = trim($href);
    if ($s === '' || $s[0] === '#') {
        return false;
    }
    if (preg_match('#^(mailto:|tel:|javascript:)#i', $s)) {
        return false;
    }
    if (preg_match('#^https?://#i', $s)) {
        $path = parse_url($s, PHP_URL_PATH);
        $s = is_string($path) ? $path : '/';
    }
    $d = str_replace(['\\', '%5C'], '/', rawurldecode($s));

    return function_exists('mynak_path_has_windows_filesystem_leak')
        && mynak_path_has_windows_filesystem_leak($d);
}

/**
 * Menü / buton / mobil alt menü URL’leri (DB veya JSON önbellek): Windows dosya yolu sızıntısını kırpar.
 */
function mynak_sanitize_nav_url_string(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }
    if (preg_match('#^file:#i', $raw)) {
        $p = (string) (parse_url($raw, PHP_URL_PATH) ?? '');
        $p = str_replace('\\', '/', rawurldecode($p));
        if (preg_match('#^/[a-zA-Z]:$#', $p) === 1) {
            $p .= '/';
        }
        if ($p !== '' && preg_match('#^/[a-zA-Z]:(/|$)#', $p) === 1 && function_exists('mynak_normalize_leaked_windows_request_path')) {
            $p = mynak_normalize_leaked_windows_request_path($p);
        }
        if (function_exists('mynak_web_url_from_site_path')) {
            return mynak_web_url_from_site_path($p === '' ? '/' : $p);
        }

        return '/';
    }
    if (preg_match('#^https?://#i', $raw)) {
        $parts = parse_url($raw);
        if (is_array($parts) && !empty($parts['path']) && function_exists('mynak_fix_uri_path_windows_leak')) {
            $pr = str_replace('\\', '/', rawurldecode((string) $parts['path']));
            if (preg_match('#^/[a-zA-Z]:$#', $pr) === 1) {
                $pr .= '/';
            }
            $prFixed = mynak_fix_uri_path_windows_leak($pr);
            if ($prFixed !== $pr) {
                $raw = ($parts['scheme'] ?? 'http') . '://' . ($parts['host'] ?? '')
                    . (isset($parts['port']) ? ':' . (int) $parts['port'] : '')
                    . ($prFixed === '/' || $prFixed === '' ? '' : $prFixed)
                    . (isset($parts['query']) ? '?' . $parts['query'] : '')
                    . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
            }
        }
    } elseif (preg_match('#^[a-zA-Z]:[/\\\\]#', $raw)) {
        $p = '/' . str_replace('\\', '/', $raw);
        if (function_exists('mynak_normalize_leaked_windows_request_path')) {
            $p = mynak_normalize_leaked_windows_request_path($p);
        }
        $raw = $p;
    }

    if (function_exists('normalize_internal_link_url')) {
        return normalize_internal_link_url($raw);
    }

    return $raw;
}

/**
 * SITE_URL kökteyken (path yok) menü/DB’de kalan /mynakliyat/... (XAMPP) önekini kırpar.
 * Gerçek alt klasör kurulumu: SITE_URL’de /mynakliyat varken dokunmaz.
 */
function mynak_strip_stale_mynakliyat_path_if_root_site(string $path): string
{
    if (!defined('SITE_URL') || !function_exists('mynak_sanitize_parsed_url_path')) {
        return $path;
    }
    $raw = parse_url((string) SITE_URL, PHP_URL_PATH);
    $sp = mynak_sanitize_parsed_url_path(is_string($raw) ? $raw : null);
    if ($sp !== '' && $sp !== '/') {
        return $path;
    }
    for ($i = 0; $i < 6; $i++) {
        if (str_starts_with($path, '/mynakliyat/')) {
            $path = substr($path, strlen('/mynakliyat'));
            if ($path === '' || $path[0] !== '/') {
                $path = '/' . ltrim($path, '/');
            }
            continue;
        }
        if ($path === '/mynakliyat') {
            return '/';
        }
        break;
    }

    return $path;
}

/**
 * İstek veya tam URL path'inden SITE_URL önekini kırparak site içi path (/blog, /hakkimizda).
 * Aynı önek birden fazla kez yazılmışsa (örn. /mynakliyat/mynakliyat/blog) hepsini kırpar.
 */
function mynak_site_path_from_request_path(string $path): string
{
    $path = '/' . trim(str_replace('\\', '/', $path), '/');
    $prefix = trim(str_replace('\\', '/', mynak_url_path_prefix()), '/');
    if ($prefix === '') {
        $path = mynak_strip_stale_mynakliyat_path_if_root_site($path);
        if ($path !== '/' && $path !== '') {
            $path = '/' . ltrim($path, '/');
        }

        return $path === '' ? '/' : $path;
    }
    $p = '/' . $prefix;
    for ($i = 0; $i < 16; $i++) {
        if (strpos($path, $p . '/') === 0) {
            $path = substr($path, strlen($p));
            $path = $path === '' ? '/' : $path;
            continue;
        }
        if ($path === $p) {
            $path = '/';
            break;
        }
        break;
    }
    if ($path !== '/' && $path !== '') {
        $path = '/' . ltrim($path, '/');
    }

    return $path === '' ? '/' : $path;
}

/**
 * Kök-relative path (?# dahil) veya http(s) URL → href için path (localhost alt klasör uyumlu).
 */
function mynak_web_url_from_site_path(string $pathOrUrl): string
{
    $pathOrUrl = trim($pathOrUrl);
    if ($pathOrUrl === '') {
        return mynak_public_path('');
    }
    if (preg_match('#^https?://#i', $pathOrUrl)) {
        $parts = parse_url($pathOrUrl);
        if (is_array($parts) && !empty($parts['path'])) {
            $h = strtolower(preg_replace('/:\d+$/', '', (string) ($parts['host'] ?? '')));
            $req = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
            if ($h !== '' && $h === $req && function_exists('mynak_site_path_from_request_path')) {
                $fixed = mynak_site_path_from_request_path((string) $parts['path']);
                $t = trim($fixed, '/');
                $qs = isset($parts['query']) ? '?' . $parts['query'] : '';
                $frag = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

                return ($t === '' ? mynak_public_path('') : mynak_public_path($t)) . $qs . $frag;
            }
        }

        return $pathOrUrl;
    }
    $frag = '';
    $u = $pathOrUrl;
    if (strpos($u, '#') !== false) {
        $p = explode('#', $u, 2);
        $u = $p[0];
        $frag = '#' . $p[1];
    }
    $qs = '';
    if (strpos($u, '?') !== false) {
        $p = explode('?', $u, 2);
        $u = $p[0];
        $qs = '?' . $p[1];
    }
    $pathOnly = $u;
    if ($pathOnly === '') {
        return mynak_public_path('') . $qs . $frag;
    }
    if ($pathOnly[0] !== '/') {
        $pathOnly = '/' . $pathOnly;
    }
    if (function_exists('mynak_site_path_from_request_path')) {
        $pathOnly = mynak_site_path_from_request_path($pathOnly);
    }
    $t = trim($pathOnly, '/');
    return ($t === '' ? mynak_public_path('') : mynak_public_path($t)) . $qs . $frag;
}

/**
 * @param string $path örn. "css/main.min.css" veya "/css/main.min.css"
 */
function seo_asset_url(string $path): string
{
    return ASSET_PATH . ltrim($path, '/');
}

/**
 * @param string $path örn. "settings/logo.png"
 */
function seo_upload_url(string $path): string
{
    return UPLOAD_PATH . ltrim($path, '/');
}

/**
 * og:image fallback. Aday asset/upload yollarını filesystem'de doğrular;
 * hiçbiri yoksa boş döner (header.php boş meta'yı zaten basmaz).
 */
function seo_default_placeholder_image_url(): string
{
    if (!defined('PROJECT_ROOT')) {
        return '';
    }
    $candidates = [
        ['assets', 'images/default.webp'],
        ['assets', 'images/default.png'],
        ['assets', 'images/default.jpg'],
        ['assets', 'images/og-default.webp'],
        ['assets', 'images/og-default.png'],
        ['assets', 'img/og-default.png'],
        ['assets', 'img/og-default.webp'],
        ['assets', 'img/og-default.jpg'],
        ['assets', 'img/logo.png'],
        ['assets', 'img/logo.svg'],
        ['assets', 'img/truck.svg'],
        ['uploads', 'settings/og-default.png'],
        ['uploads', 'settings/og-default.webp'],
        ['uploads', 'settings/og-default.jpg'],
    ];
    foreach ($candidates as [$root, $rel]) {
        $fs = PROJECT_ROOT . DIRECTORY_SEPARATOR . $root . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        if (is_file($fs)) {
            return $root === 'assets' ? seo_asset_url($rel) : seo_upload_url($rel);
        }
    }
    return '';
}

function seo_upload_file_exists(string $relativeUnderUploads): bool
{
    if (!defined('PROJECT_ROOT')) {
        return false;
    }
    $relativeUnderUploads = ltrim(str_replace('\\', '/', $relativeUnderUploads), '/');
    $fs = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $relativeUnderUploads);

    return is_file($fs);
}

/**
 * JSON-LD dizisinin geçerli JSON ürettiğini doğrular (yayına çıkmadan).
 *
 * @param array<string,mixed> $data
 */
function seo_json_ld_encode(array $data): string
{
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return '{}';
    }
    json_decode($json);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return '{}';
    }

    return $json;
}
