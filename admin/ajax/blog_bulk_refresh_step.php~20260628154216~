<?php
/**
 * Toplu yenileme — tek yazı (timeout önlemi: web'de 1'er 1'er çağrılır).
 */
declare(strict_types=1);

require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

@set_time_limit(300);
@ini_set('max_execution_time', '300');
@ignore_user_abort(true);

header('Content-Type: application/json; charset=utf-8');

include __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auto_blog_functions.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/blog_bulk_content_refresh_lib.php';

if (!hasPermission('blog_view')) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz'], JSON_UNESCAPED_UNICODE);
    exit;
}

$postId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
$ignoreQuota = !empty($_POST['ignore_quota']);
$useDateCutoff = !empty($_POST['use_date_cutoff']);
$before = isset($_POST['before']) ? trim((string) $_POST['before']) : '2025-01-01';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $before)) {
    $before = '2025-01-01';
}

try {
    $opts = [
        'dry_run' => false,
        'ignore_quota' => $ignoreQuota,
        'limit' => 1,
        'before' => $before,
        'post_id' => $postId > 0 ? $postId : null,
        'date_cutoff' => $useDateCutoff,
        'production_test' => false,
    ];

    $result = blog_bulk_content_refresh_run($conn, $opts);
    $processed = (int) ($result['processed'] ?? 0);
    $today = date('Y-m-d');
    $dailyMax = br_daily_max($conn);
    $quotaDay = br_setting_get($conn, 'blog_bulk_refresh_quota_day', '');
    $quotaUsed = (int) br_setting_get($conn, 'blog_bulk_refresh_quota_used', '0');
    if ($quotaDay !== $today) {
        $quotaUsed = 0;
    }
    $quotaRemaining = max(0, $dailyMax - $quotaUsed);

    echo json_encode([
        'success' => $processed > 0 || ($result['success'] ?? false),
        'done' => $processed === 0,
        'processed' => $processed,
        'candidates' => (int) ($result['candidates'] ?? 0),
        'qc_passed' => (int) ($result['qc_passed'] ?? 0),
        'qc_failed' => (int) ($result['qc_failed'] ?? 0),
        'fatal' => $result['fatal'] ?? null,
        'message' => $processed > 0
            ? '1 yazı işlendi.'
            : (($result['fatal'] ?? '') !== '' ? (string) $result['fatal'] : 'Bu adımda işlenecek yazı kalmadı veya kota doldu.'),
        'logs' => array_slice($result['logs'] ?? [], -12),
        'quota' => [
            'daily_max' => $dailyMax,
            'used' => $quotaUsed,
            'remaining' => $quotaRemaining,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'done' => true,
        'message' => 'Hata: ' . $e->getMessage(),
        'logs' => ['[FATAL] ' . $e->getMessage()],
    ], JSON_UNESCAPED_UNICODE);
}
