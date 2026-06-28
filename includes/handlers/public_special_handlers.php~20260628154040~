<?php
declare(strict_types=1);

/**
 * Front controller özel uçları (robots, sitemap, llms-full-tr).
 * Çağıran betik bootstrap + config (+ db) yüklemiş olmalı.
 */
function mynak_handler_robots_txt(): void
{
    if (!defined('SITE_URL')) {
        require_once dirname(__DIR__, 2) . '/config/site_url_define.php';
    }
    $base = rtrim((string) SITE_URL, '/');
    $path = dirname(__DIR__, 2) . '/robots.txt';
    $body = is_readable($path) ? (string) file_get_contents($path) : "User-agent: *\nAllow: /\n";
    $body = preg_replace('/^Sitemap:\s*\S+\s*$/mi', '', $body) ?? $body;
    $body = preg_replace("/\n{3,}/", "\n\n", trim($body)) ?? trim($body);
    $body = rtrim($body) . "\n\nSitemap: {$base}/sitemap-index.xml\n";

    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: public, max-age=3600');
    echo $body;
}

function mynak_handler_sitemap_index(): void
{
    require_once dirname(__DIR__, 2) . '/includes/sitemap_build.php';
    $site = rtrim((string) SITE_URL, '/');
    if (!defined('PROJECT_ROOT')) {
        define('PROJECT_ROOT', dirname(__DIR__, 2));
    }
    $xml = sitemap_build_index_xml($site, PROJECT_ROOT);
    header('Content-Type: application/xml; charset=UTF-8');
    echo $xml;
}

function mynak_handler_sitemap_main_xml(mysqli $conn): void
{
    require_once dirname(__DIR__, 2) . '/includes/sitemap_build.php';
    header('Content-Type: application/xml; charset=UTF-8');
    $site = rtrim((string) SITE_URL, '/');
    $static = dirname(__DIR__, 2) . '/sitemap.xml';
    try {
        $built = sitemap_build_main_urlset($conn, $site, []);
        echo $built['xml'];
    } catch (Throwable $e) {
        error_log('mynak_handler_sitemap_main_xml: ' . $e->getMessage());
        if (is_readable($static) && (int) filesize($static) > 50) {
            readfile($static);
        } else {
            http_response_code(503);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Sitemap gecici olarak kullanilamiyor.';
        }
    }
}

function mynak_handler_image_sitemap_xml(): void
{
    $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'image-sitemap.xml';
    header('Content-Type: application/xml; charset=UTF-8');
    if (is_readable($path) && (int) filesize($path) > 100) {
        readfile($path);
        return;
    }
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
    echo '</urlset>' . "\n";
}

function mynak_handler_video_sitemap_xml(): void
{
    $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'video-sitemap.xml';
    header('Content-Type: application/xml; charset=UTF-8');
    if (is_readable($path) && (int) filesize($path) > 100) {
        readfile($path);
        return;
    }
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . "\n";
    echo '</urlset>' . "\n";
}

function mynak_handler_llms_full_tr(): void
{
    $path = dirname(__DIR__, 2) . '/llms-full-tr.txt';
    if (is_readable($path)) {
        header('Content-Type: text/plain; charset=UTF-8');
        echo "# NON-CANONICAL MIRROR — politika metni; canonical structured export: /llms.txt (llms.php).\n\n";
        readfile($path);
        return;
    }
    require dirname(__DIR__, 2) . '/llms-full-tr.php';
}

/**
 * Yerel denetim: HTTP SAPI PHP ortamı (yalnızca loopback + isteğe bağlı gizli anahtar).
 * URL: /audit_env veya /audit_env.php (front controller slug: audit_env)
 */
function mynak_handler_audit_php_env_json(): void
{
    $addr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    if ($addr !== '127.0.0.1' && $addr !== '::1') {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Forbidden';
        return;
    }
    $secret = getenv('MYNAK_AUDIT_SECRET');
    if (is_string($secret) && $secret !== '') {
        $h = (string) ($_SERVER['HTTP_X_MYNAK_AUDIT_SECRET'] ?? '');
        if (!hash_equals($secret, $h)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Forbidden';
            return;
        }
    }

    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode([
        'sapi' => PHP_SAPI,
        'display_errors' => (string) ini_get('display_errors'),
        'log_errors' => (string) ini_get('log_errors'),
        'error_reporting' => error_reporting(),
        'php_ini_loaded_file' => (string) (php_ini_loaded_file() ?: ''),
        'php_ini_scanned_files' => (string) (php_ini_scanned_files() ?: ''),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
