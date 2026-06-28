<?php
declare(strict_types=1);

/**
 * Sitemap-index ve bölünmüş sitemap'lerin HTTP durum denetimi.
 * 404, 500, yönlendirme veya geçersiz XML varsa rapor verir (CI/operasyon için).
 *
 * Kullanım:
 *   php scripts/sitemap_http_probe.php                  # yerel: http://localhost/mynakliyat
 *   php scripts/sitemap_http_probe.php --base=https://www.mynakliyat.com.tr
 */

$argvList = isset($argv) && is_array($argv) ? $argv : [];
$base = 'http://localhost/mynakliyat';
foreach ($argvList as $a) {
    if (is_string($a) && strncmp($a, '--base=', 7) === 0) {
        $base = rtrim(substr($a, 7), '/');
    }
}

$targets = [
    '/robots.txt',
    '/sitemap-index.xml',
    '/sitemap.xml',
    '/image-sitemap.xml',
    '/video-sitemap.xml',
];

/**
 * @return array{status:int,bytes:int,ctype:string,err:string}
 */
function sitemapProbeFetch(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_HEADER => false,
        CURLOPT_USERAGENT => 'MYNak-SitemapProbe/1.0',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    $body = curl_exec($ch);
    $err = (string) curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $ctype = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    return [
        'status' => $status,
        'bytes' => is_string($body) ? strlen($body) : 0,
        'ctype' => $ctype,
        'err' => $err,
        'body' => is_string($body) ? $body : '',
    ];
}

function sitemapProbeExtractLocs(string $xml): array
{
    $out = [];
    if ($xml === '') {
        return $out;
    }
    if (preg_match_all('#<loc>\s*([^<\s]+)\s*</loc>#i', $xml, $m)) {
        foreach ($m[1] as $url) {
            $out[] = html_entity_decode(trim($url), ENT_QUOTES | ENT_XML1, 'UTF-8');
        }
    }
    return $out;
}

echo "SITEMAP HTTP PROBE base=$base\n";
echo str_repeat('-', 70) . "\n";

$fail = 0;
$discoveredFromIndex = [];

foreach ($targets as $path) {
    $url = $base . $path;
    $r = sitemapProbeFetch($url);
    $ok = $r['status'] >= 200 && $r['status'] < 300 && $r['bytes'] > 50;
    $flag = $ok ? 'OK' : 'FAIL';
    if (!$ok) $fail++;
    echo sprintf("%-6s %3d %8d b  %-40s %s\n", $flag, $r['status'], $r['bytes'], $path, $r['err']);

    if ($path === '/sitemap-index.xml' && $ok) {
        $discoveredFromIndex = sitemapProbeExtractLocs($r['body']);
    }
}

$additional = [];
foreach ($discoveredFromIndex as $u) {
    $parsed = parse_url($u);
    if (!is_array($parsed) || empty($parsed['path'])) {
        continue;
    }
    $p = (string) $parsed['path'];
    if (!in_array($p, $targets, true)) {
        $additional[] = $p;
    }
}

if ($additional) {
    echo "\nEK (index'ten keşfedilen):\n";
    foreach (array_unique($additional) as $path) {
        $url = $base . $path;
        $r = sitemapProbeFetch($url);
        $ok = $r['status'] >= 200 && $r['status'] < 300 && $r['bytes'] > 50;
        $flag = $ok ? 'OK' : 'FAIL';
        if (!$ok) $fail++;
        echo sprintf("%-6s %3d %8d b  %-40s %s\n", $flag, $r['status'], $r['bytes'], $path, $r['err']);
    }
}

echo str_repeat('-', 70) . "\n";
if ($fail === 0) {
    echo "Hepsi OK.\n";
    exit(0);
}
echo "Basarisiz: $fail\n";
exit(1);
