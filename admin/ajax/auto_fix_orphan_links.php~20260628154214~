<?php
/**
 * Orphan Sayfalar için Otomatik Link Düzeltme
 * AI kullanarak orphan sayfalara otomatik internal link ekler
 */

// EN BAŞTA - Hata çıktısını engelle
error_reporting(0);
ini_set('display_errors', '0');

// Output buffering - config.php'deki olası çıktıları yakala
ob_start();

header('Content-Type: application/json; charset=utf-8');

// Config ve DB yükle
require_once '../../config/config.php';
require_once '../../config/db.php';

// Config'den sonra tekrar kapat (config açmış olabilir)
error_reporting(0);
ini_set('display_errors', '0');

// Tüm buffer'ı temizle
ob_end_clean();

// Veritabanı bağlantısını kontrol et
if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantısı kurulamadı'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action === 'start') {
        // Tüm orphan sayfaları bul
        $orphan_pages = getOrphanPages($conn);
        
        echo json_encode([
            'success' => true,
            'orphan_pages' => $orphan_pages,
            'total' => count($orphan_pages)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if ($action === 'fix') {
        $type = $input['type'] ?? '';
        $id = (int)($input['id'] ?? 0);
        $title = $input['title'] ?? '';
        $url = $input['url'] ?? '';
        
        if (empty($type) || empty($id) || empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Eksik parametreler'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Bu orphan sayfaya link eklenebilecek kaynak sayfaları bul ve düzelt
        $result = fixOrphanPage($conn, $type, $id, $title, $url);
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Geçersiz action'], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
    echo json_encode(['success' => false, 'message' => 'PHP Hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

/**
 * Tüm orphan sayfaları bul (hiçbir yerden link almayan)
 */
function getOrphanPages($conn) {
    $site_url = SITE_URL;
    $all_content = [];
    $link_map = [];
    
    // Blog yazıları
    $blog_query = $conn->query("SELECT id, baslik, slug, icerik FROM blog_posts WHERE durum = 3");
    if ($blog_query) {
        while ($row = $blog_query->fetch_assoc()) {
            $url = '/' . $row['slug'];
            $links = extractInternalLinks($row['icerik'], $site_url);
            
            $all_content[] = [
                'id' => $row['id'],
                'type' => 'blog',
                'title' => $row['baslik'],
                'slug' => $row['slug'],
                'url' => $url
            ];
            
            $link_map[$url] = $links;
        }
    }
    
    // Sayfalar
    $pages_query = $conn->query("SELECT id, title, slug, content FROM pages WHERE status = 1");
    if ($pages_query) {
        while ($row = $pages_query->fetch_assoc()) {
            $url = '/' . $row['slug'];
            $links = extractInternalLinks($row['content'], $site_url);
            
            $all_content[] = [
                'id' => $row['id'],
                'type' => 'page',
                'title' => $row['title'],
                'slug' => $row['slug'],
                'url' => $url
            ];
            
            $link_map[$url] = $links;
        }
    }
    
    // Hizmetler
    $services_query = $conn->query("SELECT id, ana_baslik, slug, aciklama FROM services WHERE status = 1");
    if ($services_query) {
        while ($row = $services_query->fetch_assoc()) {
            $slug = $row['slug'] ?? '';
            if (empty($slug)) {
                $slug = createSlug($row['ana_baslik']);
            }
            $url = '/hizmet/' . $slug;
            $links = extractInternalLinks($row['aciklama'] ?? '', $site_url);
            
            $all_content[] = [
                'id' => $row['id'],
                'type' => 'service',
                'title' => $row['ana_baslik'],
                'slug' => $slug,
                'url' => $url
            ];
            
            $link_map[$url] = $links;
        }
    }
    
    // Orphan sayfaları bul
    $orphan_pages = [];
    foreach ($all_content as $item) {
        $has_incoming = false;
        foreach ($link_map as $source_url => $links) {
            if ($source_url !== $item['url']) {
                foreach ($links as $link) {
                    if ($link === $item['url'] || 
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
    
    return $orphan_pages;
}

/**
 * Orphan sayfaya link ekle
 */
function fixOrphanPage($conn, $type, $id, $title, $url) {
    // Bu sayfa için en uygun kaynak sayfaları bul
    $sources = findBestSources($conn, $type, $id, $title);
    
    if (empty($sources)) {
        return ['success' => true, 'links_added' => 0, 'message' => 'Uygun kaynak bulunamadı'];
    }
    
    $links_added = 0;
    $added_sources = [];
    
    // İlk 2 kaynağa link ekle
    foreach (array_slice($sources, 0, 2) as $source) {
        $result = addLinkToContent($conn, $source, $title, $url);
        if ($result) {
            $links_added++;
            $added_sources[] = $source['title'];
        }
    }
    
    return [
        'success' => true,
        'links_added' => $links_added,
        'sources' => $added_sources
    ];
}

/**
 * Orphan sayfa için en uygun kaynak sayfaları bul
 */
function findBestSources($conn, $target_type, $target_id, $target_title) {
    $sources = [];
    $target_keywords = extractKeywords($target_title);
    
    // Blog yazılarını kontrol et
    $blog_query = $conn->query("SELECT id, baslik, slug, icerik FROM blog_posts WHERE durum = 3");
    if ($blog_query) {
        while ($row = $blog_query->fetch_assoc()) {
            // Kendisi değilse
            if ($target_type === 'blog' && $row['id'] == $target_id) continue;
            
            $score = calculateRelevanceScore($target_title, $target_keywords, $row['baslik'], $row['icerik']);
            if ($score > 0) {
                $sources[] = [
                    'id' => $row['id'],
                    'type' => 'blog',
                    'title' => $row['baslik'],
                    'slug' => $row['slug'],
                    'content' => $row['icerik'],
                    'score' => $score
                ];
            }
        }
    }
    
    // Sayfaları kontrol et
    $pages_query = $conn->query("SELECT id, title, slug, content FROM pages WHERE status = 1");
    if ($pages_query) {
        while ($row = $pages_query->fetch_assoc()) {
            if ($target_type === 'page' && $row['id'] == $target_id) continue;
            
            $score = calculateRelevanceScore($target_title, $target_keywords, $row['title'], $row['content']);
            if ($score > 0) {
                $sources[] = [
                    'id' => $row['id'],
                    'type' => 'page',
                    'title' => $row['title'],
                    'slug' => $row['slug'],
                    'content' => $row['content'],
                    'score' => $score
                ];
            }
        }
    }
    
    // Hizmetleri kontrol et
    $services_query = $conn->query("SELECT id, ana_baslik, slug, aciklama FROM services WHERE status = 1");
    if ($services_query) {
        while ($row = $services_query->fetch_assoc()) {
            if ($target_type === 'service' && $row['id'] == $target_id) continue;
            
            $slug = $row['slug'] ?? createSlug($row['ana_baslik']);
            $score = calculateRelevanceScore($target_title, $target_keywords, $row['ana_baslik'], $row['aciklama'] ?? '');
            if ($score > 0) {
                $sources[] = [
                    'id' => $row['id'],
                    'type' => 'service',
                    'title' => $row['ana_baslik'],
                    'slug' => $slug,
                    'content' => $row['aciklama'] ?? '',
                    'score' => $score
                ];
            }
        }
    }
    
    // Skora göre sırala
    usort($sources, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    
    return $sources;
}

/**
 * İçerik benzerlik skoru hesapla
 */
function calculateRelevanceScore($target_title, $target_keywords, $source_title, $source_content) {
    $score = 0;
    $source_text = strtolower($source_title . ' ' . strip_tags($source_content));
    $target_title_lower = strtolower($target_title);
    
    // Başlık eşleşmesi
    if (stripos($source_text, $target_title_lower) !== false) {
        $score += 50;
    }
    
    // Anahtar kelime eşleşmeleri
    foreach ($target_keywords as $keyword) {
        if (strlen($keyword) >= 3) {
            $keyword_lower = strtolower($keyword);
            $count = substr_count($source_text, $keyword_lower);
            $score += min($count * 5, 30); // Max 30 puan per keyword
        }
    }
    
    // İçerik uzunluğu bonusu (daha uzun içeriklere link eklemek daha kolay)
    $content_length = strlen($source_content);
    if ($content_length > 500) $score += 10;
    if ($content_length > 1000) $score += 10;
    if ($content_length > 2000) $score += 10;
    
    return $score;
}

/**
 * Başlıktan anahtar kelimeler çıkar
 */
function extractKeywords($title) {
    $stop_words = ['ve', 'ile', 'için', 'bir', 'bu', 'da', 'de', 'mi', 'mı', 'ne', 'ya', 'en', 'çok', 'daha', 'nasıl', 'neden', 'hangi'];
    
    $words = preg_split('/[\s\-_,.:;]+/', $title);
    $keywords = [];
    
    foreach ($words as $word) {
        $word = trim(strtolower($word));
        if (strlen($word) >= 3 && !in_array($word, $stop_words)) {
            $keywords[] = $word;
        }
    }
    
    return $keywords;
}

/**
 * Kaynak içeriğe link ekle
 */
function addLinkToContent($conn, $source, $target_title, $target_url) {
    $content = $source['content'];
    
    if (empty($content)) {
        return false;
    }
    
    // İçerikte zaten bu linki kontrol et
    if (stripos($content, $target_url) !== false || stripos($content, 'href="' . $target_url) !== false) {
        return false;
    }
    
    // Anchor text belirle
    $anchor_text = generateAnchorText($target_title);
    
    // Link HTML
    $link_html = '<a href="' . htmlspecialchars($target_url) . '">' . htmlspecialchars($anchor_text) . '</a>';
    
    // İçerikte uygun bir yer bul ve link ekle
    $new_content = insertLinkIntoContent($content, $link_html, $anchor_text, $target_title);
    
    if ($new_content === $content) {
        // Uygun yer bulunamadı, sonuna ekle
        if (preg_match('/<\/p>\s*$/i', $content)) {
            // Son </p>'den önce ekle
            $new_content = preg_replace('/(<\/p>)\s*$/i', ' Ayrıca ' . $link_html . ' konusuna da göz atabilirsiniz.$1', $content);
        } else {
            $new_content = $content . '<p>Ayrıca ' . $link_html . ' konusuna da göz atabilirsiniz.</p>';
        }
    }
    
    // Veritabanını güncelle
    return updateSourceContent($conn, $source['type'], $source['id'], $new_content);
}

/**
 * Anchor text oluştur
 */
function generateAnchorText($title) {
    // Başlığı kısalt ve temizle
    $anchor = $title;
    
    // Çok uzunsa kısalt
    if (mb_strlen($anchor) > 50) {
        $anchor = mb_substr($anchor, 0, 47) . '...';
    }
    
    return $anchor;
}

/**
 * İçerikte uygun yere link ekle
 */
function insertLinkIntoContent($content, $link_html, $anchor_text, $target_title) {
    $keywords = extractKeywords($target_title);
    
    // En uzun eşleşen kelimeyi bul
    foreach ($keywords as $keyword) {
        if (strlen($keyword) < 4) continue;
        
        // Kelimeyi içerikte ara (link içinde değilse)
        $pattern = '/(?<!href=["\'])(?<![>])(' . preg_quote($keyword, '/') . ')(?![^<]*<\/a>)(?![^<]*>)/iu';
        
        if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            // İlk eşleşmeyi linkle değiştir
            $found_word = $matches[1][0];
            $pos = $matches[1][1];
            
            // Sadece ilk geçişi değiştir
            $replacement = '<a href="' . htmlspecialchars($target_title === $anchor_text ? '' : '') . '">' . $found_word . '</a>';
            
            // Daha iyi bir yaklaşım: kelimeyi içeren cümlenin sonuna link ekle
            $sentence_end = strpos($content, '.', $pos);
            if ($sentence_end !== false && ($sentence_end - $pos) < 200) {
                $before = substr($content, 0, $sentence_end);
                $after = substr($content, $sentence_end);
                return $before . ' (' . $link_html . ')' . $after;
            }
        }
    }
    
    return $content;
}

/**
 * Kaynak içeriği güncelle
 */
function updateSourceContent($conn, $type, $id, $new_content) {
    switch ($type) {
        case 'blog':
            $stmt = $conn->prepare("UPDATE blog_posts SET icerik = ? WHERE id = ?");
            break;
        case 'page':
            $stmt = $conn->prepare("UPDATE pages SET content = ? WHERE id = ?");
            break;
        case 'service':
            $stmt = $conn->prepare("UPDATE services SET aciklama = ? WHERE id = ?");
            break;
        default:
            return false;
    }
    
    if (!$stmt) return false;
    
    $stmt->bind_param("si", $new_content, $id);
    return $stmt->execute();
}

/**
 * İçerikten internal linkleri çıkar
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

/**
 * Slug oluştur
 */
function createSlug($text) {
    $text = str_replace(
        ['ı','ğ','ü','ş','ö','ç','İ','Ğ','Ü','Ş','Ö','Ç'],
        ['i','g','u','s','o','c','i','g','u','s','o','c'],
        $text
    );
    $text = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text)));
    return trim($text, '-');
}

