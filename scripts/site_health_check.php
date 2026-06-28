<?php
/**
 * Canlı / staging HTTP sağlık ve güvenlik başlığı özeti (DB gerekmez).
 *
 * KULLANIM:
 *   php scripts/site_health_check.php
 *   php scripts/site_health_check.php --base=https://www.mynakliyat.com.tr
 *   php scripts/site_health_check.php --base=https://www.mynakliyat.com.tr --sitemap-sample=80
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$defaults = [
    'base' => 'https://www.mynakliyat.com.tr',
    'sitemap_sample' => 60,
    'sitemap_max_parse' => 2000,
];

$opts = $defaults;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--base=')) {
        $v = trim(substr($arg, 7));
        if ($v !== '') {
            $opts['base'] = $v;
        }
    }
    if (preg_match('/^--sitemap-sample=(\d+)$/', $arg, $m)) {
        $opts['sitemap_sample'] = max(0, (int) $m[1]);
    }
    if (preg_match('/^--sitemap-max-parse=(\d+)$/', $arg, $m)) {
        $opts['sitemap_max_parse'] = max(100, (int) $m[1]);
    }
}

$base = rtrim((string) $opts['base'], '/');
if (!preg_match('#^https://#i', $base)) {
    fwrite(STDERR, "[FATAL] --base HTTPS olmalı (örn. https://www.mynakliyat.com.tr)\n");
    exit(1);
}

$host = (string) (parse_url($base, PHP_URL_HOST) ?? '');
if ($host === '') {
    fwrite(STDERR, "[FATAL] Geçersiz --base\n");
    exit(1);
}

$apex = preg_replace('/^www\./i', '', $host);

function mynak_health_fetch(string $url, bool $follow, int $timeout = 12): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_ENCODING => '',
        CURLOPT_USERAGENT => 'mynak-health-check/1.1',
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    return [
        'ok' => $body !== false,
        'code' => $code,
        'body' => $body === false ? '' : (string) $body,
        'err' => $err,
    ];
}

function mynak_health_fetch_with_headers(string $url, int $timeout = 12): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_ENCODING => '',
        CURLOPT_HEADER => true,
        CURLOPT_USERAGENT => 'mynak-health-check/1.1',
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hs = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false) {
        return ['code' => 0, 'headers' => [], 'err' => $err];
    }
    $headerBlock = substr((string) $raw, 0, $hs);
    $lines = preg_split("/\r?\n/", $headerBlock) ?: [];
    $headers = [];
    foreach ($lines as $line) {
        if (stripos($line, ':') !== false) {
            [$k, $v] = explode(':', $line, 2);
            $headers[strtolower(trim($k))] = trim($v);
        }
    }

    return ['code' => $code, 'headers' => $headers, 'err' => $err];
}

echo "=== MYNAK SITE HEALTH ===\n";
echo 'Hedef: ' . $base . "\n\n";

$fail = 0;

/* A) HTTP → HTTPS yönlendirme */
echo "--- A) HTTP → HTTPS ---\n";
$httpTests = ['http://' . $host . '/'];
$wwwHost = 'www.' . $apex;
if (strtolower($host) !== strtolower($wwwHost)) {
    $httpTests[] = 'http://' . $wwwHost . '/';
}
if (strtolower($host) !== strtolower($apex)) {
    $httpTests[] = 'http://' . $apex . '/';
}
$httpTests = array_values(array_unique($httpTests));
foreach ($httpTests as $httpUrl) {
    $ch = curl_init($httpUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'mynak-health-check/1.1',
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($raw === false || ($code !== 301 && $code !== 302 && $code !== 308)) {
        echo "  [UYARI] $httpUrl → kod $code (301/302 beklenir)\n";
        ++$fail;
        continue;
    }
    if (preg_match('/^Location:\s*(.+)\s*$/mi', (string) $raw, $m)) {
        $loc = trim($m[1]);
        $ok = stripos($loc, 'https://') === 0;
        echo '  [OK] ' . $httpUrl . ' → ' . $code . ' → ' . $loc . ($ok ? '' : ' (HTTPS değil?)') . "\n";
        if (!$ok) {
            ++$fail;
        }
    } else {
        echo "  [UYARI] $httpUrl Location başlığı yok\n";
        ++$fail;
    }
}

/* B) Kritik yollar */
echo "\n--- B) Kritik URL (GET) ---\n";
$critical = ['/', '/blog', '/robots.txt', '/sitemap.xml', '/llms.txt', '/teklif-al'];
foreach ($critical as $path) {
    $u = $base . $path;
    $r = mynak_health_fetch($u, true);
    if ($r['code'] === 200) {
        $kb = round(strlen($r['body']) / 1024, 1);
        echo "  [OK] {$r['code']} $path ({$kb} KB)\n";
    } else {
        echo "  [HATA] {$r['code']} $path " . ($r['err'] !== '' ? $r['err'] : '') . "\n";
        ++$fail;
    }
}

/* C) Ana sayfa güvenlik / SEO başlıkları */
echo "\n--- C) Ana sayfa başlıkları ---\n";
$r = mynak_health_fetch_with_headers($base . '/');
if ($r['code'] !== 200) {
    echo "  [HATA] Ana sayfa {$r['code']}\n";
    ++$fail;
} else {
    $want = [
        'strict-transport-security' => 'HSTS',
        'x-content-type-options' => 'nosniff',
        'x-frame-options' => 'Clickjacking',
        'content-type' => 'charset',
    ];
    foreach ($want as $h => $label) {
        if (!isset($r['headers'][$h])) {
            if ($h === 'strict-transport-security') {
                echo "  [BİLGİ] $label ($h) yok — önerilir\n";
            } else {
                echo "  [UYARI] $label ($h) yok\n";
            }
            continue;
        }
        $v = $r['headers'][$h];
        echo '  [OK] ' . $h . ': ' . (strlen($v) > 72 ? substr($v, 0, 72) . '…' : $v) . "\n";
    }
}

/* D) Sitemap’ten örnekleme */
$sampleN = (int) $opts['sitemap_sample'];
$maxParse = (int) $opts['sitemap_max_parse'];
if ($sampleN > 0) {
    echo "\n--- D) Sitemap örneklem ($sampleN URL) ---\n";
    $sm = mynak_health_fetch($base . '/sitemap.xml', true, 20);
    if ($sm['code'] !== 200) {
        echo "  [HATA] sitemap.xml {$sm['code']}\n";
        ++$fail;
    } else {
        preg_match_all('#<loc>\s*([^<\s]+)\s*</loc>#i', $sm['body'], $m);
        $locs = $m[1] ?? [];
        $same = [];
        foreach ($locs as $loc) {
            $h = (string) (parse_url($loc, PHP_URL_HOST) ?? '');
            if ($h !== '' && strcasecmp($h, $host) !== 0 && strcasecmp($h, 'www.' . $apex) !== 0) {
                continue;
            }
            $same[] = $loc;
            if (count($same) >= $maxParse) {
                break;
            }
        }
        shuffle($same);
        $pick = array_slice($same, 0, $sampleN);
        $badSm = 0;
        foreach ($pick as $u) {
            $x = mynak_health_fetch($u, true, 10);
            if ($x['code'] !== 200) {
                echo "  [HATA] {$x['code']} $u\n";
                ++$badSm;
                ++$fail;
            }
        }
        echo '  [OK] ' . (count($pick) - $badSm) . '/' . count($pick) . " örnek 200 (toplam parse edilen loc ≤ $maxParse)\n";
    }
}

echo "\n=== ÖZET ===\n";
if ($fail === 0) {
    echo "Kritik hata sayısı: 0\n";
    exit(0);
}
echo "Uyarı/hata puanı: $fail (0 ideal; B ve D'deki HATA satırlarını inceleyin)\n";
exit($fail > 3 ? 2 : 1);
