<?php
/**
 * AI ile SEO İçeriği Oluşturma
 * OpenAI API kullanarak SEO metinleri üretir
 */

// Output buffering başlat - herhangi bir çıktıyı yakala
ob_start();

// Hata raporlamayı kapat (JSON çıktısını bozmasın)
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

header('Content-Type: application/json; charset=utf-8');

// Global hata yakalayıcı
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'PHP Hatası: ' . $errstr]);
    exit;
});

// POST verilerini al
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

$type = $input['type'] ?? '';
$id = intval($input['id'] ?? 0);
$action = $input['action'] ?? 'generate';
$title = $input['title'] ?? '';
$content = $input['content'] ?? '';
$focus_keyword = $input['focus_keyword'] ?? '';

// OpenAI API anahtarını al
$api_key = '';

// seo_settings tablosundan kontrol et (tablo varsa)
$table_check = $conn->query("SHOW TABLES LIKE 'seo_settings'");
if ($table_check && $table_check->num_rows > 0) {
    $api_result = $conn->query("SELECT setting_value FROM seo_settings WHERE setting_key = 'openai_api_key'");
    if ($api_result && $api_result->num_rows > 0) {
        $api_key = $api_result->fetch_assoc()['setting_value'];
    }
}

// Eğer settings tablosunda varsa oradan da kontrol et
if (empty($api_key)) {
    $settings_check = $conn->query("SHOW TABLES LIKE 'settings'");
    if ($settings_check && $settings_check->num_rows > 0) {
        $api_result = $conn->query("SELECT value FROM settings WHERE name = 'openai_api_key'");
        if ($api_result && $api_result->num_rows > 0) {
            $api_key = $api_result->fetch_assoc()['value'];
        }
    }
}

// Türkçe kelime sayma fonksiyonu
function countTurkishWords($text) {
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text); // Birden fazla boşluğu teke indir
    $text = trim($text);
    if (empty($text)) return 0;
    $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    return count($words);
}

// Ek bilgiler için değişkenler
$word_count = 0;
$has_image = false;
$has_slug = false;

// Eğer ID verilmişse içeriği veritabanından al
if ($id > 0 && !empty($type)) {
    switch ($type) {
        case 'blog':
            $stmt = $conn->prepare("SELECT baslik, icerik, focus_keyword, slug, kapak_foto FROM blog_posts WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $title = $row['baslik'];
                $content = strip_tags($row['icerik']);
                $focus_keyword = $focus_keyword ?: $row['focus_keyword'];
                $has_slug = !empty($row['slug']);
                $has_image = !empty($row['kapak_foto']);
                $word_count = countTurkishWords($row['icerik']);
            }
            break;

        case 'page':
            $stmt = $conn->prepare("SELECT title, content, focus_keyword, slug FROM pages WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $title = $row['title'];
                $content = strip_tags($row['content']);
                $focus_keyword = $focus_keyword ?: $row['focus_keyword'];
                $has_slug = !empty($row['slug']);
                $word_count = countTurkishWords($row['content']);
            }
            break;

        case 'service':
            // Önce focus_keyword sütununun var olup olmadığını kontrol et
            $col_check = $conn->query("SHOW COLUMNS FROM services LIKE 'focus_keyword'");
            $has_focus = ($col_check && $col_check->num_rows > 0);

            if ($has_focus) {
                $stmt = $conn->prepare("SELECT ana_baslik, aciklama, focus_keyword, slug FROM services WHERE id = ?");
            } else {
                $stmt = $conn->prepare("SELECT ana_baslik, aciklama, slug FROM services WHERE id = ?");
            }
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $title = $row['ana_baslik'];
                $content = $row['aciklama'] ?? '';
                $has_slug = !empty($row['slug']);
                $word_count = countTurkishWords($content);
                if ($has_focus) {
                    $focus_keyword = $focus_keyword ?: ($row['focus_keyword'] ?? '');
                }
            }
            break;
    }
}

if (empty($title)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Başlık bulunamadı']);
    exit;
}

// İçeriği kısalt (token limiti için)
$content = mb_substr($content, 0, 2000);

// Buffered çıktıyı temizle
ob_end_clean();

// Eğer API anahtarı yoksa basit SEO önerileri oluştur
if (empty($api_key)) {
    $seo_data = generateBasicSeo($title, $content, $focus_keyword);
} else {
    $seo_data = generateAISeo($api_key, $title, $content, $focus_keyword);
}

