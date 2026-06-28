<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

header('Content-Type: application/json; charset=utf-8');

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_level()) ob_end_clean();
        if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $error['message']]);
    }
});

set_error_handler(function($errno, $errstr) {
    if (ob_get_level()) ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'PHP Hatası: ' . $errstr]);
    exit;
});

require_once '../includes/auto_blog_functions.php';

error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(180);

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    if (ob_get_level()) ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

$action = $input['action'] ?? '';
$postId = intval($input['post_id'] ?? 0);

if ($postId <= 0) {
    if (ob_get_level()) ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Geçersiz yazı ID']);
    exit;
}

if (ob_get_level()) ob_end_clean();

switch ($action) {
    case 'optimize':
        handleOptimize($conn, $postId);
        break;
    case 'restore':
        handleRestore($conn, $postId);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Geçersiz aksiyon']);
}

function handleOptimize($conn, $postId) {
    $apiKey = get_openai_api_key();
    if (empty($apiKey)) {
        echo json_encode(['success' => false, 'message' => 'OpenAI API anahtarı tanımlı değil']);
        return;
    }

    // Yazıyı al
    $stmt = $conn->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    
    if (!$post) {
        echo json_encode(['success' => false, 'message' => 'Yazı bulunamadı']);
        return;
    }

    // Yedek al
    $bkStmt = $conn->prepare("INSERT INTO blog_seo_backups (post_id, original_content, original_seo_title, original_meta_description, original_meta_keywords, original_focus_keyword) VALUES (?, ?, ?, ?, ?, ?)");
    $bkStmt->bind_param("isssss", $postId, $post['icerik'], $post['seo_title'], $post['meta_description'], $post['meta_keywords'], $post['focus_keyword']);
    $bkStmt->execute();

    // Site URL ve iç link hedefleri
    $siteUrl = SITE_URL;
    $internalLinks = getInternalLinkTargets($conn, $postId, $siteUrl);

    // Mevcut içerik analizi
    $content = $post['icerik'];
    $plainText = strip_tags(html_entity_decode($content, ENT_QUOTES, 'UTF-8'));
    $wordCount = count(preg_split('/\s+/', trim($plainText), -1, PREG_SPLIT_NO_EMPTY));

    // OpenAI ile optimize et
    $result = callOpenAIOptimize($apiKey, $post, $internalLinks, $siteUrl, $wordCount);
    
    if (!$result['success']) {
        echo json_encode(['success' => false, 'message' => $result['message'] ?? 'AI optimizasyonu başarısız']);
        return;
    }

    // Güncellemeleri kaydet
    $newContent = $result['content'] ?? $post['icerik'];
    $newSeoTitle = $result['seo_title'] ?? $post['seo_title'];
    $newMetaDesc = $result['meta_description'] ?? $post['meta_description'];
    $newMetaKeywords = $result['meta_keywords'] ?? $post['meta_keywords'];
    $newFocusKeyword = $result['focus_keyword'] ?? $post['focus_keyword'];

    $updStmt = $conn->prepare("UPDATE blog_posts SET icerik = ?, seo_title = ?, meta_description = ?, meta_keywords = ?, focus_keyword = ? WHERE id = ?");
    $updStmt->bind_param("sssssi", $newContent, $newSeoTitle, $newMetaDesc, $newMetaKeywords, $newFocusKeyword, $postId);
    
    if (!$updStmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Veritabanı güncelleme hatası: ' . $updStmt->error]);
        return;
    }

    // Yeni skoru hesapla
    $updatedPost = $post;
    $updatedPost['icerik'] = $newContent;
    $updatedPost['seo_title'] = $newSeoTitle;
    $updatedPost['meta_description'] = $newMetaDesc;
    $updatedPost['meta_keywords'] = $newMetaKeywords;
    $updatedPost['focus_keyword'] = $newFocusKeyword;
    
    $newAnalysis = analyzeBlogSeoSimple($updatedPost, $siteUrl);
    
    $scoreStmt = $conn->prepare("UPDATE blog_posts SET seo_score = ? WHERE id = ?");
    $scoreStmt->bind_param("ii", $newAnalysis['score'], $postId);
    $scoreStmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Optimizasyon tamamlandı',
        'new_score' => $newAnalysis['score'],
        'old_score' => $post['seo_score'] ?? 0
    ]);
}

