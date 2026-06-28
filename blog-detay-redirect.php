<?php
/**
 * Blog Detay 301 Redirect
 * Eski blog-detay.php?id=X linklerini yeni /blog/slug yapısına yönlendirir
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Blog ID'sini al
$blog_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($blog_id <= 0) {
    header('Location: ' . mynak_abs_url_from_public_path(mynak_public_path('')), true, 301);
    exit;
}

// Blog yazısını veritabanından getir
$stmt = $conn->prepare("SELECT slug, baslik FROM blog_posts WHERE id = ? AND durum = 3");
$stmt->bind_param("i", $blog_id);
$stmt->execute();
$rows = mysqli_stmt_fetch_all_assoc($stmt);
$stmt->close();

if ($rows === []) {
    // Blog bulunamadı - 404 sayfasına yönlendir
    header('HTTP/1.1 404 Not Found');
    echo '<h1>404 - Blog Yazısı Bulunamadı</h1>';
    echo '<p>Aradığınız blog yazısı artık mevcut değil.</p>';
    echo '<a href="' . htmlspecialchars(mynak_blog_href_path(''), ENT_QUOTES, 'UTF-8') . '">Blog Ana Sayfasına Dön</a>';
    exit;
}

$blog = $rows[0];

// Slug kontrolü
if (empty($blog['slug'])) {
    // Slug yoksa başlıktan oluştur
    $slug = create_seo_slug($blog['baslik']);
    
    // Slug'ı veritabanına kaydet
    $update_stmt = $conn->prepare("UPDATE blog_posts SET slug = ? WHERE id = ?");
    $update_stmt->bind_param("si", $slug, $blog_id);
    $update_stmt->execute();
} else {
    $slug = $blog['slug'];
}

// Kanonik yazı URL'si kök slug ile (slug-router); /blog/slug değil
header('Location: ' . mynak_abs_url_from_public_path(mynak_public_path($slug)), true, 301);
exit;

/**
 * SEO dostu slug oluşturma fonksiyonu
 */
function create_seo_slug($string) {
    $string = mb_strtolower($string, 'UTF-8');
    
    // Türkçe karakterleri dönüştür
    $replace = array(
        'ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'İ' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u',
        'Ç' => 'c', 'Ğ' => 'g', 'Ö' => 'o', 'Ş' => 's', 'Ü' => 'u'
    );
    $string = strtr($string, $replace);
    
    // Özel karakterleri temizle
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    $string = trim($string, '-');
    
    return $string;
}
?> 