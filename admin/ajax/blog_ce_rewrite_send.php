<?php
/**
 * Tek tık: Tekrar yaz + editöre al (basit akış).
 */
declare(strict_types=1);

require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

@set_time_limit(600);
@ini_set('max_execution_time', '600');
@ini_set('max_input_time', '600');
@ignore_user_abort(true);
header('Content-Type: application/json; charset=utf-8');
header('X-Accel-Buffering: no');

include __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auto_blog_functions.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/mynak_local_seo_content_engine.php';
require_once __DIR__ . '/../../includes/mynak_ce_production.php';
require_once __DIR__ . '/../../includes/auto_blog_ce_adapter.php';

$postId = (int) ($_POST['post_id'] ?? $_POST['id'] ?? 0);
if ($postId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz yazı ID'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $out = ab_ce_rewrite_and_send($conn, $postId);
    $out['ce_rewrite_version'] = function_exists('br_ce_rewrite_longform_only') ? '2026-05-22-v2' : 'legacy';
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
