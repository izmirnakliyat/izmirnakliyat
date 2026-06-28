<?php
/**
 * AI Destekli Internal Link Önerileri
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
$title = $input['title'] ?? '';

if (empty($type) || $id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz parametreler']);
    exit;
}

// Hedef içeriği al
$target_content = '';
$target_title = '';
$target_keywords = [];

switch ($type) {
    case 'blog':
        $stmt = $conn->prepare("SELECT baslik, icerik, meta_keywords FROM blog_posts WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $target_title = $row['baslik'];
            $target_content = strip_tags($row['icerik']);
            $target_keywords = array_filter(array_map('trim', explode(',', $row['meta_keywords'] ?? '')));
        }
        break;
        
    case 'page':
        $stmt = $conn->prepare("SELECT title, content, meta_keywords FROM pages WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $target_title = $row['title'];
            $target_content = strip_tags($row['content']);
            $target_keywords = array_filter(array_map('trim', explode(',', $row['meta_keywords'] ?? '')));
        }
        break;
        
    case 'service':
        $stmt = $conn->prepare("SELECT ana_baslik, aciklama, meta_keywords FROM services WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $target_title = $row['ana_baslik'];
            $target_content = strip_tags($row['aciklama'] ?? '');
            $target_keywords = array_filter(array_map('trim', explode(',', $row['meta_keywords'] ?? '')));
        }
        break;
}

// Başlıktan anahtar kelimeler çıkar
$title_words = preg_split('/\s+/', mb_strtolower($target_title));
$stopwords = ['ve', 'ile', 'için', 'de', 'da', 'bir', 'bu', 'ne', 'nasıl', 'neden', 'hakkında', 'olan', 'olarak', 'en', 'çok', 'az', 'her'];
$title_keywords = array_filter($title_words, function($word) use ($stopwords) {
    return mb_strlen($word) > 2 && !in_array($word, $stopwords);
});

$all_keywords = array_unique(array_merge($target_keywords, $title_keywords));

// Tüm diğer içerikleri al ve benzerlik hesapla
$suggestions = [];

// Blog yazıları
$blog_query = $conn->query("SELECT id, baslik, slug, icerik, meta_keywords FROM blog_posts WHERE id != " . ($type === 'blog' ? $id : 0));
if ($blog_query) {
    while ($row = $blog_query->fetch_assoc()) {
        $score = calculateRelevanceScore($row['baslik'], $row['icerik'], $row['meta_keywords'], $all_keywords, $target_title);
        if ($score > 0) {
            $suggestions[] = [
                'type' => 'blog',
                'id' => $row['id'],
                'title' => $row['baslik'],
                'url' => '/' . $row['slug'],
                'score' => $score,
                'anchor_text' => generateAnchorText($row['baslik'], $all_keywords),
                'reason' => generateReason($row['baslik'], $target_title, $score)
            ];
        }
    }
}

// Sayfalar
$pages_query = $conn->query("SELECT id, title, slug, content, meta_keywords FROM pages WHERE id != " . ($type === 'page' ? $id : 0));
if ($pages_query) {
    while ($row = $pages_query->fetch_assoc()) {
        $score = calculateRelevanceScore($row['title'], $row['content'], $row['meta_keywords'], $all_keywords, $target_title);
        if ($score > 0) {
            $suggestions[] = [
                'type' => 'page',
                'id' => $row['id'],
                'title' => $row['title'],
                'url' => '/' . $row['slug'],
                'score' => $score,
                'anchor_text' => generateAnchorText($row['title'], $all_keywords),
                'reason' => generateReason($row['title'], $target_title, $score)
            ];
        }
    }
}

// Hizmetler
$services_query = $conn->query("SELECT id, ana_baslik, slug, aciklama, meta_keywords FROM services WHERE id != " . ($type === 'service' ? $id : 0));
if ($services_query) {
    while ($row = $services_query->fetch_assoc()) {
        $score = calculateRelevanceScore($row['ana_baslik'], $row['aciklama'] ?? '', $row['meta_keywords'] ?? '', $all_keywords, $target_title);
        if ($score > 0) {
            $slug = $row['slug'] ?? strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', 
                str_replace(['ı','ğ','ü','ş','ö','ç'], ['i','g','u','s','o','c'], $row['ana_baslik']))));
            $suggestions[] = [
                'type' => 'service',
                'id' => $row['id'],
                'title' => $row['ana_baslik'],
                'url' => '/hizmet/' . $slug,
                'score' => $score,
                'anchor_text' => generateAnchorText($row['ana_baslik'], $all_keywords),
                'reason' => generateReason($row['ana_baslik'], $target_title, $score)
            ];
        }
    }
}

// Skora göre sırala ve ilk 5'i al
usort($suggestions, function($a, $b) {
    return $b['score'] - $a['score'];
});

$suggestions = array_slice($suggestions, 0, 5);

echo json_encode([
    'success' => true,
    'target_title' => $target_title,
    'suggestions' => $suggestions
]);

/**
 * İçerik benzerlik skoru hesapla
 */
function calculateRelevanceScore($title, $content, $keywords, $target_keywords, $target_title) {
    $score = 0;
    $title_lower = mb_strtolower($title);
    $content_lower = mb_strtolower($content);
    $keywords_lower = mb_strtolower($keywords);
    $target_title_lower = mb_strtolower($target_title);
    
    // Başlık kelime eşleşmesi
    foreach ($target_keywords as $keyword) {
        $keyword = mb_strtolower($keyword);
        if (mb_strlen($keyword) < 3) continue;
        
        if (strpos($title_lower, $keyword) !== false) {
            $score += 30;
        }
        if (strpos($content_lower, $keyword) !== false) {
            $score += 10;
        }
        if (strpos($keywords_lower, $keyword) !== false) {
            $score += 20;
        }
    }
    
    // Ortak kelimeler
    $title_words = preg_split('/\s+/', $title_lower);
    $target_words = preg_split('/\s+/', $target_title_lower);
    $common = array_intersect($title_words, $target_words);
    $score += count($common) * 15;
    
    return $score;
}

/**
 * Anchor text öner
 */
function generateAnchorText($title, $keywords) {
    // Önce anahtar kelimelerden birini kullan
    foreach ($keywords as $keyword) {
        if (mb_strlen($keyword) > 3 && stripos($title, $keyword) !== false) {
            return $keyword;
        }
    }
    
    // Başlığı kısalt
    $words = explode(' ', $title);
    if (count($words) > 4) {
        return implode(' ', array_slice($words, 0, 4));
    }
    
    return $title;
}

/**
 * Öneri sebebi oluştur
 */
function generateReason($title, $target_title, $score) {
    if ($score >= 60) {
        return "Bu içerik, \"{$target_title}\" ile yüksek ilişkili. Mutlaka link ekleyin.";
    } elseif ($score >= 30) {
        return "Orta düzeyde ilişkili içerik. Link eklemeniz önerilir.";
    } else {
        return "Konu benzerliği var, değerlendirebilirsiniz.";
    }
}

