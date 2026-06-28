<?php
declare(strict_types=1);

/**
 * Üretim doğrulaması: HTML uçlarında ilk bayt <!doctype html>, uyarı yok, yol sızıntısı yok.
 * Kullanım: php scripts/validate_http_output.php [BASE_URL]
 * Örnek: php scripts/validate_http_output.php http://127.0.0.1/mynakliyat
 */

$base = rtrim($argv[1] ?? (getenv('MYNAK_VALIDATE_BASE') ?: 'http://127.0.0.1/mynakliyat'), '/');

$endpoints = [
    ['path' => '/', 'expect_html' => true],
    ['path' => '/blog', 'expect_html' => true],
    ['path' => '/iletisim', 'expect_html' => true],
    ['path' => '/robots.txt', 'expect_html' => false],
    ['path' => '/sitemap-index.xml', 'expect_html' => false],
];

$fail = 0;
foreach ($endpoints as $ep) {
    $url = $base . $ep['path'];
    $ch = curl_init($url);
    if ($ch === false) {
        fwrite(STDERR, "FAIL: curl_init $url\n");
        $fail++;
        continue;
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    if ($raw === false || $code < 200 || $code >= 400) {
        fwrite(STDERR, "FAIL: $url HTTP $code\n");
        $fail++;
        continue;
    }
    $body = substr($raw, $headerSize);
    if ($ep['expect_html']) {
        $head = strtolower(substr(ltrim($body, "\xEF\xBB\xBF \t\r\n"), 0, 20));
        if (!str_starts_with($head, '<!doctype html')) {
            fwrite(STDERR, "FAIL: $url first bytes not <!doctype html (got: " . json_encode(substr($body, 0, 80)) . ")\n");
            $fail++;
            continue;
        }
        if (stripos($body, '<br /><b>Warning</b>') !== false || stripos($body, '<br/><b>Warning</b>') !== false) {
            fwrite(STDERR, "FAIL: $url PHP Warning HTML in body\n");
            $fail++;
            continue;
        }
        if (preg_match('#([a-zA-Z]:\\\\|/var/www/|/xampp/htdocs/)#', $body) === 1) {
            fwrite(STDERR, "FAIL: $url possible filesystem path leak in body\n");
            $fail++;
            continue;
        }
    }
    fwrite(STDOUT, "OK $url\n");
}

exit($fail > 0 ? 1 : 0);
