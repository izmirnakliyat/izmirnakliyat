<?php
@set_time_limit(300);
require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

require_once '../includes/init.php';
require_once '../includes/auto_blog_functions.php';

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$post_id = (int)($input['post_id'] ?? 0);

if (!$post_id) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz blog ID.']);
    exit;
}

$openai_key   = get_openai_api_key();
$openai_model = get_openai_model();

if (!$openai_key) {
    echo json_encode(['success' => false, 'message' => 'OpenAI API anahtarı tanımlı değil.']);
    exit;
}

// Blog yazısını al
$stmt = $conn->prepare("SELECT id, baslik, icerik FROM blog_posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$blog = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$blog) {
    echo json_encode(['success' => false, 'message' => 'Blog yazısı bulunamadı.']);
    exit;
}

// HTML içeriği düz metne çevir (AI daha iyi işler)
$plain_content = strip_tags($blog['icerik']);
$plain_content = html_entity_decode($plain_content, ENT_QUOTES, 'UTF-8');
$plain_content = preg_replace('/\s+/', ' ', $plain_content);
$plain_content = trim($plain_content);

// Çok uzunsa kırp (token limiti)
if (mb_strlen($plain_content) > 8000) {
    $plain_content = mb_substr($plain_content, 0, 8000);
}

$prompt = <<<PROMPT
Aşağıdaki blog yazısını Google'ın AI dedektörlerini geçecek şekilde doğal, insansı bir dile dönüştür.

KURALLAR:
- Yazının anlamını, konusunu ve SEO anahtar kelimelerini KORU
- Robota özgü kalıpları, klişeleri ve tekrarlayan yapıları değiştir
- Doğal Türkçe konuşma diline yakın, samimi bir üslup kullan
- Farklı cümle uzunlukları (kısa, orta, uzun) karıştır
- Zaman zaman soru cümlesi, ünlem veya birinci/ikinci şahıs ifadeler kullan
- Pasif yapı yerine aktif yapı tercih et
- H2/H3 başlık yapısını koru, sadece başlık metinlerini de insanlaştır
- HTML etiketleri kullan (h2, h3, p, strong, ul, li vb.)
- Kelime sayısını orijinale yakın tut
- Sadece dönüştürülmüş içeriği döndür, açıklama veya yorum ekleme

BAŞLIK: {$blog['baslik']}

İÇERİK:
{$plain_content}
PROMPT;

// Token hesabı
$word_count = str_word_count($plain_content);
$max_tokens = max(2000, min(4096, (int)($word_count * 1.6) + 500));

$post_data = [
    'model' => $openai_model,
    'messages' => [
        [
            'role'    => 'system',
            'content' => 'Sen deneyimli bir Türkçe içerik editörüsün. AI tarafından yazılmış metinleri doğal, insan yazısı gibi görünecek şekilde yeniden yazarsın. HTML formatını korursun.',
        ],
        ['role' => 'user', 'content' => $prompt],
    ],
    'max_tokens'  => $max_tokens,
    'temperature' => 0.9,
];

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($post_data),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $openai_key,
    ],
    CURLOPT_TIMEOUT        => 180,
    CURLOPT_CONNECTTIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_FOLLOWLOCATION => true,
]);

$response  = curl_exec($ch);
$http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_err  = curl_error($ch);
$curl_info = curl_getinfo($ch);
curl_close($ch);

if (!$response || $curl_err) {
    echo json_encode(['success' => false, 'message' => 'cURL hatası: ' . $curl_err . ' | Toplam süre: ' . round($curl_info['total_time'], 1) . 'sn']);
    exit;
}
if ($http_code !== 200) {
    $api_err = json_decode($response, true);
    $msg = $api_err['error']['message'] ?? ('HTTP ' . $http_code . ' - ' . substr($response, 0, 200));
    echo json_encode(['success' => false, 'message' => 'OpenAI hatası: ' . $msg]);
    exit;
}

$data       = json_decode($response, true);
$new_content = trim($data['choices'][0]['message']['content'] ?? '');

if (empty($new_content)) {
    echo json_encode(['success' => false, 'message' => 'AI yanıt vermedi.']);
    exit;
}

// Markdown kod bloğu kalıntılarını temizle
$new_content = preg_replace('/^```[a-zA-Z0-9_\-]*[ \t]*\r?\n/m', '', $new_content);
$new_content = preg_replace('/^```[ \t]*\r?\n?/m', '', $new_content);
$new_content = preg_replace('/\r?\n?[ \t]*```[ \t]*$/m', '', $new_content);
$new_content = str_replace('```', '', $new_content);
$new_content = trim($new_content);

// Eğer AI düz metin döndürdüyse <p> ile sar
if (strpos($new_content, '<') === false) {
    $paragraphs = explode("\n\n", $new_content);
    $new_content = implode('', array_map(function($p) {
        $p = trim($p);
        return !empty($p) ? '<p>' . nl2br(htmlspecialchars($p)) . '</p>' : '';
    }, $paragraphs));
}

// humanized_at sütunu yoksa ekle
$col_check = $conn->query("SHOW COLUMNS FROM blog_posts LIKE 'humanized_at'");
if ($col_check && $col_check->num_rows === 0) {
    $conn->query("ALTER TABLE blog_posts ADD COLUMN humanized_at DATETIME DEFAULT NULL");
}

// DB'ye kaydet
$stmt = $conn->prepare("UPDATE blog_posts SET icerik = ?, updated_at = NOW(), humanized_at = NOW() WHERE id = ?");
$stmt->bind_param("si", $new_content, $post_id);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı güncelleme hatası: ' . $conn->error]);
    exit;
}

$new_word_count = str_word_count(strip_tags($new_content));

echo json_encode([
    'success'        => true,
    'message'        => 'Blog yazısı başarıyla insanlaştırıldı.',
    'post_id'        => $post_id,
    'new_word_count' => $new_word_count,
    'preview'        => mb_substr(strip_tags($new_content), 0, 200) . '...',
], JSON_UNESCAPED_UNICODE);
