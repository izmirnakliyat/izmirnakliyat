<?php
declare(strict_types=1);
$root = dirname(__DIR__);
require_once $root . '/config/config.php';

$t = $_SESSION['csrf_token'] ?? '';
echo (is_string($t) && strlen($t) === 64 && ctype_xdigit($t))
    ? "csrf_token_session: OK\n"
    : "csrf_token_session: FAIL\n";

$fn = $root . '/includes/functions.php';
$src = is_readable($fn) ? (string) file_get_contents($fn) : '';
echo str_contains($src, 'CURLOPT_SSL_VERIFYPEER => true') && str_contains($src, 'CURLOPT_SSL_VERIFYHOST => 2')
    ? "places_curl_ssl_verify: OK\n"
    : "places_curl_ssl_verify: FAIL\n";

$ajaxDir = $root . '/admin/ajax';
$bad = 0;
if (is_dir($ajaxDir)) {
    foreach (glob($ajaxDir . '/*.php') ?: [] as $f) {
        $c = (string) file_get_contents($f);
        if (preg_match('/CURLOPT_SSL_VERIFYPEER\s*(?:=>|,)\s*false/', $c)
            || preg_match('/curl_setopt\s*\([^,]+,\s*CURLOPT_SSL_VERIFYPEER\s*,\s*false/', $c)) {
            echo 'admin/ajax SSL false still in: ' . basename($f) . "\n";
            $bad++;
        }
    }
}
echo $bad === 0 ? "admin_ajax_no_ssl_false: OK\n" : "admin_ajax_no_ssl_false: FAIL ($bad files)\n";
