<?php
/**
 * 2024 ve öncesi yayınlanmış blog yazılarının gövdesini ChatGPT ile yeniden üretir;
 * başlık ve slug ASLA değişmez. Yeni içerik editör kuyruğuna düşer (durum=1).
 *
 * Günlük kota: varsayılan 3 (ayar: blog_bulk_refresh_daily_max, yoksa 3).
 * Ortak mantık: includes/blog_bulk_content_refresh_lib.php
 *
 * KULLANIM (XAMPP örnek):
 *   C:\xampp\php\php.exe scripts/blog_bulk_content_refresh.php
 *   C:\xampp\php\php.exe scripts/blog_bulk_content_refresh.php --dry-run
 *   C:\xampp\php\php.exe scripts/blog_bulk_content_refresh.php --limit=1 --ignore-quota
 *   C:\xampp\php\php.exe scripts/blog_bulk_content_refresh.php --any-date
 *   C:\xampp\php\php.exe scripts/blog_bulk_content_refresh.php --id=123 --ignore-quota
 *
 * Admin panel: admin/blog_bulk_refresh.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Bu script yalnızca CLI. Arayüz: admin/blog_bulk_refresh.php\n");
    exit(1);
}

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../admin/includes/auto_blog_functions.php';
require __DIR__ . '/../includes/blog_bulk_content_refresh_lib.php';

$dryRun = in_array('--dry-run', $argv, true);
$ignoreQuota = in_array('--ignore-quota', $argv, true);
$anyDate = in_array('--any-date', $argv, true);
$limitRun = 3;
$cutoffDate = '2025-01-01';
$singleId = null;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limitRun = max(1, (int) substr($arg, 8));
    }
    if (str_starts_with($arg, '--before=')) {
        $cutoffDate = substr($arg, 9);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $cutoffDate)) {
            fwrite(STDERR, "Geçersiz --before= formatı (YYYY-MM-DD).\n");
            exit(1);
        }
    }
    if (str_starts_with($arg, '--id=')) {
        $singleId = max(0, (int) substr($arg, 5));
    }
}

$result = blog_bulk_content_refresh_run($conn, [
    'dry_run' => $dryRun,
    'ignore_quota' => $ignoreQuota,
    'limit' => $limitRun,
    'before' => $cutoffDate,
    'post_id' => $singleId ?: null,
    'date_cutoff' => !$anyDate,
]);

foreach ($result['logs'] as $line) {
    echo $line . "\n";
}

exit($result['fatal'] !== null ? 1 : 0);
