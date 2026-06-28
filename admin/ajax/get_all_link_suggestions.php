<?php
/**
 * Tüm Orphan Sayfalar İçin Link Önerileri
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

$site_url = SITE_URL;

/**
 * İçerikten linkleri çıkaran fonksiyon
 */
function extractInternalLinks($content, $site_url) {
    $links = [];
    if (empty($content)) return $links;
    
    preg_match_all('/href=["\']([^"\']+)["\']/i', $content, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $link) {
            if (strpos($link, $site_url) !== false || 
                (strpos($link, 'http') === false && strpos($link, '//') !== 0)) {
                $link = str_replace($site_url, '', $link);
                $link = '/' . ltrim($link, '/');
                if ($link !== '/' && !in_array($link, $links)) {
                    $links[] = $link;
                }
            }
        }
    }
    
    return $links;
}

// Tüm içerikleri topla
$all_content = [];
$link_map = [];

// Blog yazıları
$blog_query = $conn->query("SELECT id, baslik, slug, icerik, meta_keywords FROM blog_posts");
if ($blog_query) {
    while ($row = $blog_query->fetch_assoc()) {
        $url = '/' . $row['slug'];
        $links = extractInternalLinks($row['icerik'], $site_url);
        
        $all_content[] = [
            'id' => $row['id'],
            'type' => 'blog',
            'title' => $row['baslik'],
            'slug' => $row['slug'],
            'url' => $url,
            'content' => $row['icerik'],
            'keywords' => $row['meta_keywords'] ?? ''
        ];
        
        $link_map[$url] = $links;
    }
}

// Sayfalar
$pages_query = $conn->query("SELECT id, title, slug, content, meta_keywords FROM pages");
if ($pages_query) {
    while ($row = $pages_query->fetch_assoc()) {
        $url = '/' . $row['slug'];
        $links = extractInternalLinks($row['content'], $site_url);
        
        $all_content[] = [
            'id' => $row['id'],
            'type' => 'page',
            'title' => $row['title'],
            'slug' => $row['slug'],
            'url' => $url,
            'content' => $row['content'],
            'keywords' => $row['meta_keywords'] ?? ''
        ];
        
        $link_map[$url] = $links;
    }
}

// Hizmetler
$services_query = $conn->query("SELECT id, ana_baslik, slug, aciklama, meta_keywords FROM services");
if ($services_query) {
    while ($row = $services_query->fetch_assoc()) {
        $slug = $row['slug'] ?? '';
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', 
                str_replace(['ı','ğ','ü','ş','ö','ç','İ','Ğ','Ü','Ş','Ö','Ç'], 
                            ['i','g','u','s','o','c','i','g','u','s','o','c'], $row['ana_baslik']))));
        }
        $url = '/hizmet/' . $slug;
        $links = extractInternalLinks($row['aciklama'] ?? '', $site_url);
        
        $all_content[] = [
            'id' => $row['id'],
            'type' => 'service',
            'title' => $row['ana_baslik'],
            'slug' => $slug,
            'url' => $url,
            'content' => $row['aciklama'] ?? '',
            'keywords' => $row['meta_keywords'] ?? ''
        ];
        
        $link_map[$url] = $links;
    }
}

// Orphan sayfaları bul (hiç link almayan)
$orphan_pages = [];
foreach ($all_content as $item) {
    $item_url = $item['url'];
    $has_incoming = false;
    
    foreach ($link_map as $source_url => $links) {
        if ($source_url !== $item_url) {
            foreach ($links as $link) {
                if ($link === $item_url || 
                    strpos($link, $item['slug']) !== false ||
                    $link === '/' . $item['slug']) {
                    $has_incoming = true;
                    break 2;
                }
            }
        }
    }
    
    if (!$has_incoming) {
        $orphan_pages[] = $item;
    }
}

if (empty($orphan_pages)) {
    echo json_encode([
        'success' => true,
        'message' => 'Orphan sayfa bulunamadı!',
        'results' => []
    ]);
    exit;
}

// Her orphan sayfa için öneri oluştur
$results = [];

foreach ($orphan_pages as $orphan) {
    // Anahtar kelimeler çıkar
    $title_words = preg_split('/\s+/', mb_strtolower($orphan['title']));
    $stopwords = ['ve', 'ile', 'için', 'de', 'da', 'bir', 'bu', 'ne', 'nasıl', 'neden', 'hakkında', 'olan', 'olarak', 'en', 'çok', 'az', 'her'];
    $title_keywords = array_filter($title_words, function($word) use ($stopwords) {
        return mb_strlen($word) > 2 && !in_array($word, $stopwords);
    });
    
    $orphan_keywords = array_filter(array_map('trim', explode(',', $orphan['keywords'])));
    $all_keywords = array_unique(array_merge($orphan_keywords, $title_keywords));
    
    // Diğer içeriklerle karşılaştır
    $suggestions = [];
    
    foreach ($all_content as $content) {
        // Kendisiyle karşılaştırma
        if ($content['url'] === $orphan['url']) continue;
        
        $score = 0;
        $content_title_lower = mb_strtolower($content['title']);
        $content_text_lower = mb_strtolower($content['content']);
        $content_keywords_lower = mb_strtolower($content['keywords']);
        
        foreach ($all_keywords as $keyword) {
            $keyword = mb_strtolower($keyword);
            if (mb_strlen($keyword) < 3) continue;
            
            if (strpos($content_title_lower, $keyword) !== false) {
                $score += 30;
            }
            if (strpos($content_text_lower, $keyword) !== false) {
                $score += 10;
            }
            if (strpos($content_keywords_lower, $keyword) !== false) {
                $score += 20;
            }
        }
        
        if ($score > 20) {
            $suggestions[] = [
                'id' => $content['id'],
                'type' => $content['type'],
                'title' => $content['title'],
                'url' => $content['url'],
                'score' => $score,
                'anchor_text' => generateAnchorText($orphan['title'], $all_keywords)
            ];
        }
    }
    
    // Skora göre sırala
    usort($suggestions, function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    $results[] = [
        'type' => $orphan['type'],
        'id' => $orphan['id'],
        'title' => $orphan['title'],
        'url' => $orphan['url'],
        'suggestions' => array_slice($suggestions, 0, 3)
    ];
}

echo json_encode([
    'success' => true,
    'results' => $results
]);

/**
 * Anchor text öner
 */
function generateAnchorText($title, $keywords) {
    foreach ($keywords as $keyword) {
        if (mb_strlen($keyword) > 3) {
            return $keyword;
        }
    }
    
    $words = explode(' ', $title);
    if (count($words) > 3) {
        return implode(' ', array_slice($words, 0, 3));
    }
    
    return $title;
}

