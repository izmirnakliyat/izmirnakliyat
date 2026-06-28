<?php
declare(strict_types=1);

/**
 * Tek giriş öncesi ortam: hata politikası + çıktı tamponu + zaman dilimi.
 * echo yok; config/db yok.
 */
if (defined('MYNAK_BOOTSTRAP_LOADED')) {
    return;
}
define('MYNAK_BOOTSTRAP_LOADED', true);

if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg' && !headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
}

require_once __DIR__ . '/config/windows_request_uri_shim.php';
require_once __DIR__ . '/config/env_functions.php';
require_once __DIR__ . '/config/http_output_handler.php';

if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg' && ob_get_level() === 0) {
    $obFlags = PHP_OUTPUT_HANDLER_CLEANABLE | PHP_OUTPUT_HANDLER_FLUSHABLE | PHP_OUTPUT_HANDLER_REMOVABLE;
    if (defined('PHP_OUTPUT_HANDLER_STDFLAGS')) {
        $obFlags = PHP_OUTPUT_HANDLER_STDFLAGS;
    }
    ob_start('mynak_http_output_buffer_handler', 0, $obFlags);
}

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
$__mynakErrLog = __DIR__ . '/logs/php_errors.log';
$__mynakLogDir = dirname($__mynakErrLog);
if (!is_dir($__mynakLogDir)) {
    @mkdir($__mynakLogDir, 0755, true);
}
if (is_dir($__mynakLogDir)) {
    ini_set('error_log', $__mynakErrLog);
}
unset($__mynakErrLog, $__mynakLogDir);

$__mynakCacheDir = __DIR__ . '/cache';
if (!is_dir($__mynakCacheDir)) {
    @mkdir($__mynakCacheDir, 0755, true);
}
unset($__mynakCacheDir);

$__mynakProdIni = __DIR__ . '/config/production.ini';
if (is_readable($__mynakProdIni)) {
    $__mynakProd = parse_ini_file($__mynakProdIni);
    if (is_array($__mynakProd)) {
        foreach ($__mynakProd as $__k => $__v) {
            if (is_string($__k) && $__v !== null && $__v !== '') {
                ini_set($__k, (string) $__v);
            }
        }
    }
    unset($__mynakProd);
}
unset($__mynakProdIni);

ini_set('default_charset', 'UTF-8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}
if (function_exists('mb_http_output')) {
    mb_http_output('UTF-8');
}
if (function_exists('mb_regex_encoding')) {
    mb_regex_encoding('UTF-8');
}

date_default_timezone_set('Europe/Istanbul');
