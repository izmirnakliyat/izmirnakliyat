<?php
require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function debug_log($msg) {
    $logDir = realpath(__DIR__ . '/../../logs');
    if ($logDir === false) {
        @mkdir(__DIR__ . '/../../logs', 0777, true);
        $logDir = realpath(__DIR__ . '/../../logs');
    }
    $logFile = ($logDir ? $logDir : __DIR__) . '/openai_debug.log';
    @file_put_contents($logFile, date('Y-m-d H:i:s') . "\n" . print_r($msg, true) . "\n\n", FILE_APPEND);
}

include '../includes/init.php';
include '../includes/auto_blog_functions.php';
header('Content-Type: application/json');

if (!isset($_POST['api_key']) || empty($_POST['api_key'])) {
    echo json_encode(['success' => false, 'message' => 'API anahtarı gerekli!']);
    exit;
}

$api_key = trim($_POST['api_key']);

// Basit bir test isteği gönder (cPanel sunucuları için optimize edildi)
$ch = curl_init('https://api.openai.com/v1/models');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60); // cPanel için daha uzun timeout
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); // cPanel için daha uzun bağlantı timeout
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // Maksimum yönlendirme sayısı
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1); // HTTP 1.1 kullan
curl_setopt($ch, CURLOPT_ENCODING, ''); // Tüm encoding'leri kabul et
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $api_key
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

debug_log(['test_connection' => [
    'http_code' => $http_code,
    'curl_error' => $curl_error,
    'response_length' => strlen($response),
    'response_preview' => substr($response, 0, 200)
]]);

if ($response === false) {
    echo json_encode(['success' => false, 'message' => 'cURL hatası: ' . $curl_error]);
    exit;
}

if ($http_code === 200) {
    $data = json_decode($response, true);
    if (isset($data['data']) && is_array($data['data'])) {
        echo json_encode(['success' => true, 'message' => 'Bağlantı başarılı! ' . count($data['data']) . ' model bulundu.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'API yanıtı beklenmeyen formatta.']);
    }
} elseif ($http_code === 401) {
    echo json_encode(['success' => false, 'message' => 'API anahtarı geçersiz veya süresi dolmuş.']);
} elseif ($http_code === 0) {
    echo json_encode(['success' => false, 'message' => 'Bağlantı hatası: Sunucu OpenAI API\'sine ulaşamıyor.']);
} else {
    echo json_encode(['success' => false, 'message' => 'HTTP ' . $http_code . ' hatası: ' . $response]);
} 