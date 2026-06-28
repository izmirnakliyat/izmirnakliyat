<?php
require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

require_once '../includes/init.php';
require_once '../includes/auto_blog_functions.php';
header('Content-Type: application/json; charset=utf-8');

ensure_auto_blog_tasks_table();

$task_id         = (int)($_POST['task_id'] ?? 0);
$name            = trim($_POST['name']            ?? '');
$topics          = trim($_POST['topics']          ?? '');
$schedule        = $_POST['schedule']             ?? 'daily';
$articles_per_run= max(1, min(10, (int)($_POST['articles_per_run'] ?? 1)));
$category_id     = (int)($_POST['category_id']   ?? 0) ?: null;
$tone            = $_POST['tone']                 ?? 'professional';
$length          = $_POST['length']               ?? 'medium';
$default_image   = trim($_POST['default_image']  ?? '');
$status          = (int)($_POST['status']         ?? 1);

if (!$name || !$topics) {
    echo json_encode(['success' => false, 'message' => 'Görev adı ve konular zorunludur.']);
    exit;
}

$allowed_schedules = ['hourly','twice_daily','daily','weekly'];
if (!in_array($schedule, $allowed_schedules)) $schedule = 'daily';

$next_run = calculate_next_run($schedule);

if ($task_id) {
    // Güncelle
    $stmt = $conn->prepare("UPDATE auto_blog_tasks SET name=?, topics=?, schedule=?, articles_per_run=?, category_id=?, tone=?, length=?, default_image=?, status=?, next_run=?, updated_at=NOW() WHERE id=?");
    $stmt->bind_param("sssiiissssi", $name, $topics, $schedule, $articles_per_run, $category_id, $tone, $length, $default_image, $status, $next_run, $task_id);
} else {
    // Yeni
    $stmt = $conn->prepare("INSERT INTO auto_blog_tasks (name, topics, schedule, articles_per_run, category_id, tone, length, default_image, status, next_run) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("sssiiisssi", $name, $topics, $schedule, $articles_per_run, $category_id, $tone, $length, $default_image, $status, $next_run);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $task_id ?: $conn->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
