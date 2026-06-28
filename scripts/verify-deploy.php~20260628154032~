<?php
/**
 * Canlı sunucuya yüklemeden önce veya yükleme sonrası SSH/CLI ile çalıştırın:
 *   php scripts/verify-deploy.php
 *   php scripts/verify-deploy.php --strict   (.env yoksa çıkış kodu 1)
 * Proje kökünde .env ve kritik klasörleri kontrol eder (web’den çağırmayın).
 */
declare(strict_types=1);

$strict = in_array('--strict', $argv, true);

$root = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
$envPath = $root . DIRECTORY_SEPARATOR . '.env';

require_once $root . '/config/env_loader.php';
mynak_load_dotenv($envPath);

$errors = [];
$warnings = [];

if (!is_readable($envPath)) {
    $msg = '.env bulunamıyor veya okunamıyor: ' . $envPath;
    if ($strict) {
        $errors[] = $msg;
    } else {
        $warnings[] = $msg . ' (canlı sunucuda zorunlu; yerelde XAMPP için normal olabilir)';
    }
} else {
    $dbUser = trim((string) (getenv('DB_USER') ?: ''));
    $dbName = trim((string) (getenv('DB_NAME') ?: ''));
    if ($dbUser === '') {
        $errors[] = 'DB_USER .env içinde dolu olmalı (canlı MySQL kullanıcısı).';
    }
    if ($dbName === '') {
        $errors[] = 'DB_NAME .env içinde dolu olmalı.';
    }
    $siteUrl = getenv('SITE_URL');
    $siteUrl = is_string($siteUrl) ? rtrim(trim($siteUrl), '/') : '';
    if ($siteUrl === '') {
        $warnings[] = 'SITE_URL tanımsız; config varsayılanı kullanılacak: https://www.mynakliyat.com.tr';
    } elseif (!preg_match('#^https://#i', $siteUrl)) {
        $warnings[] = 'SITE_URL https ile başlamıyor; canlıda SSL önerilir: ' . $siteUrl;
    }
}

$uploads = $root . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploads)) {
    $warnings[] = 'uploads/ klasörü yok (medya yolları kırılabilir). Oluşturun veya yedeği yükleyin.';
} elseif (!is_writable($uploads)) {
    $warnings[] = 'uploads/ yazılabilir değil; yüklemeler için izin verin (ör. 755 veya hosting önerisi).';
}

$cache = $root . DIRECTORY_SEPARATOR . 'cache';
if (is_dir($cache) && !is_writable($cache)) {
    $warnings[] = 'cache/ yazılabilir değil.';
}

echo "MyNakliyat deploy doğrulama (kök: {$root})\n";
echo str_repeat('-', 50) . "\n";

foreach ($warnings as $w) {
    echo "[UYARI] {$w}\n";
}
foreach ($errors as $e) {
    echo "[HATA] {$e}\n";
}

if ($errors === [] && $warnings === []) {
    echo "Temel kontroller tamam.\n";
    exit(0);
}
if ($errors !== []) {
    echo "\nÖnce hataları giderin.\n";
    exit(1);
}
echo "\nUyarılar var; yine de devam edebilirsiniz.\n";
exit(0);
