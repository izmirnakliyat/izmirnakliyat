<?php
$page_title = "Rol Ekle/Düzenle";
require_once 'includes/header.php';

// Yetki kontrolü
if (!hasPermission('role_add') && !hasPermission('role_edit')) {
    echo "<div class='alert alert-danger'>Bu sayfayı görüntüleme yetkiniz bulunmamaktadır.</div>";
    require_once 'includes/footer.php';
    exit;
}

$errors = [];
$role = [
    'id' => '',
    'role_name' => '',
    'description' => ''
];

// Düzenleme modunda rol bilgilerini getir
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $roleId = (int)$_GET['id'];
    
    if (!hasPermission('role_edit')) {
        echo "<div class='alert alert-danger'>Rolleri düzenleme yetkiniz bulunmamaktadır.</div>";
        require_once 'includes/footer.php';
        exit;
    }
    
    // Super Admin (ID 1) rol düzenlemesinde özel durum
    if ($roleId == 1 && $_SESSION['admin_id'] != 1) {
        echo "<div class='alert alert-danger'>Super Admin rolünü sadece Super Admin düzenleyebilir.</div>";
        require_once 'includes/footer.php';
        exit;
    }
    
    $stmt = $conn->prepare("SELECT id, role_name, description FROM admin_roles WHERE id = ?");
    $stmt->bind_param("i", $roleId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "<div class='alert alert-danger'>Rol bulunamadı.</div>";
        require_once 'includes/footer.php';
        exit;
    }
    
    $role = $result->fetch_assoc();
    $isEdit = true;
    $submitButtonText = "Güncelle";
    
    // Mevcut izinleri getir
    $rolePermissions = [];
    $permStmt = $conn->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
    $permStmt->bind_param("i", $roleId);
    $permStmt->execute();
    $permResult = $permStmt->get_result();
    
    while ($row = $permResult->fetch_assoc()) {
        $rolePermissions[] = $row['permission_id'];
    }
} else {
    if (!hasPermission('role_add')) {
        echo "<div class='alert alert-danger'>Yeni rol ekleme yetkiniz bulunmamaktadır.</div>";
        require_once 'includes/footer.php';
        exit;
    }
    
    $isEdit = false;
    $submitButtonText = "Ekle";
    $rolePermissions = [];
}

// Tüm izinleri modül bazlı getir
$permissionsByModule = getPermissionsByModule();

