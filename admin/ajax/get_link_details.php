<?php
/**
 * Internal Link Detayları
 */

// EN BAŞTA - Hata çıktısını engelle
error_reporting(0);
ini_set('display_errors', '0');

// Output buffering - config.php'deki olası çıktıları yakala
ob_start();

require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

// Config'den sonra tekrar kapat (config açmış olabilir)
error_reporting(0);
ini_set('display_errors', '0');

// Buffer'daki her şeyi temizle
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$type = $input['type'] ?? '';
$id = intval($input['id'] ?? 0);

if (empty($type) || $id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz parametreler']);
    exit;
}

$site_url = SITE_URL;

// İçeriği al
$content = '';
$slug = '';
$title = '';

switch ($type) {
    case 'blog':
        $stmt = $conn->prepare("SELECT baslik, slug, icerik FROM blog_posts WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $title = $row['baslik'];
            $slug = $row['slug'];
            $content = $row['icerik'];
        }
        $url = '/' . $slug;
        break;
        
    case 'page':
        $stmt = $conn->prepare("SELECT title, slug, content FROM pages WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $title = $row['title'];
            $slug = $row['slug'];
            $content = $row['content'];
        }
        $url = '/' . $slug;
        break;
        
    case 'service':
        $stmt = $conn->prepare("SELECT ana_baslik, slug, aciklama FROM services WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $title = $row['ana_baslik'];
            $slug = $row['slug'] ?? '';
            $content = $row['aciklama'] ?? '';
        }
        $url = '/hizmet/' . $slug;
        break;
}

// Giden linkleri bul
$outgoing = [];
preg_match_all('/href=["\']([^"\']+)["\']/i', $content, $matches);
if (!empty($matches[1])) {
    foreach ($matches[1] as $link) {
        if (strpos($link, $site_url) !== false || 
            (strpos($link, 'http') === false && strpos($link, '//') !== 0 && strpos($link, '#') !== 0 && strpos($link, 'mailto:') !== 0)) {
            $link = str_replace($site_url, '', $link);
            $link = '/' . ltrim($link, '/');
            if (!in_array($link, $outgoing)) {
                $outgoing[] = $link;
            }
        }
    }
}

// Gelen linkleri bul
$incoming = [];

// Blog yazılarından
$blog_query = $conn->query("SELECT id, baslik, slug, icerik FROM blog_posts");
while ($row = $blog_query->fetch_assoc()) {
    if (strpos($row['icerik'], $slug) !== false || strpos($row['icerik'], $url) !== false) {
        $incoming[] = [
            'type' => 'Blog',
            'title' => $row['baslik'],
            'url' => '/' . $row['slug']
        ];
    }
}

// Sayfalardan
$pages_query = $conn->query("SELECT id, title, slug, content FROM pages");
while ($row = $pages_query->fetch_assoc()) {
    if (strpos($row['content'], $slug) !== false || strpos($row['content'], $url) !== false) {
        $incoming[] = [
            'type' => 'Sayfa',
            'title' => $row['title'],
            'url' => '/' . $row['slug']
        ];
    }
}

// Hizmetlerden
$services_query = $conn->query("SELECT id, ana_baslik, slug, aciklama FROM services");
if ($services_query) {
    while ($row = $services_query->fetch_assoc()) {
        $aciklama = $row['aciklama'] ?? '';
        if (strpos($aciklama, $slug) !== false || strpos($aciklama, $url) !== false) {
            $incoming[] = [
                'type' => 'Hizmet',
                'title' => $row['ana_baslik'],
                'url' => '/hizmet/' . ($row['slug'] ?? '')
            ];
        }
    }
}

echo json_encode([
    'success' => true,
    'title' => $title,
    'url' => $url,
    'incoming' => $incoming,
    'outgoing' => $outgoing
]);

