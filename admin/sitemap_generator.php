<?php
// Çıktı tamponlaması başlat - herhangi bir hatanın AJAX yanıtını bozmasını önler
ob_start();
require_once __DIR__ . '/includes/require_admin_web.php';

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// AJAX isteği kontrolü
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
$response = ['status' => 'error', 'message' => 'Bir hata oluştu.'];

// Her zaman JSON döndür - 404 hatasını önlemek için
header('Content-Type: application/json');

// Kanonik site kökü ve proje kökü (config ile generate_full_sitemap.php ile aynı)
$site_url = rtrim(SITE_URL, '/');
$projectRoot = dirname(__DIR__);
$sitemap_path = $projectRoot . '/sitemap.xml';
$image_sitemap_path = $projectRoot . '/image-sitemap.xml';
$sitemap_index_path = $projectRoot . '/sitemap-index.xml';

require_once $projectRoot . '/includes/sitemap_build.php';

// SEO dostu slug oluşturma fonksiyonu
function create_seo_slug($text) {
    // Türkçe karakterleri dönüştür
    $turkish = array('ç', 'ğ', 'ı', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'I', 'İ', 'Ö', 'Ş', 'Ü');
    $english = array('c', 'g', 'i', 'o', 's', 'u', 'c', 'g', 'i', 'i', 'o', 's', 'u');
    $text = str_replace($turkish, $english, $text);
    
    // Diğer işlemler
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    
    return $text;
}

// Parametreleri al - Varsayılanları true yap eğer POST verisi yoksa
$include_pages = !isset($_POST['include_pages']) || $_POST['include_pages'] == '1';
$include_blog = !isset($_POST['include_blog']) || $_POST['include_blog'] == '1';
$include_categories = !isset($_POST['include_categories']) || $_POST['include_categories'] == '1';
$include_images = isset($_POST['include_images']) && $_POST['include_images'] == '1';
$priority_homepage = isset($_POST['priority_homepage']) ? floatval($_POST['priority_homepage']) : 1.0;
$priority_other = isset($_POST['priority_other']) ? floatval($_POST['priority_other']) : 0.7;

try {
    $built = sitemap_build_main_urlset($conn, $site_url, [
        'include_pages' => $include_pages,
        'include_blog' => $include_blog,
        'include_blog_categories' => $include_categories,
        'include_services' => true,
        'include_static' => true,
        'include_llm' => true,
    ]);
    $sitemap_content = $built['xml'];
    $url_count = $built['url_count'];

    $success = file_put_contents($sitemap_path, $sitemap_content);
    @file_put_contents($sitemap_index_path, sitemap_build_index_xml($site_url, $projectRoot));

    // Görsel sitemap isteniyorsa üret
    if ($include_images) {
        $image_sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $image_sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . PHP_EOL;
        $image_sitemap .= '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . PHP_EOL;
        // Blog görselleri
        $imgRes = $conn->query("SELECT slug, baslik, kapak_foto FROM blog_posts WHERE durum = 3 AND kapak_foto IS NOT NULL AND kapak_foto != '' ORDER BY created_at DESC");
        if ($imgRes) {
            while ($row = $imgRes->fetch_assoc()) {
                $image_sitemap .= '  <url>' . PHP_EOL;
                $image_sitemap .= '    <loc>' . htmlspecialchars($site_url . '/' . (!empty($row['slug']) ? $row['slug'] : create_seo_slug($row['baslik']))) . '</loc>' . PHP_EOL;
                $image_sitemap .= '    <image:image>' . PHP_EOL;
                $image_sitemap .= '      <image:loc>' . htmlspecialchars($site_url . '/uploads/blog/' . $row['kapak_foto']) . '</image:loc>' . PHP_EOL;
                $image_sitemap .= '      <image:title>' . htmlspecialchars($row['baslik']) . '</image:title>' . PHP_EOL;
                $image_sitemap .= '    </image:image>' . PHP_EOL;
                $image_sitemap .= '  </url>' . PHP_EOL;
            }
        }
        $image_sitemap .= '</urlset>';
        @file_put_contents($image_sitemap_path, $image_sitemap);
        @file_put_contents($sitemap_index_path, sitemap_build_index_xml($site_url, $projectRoot));
    }

    if ($success !== false) {
        $response = [
            'status' => 'success',
            'message' => "Sitemap başarıyla oluşturuldu! Toplam {$url_count} URL eklendi.",
            'url_count' => $url_count,
            'file_size' => strlen($sitemap_content)
        ];
    } else {
        $response = [
            'status' => 'error',
            'message' => 'Sitemap dosyası yazılamadı. Dosya izinlerini kontrol edin.'
        ];
    }
    
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => 'Sitemap oluşturulurken hata: ' . $e->getMessage()
    ];
}

// Tamponu temizle ve JSON yanıtını gönder
ob_end_clean();
echo json_encode($response);
?> 