<?php
require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

require_once '../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz ID.']);
    exit;
}

$stmt = $conn->prepare("SELECT filename, file_path FROM media_library WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Dosya bulunamadı.']);
    exit;
}

// Fiziksel dosyayı sil (file_path: uploads/blog/abc.jpg veya uploads/media/xyz.jpg)
$physical = '../../' . ltrim($row['file_path'], '/');
if (file_exists($physical)) {
    unlink($physical);
}

// DB'den sil
$stmt = $conn->prepare("DELETE FROM media_library WHERE id = ?");
$stmt->bind_param("i", $id);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $ok, 'message' => $ok ? 'Silindi.' : $conn->error]);
