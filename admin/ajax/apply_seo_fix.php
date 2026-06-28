<?php
/**
 * SEO Düzeltmelerini Uygula
 * Skor hesaplaması seo_detector.php ile BİREBİR AYNI
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

header('Content-Type: application/json; charset=utf-8');

// Türkçe kelime sayma fonksiyonu
function countTurkishWords($text) {
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    if (empty($text)) return 0;
    $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    return count($words);
}

// POST verilerini al
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

$type = $input['type'] ?? '';
$id = intval($input['id'] ?? 0);

if (empty($type) || $id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz parametreler']);
    exit;
}

// Form değerlerini al
$seo_title = trim($input['seo_title'] ?? '');
$meta_description = trim($input['meta_description'] ?? '');
$meta_keywords = trim($input['meta_keywords'] ?? '');
$focus_keyword = trim($input['focus_keyword'] ?? '');
$og_title = trim($input['og_title'] ?? '') ?: $seo_title;
$og_description = trim($input['og_description'] ?? '') ?: $meta_description;
$h1_title = trim($input['h1_title'] ?? '');
$content_html = trim($input['content'] ?? '');

// Veritabanını güncelle
$success = false;

switch ($type) {
    case 'blog':
        $sql = "UPDATE blog_posts SET 
            seo_title = ?, 
            meta_description = ?, 
            meta_keywords = ?, 
            og_title = ?, 
            og_description = ?, 
            focus_keyword = ?";
        
        $types = "ssssss";
        $params = [$seo_title, $meta_description, $meta_keywords, $og_title, $og_description, $focus_keyword];
        
        if (!empty($h1_title)) {
            $sql .= ", baslik = ?";
            $types .= "s";
            $params[] = $h1_title;
        }
        if (!empty($content_html)) {
            $sql .= ", icerik = ?";
            $types .= "s";
            $params[] = $content_html;
        }
        
        $sql .= " WHERE id = ?";
        $types .= "i";
        $params[] = $id;
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $success = $stmt->execute();
        break;

    case 'page':
        $sql = "UPDATE pages SET 
            seo_title = ?, 
            meta_description = ?, 
            meta_keywords = ?, 
            og_title = ?, 
            og_description = ?, 
            focus_keyword = ?";
        
        $types = "ssssss";
        $params = [$seo_title, $meta_description, $meta_keywords, $og_title, $og_description, $focus_keyword];
        
        if (!empty($h1_title)) {
            $sql .= ", title = ?";
            $types .= "s";
            $params[] = $h1_title;
        }
        if (!empty($content_html)) {
            $sql .= ", content = ?";
            $types .= "s";
            $params[] = $content_html;
        }
        
        $sql .= " WHERE id = ?";
        $types .= "i";
        $params[] = $id;
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $success = $stmt->execute();
        break;

    case 'service':
        $col_check = $conn->query("SHOW COLUMNS FROM services LIKE 'seo_title'");
        if (!$col_check || $col_check->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'SEO alanları henüz eklenmemiş.']);
            exit;
        }
        
        $sql = "UPDATE services SET 
            seo_title = ?, 
            meta_description = ?, 
            meta_keywords = ?, 
            focus_keyword = ?";
        
        $types = "ssss";
        $params = [$seo_title, $meta_description, $meta_keywords, $focus_keyword];
        
        if (!empty($h1_title)) {
            $sql .= ", ana_baslik = ?";
            $types .= "s";
            $params[] = $h1_title;
        }
        if (!empty($content_html)) {
            $sql .= ", aciklama = ?";
            $types .= "s";
            $params[] = $content_html;
        }
        
        $sql .= " WHERE id = ?";
        $types .= "i";
        $params[] = $id;
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $success = $stmt->execute();
        break;
}

if ($success) {
    // Güncellenmiş veriyi al ve skoru hesapla (seo_detector.php ile AYNI mantık)
    $score = calculateSeoScore($conn, $type, $id);
    
    // Skoru güncelle
    $score_table = $type === 'blog' ? 'blog_posts' : ($type === 'page' ? 'pages' : 'services');
    $conn->query("UPDATE {$score_table} SET seo_score = {$score} WHERE id = {$id}");
    
    echo json_encode([
        'success' => true,
        'message' => 'SEO bilgileri başarıyla güncellendi',
        'score' => $score
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Güncelleme sırasında hata: ' . $conn->error
    ]);
}

/**
 * SEO Skoru Hesapla - seo_detector.php analyzeSeoIssues ile BİREBİR AYNI
 */
