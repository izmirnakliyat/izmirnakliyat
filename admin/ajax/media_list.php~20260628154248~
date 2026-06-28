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

function fmt_size($bytes) {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

// Tek dosya detayı
if (isset($_GET['id'])) {
    $id   = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM media_library WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        $row['file_size_str'] = fmt_size($row['file_size']);
        $row['created_at']    = date('d.m.Y H:i', strtotime($row['created_at']));
        // URL: file_path tabanlı
        $base = defined('SITE_URL') ? SITE_URL : '';
        $row['url'] = $base . '/' . ltrim($row['file_path'], '/');
    }
    echo json_encode(['item' => $row]);
    exit;
}

// Alt metin güncelle
if (isset($_POST['action']) && $_POST['action'] === 'update_alt') {
    $id  = (int)$_POST['id'];
    $alt = trim($_POST['alt_text'] ?? '');
    $stmt = $conn->prepare("UPDATE media_library SET alt_text = ? WHERE id = ?");
    $stmt->bind_param("si", $alt, $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
    exit;
}

// Modal için liste (AJAX arama + sayfalama)
$q       = trim($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$per_pg  = (int)($_GET['per_page'] ?? 30);
$offset  = ($page - 1) * $per_pg;

$where  = '';
$params = [];
$types  = '';
if ($q) {
    $where   = "WHERE original_name LIKE ? OR alt_text LIKE ?";
    $like    = '%' . $q . '%';
    $params  = [$like, $like];
    $types   = 'ss';
}

// Toplam
$cnt_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM media_library $where");
if ($params) $cnt_stmt->bind_param($types, ...$params);
$cnt_stmt->execute();
$total = $cnt_stmt->get_result()->fetch_assoc()['cnt'];
$cnt_stmt->close();

// Liste
$list_params  = $params;
$list_params[] = $per_pg;
$list_params[] = $offset;
$list_types   = $types . 'ii';

$lst_stmt = $conn->prepare("SELECT * FROM media_library $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
$lst_stmt->bind_param($list_types, ...$list_params);
$lst_stmt->execute();
$rows = $lst_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$lst_stmt->close();

$items = array_map(function($r) {
    $r['file_size_str'] = fmt_size($r['file_size']);
    // file_path'i kullan (ör: uploads/blog/abc.jpg veya uploads/media/xyz.jpg)
    $base = defined('SITE_URL') ? SITE_URL : '';
    $r['url'] = $base . '/' . ltrim($r['file_path'], '/');
    return $r;
}, $rows);

echo json_encode([
    'items'       => $items,
    'total'       => $total,
    'page'        => $page,
    'total_pages' => ceil($total / $per_pg),
], JSON_UNESCAPED_UNICODE);