function handleRestore($conn, $postId) {
    $stmt = $conn->prepare("SELECT * FROM blog_seo_backups WHERE post_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $backup = $stmt->get_result()->fetch_assoc();
    
    if (!$backup) {
        echo json_encode(['success' => false, 'message' => 'Yedek bulunamadı']);
        return;
    }

    $updStmt = $conn->prepare("UPDATE blog_posts SET icerik = ?, seo_title = ?, meta_description = ?, meta_keywords = ?, focus_keyword = ? WHERE id = ?");
    $updStmt->bind_param("sssssi", $backup['original_content'], $backup['original_seo_title'], $backup['original_meta_description'], $backup['original_meta_keywords'], $backup['original_focus_keyword'], $postId);
    
    if ($updStmt->execute()) {
        // Kullanılan yedeği sil
        $delStmt = $conn->prepare("DELETE FROM blog_seo_backups WHERE id = ?");
        $delStmt->bind_param("i", $backup['id']);
        $delStmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Yedek başarıyla geri yüklendi']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Geri yükleme başarısız']);
    }
}

function getInternalLinkTargets($conn, $excludePostId, $siteUrl) {
    $links = [];
    
    // Diğer blog yazıları
    $stmt = $conn->prepare("SELECT id, baslik, slug FROM blog_posts WHERE id != ? AND durum = 3 ORDER BY RAND() LIMIT 10");
    $stmt->bind_param("i", $excludePostId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $links[] = [
            'url' => $siteUrl . '/' . $row['slug'],
            'title' => $row['baslik']
        ];
    }
    
    // Sayfalar
    $pResult = $conn->query("SELECT title, slug FROM pages WHERE status = 1 LIMIT 5");
    if ($pResult) {
        while ($row = $pResult->fetch_assoc()) {
            $links[] = [
                'url' => $siteUrl . '/' . $row['slug'],
                'title' => $row['title']
            ];
        }
    }
    
    // Hizmetler
    $sResult = $conn->query("SELECT ana_baslik, slug FROM services WHERE status = 1 LIMIT 5");
    if ($sResult) {
        while ($row = $sResult->fetch_assoc()) {
            $links[] = [
                'url' => $siteUrl . '/' . $row['slug'],
                'title' => $row['ana_baslik']
            ];
        }
    }
    
    return $links;
}

function callOpenAIOptimize($apiKey, $post, $internalLinks, $siteUrl, $currentWordCount) {
    $title = $post['baslik'];
    $content = $post['icerik'];
    $focusKeyword = $post['focus_keyword'] ?? '';
    $existingSeoTitle = $post['seo_title'] ?? '';
    $existingMetaDesc = $post['meta_description'] ?? '';
    
    // İç link listesini hazırla
    $linkList = "";
    foreach ($internalLinks as $link) {
        $linkList .= "- [{$link['title']}]({$link['url']})\n";
    }
    
    $contentPreview = mb_substr(strip_tags($content), 0, 3000);

    $systemPrompt = "Sen uzman bir SEO editörüsün. Blog yazılarını SEO açısından optimize ediyorsun. Türkçe içerik üretiyorsun. HTML formatında çalışıyorsun.";

    $userPrompt = "Aşağıdaki blog yazısını SEO açısından optimize et.

MEVCUT YAZI:
Başlık: {$title}
Odak Kelime: " . ($focusKeyword ?: '(belirlenmemiş - uygun bir tane belirle)') . "
Mevcut Kelime Sayısı: {$currentWordCount}
Mevcut SEO Başlık: " . ($existingSeoTitle ?: '(yok)') . "
Mevcut Meta Açıklama: " . ($existingMetaDesc ?: '(yok)') . "

İÇERİK:
{$contentPreview}

İÇ LİNK HEDEFLERİ (bunlardan en az 2-3 tanesini içeriğe doğal şekilde yerleştir):
{$linkList}

OPTİMİZASYON KURALLARI:
1. İçeriği HTML formatında (<p>, <h2>, <h3>, <ul>, <li>, <strong>, <a> etiketleri) döndür
2. En az 3 adet <h2> başlık ekle (varsa düzenle, yoksa ekle)
3. İç linkleri doğal bir şekilde metin içine yerleştir (<a href=\"URL\">anchor text</a>)
4. Paragrafları kısa ve okunabilir tut (her biri 2-4 cümle)
5. Kelime sayısını en az 500'e çıkar (mevcut içeriği genişlet, yeni bilgiler ekle)
6. Odak kelimeyi başlıkta ve içerikte en az 3-4 kez doğal kullanıma yerleştir
7. Varolan içeriğin anlamını ve konusunu koru, sadece SEO açısından geliştir
8. SEO başlık 35-55 karakter arası olmalı
9. Meta açıklama 130-155 karakter arası, harekete geçirici olmalı
10. İçeriğe <h1> etiketi EKLEME, zaten sayfa şablonunda var

YANIT FORMAT (SADECE JSON):
{
    \"content\": \"<p>Optimize edilmiş HTML içerik...</p>\",
    \"seo_title\": \"SEO başlık (35-55 karakter)\",
    \"meta_description\": \"Meta açıklama (130-155 karakter)\",
    \"meta_keywords\": \"anahtar, kelimeler, virgülle, ayrılmış\",
    \"focus_keyword\": \"odak kelime\"
}";

    $data = [
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'temperature' => 0.7,
        'max_tokens' => 4000
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 120
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError) {
        return ['success' => false, 'message' => 'API bağlantı hatası: ' . $curlError];
    }
    
    if ($httpCode !== 200) {
        $errBody = json_decode($response, true);
        $errMsg = $errBody['error']['message'] ?? "HTTP $httpCode";
        return ['success' => false, 'message' => 'API hatası: ' . $errMsg];
    }

    $result = json_decode($response, true);
    if (!isset($result['choices'][0]['message']['content'])) {
        return ['success' => false, 'message' => 'API yanıt formatı hatalı'];
    }

    $aiContent = $result['choices'][0]['message']['content'];
    
    // JSON temizle
    $aiContent = preg_replace('/```json\s*/', '', $aiContent);
    $aiContent = preg_replace('/```\s*/', '', $aiContent);
    $aiContent = trim($aiContent);

    $seoData = json_decode($aiContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'message' => 'AI yanıtı parse edilemedi: ' . json_last_error_msg()];
    }

    // Doğrulama ve düzeltme
    $finalSeoTitle = $seoData['seo_title'] ?? '';
    if (mb_strlen($finalSeoTitle) > 60) {
        $finalSeoTitle = mb_substr($finalSeoTitle, 0, 57) . '...';
    }

    $finalMetaDesc = $seoData['meta_description'] ?? '';
    if (mb_strlen($finalMetaDesc) > 160) {
        $finalMetaDesc = mb_substr($finalMetaDesc, 0, 157) . '...';
    }

    return [
        'success' => true,
        'content' => $seoData['content'] ?? '',
        'seo_title' => $finalSeoTitle,
        'meta_description' => $finalMetaDesc,
        'meta_keywords' => $seoData['meta_keywords'] ?? '',
        'focus_keyword' => $seoData['focus_keyword'] ?? ''
    ];
}