// Eğer action generate_and_apply ise direkt kaydet
if ($action === 'generate_and_apply' && $seo_data['success'] && $id > 0) {
    $save_result = saveSeoDataOptimized($conn, $type, $id, $seo_data, $title);
    if ($save_result) {
        $seo_data['score'] = $save_result['score'];
        $seo_data['message'] = 'SEO başarıyla güncellendi';
    } else {
        $seo_data['message'] = 'SEO içeriği oluşturuldu ama kaydedilemedi';
    }
}

// Ek bilgileri ekle
$seo_data['word_count'] = $word_count;
$seo_data['has_image'] = $has_image;
$seo_data['has_slug'] = $has_slug;

echo json_encode($seo_data);

/**
 * Optimize edilmiş SEO kaydetme - H1 dahil
 */
function saveSeoDataOptimized($conn, $type, $id, $data, $original_title) {
    $seo_title = $data['seo_title'] ?? '';
    $meta_description = $data['meta_description'] ?? '';
    $meta_keywords = $data['meta_keywords'] ?? '';
    $og_title = $data['og_title'] ?? $seo_title;
    $og_description = $data['og_description'] ?? $meta_description;
    $focus_keyword = $data['focus_keyword'] ?? '';
    $h1_title = $data['h1_title'] ?? '';
    
    $success = false;
    
    switch ($type) {
        case 'blog':
            $sql = "UPDATE blog_posts SET seo_title = ?, meta_description = ?, meta_keywords = ?, og_title = ?, og_description = ?, focus_keyword = ?";
            $types = "ssssss";
            $params = [$seo_title, $meta_description, $meta_keywords, $og_title, $og_description, $focus_keyword];
            
            if (!empty($h1_title)) {
                $sql .= ", baslik = ?";
                $types .= "s";
                $params[] = $h1_title;
            }
            
            $sql .= " WHERE id = ?";
            $types .= "i";
            $params[] = $id;
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $success = $stmt->execute();
            break;
            
        case 'page':
            $sql = "UPDATE pages SET seo_title = ?, meta_description = ?, meta_keywords = ?, og_title = ?, og_description = ?, focus_keyword = ?";
            $types = "ssssss";
            $params = [$seo_title, $meta_description, $meta_keywords, $og_title, $og_description, $focus_keyword];
            
            if (!empty($h1_title)) {
                $sql .= ", title = ?";
                $types .= "s";
                $params[] = $h1_title;
            }
            
            $sql .= " WHERE id = ?";
            $types .= "i";
            $params[] = $id;
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $success = $stmt->execute();
            break;
            
        case 'service':
            $sql = "UPDATE services SET seo_title = ?, meta_description = ?, meta_keywords = ?, focus_keyword = ?";
            $types = "ssss";
            $params = [$seo_title, $meta_description, $meta_keywords, $focus_keyword];
            
            if (!empty($h1_title)) {
                $sql .= ", ana_baslik = ?";
                $types .= "s";
                $params[] = $h1_title;
            }
            
            $sql .= " WHERE id = ?";
            $types .= "i";
            $params[] = $id;
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $success = $stmt->execute();
            break;
    }
    
    if (!$success) return false;
    
    // Skoru hesapla ve güncelle
    $score = calculateScore($conn, $type, $id);
    
    $score_table = $type === 'blog' ? 'blog_posts' : ($type === 'page' ? 'pages' : 'services');
    $conn->query("UPDATE {$score_table} SET seo_score = {$score} WHERE id = {$id}");
    
    return ['success' => true, 'score' => $score];
}

/**
 * Skor hesapla
 */
function calculateScore($conn, $type, $id) {
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
    
    // H1 (30-70): +15
    $title_len = mb_strlen($title);
    if ($title_len >= 30 && $title_len <= 70) $score += 15;
    
    // SEO Title (30-60): +10
    if (!empty($item['seo_title'])) {
        $seo_len = mb_strlen($item['seo_title']);
        if ($seo_len >= 30 && $seo_len <= 60) $score += 10;
    }
    
    // Meta Desc: +20 ideal, +10 diğer
    if (!empty($item['meta_description'])) {
        $desc_len = mb_strlen($item['meta_description']);
        if ($desc_len >= 120 && $desc_len <= 160) $score += 20;
        elseif ($desc_len > 0) $score += 10;
    }
    
    // Keywords: +5
    if (!empty($item['meta_keywords'])) $score += 5;
    
    // Focus: +10, H1'de +10
    if (!empty($item['focus_keyword'])) {
        $score += 10;
        if (stripos($title, $item['focus_keyword']) !== false) $score += 10;
    }
    
    // Slug: +5
    if (!empty($item['slug'])) $score += 5;
    
    // İçerik: +15 (300+) veya +8 (100+)
    if (!empty($content)) {
        $word_count = countTurkishWords($content);
        if ($word_count >= 300) $score += 15;
        elseif ($word_count >= 100) $score += 8;
    }
    
    // Kapak foto (blog): +10
    if ($type === 'blog' && !empty($item['kapak_foto'])) $score += 10;
    
    // Yüzdeye çevir
    $maxScore = ($type === 'blog') ? 100 : 90;
    $percentage = round(($score / $maxScore) * 100);
    
    return min($percentage, 100);
}

