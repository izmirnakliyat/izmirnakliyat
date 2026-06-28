<?php
/**
 * Yalnızca kökteki sitemap-index.xml dosyasını yazar (DB sorgusu yok).
 * SITE_URL CLI’da canlı kalır (environment.php); image/video sitemap varsa indekse eklenir.
 *
 * php scripts/write_sitemap_index_only.php
 */
declare(strict_types=1);

$root = realpath(dirname(__DIR__));
if ($root === false) {
    fwrite(STDERR, "Kök bulunamadı.\n");
    exit(1);
}

require_once $root . '/config/config.php';
require_once $root . '/includes/sitemap_build.php';

$site = rtrim((string) SITE_URL, '/');
$path = $root . DIRECTORY_SEPARATOR . 'sitemap-index.xml';
$xml = sitemap_build_index_xml($site, $root);

if (file_put_contents($path, $xml) === false) {
    fwrite(STDERR, "Yazılamadı: {$path}\n");
    exit(1);
}

echo "OK: {$path} (" . strlen($xml) . " bayt)\n";