// Form gönderildi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role['role_name'] = trim($_POST['role_name']);
    $role['description'] = trim($_POST['description']);
    $selectedPermissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
    
    // Rol adı kontrolü
    if (empty($role['role_name'])) {
        $errors[] = "Rol adı gereklidir.";
    } elseif (strlen($role['role_name']) < 3) {
        $errors[] = "Rol adı en az 3 karakter olmalıdır.";
    } else {
        // Rol adı benzersiz mi kontrol et
        $stmt = $conn->prepare("SELECT id FROM admin_roles WHERE role_name = ? AND id != ?");
        $id = $isEdit ? $role['id'] : 0;
        $stmt->bind_param("si", $role['role_name'], $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Bu rol adı zaten kullanılmaktadır.";
        }
    }
    
    // Super Admin (ID 1) rolünün adı değiştirilemez
    if ($isEdit && $role['id'] == 1 && $role['role_name'] != 'Super Admin') {
        $errors[] = "Super Admin rolünün adı değiştirilemez.";
        $role['role_name'] = 'Super Admin';
    }
    
    // Super Admin (ID 1) rolünün tüm izinlere sahip olması gerekir
    if ($isEdit && $role['id'] == 1 && count($selectedPermissions) < count(getAllPermissions())) {
        $errors[] = "Super Admin rolü tüm izinlere sahip olmalıdır.";
        
        // Tüm izinleri seç
        $allPermissions = getAllPermissions();
        $selectedPermissions = [];
        foreach ($allPermissions as $perm) {
            $selectedPermissions[] = $perm['id'];
        }
    }
    
    // Hata yoksa işlem yap
    if (empty($errors)) {
        if ($isEdit) {
            // Mevcut rolü güncelle
            $stmt = $conn->prepare("UPDATE admin_roles SET role_name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $role['role_name'], $role['description'], $role['id']);
            
            if ($stmt->execute()) {
                // Mevcut izinleri temizle
                $conn->query("DELETE FROM role_permissions WHERE role_id = " . $role['id']);
                
                // Yeni izinleri ekle
                if (!empty($selectedPermissions)) {
                    $insertValues = [];
                    foreach ($selectedPermissions as $permId) {
                        $insertValues[] = "(" . $role['id'] . ", " . (int)$permId . ")";
                    }
                    
                    if (!empty($insertValues)) {
                        $insertQuery = "INSERT INTO role_permissions (role_id, permission_id) VALUES " . implode(", ", $insertValues);
                        $conn->query($insertQuery);
                    }
                }
                
                $message = "Rol başarıyla güncellendi.";
            } else {
                $errors[] = "Veritabanı hatası: " . $conn->error;
            }
        } else {
            // Yeni rol ekle
            $stmt = $conn->prepare("INSERT INTO admin_roles (role_name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $role['role_name'], $role['description']);
            
            if ($stmt->execute()) {
                $newRoleId = $conn->insert_id;
                
                // İzinleri ekle
                if (!empty($selectedPermissions)) {
                    $insertValues = [];
                    foreach ($selectedPermissions as $permId) {
                        $insertValues[] = "(" . $newRoleId . ", " . (int)$permId . ")";
                    }
                    
                    if (!empty($insertValues)) {
                        $insertQuery = "INSERT INTO role_permissions (role_id, permission_id) VALUES " . implode(", ", $insertValues);
                        $conn->query($insertQuery);
                    }
                }
                
                $message = "Yeni rol başarıyla eklendi.";
            } else {
                $errors[] = "Veritabanı hatası: " . $conn->error;
            }
        }
        
        if (!isset($errors[0])) {
            // Başarılı işlem sonrası yönlendirme
            header("Location: admin_roles.php?message=" . urlencode($message) . "&type=success");
            exit;
        }
    }
}

?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?php echo $isEdit ? 'Rolü Düzenle' : 'Yeni Rol Ekle'; ?></h5>
        <a href="admin_roles.php" class="btn btn-secondary btn-sm">
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
                <label for="role_name" class="form-label">Rol Adı <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="role_name" name="role_name" 
                       value="<?php echo htmlspecialchars($role['role_name']); ?>" 
                       <?php echo ($isEdit && $role['id'] == 1) ? 'readonly' : ''; ?> required>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Açıklama</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($role['description'] ?? ''); ?></textarea>
            </div>
            
            <hr>
            
            <h5>İzinler</h5>
            <p class="text-muted">Bu role sahip kullanıcıların hangi işlemleri yapabileceğini seçin.</p>
            
            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="select-all">
                        <label class="form-check-label" for="select-all">
                            <strong>Tümünü Seç / Kaldır</strong>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <?php foreach ($permissionsByModule as $module => $permissions): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <div class="form-check">
                                <input class="form-check-input module-checkbox" type="checkbox" id="module-<?php echo $module; ?>" data-module="<?php echo $module; ?>">
                                <label class="form-check-label" for="module-<?php echo $module; ?>">
                                    <strong><?php echo ucfirst($module); ?></strong>
                                </label>
                            </div>
                        </div>
                        <div class="card-body pb-0">
                            <?php foreach ($permissions as $permission): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input permission-checkbox module-<?php echo $module; ?>" 
                                       type="checkbox" 
                                       id="permission-<?php echo $permission['id']; ?>" 
                                       name="permissions[]" 
                                       value="<?php echo $permission['id']; ?>"
                                       <?php echo in_array($permission['id'], $rolePermissions) ? 'checked' : ''; ?>
                                       <?php echo ($isEdit && $role['id'] == 1) ? 'checked disabled' : ''; ?>>
                                <label class="form-check-label" for="permission-<?php echo $permission['id']; ?>">
                                    <?php echo htmlspecialchars($permission['display_name']); ?>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars($permission['description']); ?></small>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Super Admin rolünün tüm izinleri her zaman seçili olacak şekilde gizli inputlar ekle -->
            <?php if ($isEdit && $role['id'] == 1): ?>
                <?php foreach (getAllPermissions() as $perm): ?>
                    <input type="hidden" name="permissions[]" value="<?php echo $perm['id']; ?>">
                <?php endforeach; ?>
            <?php endif; ?>
            
            <button type="submit" class="btn btn-primary mt-3">
                <i class="bx bx-save"></i> <?php echo $submitButtonText; ?>
            </button>
            
            <a href="admin_roles.php" class="btn btn-secondary mt-3">
                <i class="bx bx-x"></i> İptal
            </a>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Tümünü seç/kaldır checkbox işlemleri
    $('#select-all').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.permission-checkbox:not(:disabled)').prop('checked', isChecked);
        $('.module-checkbox:not(:disabled)').prop('checked', isChecked);
    });
    
    // Modül bazlı checkbox işlemleri
    $('.module-checkbox').on('change', function() {
        const module = $(this).data('module');
        const isChecked = $(this).prop('checked');
        $(`.module-${module}:not(:disabled)`).prop('checked', isChecked);
    });
    
    // Alt checkbox'ların durumuna göre üst checkbox'ları güncelle
    function updateModuleCheckboxes() {
        $('.module-checkbox').each(function() {
            const module = $(this).data('module');
            const totalPermissions = $(`.module-${module}`).length;
            const checkedPermissions = $(`.module-${module}:checked`).length;
            
            $(this).prop('checked', totalPermissions === checkedPermissions);
        });
        
        // Tümünü seç checkbox'ını güncelle
        const totalEnabled = $('.permission-checkbox:not(:disabled)').length;
        const totalChecked = $('.permission-checkbox:checked:not(:disabled)').length;
        
        $('#select-all').prop('checked', totalEnabled === totalChecked);
    }
    
    // İzin checkbox'larının değişimine göre üst modül checkbox'larını güncelle
    $('.permission-checkbox').on('change', updateModuleCheckboxes);
    
    // Sayfa ilk yüklendiğinde checkbox durumlarını güncelle
    updateModuleCheckboxes();
});
</script>

<?php require_once 'includes/footer.php'; ?> 