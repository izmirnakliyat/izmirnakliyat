<?php
require_once __DIR__ . '/includes/require_admin_web.php';

// TinyMCE için resim yükleme işleyicisi
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log dosyası
$log_file = '../logs/upload_debug.log';
if (!is_dir('../logs')) {
    mkdir('../logs', 0777, true);
}

// Upload klasörü oluştur
$upload_dir = '../uploads/editor/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Günlük dosyasına yaz
file_put_contents($log_file, date('[Y-m-d H:i:s] ') . "TinyMCE upload işlemi başladı\n", FILE_APPEND);

try {
    // İstek tipini kontrol et
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Yalnızca POST istekleri kabul edilir');
    }
    
    // Dosya kontrolü
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Dosya yüklenirken hata: ' . ($_FILES['file']['error'] ?? 'Dosya gönderilmedi'));
    }
    
    // Dosya tipini kontrol et
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($file_info, $_FILES['file']['tmp_name']);
    finfo_close($file_info);
    
    if (!in_array($mime_type, $allowed_types)) {
        throw new Exception('Geçersiz dosya tipi: ' . $mime_type);
    }
    
    // Uzantıyı belirle
    $extension = '';
    switch ($mime_type) {
        case 'image/jpeg':
            $extension = 'jpg';
            break;
        case 'image/png':
            $extension = 'png';
            break;
        case 'image/gif':
            $extension = 'gif';
            break;
        case 'image/webp':
            $extension = 'webp';
            break;
    }
    
    // Benzersiz dosya adı oluştur
    $filename = 'image_' . uniqid() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . "Dosya: {$filename}\n", FILE_APPEND);
    
    // Dosyayı kaydet
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $filepath)) {
        throw new Exception('Dosya taşınırken hata oluştu');
    }
    
    // Dosya izinlerini ayarla
    chmod($filepath, 0644);
    
    // URL oluştur
    $server_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    $url = $server_url . '/' . str_replace('../', '', $filepath);
    
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . "Başarılı! URL: {$url}\n", FILE_APPEND);
    
    // TinyMCE'nin beklediği yanıt formatı
    header('Content-Type: application/json');
    echo json_encode([
        'location' => $url
    ]);
    
} catch (Exception $e) {
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . "Hata: {$e->getMessage()}\n", FILE_APPEND);
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => [
            'message' => $e->getMessage()
        ]
    ]);
}
?> 