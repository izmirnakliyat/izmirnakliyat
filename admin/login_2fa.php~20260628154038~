<?php
/**
 * Madde 4 — 2FA Challenge Ekrani.
 * login.php sifreyi dogruladiktan sonra (2FA aktif kullaniciler icin) buraya yonlendirir.
 * Burada 6-haneli authenticator kodu VEYA 8 karakterli backup code kabul edilir.
 *
 * Oturum 5 dakika icinde tamamlanmazsa session bilgileri silinir ve login.php'ye geri donulur.
 */

declare(strict_types=1);

require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once __DIR__ . '/includes/totp_helper.php';
require_once __DIR__ . '/includes/admin_safe_redirect.php';

if (!isset($_SESSION)) { session_start(); }

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$pendingUserId = isset($_SESSION['pending_2fa_user_id']) ? (int) $_SESSION['pending_2fa_user_id'] : 0;
$startedAt     = isset($_SESSION['pending_2fa_started_at']) ? (int) $_SESSION['pending_2fa_started_at'] : 0;

if ($pendingUserId <= 0) {
    header('Location: login.php');
    exit;
}

if ($startedAt > 0 && (time() - $startedAt) > 300) {
    unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_username'], $_SESSION['pending_2fa_started_at'], $_SESSION['pending_2fa_redirect']);
    header('Location: login.php?expired=1');
    exit;
}

function login_2fa_safe_redirect(?string $raw): string
{
    return admin_login_safe_redirect($raw);
}

$error = '';

$stmt = $conn->prepare("SELECT id, username, totp_secret, totp_enabled, totp_backup_codes FROM admin_users WHERE id = ?");
$stmt->bind_param('i', $pendingUserId);
$stmt->execute();
$res = $stmt->get_result();
$user = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$user || empty($user['totp_enabled']) || empty($user['totp_secret'])) {
    unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_username'], $_SESSION['pending_2fa_started_at'], $_SESSION['pending_2fa_redirect']);
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = (string) ($_POST['code'] ?? '');
    $codeNorm = preg_replace('/\s+/', '', $code) ?? '';
    $verified = false;
    $usedBackup = false;
    $newBackupJson = null;

    if (preg_match('/^\d{6}$/', $codeNorm)) {
        $verified = mynak_totp_verify((string) $user['totp_secret'], $codeNorm);
    } else {
        $clean = strtoupper(preg_replace('/[^A-F0-9]/', '', $codeNorm) ?? '');
        if (strlen($clean) === 8) {
            $stored = mynak_totp_decode_backup_codes_json((string) ($user['totp_backup_codes'] ?? ''));
            $r = mynak_totp_consume_backup_code($stored, $clean);
            if ($r['ok']) {
                $verified = true;
                $usedBackup = true;
                $newBackupJson = json_encode($r['updated'], JSON_UNESCAPED_SLASHES);
            }
        }
    }

    if (!$verified) {
        $error = 'Kod gecersiz. Authenticator uygulamanizdaki guncel kodu veya kullanmadiginiz bir kurtarma kodunu girin.';
    } else {
        if ($usedBackup && $newBackupJson !== null) {
            $u = $conn->prepare("UPDATE admin_users SET totp_backup_codes = ? WHERE id = ?");
            $u->bind_param('si', $newBackupJson, $pendingUserId);
            $u->execute();
            $u->close();
        }

        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = (int) $user['id'];
        $_SESSION['admin_username'] = (string) $user['username'];
        if ($usedBackup) {
            $_SESSION['flash_2fa_used_backup'] = 1;
        }

        $redirectInput = (string) ($_SESSION['pending_2fa_redirect'] ?? '');
        unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_username'], $_SESSION['pending_2fa_started_at'], $_SESSION['pending_2fa_redirect']);

        $up = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
        $up->bind_param('i', $pendingUserId);
        $up->execute();
        $up->close();

        $target = login_2fa_safe_redirect($redirectInput);
        header('Location: ' . $target);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Dogrulama - Mynakliyat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-container { max-width: 420px; width: 90%; padding: 2rem; background: rgba(255, 255, 255, 0.96); border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); }
        .login-logo { text-align: center; margin-bottom: 1.5rem; }
        .login-logo h2 { color: #333; font-weight: 600; margin-bottom: .25rem; }
        .login-logo p { color: #666; font-size: .9rem; }
        .form-control { border-radius: 8px; padding: .8rem 1rem; border: 1px solid #ddd; background: #f8f9fa; }
        .form-control:focus { box-shadow: 0 0 0 .2rem rgba(102, 126, 234, .25); border-color: #667eea; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: .8rem; border-radius: 8px; font-weight: 500; }
        .code-input { font-size: 1.4rem; letter-spacing: 6px; text-align: center; font-family: 'Courier New', monospace; }
        .alert { border-radius: 8px; padding: 1rem; }
        .small-link { color: #667eea; font-size: .85rem; text-decoration: none; }
        .small-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <h2><i class="bx bx-shield-quarter"></i> 2FA Dogrulama</h2>
            <p>Hesap: <strong><?php echo htmlspecialchars((string) ($_SESSION['pending_2fa_username'] ?? '')); ?></strong></p>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="bx bxs-error-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <div class="mb-3">
                <label for="code" class="form-label">Authenticator Kodu</label>
                <input type="text" class="form-control code-input" id="code" name="code" maxlength="9" inputmode="numeric" pattern="[0-9A-Fa-f-]{6,9}" required autofocus placeholder="000000">
                <div class="form-text">6 haneli kodu veya kurtarma kodunuzu (XXXX-XXXX) girin.</div>
            </div>
            <button type="submit" class="btn btn-primary w-100"><i class="bx bx-check-shield"></i> Dogrula</button>
        </form>
        <div class="text-center mt-3">
            <a href="logout.php" class="small-link"><i class="bx bx-arrow-back"></i> Vazgec ve cikis yap</a>
        </div>
    </div>
</body>
</html>
