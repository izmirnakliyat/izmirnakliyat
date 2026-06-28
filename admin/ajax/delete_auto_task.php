<?php
require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

require_once '../includes/init.php';
header('Content-Type: application/json; charset=utf-8');

$task_id = (int)($_POST['task_id'] ?? 0);
if (!$task_id) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz ID.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM auto_blog_tasks WHERE id = ?");
$stmt->bind_param("i", $task_id);
echo json_encode(['success' => $stmt->execute(), 'message' => $conn->error]);
