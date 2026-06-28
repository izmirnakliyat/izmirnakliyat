<?php
declare(strict_types=1);

/**
 * Hızlı sağlık kontrolü (CLI). Ön yüzü değiştirmez; deploy veya yerel geliştirme sonrası çalıştırın.
 *
 *   php scripts/smoke_check.php
 *
 * Çıkış kodu: 0 = tamam, 1 = hata
 */
if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$fail = static function (string $msg): void {
    fwrite(STDERR, $msg . PHP_EOL);
    exit(1);
};

if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    $fail('PHP 8.0+ gerekli; mevcut: ' . PHP_VERSION);
}

foreach (['mysqli', 'json', 'mbstring'] as $ext) {
    if (!extension_loaded($ext)) {
        $fail('Eksik PHP eklentisi: ' . $ext);
    }
}

$mustExist = [
    'bootstrap.php',
    'config/config.php',
    'config/db.php',
    'includes/seo_runtime.php',
    'index.php',
    '.htaccess',
];
foreach ($mustExist as $rel) {
    $p = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_readable($p)) {
        $fail('Dosya okunamıyor: ' . $rel);
    }
}

require_once $root . '/bootstrap.php';
require_once $root . '/config/config.php';
require_once $root . '/config/db.php';

if (!isset($conn) || !$conn instanceof mysqli) {
    $fail('mysqli bağlantısı oluşmadı.');
}
$res = @$conn->query('SELECT 1 AS ok');
if (!$res || !($row = $res->fetch_assoc()) || (string) ($row['ok'] ?? '') !== '1') {
    $fail('Veritabanı SELECT 1 başarısız.');
}

fwrite(STDOUT, 'smoke_check: OK (PHP ' . PHP_VERSION . ", DB " . (defined('DB_NAME') ? DB_NAME : '?') . ')' . PHP_EOL);
exit(0);
