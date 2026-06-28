<?php
// Footer menü öğeleri işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'menu_item') {
        switch ($_POST['menu_item_action']) {
            case 'add':
                $category_id = (int)$_POST['category_id'];
                $title = trim($_POST['title']);
                $url = trim($_POST['url']);
                $target = $_POST['target'];
                $order_number = !empty($_POST['order_number']) ? (int)$_POST['order_number'] : 0;
                $status = isset($_POST['status']) ? 1 : 0;
                
                $stmt = $conn->prepare("INSERT INTO footer_menu_items (category_id, title, url, target, order_number, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssii", $category_id, $title, $url, $target, $order_number, $status);
                if ($stmt->execute()) {
                    $success = "Menü öğesi başarıyla eklendi.";
                } else {
                    $error = "Menü öğesi eklenirken bir hata oluştu.";
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $category_id = (int)$_POST['category_id'];
                $title = trim($_POST['title']);
                $url = trim($_POST['url']);
                $target = $_POST['target'];
                $order_number = !empty($_POST['order_number']) ? (int)$_POST['order_number'] : 0;
                $status = isset($_POST['status']) ? 1 : 0;
                
                $stmt = $conn->prepare("UPDATE footer_menu_items SET category_id = ?, title = ?, url = ?, target = ?, order_number = ?, status = ? WHERE id = ?");
                $stmt->bind_param("isssiis", $category_id, $title, $url, $target, $order_number, $status, $id);
                if ($stmt->execute()) {
                    $success = "Menü öğesi başarıyla güncellendi.";
                } else {
                    $error = "Menü öğesi güncellenirken bir hata oluştu.";
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                $stmt = $conn->prepare("DELETE FROM footer_menu_items WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $success = "Menü öğesi başarıyla silindi.";
                } else {
                    $error = "Menü öğesi silinirken bir hata oluştu.";
                }
                break;
        }
    }
}

// Menü öğesi yükleme işlemi - düzenleme için
$edit_menu_item = null;
if (isset($_GET['edit_menu_item']) && !empty($_GET['edit_menu_item'])) {
    $edit_id = (int)$_GET['edit_menu_item'];
    $stmt = $conn->prepare("SELECT * FROM footer_menu_items WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_menu_item = $result->fetch_assoc();
    }
}

// Kategorileri getir (select için)
$categories_list = [];
$result = $conn->query("SELECT id, title FROM footer_menu_categories WHERE status = 1 ORDER BY position ASC, id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories_list[$row['id']] = $row['title'];
    }
}

// Menü öğelerini getir
$menu_items = [];
$result = $conn->query("SELECT m.*, c.title as category_title 
                        FROM footer_menu_items m 
                        LEFT JOIN footer_menu_categories c ON m.category_id = c.id 
                        ORDER BY c.position ASC, m.order_number ASC, m.id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $menu_items[] = $row;
    }
}
?>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($edit_menu_item): ?>
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Menü Öğesi Düzenle</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="menu_item">
                        <input type="hidden" name="menu_item_action" value="edit">
                        <input type="hidden" name="id" value="<?php echo $edit_menu_item['id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <select name="category_id" class="form-select" required>
                                <?php foreach ($categories_list as $id => $title): ?>
                                    <option value="<?php echo $id; ?>" <?php echo $edit_menu_item['category_id'] == $id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Başlık</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($edit_menu_item['title']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">URL</label>
                            <input type="text" name="url" class="form-control" value="<?php echo htmlspecialchars($edit_menu_item['url']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Hedef</label>
                            <select name="target" class="form-select">
                                <option value="_self" <?php echo $edit_menu_item['target'] == '_self' ? 'selected' : ''; ?>>Aynı Pencere</option>
                                <option value="_blank" <?php echo $edit_menu_item['target'] == '_blank' ? 'selected' : ''; ?>>Yeni Pencere</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Sıralama</label>
                            <input type="number" name="order_number" class="form-control" value="<?php echo (int)$edit_menu_item['order_number']; ?>" min="0">
                            <div class="form-text">Kategori içinde sıralama için kullanılır. Düşük sayılar üstte gösterilir.</div>
                        </div>
                        
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="status" id="menuItemStatus" <?php echo $edit_menu_item['status'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="menuItemStatus">Aktif</label>
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
                <th>Kategori</th>
                <th>Başlık</th>
                <th>URL</th>
                <th>Hedef</th>
                <th>Sıralama</th>
                <th>Durum</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($menu_items) > 0): ?>
                <?php foreach ($menu_items as $item): ?>
                    <tr>
                        <td><?php echo $item['id']; ?></td>
                        <td><?php echo htmlspecialchars($item['category_title']); ?></td>
                        <td><?php echo htmlspecialchars($item['title']); ?></td>
                        <td><?php echo htmlspecialchars($item['url']); ?></td>
                        <td><?php echo $item['target'] == '_blank' ? 'Yeni Pencere' : 'Aynı Pencere'; ?></td>
                        <td><?php echo $item['order_number']; ?></td>
                        <td>
                            <?php if ($item['status']): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Pasif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="footer_management.php?edit_menu_item=<?php echo $item['id']; ?>" class="btn btn-sm btn-info">
                                <i class="bx bx-edit"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteMenuItem(<?php echo $item['id']; ?>)">
                                <i class="bx bx-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">Henüz menü öğesi eklenmemiş.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Menü Öğesi Silme Form - Gizli -->
<form id="deleteMenuItemForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="menu_item">
    <input type="hidden" name="menu_item_action" value="delete">
    <input type="hidden" name="id" id="delete_menu_item_id">
</form>

<!-- Menü Öğesi Ekleme Modal -->
<div class="modal fade" id="addMenuItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Menü Öğesi Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="menu_item">
                    <input type="hidden" name="menu_item_action" value="add">
                    
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Kategori Seçin</option>
                            <?php foreach ($categories_list as $id => $title): ?>
                                <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Başlık</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">URL</label>
                        <input type="text" name="url" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Hedef</label>
                        <select name="target" class="form-select">
                            <option value="_self">Aynı Pencere</option>
                            <option value="_blank">Yeni Pencere</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Sıralama</label>
                        <input type="number" name="order_number" class="form-control" value="0" min="0">
                        <div class="form-text">Kategori içinde sıralama için kullanılır. Düşük sayılar üstte gösterilir.</div>
                    </div>
                    
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="status" id="newMenuItemStatus" checked>
                        <label class="form-check-label" for="newMenuItemStatus">Aktif</label>
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
function confirmDeleteMenuItem(id) {
    if (confirm('Bu menü öğesini silmek istediğinize emin misiniz? Bu işlem geri alınamaz.')) {
        document.getElementById('delete_menu_item_id').value = id;
        document.getElementById('deleteMenuItemForm').submit();
    }
}
</script> 