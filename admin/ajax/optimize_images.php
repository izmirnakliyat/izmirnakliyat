<?php
ini_set('display_errors', 0); // AJAX'ta error gösterme
error_reporting(0);

require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

header('Content-Type: application/json');

// Debug log fonksiyonu
function debug_log($message) {
    file_put_contents(__DIR__ . '/optimize_debug.log', date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

// Görsel optimizasyon fonksiyonu
function optimize_image($source_path, $quality = 80) {
    try {
        $info = getimagesize($source_path);
        if (!$info) {
            debug_log('getimagesize başarısız: ' . $source_path);
            return false;
        }
    
    $mime = $info['mime'];
    $original_size = filesize($source_path);
    
    // Kaynak görseli yükle
    $image = null;
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source_path);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source_path);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source_path);
            break;
        default:
            return false;
    }
    
    if (!$image) return false;
    
    // WebP formatında kaydet (destekleniyorsa)
    $webp_path = preg_replace('/\.[^.]+$/', '.webp', $source_path);
    if (function_exists('imagewebp')) {
        imagewebp($image, $webp_path, $quality);
    }
    
    // Orijinal formatı optimize et
    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($image, $source_path, $quality);
            break;
        case 'image/png':
            // PNG için kayıpsız optimizasyon
            imagepng($image, $source_path, 6);
            break;
        case 'image/gif':
            imagegif($image, $source_path);
            break;
    }
    
    imagedestroy($image);
    
    $new_size = filesize($source_path);
    $savings = $original_size - $new_size;
    
        return [
            'original_size' => $original_size,
            'new_size' => $new_size,
            'savings' => $savings,
            'webp_created' => file_exists($webp_path)
        ];
    } catch (Exception $e) {
        debug_log('optimize_image hatası: ' . $e->getMessage() . ' - ' . $source_path);
        return false;
    }
}

try {
    debug_log('Optimizasyon başladı');
    
    // GD extension kontrolü
    if (!extension_loaded('gd')) {
        throw new Exception('GD extension yüklü değil');
    }
    
    $total_savings = 0;
    $optimized_count = 0;
    $webp_count = 0;
    
    // Optimize edilecek dizinler
    $image_dirs = [
        '../../uploads/blog',
        '../../uploads/gallery',
        '../../uploads/services',
        '../../uploads/slides',
        '../../assets/img'
    ];
    
    debug_log('Dizinler tanımlandı: ' . implode(', ', $image_dirs));
    
    foreach ($image_dirs as $dir) {
        debug_log('Dizin kontrol ediliyor: ' . $dir);
        
        if (!is_dir($dir)) {
            debug_log('Dizin mevcut değil: ' . $dir);
            continue;
        }
        
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if (!$file->isFile()) continue;
                
                $extension = strtolower($file->getExtension());
                if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) continue;
                
                $file_path = $file->getPathname();
                $file_size = $file->getSize();
                
                debug_log('İşleniyor: ' . $file_path . ' (' . round($file_size/1024, 1) . 'KB)');
                
                // Sadece 50KB'dan büyük dosyaları optimize et
                if ($file_size < 50000) continue;
                
                $result = optimize_image($file_path, 85);
                if ($result) {
                    $total_savings += $result['savings'];
                    $optimized_count++;
                    if ($result['webp_created']) {
                        $webp_count++;
                    }
                    debug_log('Optimize edildi: ' . round($result['savings']/1024, 1) . 'KB tasarruf');
                }
            }
        } catch (Exception $e) {
            debug_log('Dizin okuma hatası: ' . $e->getMessage());
            continue;
        }
    }
    
    $savings_mb = round($total_savings / 1024 / 1024, 2);
    
    echo json_encode([
        'success' => true,
        'optimized_count' => $optimized_count,
        'webp_count' => $webp_count,
        'total_savings' => $total_savings,
        'savings' => $savings_mb . ' MB tasarruf sağlandı',
        'message' => "$optimized_count görsel optimize edildi, $webp_count WebP versiyonu oluşturuldu"
    ]);
    
} catch (Exception $e) {
    debug_log('Ana hata: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Optimizasyon sırasında hata: ' . $e->getMessage(),
        'debug' => 'Detaylar için optimize_debug.log dosyasını kontrol edin'
    ]);
}
?> 