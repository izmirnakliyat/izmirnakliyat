<?php
require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

header('Content-Type: application/json');

try {
    $secured_dirs = [];
    $upload_dirs = [
        '../../uploads',
        '../../uploads/blog',
        '../../uploads/gallery',
        '../../uploads/editor',
        '../../uploads/services',
        '../../uploads/slides',
        '../../uploads/content',
        '../../uploads/settings',
        '../../uploads/auto_blog_covers',
        '../../uploads/temp'
    ];

    $htaccess_content = "# Upload Güvenlik Kuralları
# PHP dosyalarının çalıştırılmasını engelle
<Files ~ \"\\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$\">
    Order Allow,Deny
    Deny from all
</Files>

# PHP engine'i kapat
php_flag engine off

# Script dosyalarını engelle
RemoveHandler .php .phtml .php3 .php4 .php5 .php6
RemoveType .php .phtml .php3 .php4 .php5 .php6

# Sadece güvenli dosya tiplerini allow et
<FilesMatch \"\\.(jpg|jpeg|png|gif|webp|avif|pdf|doc|docx|txt|zip)$\">
    Order Allow,Deny
    Allow from all
</FilesMatch>

# Directory browsing'i engelle
Options -Indexes

# Hassas dosyaları gizle
<FilesMatch \"\\.(log|sql|md|json|xml|config|ini|bak|old|tmp)$\">
    Order Allow,Deny
    Deny from all
</FilesMatch>
";

    foreach ($upload_dirs as $dir) {
        if (is_dir($dir)) {
            $htaccess_file = $dir . '/.htaccess';
            
            if (file_put_contents($htaccess_file, $htaccess_content)) {
                $secured_dirs[] = basename($dir);
            }
        } else {
            // Klasör yoksa oluştur ve güvenli hale getir
            if (mkdir($dir, 0755, true)) {
                file_put_contents($dir . '/.htaccess', $htaccess_content);
                $secured_dirs[] = basename($dir) . ' (yeni oluşturuldu)';
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Upload klasörleri güvenli hale getirildi',
        'secured_dirs' => $secured_dirs,
        'count' => count($secured_dirs)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?> 