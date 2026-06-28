<?php
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

header('Content-Type: application/json');

try {
    $response = [
        'success' => false,
        'message' => '',
        'optimized_count' => 0,
        'webp_count' => 0,
        'total_savings' => 0,
        'savings' => '0 MB'
    ];
    
    // GD extension kontrolü
    if (!extension_loaded('gd')) {
        throw new Exception('GD extension bulunamadı');
    }
    
    $total_savings = 0;
    $optimized_count = 0;
    $webp_count = 0;
    
    // Optimize edilecek dizinler
    $image_dirs = [
        '../../uploads/blog',
        '../../uploads/gallery',
        '../../uploads/services',
        '../../uploads/slides'
    ];
    
    foreach ($image_dirs as $dir) {
        if (!is_dir($dir)) continue;
        
        // Glob ile daha güvenli dosya listeleme
        $patterns = [
            $dir . '/*.jpg',
            $dir . '/*.jpeg', 
            $dir . '/*.png',
            $dir . '/*.gif'
        ];
        
        foreach ($patterns as $pattern) {
            $files = glob($pattern);
            
            foreach ($files as $file_path) {
                if (!is_file($file_path)) continue;
                
                $file_size = filesize($file_path);
                
                // Sadece 20KB'dan büyük dosyaları optimize et
                if ($file_size < 20480) continue; // 20KB
                
                $original_size = $file_size;
                
                // Basit optimizasyon
                $result = simple_optimize_image($file_path);
                
                if ($result) {
                    $new_size = filesize($file_path);
                    $savings = $original_size - $new_size;
                    
                    if ($savings > 0) {
                        $total_savings += $savings;
                        $optimized_count++;
                    }
                    
                    // WebP oluştur (varsa)
                    $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $file_path);
                    if (function_exists('imagewebp') && create_webp($file_path, $webp_path)) {
                        $webp_count++;
                    }
                }
                
                // Memory yönetimi
                if (memory_get_usage() > (400 * 1024 * 1024)) { // 400MB
                    break 3; // Tüm döngülerden çık
                }
            }
        }
    }
    
    $savings_mb = round($total_savings / 1024 / 1024, 2);
    
    $response = [
        'success' => true,
        'optimized_count' => $optimized_count,
        'webp_count' => $webp_count,
        'total_savings' => $total_savings,
        'savings' => $savings_mb . ' MB tasarruf sağlandı',
        'message' => "$optimized_count görsel optimize edildi, $webp_count WebP versiyonu oluşturuldu"
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ];
}

echo json_encode($response);

// Basit optimizasyon fonksiyonu
function simple_optimize_image($file_path) {
    try {
        $info = getimagesize($file_path);
        if (!$info) return false;
        
        $image = null;
        
        switch ($info['mime']) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($file_path);
                if ($image) {
                    imagejpeg($image, $file_path, 75); // 75% kalite (daha agresif)
                }
                break;
                
            case 'image/png':
                $image = imagecreatefrompng($file_path);
                if ($image) {
                    imagepng($image, $file_path, 9); // Compression level 9 (maksimum)
                }
                break;
                
            case 'image/gif':
                $image = imagecreatefromgif($file_path);
                if ($image) {
                    imagegif($image, $file_path);
                }
                break;
        }
        
        if ($image) {
            imagedestroy($image);
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        return false;
    }
}

// WebP oluşturma fonksiyonu
function create_webp($source_path, $webp_path) {
    try {
        if (!function_exists('imagewebp')) return false;
        
        $info = getimagesize($source_path);
        if (!$info) return false;
        
        $image = null;
        
        switch ($info['mime']) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($source_path);
                break;
            case 'image/png':
                $image = imagecreatefrompng($source_path);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($source_path);
                break;
        }
        
        if ($image) {
            $result = imagewebp($image, $webp_path, 80);
            imagedestroy($image);
            return $result;
        }
        
        return false;
        
    } catch (Exception $e) {
        return false;
    }
}
?> 