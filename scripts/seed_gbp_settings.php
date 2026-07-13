<?php
/**
 * Google Places Details API üzerinden doğrulanmış GBP verisini senkronize eder.
 * Manuel puan veya yorum sayısı kabul edilmez.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}
require __DIR__ . '/../config/db.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    throw new RuntimeException('Veritabanı bağlantısı kurulamadı.');
}
require_once __DIR__ . '/../includes/mynak_gbp_sync.php';

$result = mynak_sync_gbp_data($conn, true);
echo $result['message'] . "\n";
exit($result['ok'] ? 0 : 1);
