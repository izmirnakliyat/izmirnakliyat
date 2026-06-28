<?php
declare(strict_types=1);

/**
 * Admin AJAX: ortak oturum + DB. Her admin/ajax/*.php dosyasının en başında bir kez include edin.
 */

if (!defined('MYNAK_ADMIN_AJAX_BOOTSTRAP')) {
    define('MYNAK_ADMIN_AJAX_BOOTSTRAP', true);
}

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/db.php';

function mynak_admin_ajax_is_logged_in(): bool
{
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function mynak_admin_ajax_buffer_reset(): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}

function mynak_admin_ajax_require_json(): void
{
    if (mynak_admin_ajax_is_logged_in()) {
        return;
    }
    mynak_admin_ajax_buffer_reset();
    if (!headers_sent()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'success' => false,
        'message' => 'Oturum gerekli veya yetkisiz erişim.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function mynak_admin_ajax_require_plain(): void
{
    if (mynak_admin_ajax_is_logged_in()) {
        return;
    }
    mynak_admin_ajax_buffer_reset();
    if (!headers_sent()) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo 'Yetkisiz erişim';
    exit;
}
