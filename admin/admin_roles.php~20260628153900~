<?php
$page_title = "Rol Yönetimi";
require_once 'includes/header.php';

// Yetki kontrolü
if (!hasPermission('role_view')) {
    echo "<div class='alert alert-danger'>Bu sayfayı görüntüleme yetkiniz bulunmamaktadır.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Rol silme işlemi
if (isset($_GET['delete_id']) && hasPermission('role_delete')) {
    $deleteId = (int)$_GET['delete_id'];
    
    // Super Admin (ID 1) silinemez
    if ($deleteId == 1) {
        $alertMessage = "Super Admin rolü silinemez!";
        $alertType = "danger";
    } else {
        // Mevcut kullanıcı sayısını kontrol et
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_users WHERE role_id = ?");
        $checkStmt->bind_param("i", $deleteId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $userCount = $result->fetch_assoc()['count'];
        
        if ($userCount > 0) {
            $alertMessage = "Bu role sahip kullanıcılar olduğu için silinemez. Önce kullanıcıların rolünü değiştirin.";
            $alertType = "warning";
        } else {
            // Rolü sil
            $stmt = $conn->prepare("DELETE FROM admin_roles WHERE id = ? AND id != 1");
            $stmt->bind_param("i", $deleteId);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                // İlişkili izinleri de sil (role_permissions tablosundan)
                $conn->query("DELETE FROM role_permissions WHERE role_id = $deleteId");
                
                $alertMessage = "Rol başarıyla silindi.";
                $alertType = "success";
            } else {
                $alertMessage = "Rol silinemedi: " . $conn->error;
                $alertType = "danger";
            }
        }
    }
}

// Rolleri ve ilişkili kullanıcı sayılarını getir
$sql = "SELECT r.id, r.role_name, r.description, r.created_at, 
        (SELECT COUNT(*) FROM admin_users WHERE role_id = r.id) as user_count
        FROM admin_roles r
        ORDER BY r.id";
$result = $conn->query($sql);
?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Roller</h5>
        <?php if (hasPermission('role_add')): ?>
        <a href="role_edit.php" class="btn btn-primary btn-sm">
            <i class="bx bx-plus"></i> Yeni Rol Ekle
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
        
        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-<?php echo $_GET['type'] ?? 'success'; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="rolesTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Rol Adı</th>
                        <th>Açıklama</th>
                        <th>Kullanıcı Sayısı</th>
                        <th>Oluşturulma Tarihi</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td>
                            <span class="fw-bold"><?php echo htmlspecialchars($row['role_name']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($row['description'] ?? ''); ?></td>
                        <td>
                            <?php if ($row['user_count'] > 0): ?>
                                <span class="badge bg-info"><?php echo $row['user_count']; ?> kullanıcı</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Kullanıcı yok</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d.m.Y', strtotime($row['created_at'])); ?></td>
                        <td>
                            <?php if (hasPermission('role_edit')): ?>
                                <a href="role_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="Düzenle">
                                    <i class="bx bx-edit"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('role_delete') && $row['id'] != 1): ?>
                                <button class="btn btn-sm btn-danger delete-btn" 
                                        data-id="<?php echo $row['id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($row['role_name']); ?>"
                                        data-users="<?php echo $row['user_count']; ?>"
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
                <h5 class="modal-title">Rolü Sil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="deleteRoleMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <a href="#" id="deleteRoleBtn" class="btn btn-danger">Sil</a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // DataTable initialization
    $('#rolesTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Turkish.json"
        },
        "order": [[0, "asc"]]
    });
    
    // Delete modal setup
    $('.delete-btn').on('click', function() {
        const roleId = $(this).data('id');
        const roleName = $(this).data('name');
        const userCount = $(this).data('users');
        
        if (userCount > 0) {
            $('#deleteRoleMessage').html(`<div class="alert alert-warning">
                <strong>${roleName}</strong> rolünü kullanan <strong>${userCount}</strong> kullanıcı var. 
                Bu role sahip kullanıcılar olduğu için silemezsiniz. Önce kullanıcıların rolünü değiştirin.
            </div>`);
            $('#deleteRoleBtn').hide();
        } else {
            $('#deleteRoleMessage').text(`${roleName} rolünü silmek istediğinize emin misiniz? Bu işlem geri alınamaz.`);
            $('#deleteRoleBtn').show();
            $('#deleteRoleBtn').attr('href', '?delete_id=' + roleId);
        }
        
        $('#deleteModal').modal('show');
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 