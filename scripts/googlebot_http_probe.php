<?php
/**
 * Googlebot User-Agent ile SITE_URL üzerinde HEAD istekleri (canlı / yerel kontrol).
 *
 * php scripts/googlebot_http_probe.php
 */
declare(strict_types=1);

$root = realpath(dirname(__DIR__));
if ($root === false) {
    fwrite(STDERR, "Kök bulunamadı.\n");
    exit(1);
}

require_once $root . '/config/config.php';

$ua = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';
$origin = rtrim((string) SITE_URL, '/');

$paths = [
    '/',
    '/robots.txt',
    '/sitemap-index.xml',
    '/sitemap.xml',
    '/blog',
    '/llms.txt',
    '/teklif-alin',
];

echo "SITE_URL: {$origin}\n";
echo "User-Agent: Googlebot\n\n";

foreach ($paths as $path) {
    $url = $origin . $path;
    $ch = curl_init($url);
    if ($ch === false) {
        echo "curl yok\n";
        exit(1);
    }
    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 6,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_USERAGENT => $ua,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $final = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    $arrow = ($final !== $url) ? " -> {$final}" : '';
    echo str_pad((string) $code, 4) . $path . $arrow . "\n";
}

echo "\nNot: 404 sitemap-index → sunucuda dosya yok veya yol hatalı; generate_full_sitemap.php çalıştırın.\n";