/**
 * Basit SEO içeriği oluşturma (API olmadan)
 */
function generateBasicSeo($title, $content, $focus_keyword)
{
    // SEO başlık - minimum 30, maksimum 60 karakter olmalı
    $seo_title = $title;

    // Başlık çok kısa ise uzat (minimum 30 karakter)
    if (mb_strlen($seo_title) < 30) {
        $suffixes = [
            ' | İzmir Profesyonel Hizmet',
            ' | MY Nakliyat Güvencesiyle',
            ' Hizmeti | Sigortalı Taşımacılık',
            ' | Hemen Teklif Alın',
            ' | Güvenli ve Ekonomik Çözüm'
        ];

        foreach ($suffixes as $suffix) {
            $new_title = $seo_title . $suffix;
            if (mb_strlen($new_title) >= 30 && mb_strlen($new_title) <= 60) {
                $seo_title = $new_title;
                break;
            }
        }

        // Hala kısa ise zorla uzat
        if (mb_strlen($seo_title) < 30) {
            $seo_title = $seo_title . ' | İzmir MY Nakliyat Profesyonel Hizmet';
        }
    }

    // Başlık çok uzun ise kısalt
    if (mb_strlen($seo_title) > 60) {
        $seo_title = mb_substr($seo_title, 0, 57) . '...';
    }

    // Basit meta description oluştur - minimum 120, maksimum 160 karakter olmalı
    $meta_description = '';
    $clean_content = strip_tags($content);

    if (!empty($clean_content)) {
        // İlk cümleleri al
        $sentences = preg_split('/[.!?]+/', $clean_content, -1, PREG_SPLIT_NO_EMPTY);
        if (!empty($sentences[0])) {
            $meta_description = trim($sentences[0]);

            // Minimum 120 karakter olana kadar cümle ekle
            $i = 1;
            while (mb_strlen($meta_description) < 120 && isset($sentences[$i])) {
                $next_sentence = trim($sentences[$i]);
                if (!empty($next_sentence)) {
                    $temp = $meta_description . '. ' . $next_sentence;
                    if (mb_strlen($temp) <= 160) {
                        $meta_description = $temp;
                    } else {
                        break;
                    }
                }
                $i++;
            }

            // Hala kısa ise genişlet
            if (mb_strlen($meta_description) < 120) {
                $meta_description .= '. Profesyonel hizmet ve uygun fiyat garantisiyle yanınızdayız. Hemen iletişime geçin.';
            }

            // Çok uzun ise kısalt
            if (mb_strlen($meta_description) > 160) {
                $meta_description = mb_substr($meta_description, 0, 157) . '...';
            }
        }
    }

    // Boş veya çok kısa ise varsayılan oluştur
    if (empty($meta_description) || mb_strlen($meta_description) < 120) {
        $meta_description = $title . ' hakkında detaylı bilgi almak için sayfamızı ziyaret edin. İzmir ve çevresinde profesyonel, sigortalı ve ekonomik hizmet sunuyoruz.';
        if (mb_strlen($meta_description) > 160) {
            $meta_description = mb_substr($meta_description, 0, 157) . '...';
        }
    }

    // Anahtar kelimeler
    $keywords = [];
    if (!empty($focus_keyword)) {
        $keywords[] = $focus_keyword;
    }

    // Başlıktan kelimeler çıkar
    $title_words = preg_split('/\s+/', mb_strtolower($title));
    $stopwords = ['ve', 'ile', 'için', 'de', 'da', 'bir', 'bu', 'ne', 'nasıl', 'neden', 'hakkında', 'olan', 'olarak', 'en', 'çok'];
    foreach ($title_words as $word) {
        if (mb_strlen($word) > 3 && !in_array($word, $stopwords) && !in_array($word, $keywords)) {
            $keywords[] = $word;
        }
        if (count($keywords) >= 8)
            break;
    }

    // Focus keyword belirlenmemişse en uzun kelimeyi seç
    $final_focus = $focus_keyword;
    if (empty($final_focus) && !empty($keywords)) {
        usort($keywords, function ($a, $b) {
            return mb_strlen($b) - mb_strlen($a);
        });
        $final_focus = $keywords[0];
    }

    // H1 Başlık oluştur (30-70 karakter arası ve focus keyword içermeli)
    $h1_title = $title;
    
    // Eğer başlık çok kısa ise uzat
    if (mb_strlen($h1_title) < 30) {
        $h1_suffixes = [
            ' - Profesyonel Hizmet',
            ' | İzmir Bölgesi',
            ' Hizmetleri',
            ' - Güvenilir Çözüm'
        ];
        foreach ($h1_suffixes as $suffix) {
            if (mb_strlen($h1_title . $suffix) <= 70) {
                $h1_title .= $suffix;
                if (mb_strlen($h1_title) >= 30) break;
            }
        }
    }
    
    // Eğer başlık çok uzun ise kısalt
    if (mb_strlen($h1_title) > 70) {
        $h1_title = mb_substr($h1_title, 0, 67) . '...';
    }
    
    // Focus keyword başlıkta yoksa eklemeye çalış
    if (!empty($final_focus) && stripos($h1_title, $final_focus) === false) {
        $temp = $final_focus . ' - ' . $h1_title;
        if (mb_strlen($temp) <= 70) {
            $h1_title = $temp;
        } else {
            $temp = $h1_title . ' | ' . $final_focus;
            if (mb_strlen($temp) <= 70) {
                $h1_title = $temp;
            }
        }
    }

    return [
        'success' => true,
        'seo_title' => $seo_title,
        'meta_description' => $meta_description,
        'meta_keywords' => implode(', ', $keywords),
        'og_title' => $seo_title,
        'og_description' => $meta_description,
        'focus_keyword' => $final_focus,
        'h1_title' => $h1_title,
        'method' => 'basic'
    ];
}