function analyzeBlogSeoSimple($post, $siteUrl) {
    $content = $post['icerik'] ?? '';
    $plainText = strip_tags(html_entity_decode($content, ENT_QUOTES, 'UTF-8'));
    $plainText = preg_replace('/\s+/', ' ', trim($plainText));
    
    $score = 0;
    
    $wordCount = empty($plainText) ? 0 : count(preg_split('/\s+/', $plainText, -1, PREG_SPLIT_NO_EMPTY));
    if ($wordCount >= 800) $score += 15;
    elseif ($wordCount >= 500) $score += 12;
    elseif ($wordCount >= 300) $score += 8;
    else $score += 3;
    
    preg_match_all('/<h2[^>]*>/i', $content, $h2m);
    $h2 = count($h2m[0]);
    if ($h2 >= 3) $score += 15;
    elseif ($h2 >= 2) $score += 10;
    elseif ($h2 >= 1) $score += 5;
    
    preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $lm);
    $il = 0;
    foreach (($lm[1] ?? []) as $link) {
        if (strpos($link, $siteUrl) !== false || (strpos($link, '/') === 0 && strpos($link, '//') !== 0)) $il++;
    }
    if ($il >= 3) $score += 15;
    elseif ($il >= 2) $score += 10;
    elseif ($il >= 1) $score += 5;
    
    preg_match_all('/<p[^>]*>/i', $content, $pm);
    $pc = count($pm[0]);
    if ($pc >= 5) $score += 10;
    elseif ($pc >= 3) $score += 7;
    elseif ($pc >= 1) $score += 3;
    
    $md = $post['meta_description'] ?? '';
    $ml = mb_strlen($md);
    if ($ml >= 120 && $ml <= 160) $score += 15;
    elseif ($ml > 0) $score += 8;
    
    $st = $post['seo_title'] ?? '';
    $sl = mb_strlen($st);
    if ($sl >= 30 && $sl <= 60) $score += 15;
    elseif ($sl > 0) $score += 8;
    
    $fk = $post['focus_keyword'] ?? '';
    if (!empty($fk)) {
        $inTitle = mb_stripos($post['baslik'], $fk) !== false;
        $inContent = mb_stripos($plainText, $fk) !== false;
        if ($inTitle && $inContent) $score += 10;
        elseif ($inContent || $inTitle) $score += 5;
        else $score += 2;
    }
    
    preg_match_all('/<img[^>]*>/i', $content, $im);
    $ti = count($im[0]);
    if ($ti > 0) {
        $wa = 0;
        foreach ($im[0] as $img) { if (preg_match('/alt=["\'][^"\']+["\']/i', $img)) $wa++; }
        if ($wa == $ti) $score += 5;
        elseif ($wa > 0) $score += 3;
    } else {
        $score += 3;
    }
    
    return ['score' => min(100, $score)];
}
