<?php
declare(strict_types=1);

/**
 * IndexNow — URL bildirimi (cron + admin ortak).
 */

function mynak_indexnow_key_from_settings(mysqli $conn): string
{
    $r = $conn->query("SELECT value FROM settings WHERE name = 'indexnow_key' LIMIT 1");
    if ($r && ($row = $r->fetch_assoc())) {
        return trim((string) ($row['value'] ?? ''));
    }

    return '';
}

function mynak_indexnow_site_host(): string
{
    if (defined('SITE_URL') && is_string(SITE_URL) && SITE_URL !== '') {
        $host = (string) parse_url(SITE_URL, PHP_URL_HOST);

        return $host !== '' ? $host : 'www.mynakliyat.com.tr';
    }

    return 'www.mynakliyat.com.tr';
}

function mynak_indexnow_base_url(): string
{
    return defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : 'https://www.mynakliyat.com.tr';
}

/**
 * @param list<string> $urls Tam URL listesi
 * @return array{ok:bool,message:string,results:array<string,mixed>}
 */
function mynak_indexnow_submit_urls(string $key, array $urls): array
{
    $urls = array_values(array_unique(array_filter(array_map('trim', $urls))));
    if ($key === '') {
        return ['ok' => false, 'message' => 'indexnow_key ayarlı değil.', 'results' => []];
    }
    if ($urls === []) {
        return ['ok' => false, 'message' => 'Bildirilecek URL yok.', 'results' => []];
    }
    if (count($urls) > 10000) {
        $urls = array_slice($urls, 0, 10000);
    }

    $base = mynak_indexnow_base_url();
    $host = mynak_indexnow_site_host();
    $payload = json_encode([
        'host' => $host,
        'key' => $key,
        'keyLocation' => $base . '/' . rawurlencode($key) . '.txt',
        'urlList' => $urls,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $engines = [
        'Bing' => 'https://api.indexnow.org/indexnow',
        'Yandex' => 'https://yandex.com/indexnow',
    ];

    $results = [];
    $anyOk = false;
    foreach ($engines as $name => $endpoint) {
        if (!function_exists('curl_init')) {
            $results[$name] = ['success' => false, 'http_code' => 0, 'error' => 'curl yok'];
            continue;
        }
        $ch = curl_init($endpoint);
        if ($ch === false) {
            continue;
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => 'MY-Nakliyat-IndexNow/1.0',
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $ok = $code >= 200 && $code < 300;
        if ($ok) {
            $anyOk = true;
        }
        $results[$name] = [
            'success' => $ok,
            'http_code' => $code,
            'body' => is_string($body) ? mb_substr($body, 0, 200) : '',
        ];
    }

    return [
        'ok' => $anyOk,
        'message' => $anyOk ? count($urls) . ' URL IndexNow ile bildirildi.' : 'IndexNow başarısız.',
        'results' => $results,
    ];
}

/**
 * Son N günde güncellenen yayın URL'leri.
 *
 * @return list<string>
 */
function mynak_indexnow_recent_public_urls(mysqli $conn, int $days = 7, int $max = 200): array
{
    $days = max(1, min(30, $days));
    $max = max(10, min(500, $max));
    $base = mynak_indexnow_base_url();
    $since = date('Y-m-d H:i:s', time() - ($days * 86400));
    $urls = [];

    $queries = [
        "SELECT slug FROM blog_posts WHERE durum = 3 AND updated_at >= '$since' ORDER BY updated_at DESC LIMIT $max",
        "SELECT slug FROM pages WHERE status = 1 AND updated_at >= '$since' ORDER BY updated_at DESC LIMIT $max",
        "SELECT slug FROM services WHERE status = 1 AND updated_at >= '$since' ORDER BY updated_at DESC LIMIT $max",
    ];
    foreach ($queries as $sql) {
        $r = $conn->query($sql);
        if (!$r) {
            continue;
        }
        while ($row = $r->fetch_assoc()) {
            $slug = trim((string) ($row['slug'] ?? ''));
            if ($slug !== '') {
                $urls[] = $base . '/' . $slug;
            }
        }
    }

    $urls[] = $base . '/';
    $urls[] = $base . '/sitemap-index.xml';

    return array_values(array_unique($urls));
}
