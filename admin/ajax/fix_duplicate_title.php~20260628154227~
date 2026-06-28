<?php
@set_time_limit(120);
require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

require_once '../includes/init.php';
require_once '../includes/auto_blog_functions.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['title']) || empty($input['ids'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

$original_title = trim($input['title']);
$ids_raw = $input['ids'];
$id_list = array_filter(array_map('intval', explode(',', $ids_raw)));

if (empty($id_list)) {
    echo json_encode(['success' => false, 'message' => 'ID listesi boş.']);
    exit;
}

$openai_key   = get_openai_api_key();
$openai_model = get_openai_model();

if (!$openai_key) {
    echo json_encode(['success' => false, 'message' => 'OpenAI API anahtarı tanımlı değil.']);
    exit;
}

$count = count($id_list);

// AI'a benzersiz başlıklar ürettir
$prompt = "Aşağıdaki blog başlığının {$count} benzersiz versiyonunu üret.\n";
$prompt .= "Orijinal başlık: \"{$original_title}\"\n\n";
$prompt .= "Kurallar:\n";
$prompt .= "- Her başlık özgün ve birbirinden farklı olmalı\n";
$prompt .= "- SEO dostu, 50-70 karakter uzunluğunda olmalı\n";
$prompt .= "- Türkçe olmalı\n";
$prompt .= "- Her başlık yeni bir satırda olsun, numaralandırma veya madde imi KULLANMA\n";
$prompt .= "- Sadece başlıkları döndür, açıklama veya ek metin ekleme\n\n";
$prompt .= "Tam olarak {$count} adet başlık yaz:";

$post_data = [
    'model' => $openai_model,
    'messages' => [
        ['role' => 'system', 'content' => 'Sen SEO uzmanı bir içerik yazarısın. Sadece istenen formatta yanıt ver.'],
        ['role' => 'user', 'content' => $prompt],
    ],
    'max_tokens' => 500,
    'temperature' => 0.85,
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
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_CONNECTTIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$response  = curl_exec($ch);
$http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_err  = curl_error($ch);
curl_close($ch);

if ($http_code !== 200 || !$response) {
    $msg = $curl_err ?: 'OpenAI API hatası (HTTP ' . $http_code . ')';
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

$data = json_decode($response, true);
$ai_text = trim($data['choices'][0]['message']['content'] ?? '');

if (empty($ai_text)) {
    echo json_encode(['success' => false, 'message' => 'AI yanıt vermedi.']);
    exit;
}

// Başlıkları satır satır ayır
$lines = array_values(array_filter(array_map('trim', explode("\n", $ai_text))));

// Numaralandırma varsa temizle
$lines = array_map(function($line) {
    return preg_replace('/^[\d]+[\.\)]\s*/', '', $line);
}, $lines);
$lines = array_values(array_filter($lines));

if (empty($lines)) {
    echo json_encode(['success' => false, 'message' => 'AI başlık üretemedi.']);
    exit;
}

// Yeterli başlık üretilemezse tekrar et
while (count($lines) < $count) {
    $lines[] = $original_title . ' - ' . (count($lines) + 1);
}

// DB'ye kaydet: birden fazla ID varsa 2. ID'den itibaren yeni başlıklar ver
// İlk yazıyı olduğu gibi bırak (ya da ilk yeni başlığı ver)
$updated_titles = [];
$errors = [];

foreach ($id_list as $idx => $post_id) {
    $new_title = $lines[$idx] ?? $original_title . ' (' . ($idx + 1) . ')';
    $new_slug  = slug_olustur($new_title);

    // Slug benzersizliği kontrol et
    $base_slug = $new_slug;
    $suffix    = 1;
    do {
        $chk = $conn->prepare("SELECT id FROM blog_posts WHERE slug = ? AND id != ?");
        $chk->bind_param("si", $new_slug, $post_id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $new_slug = $base_slug . '-' . $suffix++;
        } else {
            break;
        }
        $chk->close();
    } while ($suffix < 100);

    $stmt = $conn->prepare("UPDATE blog_posts SET baslik = ?, slug = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $new_title, $new_slug, $post_id);
    if ($stmt->execute()) {
        $updated_titles[] = '#' . $post_id . ' → ' . htmlspecialchars($new_title);
    } else {
        $errors[] = '#' . $post_id . ': ' . $conn->error;
    }
    $stmt->close();
}

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => 'Kısmi hata: ' . implode(', ', $errors),
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => implode('<br>', $updated_titles),
    'count'   => count($updated_titles),
], JSON_UNESCAPED_UNICODE);
