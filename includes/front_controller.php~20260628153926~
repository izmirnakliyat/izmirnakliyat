<?php
declare(strict_types=1);

require_once __DIR__ . '/front_controller_slug.php';

/**
 * İstek URI veya ?route= → slash-normalize slug (ana sayfa = boş).
 */
function mynak_fc_normalize_slug_from_request(): string
{
    if (isset($_GET['route']) && is_string($_GET['route']) && $_GET['route'] !== '') {
        $r = trim($_GET['route'], '/');
        if (str_ends_with(strtolower($r), '.php')) {
            $r = substr($r, 0, -4);
        }
        return $r;
    }
    if (!function_exists('seo_runtime_compute_rel_path_from_request_uri')) {
        return '';
    }
    $rel = seo_runtime_compute_rel_path_from_request_uri($_SERVER['REQUEST_URI'] ?? '/');
    $rel = ltrim((string) $rel, '/');
    if ($rel === '' || strcasecmp($rel, 'index.php') === 0) {
        return '';
    }
    if (preg_match('#^(.+/)index\.php$#i', $rel, $m)) {
        return rtrim($m[1], '/');
    }
    if (str_ends_with(strtolower($rel), '.php')) {
        $rel = substr($rel, 0, -4);
        $rel = rtrim($rel, '/');
    }
    return $rel;
}

/**
 * @return true çıktı tamamlandı (çağıran exit etmeli)
 */
function mynak_fc_dispatch_special_endpoints(string $slug, mysqli $conn): bool
{
    $key = strtolower($slug);
    $handlers = __DIR__ . '/handlers/public_special_handlers.php';

    // Public read-only JSON API: /api/v1/*
    if (strpos($key, 'api/v1') === 0) {
        require_once __DIR__ . '/handlers/api_v1.php';
        return mynak_api_dispatch($conn, $key);
    }

    switch ($key) {
        case 'robots.txt':
            require_once $handlers;
            mynak_handler_robots_txt();
            return true;
        case 'sitemap-index.xml':
            require_once $handlers;
            mynak_handler_sitemap_index();
            return true;
        case 'sitemap.xml':
            require_once $handlers;
            mynak_handler_sitemap_main_xml($conn);
            return true;
        case 'image-sitemap.xml':
            require_once $handlers;
            mynak_handler_image_sitemap_xml();
            return true;
        case 'video-sitemap.xml':
            require_once $handlers;
            mynak_handler_video_sitemap_xml();
            return true;
        case 'llms.txt':
            if (!headers_sent()) {
                header('X-Robots-Tag: noindex, nofollow', true);
            }
            if (!defined('SITE_URL')) {
                require_once dirname(__DIR__) . '/config/site_url_define.php';
            }
            require_once dirname(__DIR__) . '/llms.php';
            return true;
        case 'llms-corpus.txt':
            if (!headers_sent()) {
                header('X-Robots-Tag: noindex, nofollow', true);
            }
            $_GET['full'] = '1';
            if (!defined('SITE_URL')) {
                require_once dirname(__DIR__) . '/config/site_url_define.php';
            }
            require_once dirname(__DIR__) . '/llms.php';
            return true;
        case 'llms-full-tr.txt':
            if (!headers_sent()) {
                header('X-Robots-Tag: noindex, nofollow', true);
            }
            require_once $handlers;
            mynak_handler_llms_full_tr();
            return true;
        case 'audit_env':
            require_once $handlers;
            mynak_handler_audit_php_env_json();
            return true;
        default:
            return false;
    }
}

/**
 * Tek segment legacy PHP sayfaları (pages tablosu dışı).
 * require bu fonksiyonun yerel $conn parametresini görünür kılar.
 */
function mynak_fc_try_legacy_page_include(mysqli $conn, string $slug): bool
{
    if ($slug === '' || str_contains($slug, '/')) {
        return false;
    }
    $map = [
        'iletisim' => 'iletisim.php',
        'galeri' => 'galeri.php',
        'teklif-alin' => 'teklif-alin.php',
        'blog' => 'blog.php',
        'tasinma-kontrol-listesi' => 'tasinma-kontrol-listesi.php',
        'tasinma-kontrol-listesi-icerik' => 'tasinma-kontrol-listesi-icerik.php',
        'rehber-google-isletme-gonderileri' => 'rehber-google-isletme-gonderileri.php',
        'fiyat' => 'fiyat.php',
    ];
    if (!isset($map[$slug])) {
        return false;
    }
    $file = dirname(__DIR__) . '/' . $map[$slug];
    if (!is_readable($file)) {
        return false;
    }
    require $file;
    return true;
}

/**
 * Ana sayfa değilse yanıt üretir ve çıkmalıdır.
 */
function mynak_public_front_controller_maybe_dispatch(mysqli $conn): void
{
    $slug = mynak_fc_normalize_slug_from_request();
    if ($slug === '') {
        return;
    }
    if (mynak_fc_dispatch_special_endpoints($slug, $conn)) {
        exit;
    }
    if (mynak_fc_try_legacy_page_include($conn, $slug)) {
        exit;
    }
    mynak_fc_dispatch_slug($slug, $conn);
}
