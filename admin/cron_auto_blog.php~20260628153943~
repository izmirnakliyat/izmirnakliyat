<?php
/**
 * Otomatik Blog Cron — varsayılan KAPALI.
 * Açıkken: günde max 3–5, yalnızca CE + QC (ab_ce_generate_article).
 */
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auto_blog_functions.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mynak_local_seo_content_engine.php';
require_once __DIR__ . '/../includes/mynak_ce_production.php';
require_once __DIR__ . '/../includes/auto_blog_ce_adapter.php';

$log_file = __DIR__ . '/cron_log.txt';

function cron_log(string $message): void
{
    global $log_file;
    file_put_contents($log_file, '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
}

cron_log('=== Cron Auto Blog (CE) ===');

if (!mynak_ce_cron_enabled($conn)) {
    cron_log('Cron kapalı (settings: auto_blog_cron_enabled=0). Çıkılıyor.');
    exit;
}

try {
    $settings = $conn->query('SELECT * FROM auto_blog_settings WHERE active = 1');
    if (!$settings || $settings->num_rows === 0) {
        cron_log('Aktif ayar yok');
        exit;
    }

    $current_hour = date('H:i');
    $current_day = date('N');
    cron_log("Zaman: $current_hour, gün: $current_day");

    while ($setting = $settings->fetch_assoc()) {
        $sid = (int) ($setting['id'] ?? 0);
        cron_log("Ayar ID $sid kontrol");

        $post_times = array_map('trim', explode(',', (string) ($setting['post_time'] ?? '')));
        $should_run = false;
        foreach ($post_times as $post_time) {
            if ($current_hour !== $post_time) {
                continue;
            }
            switch ($setting['period_type'] ?? 'daily') {
                case 'hourly':
                case 'daily':
                    $should_run = true;
                    break;
                case 'weekly':
                    if ((int) $current_day === 1) {
                        $should_run = true;
                    }
                    break;
            }
            break;
        }

        if (!$should_run) {
            cron_log("Ayar $sid zamanlama uymuyor");
            continue;
        }

        if (!ab_ce_check_quota($conn, false)) {
            cron_log('Günlük CE kotası doldu — cron durdu');
            break;
        }

        cron_log("Ayar $sid için CE üretimi başlıyor");
        $result = ab_ce_generate_article($conn, $setting, ['preview_mode' => false]);

        if ($result['success'] ?? false) {
            cron_log('OK blog_id=' . ($result['blog_id'] ?? '?')
                . ' durum=' . ($result['durum'] ?? '?')
                . ' qc=' . (($result['qc_pass'] ?? false) ? 'PASS' : 'FAIL'));
        } else {
            cron_log('FAIL: ' . ($result['message'] ?? 'bilinmeyen'));
        }
    }
} catch (Throwable $e) {
    cron_log('Hata: ' . $e->getMessage());
}

cron_log('=== Cron bitti ===');
