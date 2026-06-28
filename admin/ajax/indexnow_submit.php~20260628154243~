<?php
@set_time_limit(120);
require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

require_once '../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

$key  = trim($input['key'] ?? '');
$urls = $input['urls'] ?? [];
$type = $input['type'] ?? 'custom';

// Eğer sadece blog URL'leri isteniyorsa DB'den al
if ($type === 'blog' && empty($urls)) {
    $is_local = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false);
    $base = $is_local ? 'http://localhost/mynakliyat' : 'https://www.mynakliyat.com.tr';
    $res = $conn->query("SELECT slug FROM blog_posts WHERE durum = 3 ORDER BY updated_at DESC");
    while ($row = $res->fetch_assoc()) {
        $urls[] = $base . '/' . $row['slug'];
    }
    echo json_encode(['urls' => array_values($urls)]);
    exit;
}

if (empty($key)) {
    echo json_encode(['success' => false, 'message' => 'API anahtarı gerekli.']);
    exit;
}

if (empty($urls)) {
    echo json_encode(['success' => false, 'message' => 'Bildirilecek URL bulunamadı.']);
    exit;
}

// URL'leri temizle ve doğrula
$urls = array_filter(array_map('trim', (array)$urls));
$urls = array_values(array_unique($urls));

// Max 10.000 URL limiti
if (count($urls) > 10000) {
    $urls = array_slice($urls, 0, 10000);
}

// Site URL'yi belirle
$is_local = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false);
$site_host = $is_local ? 'localhost' : 'mynakliyat.com.tr';

// IndexNow endpoint'leri
$engines = [
    'Bing'    => 'https://api.indexnow.org/indexnow',
    'Yandex'  => 'https://yandex.com/indexnow',
    'Seznam'  => 'https://search.seznam.cz/indexnow',
    'Naver'   => 'https://searchadvisor.naver.com/indexnow',
];

// Payload
$payload = [
    'host'        => $site_host,
    'key'         => $key,
    'keyLocation' => ($is_local ? 'http://localhost/mynakliyat' : 'https://www.mynakliyat.com.tr') . '/' . $key . '.txt',
    'urlList'     => $urls,
];

$json_payload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$results = [];
$all_ok  = true;

foreach ($engines as $engine_name => $endpoint) {
    // Localhost'ta gerçek istekleri atla
    if ($is_local) {
        $results[$engine_name] = [
            'success' => true,
            'message' => 'Test modu (localhost) — gerçek gönderim yapılmadı.',
            'http_code' => 200,
        ];
        continue;
    }

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $json_payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json; charset=utf-8',
            'Host: ' . parse_url($endpoint, PHP_URL_HOST),
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; IndexNow-Bot/1.0)',
        CURLOPT_FOLLOWLOCATION => true,
    ]);

    $resp      = curl_exec($ch);
    $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    // IndexNow HTTP durum kodları
    if ($http_code === 200) {
        $msg = 'Kabul edildi (200 OK)';
        $ok  = true;
    } elseif ($http_code === 202) {
        $msg = 'İşlemde (202 Accepted)';
        $ok  = true;
    } elseif ($http_code === 400) {
        $msg = 'Geçersiz istek (400 Bad Request)';
        $ok  = false;
    } elseif ($http_code === 403) {
        $msg = 'API anahtarı doğrulanamadı (403 Forbidden)';
        $ok  = false;
    } elseif ($http_code === 422) {
        $msg = 'URL formatı hatalı (422)';
        $ok  = false;
    } elseif ($http_code === 429) {
        $msg = 'Çok fazla istek (429 Too Many Requests)';
        $ok  = false;
    } elseif ($http_code === 0) {
        $msg = 'Bağlantı hatası: ' . $curl_err;
        $ok  = false;
    } else {
        $msg = 'HTTP ' . $http_code;
        $ok  = ($http_code >= 200 && $http_code < 300);
    }

    if (!$ok) $all_ok = false;

    $results[$engine_name] = [
        'success'   => $ok,
        'message'   => $msg,
        'http_code' => $http_code,
    ];
}

// Özet mesaj
$success_count = count(array_filter($results, fn($r) => $r['success']));
$total = count($results);
$summary = count($urls) . ' URL, ' . $success_count . '/' . $total . ' motor başarılı.';

echo json_encode([
    'success' => $all_ok,
    'message' => $summary,
    'results' => $results,
    'url_count' => count($urls),
], JSON_UNESCAPED_UNICODE);
