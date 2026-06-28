<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if (mb_strlen($q) < 2) {
    echo json_encode(['success' => false, 'results' => []]);
    exit;
}

$stmt = $conn->prepare("SELECT baslik, slug, kapak_foto FROM blog_posts WHERE durum = 3 AND baslik LIKE CONCAT('%', ?, '%') ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param('s', $q);
$stmt->execute();
$results = mysqli_stmt_fetch_all_assoc($stmt);
$stmt->close();
echo json_encode(['success' => true, 'results' => $results]); 