function calculateSeoScore($conn, $type, $id) {
    $score = 0;
    $item = null;
    
    switch ($type) {
        case 'blog':
            $stmt = $conn->prepare("SELECT baslik, slug, seo_title, meta_description, meta_keywords, focus_keyword, kapak_foto, icerik FROM blog_posts WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $item = $stmt->get_result()->fetch_assoc();
            $title = $item['baslik'] ?? '';
            $content = $item['icerik'] ?? '';
            break;
        case 'page':
            $stmt = $conn->prepare("SELECT title, slug, seo_title, meta_description, meta_keywords, focus_keyword, content FROM pages WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $item = $stmt->get_result()->fetch_assoc();
            $title = $item['title'] ?? '';
            $content = $item['content'] ?? '';
            break;
        case 'service':
            $stmt = $conn->prepare("SELECT ana_baslik as title, slug, seo_title, meta_description, meta_keywords, focus_keyword, aciklama as content FROM services WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $item = $stmt->get_result()->fetch_assoc();
            $title = $item['title'] ?? '';
            $content = $item['content'] ?? '';
            break;
    }
    
    if (!$item) return 0;
    
    // 1. Başlık (30-70 karakter): +15 puan
    $title_len = mb_strlen($title);
    if ($title_len >= 30 && $title_len <= 70) {
        $score += 15;
    }
    
    // 2. SEO Title (30-60 karakter): +10 puan
    if (!empty($item['seo_title'])) {
        $seo_len = mb_strlen($item['seo_title']);
        if ($seo_len >= 30 && $seo_len <= 60) {
            $score += 10;
        }
    }
    
    // 3. Meta Description: ideal (120-160) +20, kısa/uzun +10
    if (!empty($item['meta_description'])) {
        $desc_len = mb_strlen($item['meta_description']);
        if ($desc_len >= 120 && $desc_len <= 160) {
            $score += 20;
        } elseif ($desc_len > 0) {
            $score += 10;
        }
    }
    
    // 4. Meta Keywords: +5 puan
    if (!empty($item['meta_keywords'])) {
        $score += 5;
    }
    
    // 5. Focus Keyword: +10 puan
    if (!empty($item['focus_keyword'])) {
        $score += 10;
        // 6. Focus Keyword başlıkta: +10 puan
        if (stripos($title, $item['focus_keyword']) !== false) {
            $score += 10;
        }
    }
    
    // 7. Slug: +5 puan
    if (!empty($item['slug'])) {
        $score += 5;
    }
    
    // 8. İçerik uzunluğu: 300+ kelime +15, 100+ kelime +8
    if (!empty($content)) {
        $word_count = countTurkishWords($content);
        if ($word_count >= 300) {
            $score += 15;
        } elseif ($word_count >= 100) {
            $score += 8;
        }
    }
    
    // 9. Kapak fotoğrafı (sadece blog): +10 puan
    if ($type === 'blog' && !empty($item['kapak_foto'])) {
        $score += 10;
    }
    
    // Sayfa ve servis için max 90 puan (kapak foto yok), blog için 100
    $maxScore = ($type === 'blog') ? 100 : 90;
    
    // Yüzdeye çevir
    $percentage = round(($score / $maxScore) * 100);
    
    return min($percentage, 100);
}
