<?php
require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

require_once '../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

$input   = json_decode(file_get_contents('php://input'), true);
$post_id = (int)($input['post_id'] ?? 0);

if (!$post_id) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz ID.']);
    exit;
}

// Mevcut içeriği al
$stmt = $conn->prepare("SELECT icerik FROM blog_posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Blog yazısı bulunamadı.']);
    exit;
}

$original = $row['icerik'];

// Temizleme işlemleri (sıralı uygula)
$cleaned = $original;

// 1. ```html, ```php, ```json, ```markdown, ```css, ```javascript vb. açılış etiketleri (büyük/küçük harf)
$cleaned = preg_replace('/^```[a-zA-Z0-9_\-]*[ \t]*\r?\n/m', '', $cleaned);

// 2. Satır başında tek başına ``` (kapanış veya açılış)
$cleaned = preg_replace('/^```[ \t]*\r?\n?/m', '', $cleaned);

// 3. Satır sonunda tek başına ``` 
$cleaned = preg_replace('/\r?\n?[ \t]*```[ \t]*$/m', '', $cleaned);

// 4. Satır ortasında kalan ``` kalıntıları
$cleaned = str_replace('```', '', $cleaned);

// 5. Baş ve sondaki fazladan boş satırları temizle
$cleaned = trim($cleaned);

// Değişiklik var mı kontrol et
if ($cleaned === $original) {
    echo json_encode(['success' => true, 'message' => 'Zaten temiz, değişiklik gerekmedi.', 'changed' => false]);
    exit;
}

// DB'ye kaydet
$stmt = $conn->prepare("UPDATE blog_posts SET icerik = ?, updated_at = NOW() WHERE id = ?");
$stmt->bind_param("si", $cleaned, $post_id);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $conn->error]);
    exit;
}

echo json_encode([
    'success'  => true,
    'message'  => 'Kod bloğu kalıntıları temizlendi.',
    'changed'  => true,
    'post_id'  => $post_id,
]);
