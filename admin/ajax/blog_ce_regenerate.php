<?php
/**
 * Tek yazı — Content Engine ile yeniden üret (editör fingerprint uygulanır).
 */
declare(strict_types=1);

require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

@set_time_limit(300);
header('Content-Type: application/json; charset=utf-8');

include __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auto_blog_functions.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/mynak_local_seo_content_engine.php';
require_once __DIR__ . '/../../includes/mynak_ce_production.php';
require_once __DIR__ . '/../../includes/blog_bulk_content_refresh_lib.php';

$postId = (int) ($_POST['post_id'] ?? $_POST['id'] ?? 0);
if ($postId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz yazı ID'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare('SELECT * FROM blog_posts WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $postId);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
if (!$post) {
    echo json_encode(['success' => false, 'message' => 'Yazı bulunamadı'], JSON_UNESCAPED_UNICODE);
    exit;
}

$meta = mynak_ce_parse_post_meta($post);
if (!empty($meta['editor_fingerprint']) && is_array($meta['editor_fingerprint'])) {
    $post['_editor_fingerprint'] = $meta['editor_fingerprint'];
}

if ((int) ($post['durum'] ?? 0) !== 3) {
    echo json_encode([
        'success' => false,
        'message' => 'Yeniden üretim yalnızca yayında (durum=3) yazılar için. Bu yazı durum=' . (int) $post['durum'],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $result = blog_bulk_content_refresh_run($conn, [
        'post_id' => $postId,
        'limit' => 1,
        'ignore_quota' => false,
        'production_test' => false,
    ]);
    $processed = (int) ($result['processed'] ?? 0);
    $qcPassed = (int) ($result['qc_passed'] ?? 0);
    $qcFailed = (int) ($result['qc_failed'] ?? 0);

    echo json_encode([
        'success' => $processed > 0,
        'message' => $processed > 0
            ? "CE yenileme tamam. QC pass: $qcPassed, fail: $qcFailed"
            : ($result['message'] ?? 'İşlem yapılamadı (kota veya uygun yazı yok).'),
        'processed' => $processed,
        'qc_passed' => $qcPassed,
        'qc_failed' => $qcFailed,
        'review_url' => '/admin/blog_review.php?focus=' . $postId,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
