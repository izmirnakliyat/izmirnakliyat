<?php
// Footer kategori işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'category') {
        switch ($_POST['category_action']) {
            case 'add':
                $title = trim($_POST['title']);
                $position = !empty($_POST['position']) ? (int)$_POST['position'] : 0;
                $status = isset($_POST['status']) ? 1 : 0;
                
                $stmt = $conn->prepare("INSERT INTO footer_menu_categories (title, position, status) VALUES (?, ?, ?)");
                $stmt->bind_param("sii", $title, $position, $status);
                if ($stmt->execute()) {
                    $success = "Kategori başarıyla eklendi.";
                } else {
                    $error = "Kategori eklenirken bir hata oluştu.";
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $title = trim($_POST['title']);
                $position = !empty($_POST['position']) ? (int)$_POST['position'] : 0;
                $status = isset($_POST['status']) ? 1 : 0;
                
                $stmt = $conn->prepare("UPDATE footer_menu_categories SET title = ?, position = ?, status = ? WHERE id = ?");
                $stmt->bind_param("siii", $title, $position, $status, $id);
                if ($stmt->execute()) {
                    $success = "Kategori başarıyla güncellendi.";
                } else {
                    $error = "Kategori güncellenirken bir hata oluştu.";
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                $stmt = $conn->prepare("DELETE FROM footer_menu_categories WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $success = "Kategori başarıyla silindi.";
                } else {
                    $error = "Kategori silinirken bir hata oluştu.";
                }
                break;
        }
    }
}

// Kategori yükleme işlemi - düzenleme için
$edit_category = null;
if (isset($_GET['edit_category']) && !empty($_GET['edit_category'])) {
    $edit_id = (int)$_GET['edit_category'];
    $stmt = $conn->prepare("SELECT * FROM footer_menu_categories WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_category = $result->fetch_assoc();
    }
}

// Kategorileri getir
$categories = [];
$result = $conn->query("SELECT * FROM footer_menu_categories ORDER BY position ASC, id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($edit_category): ?>
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Kategori Düzenle</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="category">
                        <input type="hidden" name="category_action" value="edit">
                        <input type="hidden" name="id" value="<?php echo $edit_category['id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Başlık</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($edit_category['title']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Pozisyon</label>
                            <input type="number" name="position" class="form-control" value="<?php echo (int)$edit_category['position']; ?>" min="0">
                            <div class="form-text">Sıralama için kullanılır. Düşük sayılar üstte gösterilir.</div>
                        </div>
                        
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="status" id="categoryStatus" <?php echo $edit_category['status'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="categoryStatus">Aktif</label>
                        </div>
                        
                        <div class="d-flex">
                            <button type="submit" class="btn btn-primary me-2">Güncelle</button>
                            <a href="footer_management.php" class="btn btn-secondary">İptal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Başlık</th>
                <th>Pozisyon</th>
                <th>Durum</th>
                <th>Oluşturulma Tarihi</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($categories) > 0): ?>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?php echo $category['id']; ?></td>
                        <td><?php echo htmlspecialchars($category['title']); ?></td>
                        <td><?php echo $category['position']; ?></td>
                        <td>
                            <?php if ($category['status']): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Pasif</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($category['created_at'])); ?></td>
                        <td>
                            <a href="footer_management.php?edit_category=<?php echo $category['id']; ?>" class="btn btn-sm btn-info">
                                <i class="bx bx-edit"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteCategory(<?php echo $category['id']; ?>)">
                                <i class="bx bx-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">Henüz kategori eklenmemiş.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Kategori Silme Form - Gizli -->
<form id="deleteCategoryForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="category">
    <input type="hidden" name="category_action" value="delete">
    <input type="hidden" name="id" id="delete_category_id">
</form>

<!-- Kategori Ekleme Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Kategori Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="category">
                    <input type="hidden" name="category_action" value="add">
                    
                    <div class="mb-3">
                        <label class="form-label">Başlık</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Pozisyon</label>
                        <input type="number" name="position" class="form-control" value="0" min="0">
                        <div class="form-text">Sıralama için kullanılır. Düşük sayılar üstte gösterilir.</div>
                    </div>
                    
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="status" id="newCategoryStatus" checked>
                        <label class="form-check-label" for="newCategoryStatus">Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDeleteCategory(id) {
    if (confirm('Bu kategoriyi silmek istediğinize emin misiniz? Bu işlem geri alınamaz ve kategoriye ait tüm menü öğeleri de silinecektir.')) {
        document.getElementById('delete_category_id').value = id;
        document.getElementById('deleteCategoryForm').submit();
    }
}
</script> 