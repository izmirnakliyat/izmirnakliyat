<?php
$page_title = "Admin Kullanıcısı Ekle/Düzenle";
require_once 'includes/header.php';

// Yetki kontrolü
if (!hasPermission('admin_add') && !hasPermission('admin_edit')) {
    echo "<div class='alert alert-danger'>Bu sayfayı görüntüleme yetkiniz bulunmamaktadır.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Rolleri getir
$rolesQuery = "SELECT id, role_name FROM admin_roles ORDER BY id";
$rolesResult = $conn->query($rolesQuery);
$roles = [];
while ($role = $rolesResult->fetch_assoc()) {
    $roles[] = $role;
}

$errors = [];
$admin = [
    'id' => '',
    'username' => '',
    'full_name' => '',
    'email' => '',
    'role_id' => '',
    'status' => 'active'
];

// Düzenleme modunda kullanıcı bilgilerini getir
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $adminId = (int)$_GET['id'];
    
    if (!hasPermission('admin_edit')) {
        echo "<div class='alert alert-danger'>Admin kullanıcılarını düzenleme yetkiniz bulunmamaktadır.</div>";
        require_once 'includes/footer.php';
        exit;
    }
    
    $stmt = $conn->prepare("SELECT id, username, full_name, email, role_id, status FROM admin_users WHERE id = ?");
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "<div class='alert alert-danger'>Kullanıcı bulunamadı.</div>";
        require_once 'includes/footer.php';
        exit;
    }
    
    $admin = $result->fetch_assoc();
    $isEdit = true;
    $submitButtonText = "Güncelle";
} else {
    if (!hasPermission('admin_add')) {
        echo "<div class='alert alert-danger'>Yeni admin kullanıcısı ekleme yetkiniz bulunmamaktadır.</div>";
        require_once 'includes/footer.php';
        exit;
    }
    
    $isEdit = false;
    $submitButtonText = "Ekle";
}

// Form gönderildi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin['username'] = trim($_POST['username']);
    $admin['full_name'] = trim($_POST['full_name']);
    $admin['email'] = trim($_POST['email']);
    $admin['role_id'] = isset($_POST['role_id']) ? (int)$_POST['role_id'] : '';
    $admin['status'] = isset($_POST['status']) ? $_POST['status'] : 'active';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $passwordConfirm = isset($_POST['password_confirm']) ? trim($_POST['password_confirm']) : '';
    
    // Kullanıcı adı kontrolü
    if (empty($admin['username'])) {
        $errors[] = "Kullanıcı adı gereklidir.";
    } elseif (strlen($admin['username']) < 3) {
        $errors[] = "Kullanıcı adı en az 3 karakter olmalıdır.";
    } else {
        // Kullanıcı adı benzersiz mi kontrol et
        $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ? AND id != ?");
        $id = $isEdit ? $admin['id'] : 0;
        $stmt->bind_param("si", $admin['username'], $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Bu kullanıcı adı zaten kullanılmaktadır.";
        }
    }
    
    // Email kontrolü
    if (!empty($admin['email']) && !filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçerli bir e-posta adresi girin.";
    }
    
    // Rol kontrolü
    if (empty($admin['role_id'])) {
        $errors[] = "Lütfen bir rol seçin.";
    }
    
    // Parola kontrolü
    if (!$isEdit) {
        // Yeni kullanıcı için parola gerekli
        if (empty($password)) {
            $errors[] = "Parola gereklidir.";
        } elseif (strlen($password) < 6) {
            $errors[] = "Parola en az 6 karakter olmalıdır.";
        } elseif ($password !== $passwordConfirm) {
            $errors[] = "Parolalar eşleşmiyor.";
        }
    } else {
        // Mevcut kullanıcı için parola opsiyonel
        if (!empty($password) && strlen($password) < 6) {
            $errors[] = "Parola en az 6 karakter olmalıdır.";
        } elseif (!empty($password) && $password !== $passwordConfirm) {
            $errors[] = "Parolalar eşleşmiyor.";
        }
    }
    
    // Super Admin (ID 1) rolü ve durumu değiştirilemez
    if ($isEdit && $admin['id'] == 1) {
        if ($admin['role_id'] != 1) {
            $errors[] = "Super Admin rolü değiştirilemez.";
            $admin['role_id'] = 1;
        }
        if ($admin['status'] != 'active') {
            $errors[] = "Super Admin hesabı devre dışı bırakılamaz.";
            $admin['status'] = 'active';
        }
    }
    
    // Hata yoksa işlem yap
    if (empty($errors)) {
        if ($isEdit) {
            // Mevcut kullanıcıyı güncelle
            if (!empty($password)) {
                // Parola değiştirilecekse
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE admin_users SET username = ?, full_name = ?, email = ?, role_id = ?, status = ?, password = ? WHERE id = ?");
                $stmt->bind_param("ssssssi", $admin['username'], $admin['full_name'], $admin['email'], $admin['role_id'], $admin['status'], $passwordHash, $admin['id']);
            } else {
                // Parola değiştirilmeyecekse
                $stmt = $conn->prepare("UPDATE admin_users SET username = ?, full_name = ?, email = ?, role_id = ?, status = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $admin['username'], $admin['full_name'], $admin['email'], $admin['role_id'], $admin['status'], $admin['id']);
            }
            
            $message = "Kullanıcı başarıyla güncellendi.";
        } else {
            // Yeni kullanıcı ekle
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO admin_users (username, full_name, email, role_id, status, password) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssiss", $admin['username'], $admin['full_name'], $admin['email'], $admin['role_id'], $admin['status'], $passwordHash);
            
            $message = "Yeni kullanıcı başarıyla eklendi.";
        }
        
        if ($stmt->execute()) {
            // Başarılı işlem sonrası yönlendirme
            header("Location: admin_users.php?message=" . urlencode($message) . "&type=success");
            exit;
        } else {
            $errors[] = "Veritabanı hatası: " . $conn->error;
        }
    }
}

