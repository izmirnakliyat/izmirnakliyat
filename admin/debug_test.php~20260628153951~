<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/environment.php';

if (!mynak_allow_dev_debug_tools()) {
    http_response_code(404);
    exit('Not found.');
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Sunucu Debug Bilgileri</h1>";

echo "<h2>PHP Bilgileri</h2>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Memory Limit:</strong> " . ini_get('memory_limit') . "</p>";
echo "<p><strong>Max Execution Time:</strong> " . ini_get('max_execution_time') . "</p>";
echo "<p><strong>Post Max Size:</strong> " . ini_get('post_max_size') . "</p>";
echo "<p><strong>Upload Max Filesize:</strong> " . ini_get('upload_max_filesize') . "</p>";

echo "<h2>Extension Kontrolleri</h2>";
echo "<p><strong>cURL:</strong> " . (function_exists('curl_init') ? '✅ Kurulu' : '❌ Kurulu Değil') . "</p>";
echo "<p><strong>OpenSSL:</strong> " . (extension_loaded('openssl') ? '✅ Kurulu' : '❌ Kurulu Değil') . "</p>";
echo "<p><strong>JSON:</strong> " . (extension_loaded('json') ? '✅ Kurulu' : '❌ Kurulu Değil') . "</p>";
echo "<p><strong>MySQLi:</strong> " . (extension_loaded('mysqli') ? '✅ Kurulu' : '❌ Kurulu Değil') . "</p>";

echo "<h2>cURL Test</h2>";
if (function_exists('curl_init')) {
    $ch = curl_init('https://httpbin.org/get');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($response === false) {
        echo "<p><strong>cURL Test:</strong> ❌ Hata: " . $curl_error . "</p>";
    } else {
        echo "<p><strong>cURL Test:</strong> ✅ Başarılı (HTTP " . $http_code . ")</p>";
    }
} else {
    echo "<p><strong>cURL Test:</strong> ❌ cURL kurulu değil</p>";
}

echo "<h2>OpenAI API Test</h2>";
if (function_exists('curl_init')) {
    $ch = curl_init('https://api.openai.com/v1/models');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer sk-test123'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($response === false) {
        echo "<p><strong>OpenAI API Test:</strong> ❌ Bağlantı Hatası: " . $curl_error . "</p>";
    } else {
        echo "<p><strong>OpenAI API Test:</strong> ✅ Bağlantı Başarılı (HTTP " . $http_code . ")</p>";
        if ($http_code === 401) {
            echo "<p><em>Not: 401 hatası normal, çünkü test API anahtarı kullandık</em></p>";
        }
    }
} else {
    echo "<p><strong>OpenAI API Test:</strong> ❌ cURL kurulu değil</p>";
}

echo "<h2>Dosya İzinleri</h2>";
$log_dir = '../logs';
$upload_dir = '../uploads';

echo "<p><strong>Logs Klasörü:</strong> " . (is_writable($log_dir) ? '✅ Yazılabilir' : '❌ Yazılamaz') . "</p>";
echo "<p><strong>Uploads Klasörü:</strong> " . (is_writable($upload_dir) ? '✅ Yazılabilir' : '❌ Yazılamaz') . "</p>";

echo "<h2>Sunucu Bilgileri</h2>";
echo "<p><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Bilinmiyor') . "</p>";
echo "<p><strong>Server Name:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'Bilinmiyor') . "</p>";
echo "<p><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Bilinmiyor') . "</p>";

echo "<h2>Test Sonucu</h2>";
if (function_exists('curl_init') && extension_loaded('openssl') && extension_loaded('json')) {
    echo "<p style='color: green; font-weight: bold;'>✅ Sunucu ayarları uygun görünüyor!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Sunucu ayarlarında eksiklikler var!</p>";
}
?> 