<?php
require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

require_once '../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

// Tabloyu oluştur (yoksa)
$conn->query("CREATE TABLE IF NOT EXISTS `media_library` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `filename` varchar(255) NOT NULL,
    `original_name` varchar(255) DEFAULT NULL,
    `file_path` varchar(500) NOT NULL,
    `file_type` varchar(100) DEFAULT NULL,
    `file_size` int(11) DEFAULT 0,
    `width` int(11) DEFAULT 0,
    `height` int(11) DEFAULT 0,
    `alt_text` varchar(255) DEFAULT NULL,
    `uploaded_by` int(11) DEFAULT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $err = $_FILES['file']['error'] ?? -1;
    $msg = match($err) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Dosya çok büyük (maks 10MB).',
        UPLOAD_ERR_NO_FILE  => 'Dosya seçilmedi.',
        default             => 'Yükleme hatası (kod: ' . $err . ').',
    };
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

$file      = $_FILES['file'];
$orig_name = basename($file['name']);
$ext       = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
$allowed   = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];
$max_size  = 10 * 1024 * 1024; // 10MB

if (!in_array($ext, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Desteklenmeyen format. Sadece: ' . implode(', ', $allowed)]);
    exit;
}
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'Dosya çok büyük. Maksimum 10MB.']);
    exit;
}

// MIME doğrulaması
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mime     = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
$allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'];
if (!in_array($mime, $allowed_mimes)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz dosya içeriği.']);
    exit;
}

$upload_dir = '../../uploads/media/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Benzersiz dosya adı
$filename    = uniqid('media_', true) . '.' . $ext;
$destination = $upload_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    echo json_encode(['success' => false, 'message' => 'Dosya taşıma hatası.']);
    exit;
}

// Boyutları al (resimse)
$width = $height = 0;
$img_info = @getimagesize($destination);
if ($img_info) {
    $width  = $img_info[0];
    $height = $img_info[1];
}

// DB'ye kaydet
$user_id = $_SESSION['user_id'] ?? null;
$stmt    = $conn->prepare("INSERT INTO media_library (filename, original_name, file_path, file_type, file_size, width, height, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$file_path = 'uploads/media/' . $filename;
$stmt->bind_param('ssssiiii', $filename, $orig_name, $file_path, $mime, $file['size'], $width, $height, $user_id);
$stmt->execute();
$insert_id = $conn->insert_id;
$stmt->close();

function fmt_size($bytes) {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

echo json_encode([
    'success' => true,
    'item'    => [
        'id'            => $insert_id,
        'filename'      => $filename,
        'original_name' => $orig_name,
        'file_size_str' => fmt_size($file['size']),
        'width'         => $width,
        'height'        => $height,
        'url'           => (defined('SITE_URL') ? SITE_URL : '') . '/uploads/media/' . $filename,
    ],
], JSON_UNESCAPED_UNICODE);