/**
 * OpenAI API ile SEO içeriği oluşturma
 */
function generateAISeo($api_key, $title, $content, $focus_keyword)
{
    // Kısa başlık için ek bağlam oluştur
    $title_note = "";
    if (mb_strlen($title) < 30) {
        $title_note = "\n\n⚠️ ÖNEMLİ: Mevcut başlık çok kısa ({$title}). SEO başlığını mutlaka 30-60 karakter arasında olacak şekilde, markayı veya hizmeti vurgulayarak UZAT.";
    }

    // Prompt'u daha geniş kapsamlı hale getir
    $prompt = "Aşağıdaki içerik için kapsamlı bir SEO iyileştirmesi yap. Hem meta etiketleri, hem H1 başlığı, hem de içerik metnini iyileştirmen gerekiyor.

    Giriş Bilgileri:
    Mevcut Başlık: {$title}
    Mevcut İçerik Özeti: " . mb_substr($content, 0, 800) . "
    " . ($focus_keyword ? "Odak Anahtar Kelime: {$focus_keyword}" : "") . "
    {$title_note}

    🛑 KESİN KURALLAR (Bu kurallara uyulmazsa işlem başarısız sayılır):
    1. seo_title: Tam olarak 35 ile 55 karakter arasında OLMALI. Başlık ilgi çekici olmalı.
    2. meta_description: Tam olarak 125 ile 155 karakter arasında OLMALI. Harekete geçirici mesaj içermeli.
    3. focus_keyword: Eğer verildiyse, seo_title içinde MUTLAKA geçmeli.
    4. meta_keywords: İçerik ile en alakalı 5-8 kelime seçilmeli.
    5. h1_title: Sayfanın ana başlığı (H1). 30-70 karakter arasında, açıklayıcı ve SEO uyumlu olmalı.
    6. content: MUHTEŞEM BİR İÇERİK OLMALI.
       - Mevcut meta_keywords ve focus_keyword kelimelerini temel alarak yazılmalı.
       - Asla boş, anlamsız veya kendini tekrar eden cümleler olmamalı.
       - Okuyucuya gerçekten değer katan, bilgilendirici ve profesyonel bir dil kullanılmalı.
       - MİNİMUM 300 kelime olmalı.
       - HTML etiketleri (p, h2, ul, li, strong vb.) kullanılarak zenginleştirilmeli.
       - Paragraflar kısa ve okunabilir olmalı.

    Yanıt sadece şu JSON formatında olmalı:
    {
        \"seo_title\": \"...\",
        \"meta_description\": \"...\",
        \"meta_keywords\": \"...\",
        \"focus_keyword\": \"...\",
        \"h1_title\": \"...\",
        \"content\": \"...\"
    }";

    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Sen uzman bir SEO editörüsün. Hem teknik SEO (meta tags) hem de içerik yazarlığı (copywriting) konusunda uzmansın. Türkçe içerik üretiyorsun.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.7, // İçerik üretimi için biraz yaratıcılık lazım
        'max_tokens' => 2000 // İçerik uzun olacağı için token limitini artırdım
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ],
        CURLOPT_TIMEOUT => 60 // Süreyi uzattım
    ]);

    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // DEBUG: Log response
    file_put_contents('../ai_debug.log', date('[Y-m-d H:i:s] ') . "Raw Response: " . $response . "\n", FILE_APPEND);

    if ($curl_error || $http_code !== 200) {
        file_put_contents('../ai_debug.log', date('[Y-m-d H:i:s] ') . "CURL Error: " . $curl_error . " Code: " . $http_code . "\n", FILE_APPEND);
        return generateBasicSeo($title, $content, $focus_keyword);
    }

    $result = json_decode($response, true);

    if (!isset($result['choices'][0]['message']['content'])) {
        file_put_contents('../ai_debug.log', date('[Y-m-d H:i:s] ') . "No Choice Content\n", FILE_APPEND);
        return generateBasicSeo($title, $content, $focus_keyword);
    }

    $ai_content = $result['choices'][0]['message']['content'];

    // JSON temizle
    $ai_content = preg_replace('/```json\s*/', '', $ai_content);
    $ai_content = preg_replace('/```\s*/', '', $ai_content);
    $ai_content = trim($ai_content);

    // DEBUG: Log parsed content
    file_put_contents('../ai_debug.log', date('[Y-m-d H:i:s] ') . "Parsed AI Content: " . $ai_content . "\n", FILE_APPEND);

    $seo_data = json_decode($ai_content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        file_put_contents('../ai_debug.log', date('[Y-m-d H:i:s] ') . "JSON Parse Error: " . json_last_error_msg() . "\n", FILE_APPEND);
        return generateBasicSeo($title, $content, $focus_keyword);
    }

    // --- PHP Tarafında Katı Doğrulama ve Düzeltme ---

    // 1. SEO Title Doğrulama (30-60 karakter)
    $final_title = $seo_data['seo_title'] ?? $title;

    // Başlık çok uzunsa kelime bazlı kısalt
    if (mb_strlen($final_title) > 60) {
        $words = explode(' ', $final_title);
        while (mb_strlen(implode(' ', $words)) > 57) { // ... için pay bırak
            array_pop($words);
        }
        $final_title = implode(' ', $words) . '...';
    }

    // Başlık çok kısaysa doldur
    if (mb_strlen($final_title) < 30) {
        $suffixes = [' | MY Nakliyat', ' - İzmir', ' Hizmetleri', ' Fiyatları'];
        foreach ($suffixes as $s) {
            if (mb_strlen($final_title . $s) <= 60) {
                $final_title .= $s;
            }
            if (mb_strlen($final_title) >= 30)
                break;
        }
    }

    // Focus keyword kontrolü (Title içinde var mı?)
    if (!empty($focus_keyword) && stripos($final_title, $focus_keyword) === false) {
        // Yer varsa ekle
        if (mb_strlen($focus_keyword . ' ' . $final_title) <= 60) {
            $final_title = $focus_keyword . ' ' . $final_title;
        } elseif (mb_strlen($final_title . ' | ' . $focus_keyword) <= 60) {
            $final_title = $final_title . ' | ' . $focus_keyword;
        }
    }

    // 2. Meta Description Doğrulama (120-160 karakter)
    $final_desc = $seo_data['meta_description'] ?? '';

    // Çok uzunsa cümle sonunda kesmeye çalış
    if (mb_strlen($final_desc) > 160) {
        $final_desc = mb_substr($final_desc, 0, 157); // Sert kesim
        $last_space = mb_strrpos($final_desc, ' ');
        if ($last_space !== false) {
            $final_desc = mb_substr($final_desc, 0, $last_space);
        }
        $final_desc .= '...';
    }

    // Çok kısaysa doldur
    if (mb_strlen($final_desc) < 120) {
        $fillers = [
            ' Detaylı bilgi ve en uygun fiyat teklifleri için web sitemizi ziyaret edin.',
            ' Profesyonel, sigortalı ve güvenli hizmet.',
            ' Hemen bizi arayın, fırsatları kaçırmayın.',
            ' Müşteri memnuniyeti garantisiyle hizmetinizdeyiz.'
        ];
        foreach ($fillers as $f) {
            if (mb_strlen($final_desc . $f) <= 160) {
                $final_desc .= $f;
            } else {
                break;
            }
            if (mb_strlen($final_desc) >= 120)
                break;
        }
    }

    return [
        'success' => true,
        'seo_title' => $final_title,
        'meta_description' => $final_desc,
        'meta_keywords' => $seo_data['meta_keywords'] ?? '',
        'og_title' => $final_title, // Title ile aynı olsun
        'og_description' => $final_desc, // Desc ile aynı olsun
        'focus_keyword' => $seo_data['focus_keyword'] ?? $focus_keyword,
        'h1_title' => $seo_data['h1_title'] ?? $title,
        'content' => $seo_data['content'] ?? $content,
        'method' => 'ai_expanded'
    ];
}

