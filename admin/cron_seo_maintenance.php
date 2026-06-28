<?php
/**
 * SEO bakım cron — GBP senkron + IndexNow (son güncellenen URL'ler).
 * Örnek: php admin/cron_seo_maintenance.php
 * cPanel: 0 3 * * 1 php /home/.../public_html/admin/cron_seo_maintenance.php
 */
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

$root = dirname(__DIR__);
require_once $root . '/config/db.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/mynak_gbp_sync.php';
require_once $root . '/includes/mynak_indexnow.php';

$logFile = $root . '/logs/cron_seo_maintenance.log';

function mynak_cron_seo_log(string $message): void
{
    global $logFile;
    @file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
}

mynak_cron_seo_log('=== SEO maintenance cron ===');

if (function_exists('mynak_sync_gbp_data')) {
    try {
        $gbp = mynak_sync_gbp_data($conn, false);
        mynak_cron_seo_log('GBP: ' . (string) ($gbp['message'] ?? ($gbp['ok'] ? 'ok' : 'fail')));
    } catch (Throwable $e) {
        mynak_cron_seo_log('GBP ERROR: ' . $e->getMessage());
    }
} else {
    mynak_cron_seo_log('GBP: mynak_sync_gbp_data yok');
}

$key = mynak_indexnow_key_from_settings($conn);
if ($key !== '') {
    $urls = mynak_indexnow_recent_public_urls($conn, 7, 150);
    $inx = mynak_indexnow_submit_urls($key, $urls);
    mynak_cron_seo_log('IndexNow: ' . ($inx['message'] ?? 'done'));
} else {
    mynak_cron_seo_log('IndexNow: indexnow_key ayarlı değil, atlandı');
}

mynak_cron_seo_log('=== Done ===');
