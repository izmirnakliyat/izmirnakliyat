<?php
declare(strict_types=1);
/**
 * Acil admin şifre sıfırlama — yalnızca yerel / izinli ortam + admin oturumu.
 * Kaynak kodda asla sabit şifre tutulmaz; şifre yalnızca formdan alınır (bir kez hashlenir).
 */
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/environment.php';
if (!mynak_allow_dev_debug_tools()) {
    http_response_code(404);
    exit('Not found.');
}
require_once __DIR__ . '/includes/require_admin_web.php';

$errors = [];
$success = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['update_password'] ?? '') === '1') {
    $username = trim((string) ($_POST['username'] ?? 'admin'));
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['new_password_confirm'] ?? '');

    if ($username === '') {
        $errors[] = 'Kullanıcı adı gerekli.';
    }
    if (strlen($newPassword) < 12) {
        $errors[] = 'Şifre en az 12 karakter olmalı.';
    }
    if ($newPassword !== $confirm) {
        $errors[] = 'Şifre tekrarı eşleşmiyor.';
    }

    if ($errors === []) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE admin_users SET password = ? WHERE username = ?');
        if ($stmt) {
            $stmt->bind_param('ss', $hashedPassword, $username);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $success = 'Şifre güncellendi. Bu sayfayı silin veya erişimi kapatın; girişte yeni şifreyi kullanın.';
            } else {
                $errors[] = 'Kullanıcı bulunamadı veya şifre zaten aynı: ' . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8');
            }
            $stmt->close();
        } else {
            $errors[] = 'Veritabanı hazırlığı başarısız.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Admin şifre güncelleme</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 24px; max-width: 640px; }
        .err { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin: 12px 0; }
        .ok { background: #d1e7dd; color: #0f5132; padding: 12px; border-radius: 6px; margin: 12px 0; }
        label { display: block; margin: 12px 0 4px; font-weight: 600; }
        input[type="text"], input[type="password"] { width: 100%; max-width: 400px; padding: 8px; }
        button { margin-top: 16px; padding: 10px 20px; background: #c00; color: #fff; border: none; border-radius: 6px; cursor: pointer; }
        table { border-collapse: collapse; width: 100%; margin: 16px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>Admin şifre güncelleme</h1>
    <p><strong>Uyarı:</strong> İş bitince bu dosyayı sunucudan kaldırın veya erişimi kapatın.</p>

    <?php foreach ($errors as $e): ?>
        <div class="err"><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>
    <?php if ($success !== ''): ?>
        <div class="ok"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <h2>Mevcut admin kullanıcıları</h2>
    <?php
    $result = $conn->query('SELECT id, username, full_name, status FROM admin_users');
    if ($result && $result->num_rows > 0): ?>
        <table>
            <tr><th>ID</th><th>Kullanıcı</th><th>Ad</th><th>Durum</th></tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo (int) $row['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars((string) $row['username'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                    <td><?php echo htmlspecialchars((string) $row['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) $row['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>Admin kullanıcısı bulunamadı.</p>
    <?php endif; ?>

    <h2>Yeni şifre belirle</h2>
    <form method="post" autocomplete="off">
        <input type="hidden" name="update_password" value="1">
        <label for="username">Kullanıcı adı</label>
        <input id="username" type="text" name="username" value="admin" required>

        <label for="new_password">Yeni şifre (min. 12 karakter)</label>
        <input id="new_password" type="password" name="new_password" required minlength="12">

        <label for="new_password_confirm">Yeni şifre (tekrar)</label>
        <input id="new_password_confirm" type="password" name="new_password_confirm" required minlength="12">

        <button type="submit">Şifreyi güncelle</button>
    </form>
</body>
</html>
