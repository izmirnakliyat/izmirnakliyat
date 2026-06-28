<?php
$page_title = "Admin Kullanıcıları";
require_once 'includes/header.php';

// Yetki kontrolü
if (!hasPermission('admin_view')) {
    echo "<div class='alert alert-danger'>Bu sayfayı görüntüleme yetkiniz bulunmamaktadır.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Kullanıcı silme işlemi
if (isset($_GET['delete_id']) && hasPermission('admin_delete')) {
    $deleteId = (int)$_GET['delete_id'];
    
    // Super Admin (ID 1) silinemez
    if ($deleteId == 1) {
        $alertMessage = "Super Admin hesabı silinemez!";
        $alertType = "danger";
    } else {
        // Kullanıcıyı sil
        $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ? AND id != 1");
        $stmt->bind_param("i", $deleteId);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $alertMessage = "Kullanıcı başarıyla silindi.";
            $alertType = "success";
        } else {
            $alertMessage = "Kullanıcı silinemedi: " . $conn->error;
            $alertType = "danger";
        }
    }
}

// Kullanıcı durumunu değiştirme
if (isset($_GET['toggle_status']) && hasPermission('admin_edit')) {
    $userId = (int)$_GET['toggle_status'];
    $newStatus = $_GET['status'] === 'active' ? 'inactive' : 'active';
    
    // Super Admin (ID 1) durumu değiştirilemez
    if ($userId == 1) {
        $alertMessage = "Super Admin hesabı devre dışı bırakılamaz!";
        $alertType = "danger";
    } else {
        $stmt = $conn->prepare("UPDATE admin_users SET status = ? WHERE id = ? AND id != 1");
        $stmt->bind_param("si", $newStatus, $userId);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $alertMessage = "Kullanıcı durumu başarıyla güncellendi.";
            $alertType = "success";
        } else {
            $alertMessage = "Kullanıcı durumu güncellenemedi: " . $conn->error;
            $alertType = "danger";
        }
    }
}

// Kullanıcıları getir
$sql = "SELECT au.*, ar.role_name 
        FROM admin_users au
        LEFT JOIN admin_roles ar ON au.role_id = ar.id
        ORDER BY au.id";
$result = $conn->query($sql);
?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Admin Kullanıcıları</h5>
        <?php if (hasPermission('admin_add')): ?>
        <a href="admin_edit.php" class="btn btn-primary btn-sm">
            <i class="bx bx-plus"></i> Yeni Kullanıcı Ekle
        </a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (isset($alertMessage)): ?>
            <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                <?php echo $alertMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="adminTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kullanıcı Adı</th>
                        <th>Ad Soyad</th>
                        <th>E-posta</th>
                        <th>Rol</th>
                        <th>Durum</th>
                        <th>Son Giriş</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['full_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $row['role_id'] == 1 ? 'danger' : ($row['role_id'] == 2 ? 'primary' : 'info'); ?>">
                                <?php echo htmlspecialchars($row['role_name'] ?? 'Belirsiz'); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (hasPermission('admin_edit') && $row['id'] != 1): ?>
                                <a href="?toggle_status=<?php echo $row['id']; ?>&status=<?php echo $row['status']; ?>" 
                                   class="badge bg-<?php echo $row['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo $row['status'] == 'active' ? 'Aktif' : 'Pasif'; ?>
                                </a>
                            <?php else: ?>
                                <span class="badge bg-<?php echo $row['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo $row['status'] == 'active' ? 'Aktif' : 'Pasif'; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $row['last_login'] ? date('d.m.Y H:i', strtotime($row['last_login'])) : 'Giriş Yapılmadı'; ?></td>
                        <td>
                            <?php if (hasPermission('admin_edit')): ?>
                                <a href="admin_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="Düzenle">
                                    <i class="bx bx-edit"></i>
                                </a>
                            <?php endif; ?>

                            <?php if (hasPermission('admin_delete') && $row['id'] != 1 && $row['id'] != $_SESSION['admin_id']): ?>
                                <button class="btn btn-sm btn-danger delete-btn" 
                                        data-id="<?php echo $row['id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($row['username']); ?>"
                                        title="Sil">
                                    <i class="bx bx-trash"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kullanıcıyı Sil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>
                    <span id="deleteUserName"></span> kullanıcısını silmek istediğinize emin misiniz?
                    Bu işlem geri alınamaz.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <a href="#" id="deleteUserBtn" class="btn btn-danger">Sil</a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // DataTable initialization
    $('#adminTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Turkish.json"
        },
        "order": [[0, "asc"]]
    });
    
    // Delete modal setup
    $('.delete-btn').on('click', function() {
        const userId = $(this).data('id');
        const userName = $(this).data('name');
        
        $('#deleteUserName').text(userName);
        $('#deleteUserBtn').attr('href', '?delete_id=' + userId);
        
        $('#deleteModal').modal('show');
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
