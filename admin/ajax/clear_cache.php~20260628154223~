<?php
require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

header('Content-Type: application/json');

try {
    $cleared_items = [];
    $total_cleared = 0;
    
    // CSS/JS cache'i temizle
    $cache_dirs = [
        '../../cache/css',
        '../../cache/js',
        '../../cache/html'
    ];
    
    foreach ($cache_dirs as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $total_cleared++;
                }
            }
            $cleared_items[] = basename($dir) . ' cache';
        }
    }
    
    // Browser cache'i temizlemek için .htaccess'e timestamp ekle
    $htaccess_path = '../../.htaccess';
    if (file_exists($htaccess_path)) {
        $content = file_get_contents($htaccess_path);
        $timestamp = time();
        
        // Cache buster ekle/güncelle
        $cache_buster = "\n# Cache Buster - " . date('Y-m-d H:i:s') . "\n";
        $cache_buster .= "<IfModule mod_rewrite.c>\n";
        $cache_buster .= "    RewriteRule ^assets/.*\\.(css|js)$ - [E=CACHE_VERSION:$timestamp]\n";
        $cache_buster .= "</IfModule>\n";
        
        // Eski cache buster'ı kaldır
        $content = preg_replace('/\n# Cache Buster.*?<\/IfModule>\n/s', '', $content);
        $content .= $cache_buster;
        
        file_put_contents($htaccess_path, $content);
        $cleared_items[] = 'browser cache headers';
    }
    
    // OPcache'i temizle (varsa)
    if (function_exists('opcache_reset')) {
        opcache_reset();
        $cleared_items[] = 'PHP OPcache';
    }
    
    // Geçici dosyaları temizle
    $temp_files = glob('../../uploads/temp/*');
    foreach ($temp_files as $file) {
        if (is_file($file) && time() - filemtime($file) > 3600) { // 1 saatten eski
            unlink($file);
            $total_cleared++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Önbellek başarıyla temizlendi!',
        'details' => 'Temizlenen: ' . implode(', ', $cleared_items),
        'files_cleared' => $total_cleared
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Önbellek temizlenirken hata oluştu: ' . $e->getMessage()
    ]);
}
?> 