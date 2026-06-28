<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once __DIR__ . '/includes/admin_safe_redirect.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $redirect = admin_login_safe_redirect($_GET['redirect'] ?? null);
    header('Location: ' . $redirect);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Lütfen tüm alanları doldurun.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, totp_enabled FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $login_rows = mysqli_stmt_fetch_all_assoc($stmt);
        $stmt->close();

        if (count($login_rows) === 1) {
            $user = $login_rows[0];
            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true);

                // Madde 4: 2FA aktif ise once challenge ekranina gonder
                if (!empty($user['totp_enabled'])) {
                    $_SESSION['pending_2fa_user_id'] = (int) $user['id'];
                    $_SESSION['pending_2fa_username'] = $user['username'];
                    $_SESSION['pending_2fa_started_at'] = time();
                    if (isset($_GET['redirect'])) {
                        $_SESSION['pending_2fa_redirect'] = (string) $_GET['redirect'];
                    } else {
                        unset($_SESSION['pending_2fa_redirect']);
                    }
                    header('Location: login_2fa.php');
                    exit;
                }

                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];

                $updateStmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $updateStmt->bind_param("i", $user['id']);
                $updateStmt->execute();

                $redirect = admin_login_safe_redirect($_GET['redirect'] ?? null);
                header('Location: ' . $redirect);
                exit;
            } else {
                $error = 'Geçersiz kullanıcı adı veya şifre.';
            }
        } else {
            $error = 'Geçersiz kullanıcı adı veya şifre.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi - Mynakliyat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 400px;
            width: 90%;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo h2 {
            color: #333;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .login-logo p {
            color: #666;
            font-size: 0.9rem;
        }
        .form-control {
            border-radius: 8px;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            background: #f8f9fa;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            border-color: #667eea;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.8rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .input-group-text {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-right: none;
        }
        .alert {
            border-radius: 8px;
            padding: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <h2>Mynakliyat Admin</h2>
            <p>Seo Dostu Özel Admin Paneli</p>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class='bx bxs-error-circle'></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">
            <div class="mb-4">
                <label for="username" class="form-label">Kullanıcı Adı</label>
                <div class="input-group">
                    <span class="input-group-text"><i class='bx bxs-user'></i></span>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Şifre</label>
                <div class="input-group">
                    <span class="input-group-text"><i class='bx bxs-lock-alt'></i></span>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class='bx bxs-log-in'></i> Giriş Yap
            </button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 