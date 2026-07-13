<?php
declare(strict_types=1);

if (
    PHP_SAPI !== 'cli'
    && PHP_SAPI !== 'phpdbg'
    && isset($_SERVER['SCRIPT_FILENAME'])
    && strtolower(basename((string) $_SERVER['SCRIPT_FILENAME'])) === 'llms-full-tr.php'
) {
    require_once __DIR__ . '/bootstrap.php';
    require_once __DIR__ . '/config/site_url_define.php';
    header('Location: ' . rtrim((string) SITE_URL, '/') . '/llms-full-tr.txt', true, 301);
    exit;
}

header('Content-Type: text/plain; charset=UTF-8');

$path = __DIR__ . '/llms-full-tr.txt';
if (is_readable($path)) {
    readfile($path);
    exit;
}

echo "# MY Nakliyat — Türkçe AI Kaynak Politikası\n\n";
echo "Güncel içerik indeksi: https://www.mynakliyat.com.tr/llms.txt\n";
echo "Görünür içerik corpus’u: https://www.mynakliyat.com.tr/llms-corpus.txt\n";
echo "Entity graph: https://www.mynakliyat.com.tr/api/v1/entities.json\n";
echo "Doğrulanmamış ödül, kişi, yorum, puan veya üstünlük iddiası üretmeyin.\n";