/**
 * SEO verilerini kaydet
 */
function saveSeoData($conn, $type, $id, $data)
{
    $seo_title = $data['seo_title'] ?? '';
    $meta_description = $data['meta_description'] ?? '';
    $meta_keywords = $data['meta_keywords'] ?? '';
    $og_title = $data['og_title'] ?? '';
    $og_description = $data['og_description'] ?? '';
    $focus_keyword = $data['focus_keyword'] ?? '';

    // SEO score hesapla
    $score = 0;
    if (!empty($seo_title) && mb_strlen($seo_title) >= 30 && mb_strlen($seo_title) <= 60)
        $score += 15;
    if (!empty($meta_description) && mb_strlen($meta_description) >= 120 && mb_strlen($meta_description) <= 160)
        $score += 25;
    if (!empty($meta_keywords))
        $score += 10;
    if (!empty($focus_keyword))
        $score += 15;
    if (!empty($og_title))
        $score += 10;
    if (!empty($og_description))
        $score += 10;
    $score = min($score, 100);

    switch ($type) {
        case 'blog':
            $stmt = $conn->prepare("UPDATE blog_posts SET seo_title = ?, meta_description = ?, meta_keywords = ?, og_title = ?, og_description = ?, focus_keyword = ?, seo_score = ? WHERE id = ?");
            $stmt->bind_param("ssssssii", $seo_title, $meta_description, $meta_keywords, $og_title, $og_description, $focus_keyword, $score, $id);
            return $stmt->execute();

        case 'page':
            $stmt = $conn->prepare("UPDATE pages SET seo_title = ?, meta_description = ?, meta_keywords = ?, og_title = ?, og_description = ?, focus_keyword = ?, seo_score = ? WHERE id = ?");
            $stmt->bind_param("ssssssii", $seo_title, $meta_description, $meta_keywords, $og_title, $og_description, $focus_keyword, $score, $id);
            return $stmt->execute();

        case 'service':
            // Önce sütunların var olup olmadığını kontrol et
            $col_check = $conn->query("SHOW COLUMNS FROM services LIKE 'seo_title'");
            if (!$col_check || $col_check->num_rows === 0) {
                // Sütunlar yok, migration gerekli
                return false;
            }

            // Slug oluştur
            $slug = '';
            $title_check = $conn->prepare("SELECT ana_baslik FROM services WHERE id = ?");
            $title_check->bind_param("i", $id);
            $title_check->execute();
            $title_result = $title_check->get_result();
            if ($title_row = $title_result->fetch_assoc()) {
                $slug = strtolower(trim(preg_replace(
                    '/[^A-Za-z0-9-]+/',
                    '-',
                    str_replace(
                        ['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç'],
                        ['i', 'g', 'u', 's', 'o', 'c', 'i', 'g', 'u', 's', 'o', 'c'],
                        $title_row['ana_baslik']
                    )
                )));
                $slug = trim($slug, '-');
            }

            $stmt = $conn->prepare("UPDATE services SET seo_title = ?, meta_description = ?, meta_keywords = ?, focus_keyword = ?, slug = ?, seo_score = ? WHERE id = ?");
            if (!$stmt)
                return false;
            $stmt->bind_param("sssssii", $seo_title, $meta_description, $meta_keywords, $focus_keyword, $slug, $score, $id);
            return $stmt->execute();
    }

    return false;
}