?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?php echo $isEdit ? 'Admin Kullanıcısını Düzenle' : 'Yeni Admin Kullanıcısı Ekle'; ?></h5>
        <a href="admin_users.php" class="btn btn-secondary btn-sm">
            <i class="bx bx-arrow-back"></i> Geri Dön
        </a>
    </div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Kullanıcı Adı <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="full_name" class="form-label">Ad Soyad</label>
                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($admin['full_name'] ?? ''); ?>">
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">E-posta</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>">
            </div>
            
            <div class="mb-3">
                <label for="role_id" class="form-label">Rol <span class="text-danger">*</span></label>
                <select class="form-select" id="role_id" name="role_id" required<?php echo ($isEdit && $admin['id'] == 1) ? ' disabled' : ''; ?>>
                    <option value="">Rol Seçin</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['id']; ?>" <?php echo ($admin['role_id'] == $role['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($role['role_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($isEdit && $admin['id'] == 1): ?>
                    <input type="hidden" name="role_id" value="1">
                <?php endif; ?>
            </div>
            
            <div class="mb-3">
                <label for="status" class="form-label">Durum</label>
                <select class="form-select" id="status" name="status"<?php echo ($isEdit && $admin['id'] == 1) ? ' disabled' : ''; ?>>
                    <option value="active" <?php echo ($admin['status'] == 'active') ? 'selected' : ''; ?>>Aktif</option>
                    <option value="inactive" <?php echo ($admin['status'] == 'inactive') ? 'selected' : ''; ?>>Pasif</option>
                </select>
                <?php if ($isEdit && $admin['id'] == 1): ?>
                    <input type="hidden" name="status" value="active">
                <?php endif; ?>
            </div>
            
            <hr>
            
            <div class="mb-3">
                <label for="password" class="form-label"><?php echo $isEdit ? 'Yeni Parola (değiştirmek için doldurun)' : 'Parola <span class="text-danger">*</span>'; ?></label>
                <input type="password" class="form-control" id="password" name="password" <?php echo $isEdit ? '' : 'required'; ?>>
                <?php if ($isEdit): ?>
                    <small class="text-muted">Parolayı değiştirmek istemiyorsanız boş bırakın.</small>
                <?php endif; ?>
            </div>
            
            <div class="mb-3">
                <label for="password_confirm" class="form-label">Parola Tekrar</label>
                <input type="password" class="form-control" id="password_confirm" name="password_confirm">
            </div>
            
            <button type="submit" class="btn btn-primary mt-3">
                <i class="bx bx-save"></i> <?php echo $submitButtonText; ?>
            </button>
            
            <a href="admin_users.php" class="btn btn-secondary mt-3">
                <i class="bx bx-x"></i> İptal
            </a>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 