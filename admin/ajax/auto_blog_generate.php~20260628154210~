<?php
/**
 * Otomatik Blog — manuel üretim (Content Engine Faz 1 + QC gate).
 */
require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

@set_time_limit(300);
@ini_set('max_execution_time', '300');
ini_set('display_startup_errors', '1');

function debug_log($msg): void
{
    $logDir = realpath(__DIR__ . '/../../logs');
    if ($logDir === false) {
        @mkdir(__DIR__ . '/../../logs', 0777, true);
        $logDir = realpath(__DIR__ . '/../../logs');
    }
    $logFile = ($logDir ?: __DIR__) . '/openai_debug.log';
    @file_put_contents($logFile, date('Y-m-d H:i:s') . ' ' . print_r($msg, true) . "\n\n", FILE_APPEND);
}

include '../includes/init.php';
include '../includes/auto_blog_functions.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auto_blog_ce_adapter.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['id']) || $_POST['id'] === '') {
    debug_log('ID eksik!');
    echo json_encode(['success' => false, 'message' => 'ID eksik!'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    debug_log(['POST_data' => $_POST, 'pipeline' => 'auto_blog_ce_faz1']);

    $setting = get_auto_blog_setting((int) $_POST['id']);
    if (!$setting) {
        throw new Exception('Ayar bulunamadı!');
    }

    $previewMode = isset($_POST['preview_mode']) && $_POST['preview_mode'] === '1';
    $ignoreQuota = !empty($_POST['ignore_quota']);

    $result = ab_ce_generate_article($conn, $setting, [
        'preview_mode' => $previewMode,
        'ignore_quota' => $ignoreQuota,
    ]);

    debug_log(['ab_ce_result' => array_diff_key($result, ['preview' => 1])]);

    if (!($result['success'] ?? false)) {
        echo json_encode([
            'success' => false,
            'message' => $result['message'] ?? 'Üretim başarısız.',
            'quota' => $result['quota'] ?? null,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($previewMode && !empty($result['preview'])) {
        echo json_encode([
            'success' => true,
            'preview' => $result['preview'],
            'qc' => $result['qc'] ?? null,
            'quota' => $result['quota'] ?? null,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'success' => (bool) ($result['success'] ?? false),
        'message' => $result['message'] ?? '',
        'blog_id' => $result['blog_id'] ?? null,
        'slug' => $result['slug'] ?? '',
        'durum' => $result['durum'] ?? null,
        'qc_pass' => $result['qc_pass'] ?? false,
        'qc' => $result['qc'] ?? null,
        'in_review' => $result['in_review'] ?? false,
        'needs_revision' => $result['needs_revision'] ?? false,
        'review_url' => $result['review_url'] ?? null,
        'quota' => $result['quota'] ?? null,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    debug_log(['exception_error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    echo json_encode(['success' => false, 'message' => 'Sistem hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
    debug_log(['fatal_error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    echo json_encode(['success' => false, 'message' => 'Kritik hata: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
