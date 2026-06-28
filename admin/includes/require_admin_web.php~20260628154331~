<?php
declare(strict_types=1);

/**
 * Admin web: config + DB + oturum. login.php ve logout.php hariç tüm admin kök betikleri
 * ve includes/header.php bu dosyayı kullanır.
 */

if (defined('MYNAK_ADMIN_WEB_GUARD_LOADED')) {
    return;
}
define('MYNAK_ADMIN_WEB_GUARD_LOADED', true);

$adminDir = dirname(__DIR__);
$projectRoot = dirname($adminDir);

require_once $projectRoot . '/config/config.php';
require_once $projectRoot . '/config/db.php';
require_once __DIR__ . '/admin_safe_redirect.php';

/**
 * @return list<string>
 */
function mynak_admin_web_public_scripts(): array
{
    return ['login.php', 'logout.php', 'login_2fa.php'];
}

function mynak_admin_web_require_login(): void
{
    $script = $_SERVER['SCRIPT_FILENAME'] ?? '';
    $base = $script !== '' && is_string($script) ? basename($script) : basename((string) ($_SERVER['PHP_SELF'] ?? ''));
    if (in_array($base, mynak_admin_web_public_scripts(), true)) {
        return;
    }
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        return;
    }
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $target = 'login.php';
    $rel = admin_login_redirect_param_from_request_uri($uri);
    if ($rel !== '' && strpos($uri, 'login.php') === false) {
        $target .= '?redirect=' . rawurlencode($rel);
    }
    header('Location: ' . $target);
    exit;
}

mynak_admin_web_require_login();
