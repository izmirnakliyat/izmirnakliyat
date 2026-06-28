<?php
@set_time_limit(300);
require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

require_once '../includes/init.php';
require_once '../includes/auto_blog_functions.php';
header('Content-Type: application/json; charset=utf-8');

$task_id = (int)($_POST['task_id'] ?? 0);
if (!$task_id) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz görev ID.']);
    exit;
}

$task = get_auto_blog_task($task_id);
if (!$task) {
    echo json_encode(['success' => false, 'message' => 'Görev bulunamadı.']);
    exit;
}
if (!get_openai_api_key()) {
    echo json_encode(['success' => false, 'message' => 'OpenAI API anahtarı tanımlı değil.']);
    exit;
}

// Konuları al ve karıştır
$all_topics = array_values(array_filter(array_map('trim', explode("\n", $task['topics']))));
if (empty($all_topics)) {
    echo json_encode(['success' => false, 'message' => 'Görevde hiç konu tanımlı değil.']);
    exit;
}
shuffle($all_topics);

$count   = min((int)$task['articles_per_run'], count($all_topics));
$results = [];
$errors  = [];

for ($i = 0; $i < $count; $i++) {
    $topic  = $all_topics[$i];
    $result = generate_blog_post_from_task($task, $topic);
    if ($result['success']) {
        $results[] = ['post_id' => $result['post_id'], 'title' => $result['title']];
    } else {
        $errors[] = $topic . ': ' . $result['message'];
    }
}

// last_run ve next_run güncelle
$next_run = calculate_next_run($task['schedule']);
$stmt = $conn->prepare("UPDATE auto_blog_tasks SET last_run=NOW(), next_run=? WHERE id=?");
$stmt->bind_param("si", $next_run, $task_id);
$stmt->execute();

$next_label = date('d.m H:i', strtotime($next_run));
$msg = count($results) . ' makale oluşturuldu.';
if ($errors) $msg .= ' ' . count($errors) . ' hata.';

echo json_encode([
    'success'  => count($results) > 0,
    'message'  => $msg,
    'posts'    => $results,
    'errors'   => $errors,
    'next_run' => $next_label,
]);
