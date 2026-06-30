<?php
/**
 * Veritabanı: yerel ortam sabitler; canlıda değerler .env (DB_*) üzerinden.
 * Canlıda köke .env koyun — örnek: .env.example
 *
 * Sadece db.php açılıyorsa (config yok) config.php bir kez yüklenir; mynak_env_str kesin tanımlı olur.
 */
if (!function_exists('mynak_env_str')) {
    require_once __DIR__ . '/config.php';
}
require_once __DIR__ . '/environment.php';
$is_local = mynak_db_use_local_credentials();
if (defined('MYNAK_FORCE_PRODUCTION_DB') && MYNAK_FORCE_PRODUCTION_DB) {
    $is_local = false;
}

if ($is_local) {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'mynakliyat');
    define('DB_PORT', 3306);
} else {
    require_once __DIR__ . '/env_loader.php';
    $root = defined('PROJECT_ROOT') ? PROJECT_ROOT : (realpath(__DIR__ . '/..') ?: dirname(__DIR__));
    mynak_load_dotenv($root . DIRECTORY_SEPARATOR . '.env');

    $dbHost = mynak_env_str('DB_HOST') ?: 'localhost';
    $dbUser = mynak_env_str('DB_USER');
    $dbPass = mynak_env_str('DB_PASS');
    $dbName = mynak_env_str('DB_NAME');
    $dbPort = (int) (mynak_env_str('DB_PORT') ?: '3306');

    if ($dbUser === '' || $dbName === '') {
        error_log('mynak: Canlı ortamda .env eksik veya DB_USER/DB_NAME boş. .env.example dosyasına bakın.');
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            http_response_code(503);
            header('Content-Type: text/html; charset=UTF-8');
            header('Retry-After: 300');
            header('X-Robots-Tag: noindex, nofollow', true);
        }
        die('<!doctype html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>503 — Bakım</title></head><body><h1>Geçici bakım</h1><p>Sistem yapılandırması eksik. Lütfen daha sonra tekrar deneyin.</p></body></html>');
    }

    define('DB_HOST', $dbHost);
    define('DB_USER', $dbUser);
    define('DB_PASS', $dbPass);
    define('DB_NAME', $dbName);
    define('DB_PORT', $dbPort > 0 ? $dbPort : 3306);
}

try {
    if (function_exists('mysqli_report')) {
        mysqli_report(MYSQLI_REPORT_OFF);
    }

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);

    if ($conn->connect_error) {
        error_log('Veritabanı bağlantı hatası: ' . $conn->connect_error);
        if ($is_local) {
            die('Veritabanı bağlantı hatası: ' . $conn->connect_error);
        }
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            http_response_code(503);
            header('Content-Type: text/html; charset=UTF-8');
            header('Retry-After: 300');
            header('X-Robots-Tag: noindex, nofollow', true);
        }
        die('<!doctype html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>503 — Bakım</title></head><body><h1>Geçici bakım</h1><p>Sistem şu anda bakımda. Lütfen daha sonra tekrar deneyin.</p></body></html>');
    }

    $conn->set_charset('utf8mb4');
    $conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->query('SET CHARACTER SET utf8mb4');
    $conn->query('SET character_set_client = utf8mb4');
    $conn->query('SET character_set_connection = utf8mb4');
    $conn->query('SET character_set_results = utf8mb4');
    $conn->query('SET collation_connection = utf8mb4_unicode_ci');
    $conn->query("SET sql_mode = ''");

    require_once dirname(__DIR__) . '/includes/mynak_blog_posts_id_guard.php';
    mynak_blog_posts_id_guard_run($conn);
} catch (Exception $e) {
    error_log('Veritabanı bağlantı exception: ' . $e->getMessage());
    if ($is_local) {
        die('Veritabanı bağlantı hatası: ' . $e->getMessage());
    }
    if (PHP_SAPI !== 'cli' && !headers_sent()) {
        http_response_code(503);
        header('Content-Type: text/html; charset=UTF-8');
        header('Retry-After: 300');
        header('X-Robots-Tag: noindex, nofollow', true);
    }
    die('<!doctype html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>503 — Bakım</title></head><body><h1>Geçici bakım</h1><p>Sistem şu anda bakımda. Lütfen daha sonra tekrar deneyin.</p></body></html>');
}
