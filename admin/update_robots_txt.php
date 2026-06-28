<?php
// Çıktı tamponlaması başlat - herhangi bir hatanın AJAX yanıtını bozmasını önler
ob_start();
require_once __DIR__ . '/includes/require_admin_web.php';

// Hata ayıklama için
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log dosyası oluştur
$log_file = __DIR__ . '/robots_debug.log';
file_put_contents($log_file, "Robots.txt güncelleme başlatıldı: " . date('Y-m-d H:i:s') . "\n");

file_put_contents($log_file, "Admin girişi doğrulandı\n", FILE_APPEND);

// AJAX isteği kontrolü
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
$response = ['status' => 'error', 'message' => 'Bir hata oluştu.'];

// robots.txt dosya yolu
$robots_path = $_SERVER['DOCUMENT_ROOT'] . '/robots.txt';
file_put_contents($log_file, "Robots.txt yolu: " . $robots_path . "\n", FILE_APPEND);

// Hedef robots.txt yolunun yazılabilir olup olmadığını kontrol et
$document_root_writeable = is_writable($_SERVER['DOCUMENT_ROOT']);
file_put_contents($log_file, "Document root yazılabilir: " . ($document_root_writeable ? "Evet" : "Hayır") . "\n", FILE_APPEND);

// Robots.txt içeriğini al
if (isset($_POST['robots_content'])) {
    $robots_content = $_POST['robots_content'];
    file_put_contents($log_file, "Robots.txt içeriği alındı, uzunluk: " . strlen($robots_content) . "\n", FILE_APPEND);
    
    try {
        // Robots.txt dosyasını kaydet
        $success = false;
        try {
            $success = file_put_contents($robots_path, $robots_content);
            file_put_contents($log_file, "Robots.txt dosyası yazdırma sonucu: " . ($success ? "Başarılı" : "Başarısız") . "\n", FILE_APPEND);
        } catch (Exception $e) {
            file_put_contents($log_file, "Hata: " . $e->getMessage() . "\n", FILE_APPEND);
        }
        
        if ($success) {
            $response = [
                'status' => 'success', 
                'message' => 'Robots.txt dosyası başarıyla güncellendi!'
            ];
        } else {
            // Alternatif bir yol dene - web dizininin içinde kaydet
            $alt_robots_path = __DIR__ . '/../robots.txt';
            file_put_contents($log_file, "Alternatif robots.txt yolu deneniyor: " . $alt_robots_path . "\n", FILE_APPEND);
            
            if (file_put_contents($alt_robots_path, $robots_content)) {
                $response = [
                    'status' => 'success', 
                    'message' => 'Robots.txt dosyası başarıyla güncellendi! Ancak root dizine yazma izni olmadığı için web dizinine kaydedildi.'
                ];
                file_put_contents($log_file, "Alternatif yol başarılı\n", FILE_APPEND);
            } else {
                throw new Exception("Robots.txt dosyası yazılamadı. Dosya izinlerini kontrol edin.");
            }
        }
    } catch (Exception $e) {
        file_put_contents($log_file, "Hata: " . $e->getMessage() . "\n", FILE_APPEND);
        $response = ['status' => 'error', 'message' => $e->getMessage()];
    }
} else {
    file_put_contents($log_file, "Robots.txt içeriği boş!\n", FILE_APPEND);
    $response = ['status' => 'error', 'message' => 'Robots.txt içeriği boş olamaz!'];
}

// Çıktı tamponunu temizle
ob_end_clean();

// Yanıt döndür
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    // Form submit yanıtı
    if ($response['status'] === 'success') {
        header("Location: seo_management.php?robots_updated=1&message=" . urlencode($response['message']));
    } else {
        header("Location: seo_management.php?robots_error=1&message=" . urlencode($response['message']));
    }
}
?> 