<?php
declare(strict_types=1);
/**
 * Tek seferlik: dinamik robots + layout önbellekte API anahtarı yok mu?
 * php scripts/verify_seo_dynamic.php
 */
$root = dirname(__DIR__);
require_once $root . '/bootstrap.php';
require_once $root . '/config/site_url_define.php';
require_once $root . '/includes/handlers/public_special_handlers.php';

ob_start();
mynak_handler_robots_txt();
$robots = ob_get_clean();

$hasSitemap = (bool) preg_match('/^Sitemap:\s+\S+/m', $robots);
echo $hasSitemap ? "robots Sitemap: OK\n" : "robots Sitemap: FAIL\n";
echo "--- robots (first 30 lines) ---\n";
$lines = explode("\n", $robots);
echo implode("\n", array_slice($lines, 0, 30)) . "\n";

$cacheFile = $root . '/cache/public_layout_settings_menus.json';
if (!is_readable($cacheFile)) {
    require_once $root . '/config/config.php';
    require_once $root . '/config/db.php';
    require_once $root . '/includes/functions.php';
    require_once $root . '/includes/pipeline/public_layout_context.php';
    mynak_public_layout_load_settings_and_menus($conn);
}

if (is_readable($cacheFile)) {
    $raw = (string) file_get_contents($cacheFile);
    $bad = str_contains($raw, 'google_places_api_key')
        || str_contains($raw, 'AIzaSy')
        || str_contains($raw, 'openai_api_key');
    echo $bad ? "cache JSON: CONTAINS sensitive pattern (FAIL)\n" : "cache JSON: no obvious API keys (OK)\n";
} else {
    echo "cache JSON: file missing (run a web request to build)\n";
}
