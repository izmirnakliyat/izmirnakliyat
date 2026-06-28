<?php
require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

header('Content-Type: application/json');

$gallery_id = isset($_GET['gallery_id']) ? intval($_GET['gallery_id']) : 0;
if ($gallery_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz galeri ID']);
    exit;
}

// Galeriye ait resimleri çek
$resimler = [];
$stmt = $conn->prepare("SELECT image FROM gallery WHERE id = ?");
$stmt->bind_param('i', $gallery_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    // Eğer image alanı virgül ile ayrılmışsa (çoklu resim için)
    if (strpos($row['image'], ',') !== false) {
        $resimler = array_map('trim', explode(',', $row['image']));
    } else {
        $resimler = [$row['image']];
    }
}
echo json_encode(['success' => true, 'images' => $resimler]); 