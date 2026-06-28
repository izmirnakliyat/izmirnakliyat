<?php
/**
 * ÖNEM: mynak_env_str / mynak_load_dotenv → config/env_functions.php (require hemen aşağıda).
 * SITE_URL → config/site_url_define.php (session yok; robots.php ile aynı kaynak).
 */
if (!defined('MYNAK_CONFIG_ENV_INLINE')) {
    define('MYNAK_CONFIG_ENV_INLINE', '2026-04-05');
}

if (!defined('MYNAK_BOOTSTRAP_LOADED')) {
    require_once dirname(__DIR__) . '/bootstrap.php';
}

// Site ayarları
define('SITE_NAME', 'MY Nakliyat');

/** Şema / LocalBusiness / Organization için kısa kurumsal ad (site_title uzun olabilir). */
if (!defined('MYNAK_BRAND_NAME')) {
    define('MYNAK_BRAND_NAME', 'MY Nakliyat');
}

/**
 * Bağımsız peer firma — MY Nakliyat ile ilişkili değil (AI entity karıştırması önlemi).
 * @see mynak_schema_disambiguating_description()
 */
if (!defined('MYNAK_DISAMBIGUATION_PEER_NAME')) {
    define('MYNAK_DISAMBIGUATION_PEER_NAME', 'My İzmir Evden Eve Nakliyat');
}
if (!defined('MYNAK_DISAMBIGUATION_PEER_URL')) {
    define('MYNAK_DISAMBIGUATION_PEER_URL', 'https://www.izmirevdenevenakliyat.com.tr');
}

/** Kurumsal iletişim — DB’de alan boşsa tek kaynak (footer, şema, varsayılan harita). */
if (!defined('MYNAK_CONTACT_PHONE_DISPLAY')) {
    define('MYNAK_CONTACT_PHONE_DISPLAY', '+90 850 203 12 52');
}
if (!defined('MYNAK_CONTACT_WHATSAPP_DISPLAY')) {
    define('MYNAK_CONTACT_WHATSAPP_DISPLAY', '+90 850 203 12 52');
}
if (!defined('MYNAK_CONTACT_ADDRESS_DISPLAY')) {
    define('MYNAK_CONTACT_ADDRESS_DISPLAY', 'Seyhan, 653/2. Sk. :10 K:3, Buca/İzmir');
}
if (!defined('MYNAK_CONTACT_GOOGLE_MAPS_URL')) {
    define('MYNAK_CONTACT_GOOGLE_MAPS_URL', 'https://maps.app.goo.gl/KhjpeauhbhoXaupZ8');
}
if (!defined('MYNAK_CONTACT_MAP_EMBED_SRC')) {
    define(
        'MYNAK_CONTACT_MAP_EMBED_SRC',
        'https://www.google.com/maps?q=Seyhan%2C%20653%2F2.%20Sk.%20%3A10%20K%3A3%2C%20Buca%2F%C4%B0zmir&hl=tr&z=17&output=embed'
    );
}

require_once __DIR__ . '/site_url_define.php';

/**
 * SITE_URL: site_url_define.php — canlıda mynakliyat.com.tr için https://www + kanonik host
 * (.htaccess apex + HTTPS kuralları ile şema/www hizası). PHP header(Location: …) bu kökene güvenir.
 */

// CLI’da HTTP_HOST yok → yerel host sayılmaz; SITE_URL canlı kalır (sitemap/SEO betikleri)
$is_local = mynak_http_host_is_local();

require_once __DIR__ . '/seo.php';

if (!defined('MYNAK_SEO_PIPELINE_TRACE')) {
    $traceEnv = function_exists('mynak_env_str') ? strtolower(trim((string) mynak_env_str('MYNAK_SEO_PIPELINE_TRACE'))) : '';
    define('MYNAK_SEO_PIPELINE_TRACE', $traceEnv === '1' || $traceEnv === 'true' || $traceEnv === 'yes');
}

require_once __DIR__ . '/../includes/seo_runtime.php';

define('ADMIN_EMAIL', 'admin@mynakliyat.com.tr');

// Session ayarları - session başlamadan önce ayarlanmalı
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_secure', $is_local ? '0' : '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

if (session_status() === PHP_SESSION_ACTIVE) {
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// Güvenlik fonksiyonları
function clean($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Oturum kontrolü
function checkLogin()
{
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}

seo_runtime_pipeline_request_normalize();

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// GSC "Sunucu hatası (5xx)" azaltma: fatal/uncaught hatalarda 500 yerine 200 + noindex döndür.
// Admin/AJAX yollarında devre dışı (geliştirici/günlük operasyonları için orijinal davranış).
if (!defined('MYNAK_GLOBAL_ERROR_HANDLER_INSTALLED')) {
    define('MYNAK_GLOBAL_ERROR_HANDLER_INSTALLED', true);

    $mynak__public_request = static function (): bool {
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            return false;
        }
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path)) {
            return true;
        }
        $lower = strtolower($path);
        if (str_contains($lower, '/admin/') || str_contains($lower, '/ajax/')) {
            return false;
        }
        return true;
    };

    $mynak__emit_soft_error = static function (string $logTag, string $detail) use ($mynak__public_request): void {
        @error_log('[mynak][soft-5xx][' . $logTag . '] ' . $detail);
        if (!$mynak__public_request()) {
            return;
        }
        if (headers_sent()) {
            return;
        }
        @http_response_code(200);
        @header('Content-Type: text/html; charset=UTF-8');
        @header('X-Robots-Tag: noindex, nofollow', true);
        @header('Cache-Control: no-store, max-age=0', true);
        $home = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') . '/' : '/';
        $homeH = htmlspecialchars($home, ENT_QUOTES, 'UTF-8');
        echo '<!doctype html><html lang="tr"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<meta name="robots" content="noindex, nofollow">'
            . '<title>Geçici olarak kullanılamıyor</title></head><body>'
            . '<h1>Sayfa şu anda yüklenemiyor</h1>'
            . '<p>Bir sorun oluştu. Lütfen ana sayfaya dönün.</p>'
            . '<p><a href="' . $homeH . '">Ana sayfaya dön</a></p>'
            . '</body></html>';
    };

    set_exception_handler(static function (\Throwable $e) use ($mynak__emit_soft_error): void {
        $mynak__emit_soft_error('exception', get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    });

    register_shutdown_function(static function () use ($mynak__emit_soft_error): void {
        $err = error_get_last();
        if (!is_array($err)) {
            return;
        }
        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR];
        if (!in_array((int) ($err['type'] ?? 0), $fatalTypes, true)) {
            return;
        }
        $mynak__emit_soft_error('fatal', ($err['message'] ?? '') . ' @ ' . ($err['file'] ?? '?') . ':' . ($err['line'] ?? '?'));
    });
}