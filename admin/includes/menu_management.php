<?php
// Menü işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'menu') {
        switch ($_POST['menu_action']) {
            case 'add':
                $title = trim($_POST['title']);
                $url = trim($_POST['url']);
                $target = $_POST['target'];
                $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
                $stmt = $conn->prepare("INSERT INTO menus (title, url, target, parent_id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $title, $url, $target, $parent_id);
                if ($stmt->execute()) {
                    $success = "Menü başarıyla eklendi.";
                } else {
                    $error = "Menü eklenirken bir hata oluştu.";
                }
                break;
            case 'edit':
                $id = (int)$_POST['id'];
                $title = trim($_POST['title']);
                $url = trim($_POST['url']);
                $target = $_POST['target'];
                $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
                $stmt = $conn->prepare("UPDATE menus SET title = ?, url = ?, target = ?, parent_id = ? WHERE id = ?");
                $stmt->bind_param("sssii", $title, $url, $target, $parent_id, $id);
                if ($stmt->execute()) {
                    $success = "Menü başarıyla güncellendi.";
                } else {
                    $error = "Menü güncellenirken bir hata oluştu.";
                }
                break;
            case 'delete':
                $id = (int)$_POST['id'];
                $stmt = $conn->prepare("DELETE FROM menus WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $success = "Menü başarıyla silindi.";
                } else {
                    $error = "Menü silinirken bir hata oluştu.";
                }
                break;
            case 'update_order':
                $menu_ids = isset($_POST['menu_ids']) ? $_POST['menu_ids'] : [];
                $parent_ids = isset($_POST['parent_ids']) ? $_POST['parent_ids'] : [];
                $menu_levels = isset($_POST['menu_levels']) ? $_POST['menu_levels'] : [];
                
                // Menüleri düzenle
                foreach ($menu_ids as $index => $id) {
                    $parent_id = !empty($parent_ids[$index]) ? (int)$parent_ids[$index] : null;
                    $order = $index + 1;
                    
                    $stmt = $conn->prepare("UPDATE menus SET parent_id = ?, menu_order = ? WHERE id = ?");
                    $stmt->bind_param("iii", $parent_id, $order, $id);
                    $stmt->execute();
                }
                $success = "Menü sıralaması güncellendi.";
                break;
        }
    }
}

// Menü yükleme işlemi - düzenleme için
$edit_menu = null;
if (isset($_GET['edit_menu']) && !empty($_GET['edit_menu'])) {
    $edit_id = (int)$_GET['edit_menu'];
    $stmt = $conn->prepare("SELECT * FROM menus WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_menu = $result->fetch_assoc();
    }
}

// Menüleri getir
$menus = [];
$result = $conn->query("SELECT * FROM menus ORDER BY menu_order ASC, id ASC");
while ($row = $result->fetch_assoc()) {
    $menus[] = $row;
}

// Menü ağacını oluştur
function buildMenuTree($items, $parent_id = null) {
    $tree = [];
    foreach ($items as $item) {
        if ($item['parent_id'] == $parent_id) {
            $item['children'] = buildMenuTree($items, $item['id']);
            $tree[] = $item;
        }
    }
    return $tree;
}
$menuTree = buildMenuTree($menus);
?>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($edit_menu): ?>
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Menü Düzenle</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="menu">
                        <input type="hidden" name="menu_action" value="edit">
                        <input type="hidden" name="id" value="<?php echo $edit_menu['id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Başlık</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($edit_menu['title']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">URL</label>
                            <input type="text" name="url" class="form-control" value="<?php echo htmlspecialchars($edit_menu['url']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Hedef</label>
                            <select name="target" class="form-select">
                                <option value="_self" <?php echo $edit_menu['target'] == '_self' ? 'selected' : ''; ?>>Aynı Pencere</option>
                                <option value="_blank" <?php echo $edit_menu['target'] == '_blank' ? 'selected' : ''; ?>>Yeni Pencere</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Üst Menü</label>
                            <select name="parent_id" class="form-select">
                                <option value="">Ana Menü (üst menü yok)</option>
                                <?php foreach ($menus as $menu): ?>
                                    <?php if ($menu['id'] != $edit_menu['id']): // Kendisini üst menü olarak seçmesin ?>
                                        <option value="<?php echo $menu['id']; ?>" <?php echo $edit_menu['parent_id'] == $menu['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($menu['title']); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="d-flex">
                            <button type="submit" class="btn btn-primary me-2">Güncelle</button>
                            <a href="menu_management.php" class="btn btn-secondary">İptal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="menu-container">
    <div class="dd" id="menuList">
        <?php if (count($menuTree) > 0): ?>
            <ol class="dd-list">
                <?php renderMenuItems($menuTree); ?>
            </ol>
        <?php else: ?>
            <div class="alert alert-info">Henüz menü eklenmemiş.</div>
        <?php endif; ?>
    </div>
    
    <form id="menuOrderForm" method="POST" class="mt-3">
        <input type="hidden" name="action" value="menu">
        <input type="hidden" name="menu_action" value="update_order">
        <div id="menuOrderInputs"></div>
        <button type="submit" class="btn btn-primary" id="saveOrderBtn" style="display: none;">
            <i class='bx bx-save'></i> Sıralamayı Kaydet
        </button>
    </form>
</div>

<!-- Menü Silme Form - Gizli -->
<form id="deleteMenuForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="menu">
    <input type="hidden" name="menu_action" value="delete">
    <input type="hidden" name="id" id="delete_menu_id">
</form>

<!-- Menü Ekleme Modal -->
<div class="modal fade" id="addMenuModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Menü Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="menu">
                    <input type="hidden" name="menu_action" value="add">
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
                        <label class="form-label">Üst Menü</label>
                        <select name="parent_id" class="form-select">
                            <option value="">Ana Menü (üst menü yok)</option>
                            <?php foreach ($menus as $menu): ?>
                                <option value="<?php echo $menu['id']; ?>">
                                    <?php echo htmlspecialchars($menu['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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

<style>
/* Menü Ağacı Stilleri */
.dd { position: relative; display: block; margin: 0; padding: 0; list-style: none; }
.dd-list { display: block; position: relative; margin: 0; padding: 0; list-style: none; }
.dd-list .dd-list { padding-left: 30px; }
.dd-collapsed .dd-list { display: none; }

.dd-item {
    display: block;
    position: relative;
    margin: 0 0 10px 0;
    padding: 0;
    min-height: 20px;
}

.dd-handle {
    display: block;
    margin: 0;
    padding: 10px 15px;
    color: #333;
    text-decoration: none;
    font-weight: 500;
    border: 1px solid #e3e6f0;
    background: #f8f9fc;
    border-radius: 4px;
    box-sizing: border-box;
    cursor: move;
}

.dd-handle:hover {
    background: #eaecf4;
}

.dd-item-btns {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
}

.dd-item-btns .btn {
    margin-left: 4px;
    padding: 2px 6px;
}

.dd-placeholder {
    margin: 5px 0;
    padding: 0;
    min-height: 30px;
    background: #f2fbff;
    border: 1px dashed #b6bcbf;
    box-sizing: border-box;
}

.dd-dragging {
    opacity: 0.5;
}

.dd-hover {
    border-top: 2px dashed #4e73df;
}
</style>

<script>
<?php
// Menü öğelerini oluşturan fonksiyon
function renderMenuItems($items, $level = 0) {
    foreach ($items as $item) {
        echo '<li class="dd-item" data-id="' . $item['id'] . '" data-parent="' . ($item['parent_id'] ?: '') . '" data-level="' . $level . '">';
        echo '<div class="dd-handle">' . htmlspecialchars($item['title']) . '</div>';
        echo '<div class="dd-item-btns">';
        echo '<a href="menu_management.php?edit_menu=' . $item['id'] . '" class="btn btn-sm btn-info"><i class="bx bx-edit"></i></a>';
        echo '<button type="button" class="btn btn-sm btn-danger delete-btn" onclick="confirmDeleteMenu(' . $item['id'] . ', \'' . htmlspecialchars($item['title']) . '\')"><i class="bx bx-trash"></i></button>';
        echo '</div>';
        
        if (!empty($item['children'])) {
            echo '<ol class="dd-list">';
            renderMenuItems($item['children'], $level + 1);
            echo '</ol>';
        }
        
        echo '</li>';
    }
}
?>

document.addEventListener('DOMContentLoaded', function() {
    // Menü yönetimi için kod
    initializeMenuDragDrop();
});

function initializeMenuDragDrop() {
    // Tüm menü öğelerini topla
    var menuItems = document.querySelectorAll('.dd-item');
    var draggedItem = null;
    var isDragging = false;
    var dragDepth = 0;
    var lastTarget = null;
    var originalParent = null;
    
    // Her menü öğesini sürüklenebilir yap
    menuItems.forEach(function(item) {
        var handle = item.querySelector('.dd-handle');
        if (!handle) return;
        
        handle.addEventListener('mousedown', function(e) {
            e.preventDefault();
            draggedItem = item;
            isDragging = true;
            
            // Orijinal ebeveyn ve özellikleri kaydet
            originalParent = draggedItem.parentNode;
            var rect = draggedItem.getBoundingClientRect();
            
            // Sürükleme özelliklerini ayarla
            draggedItem.style.width = rect.width + 'px';
            draggedItem.classList.add('dd-dragging');
            
            // Fare hareketini izle
            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        });
    });
    
    function onMouseMove(e) {
        if (!isDragging || !draggedItem) return;
        
        var mouseY = e.clientY;
        var target = null;
        var targetDepth = 0;
        
        // Fare hangi öğenin üzerinde?
        menuItems.forEach(function(item) {
            if (item === draggedItem) return;
            
            var rect = item.getBoundingClientRect();
            if (mouseY > rect.top && mouseY < rect.bottom) {
                // Öğeyi bulduğumuzda hafızaya alalım
                target = item;
                
                // Tüm hover efektlerini temizle
                menuItems.forEach(function(mi) {
                    mi.classList.remove('dd-hover');
                });
                
                // Hedef üzerine hover efekti ekle
                target.classList.add('dd-hover');
            }
        });
        
        lastTarget = target;
    }
    
    function onMouseUp(e) {
        if (!isDragging || !draggedItem) return;
        isDragging = false;
        
        // Sürükleme efektlerini temizle
        draggedItem.classList.remove('dd-dragging');
        
        // Son hedef var mı kontrol et
        if (lastTarget) {
            // Tüm hover efektlerini temizle
            menuItems.forEach(function(mi) {
                mi.classList.remove('dd-hover');
            });
            
            // Sürüklenen öğeyi yeni konumuna taşı
            var targetList = lastTarget.parentNode;
            var targetIndex = Array.from(targetList.children).indexOf(lastTarget);
            
            // Hedefin altına taşı
            if (targetList.children[targetIndex + 1]) {
                targetList.insertBefore(draggedItem, targetList.children[targetIndex + 1]);
            } else {
                targetList.appendChild(draggedItem);
            }
            
            // Sıralama değişti, butonunu göster
            document.getElementById('saveOrderBtn').style.display = 'inline-block';
            
            // Form girdilerini güncelle
            updateMenuOrder();
        }
        
        // Event dinleyicileri temizle
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
        
        // Sürükleme değişkenlerini sıfırla
        draggedItem = null;
        lastTarget = null;
    }
    
    // Menü sıralamasını güncelle
    function updateMenuOrder() {
        var menuOrderInputs = document.getElementById('menuOrderInputs');
        menuOrderInputs.innerHTML = '';
        
        // Tüm menü öğelerini topla
        var allItems = document.querySelectorAll('.dd-item');
        allItems.forEach(function(item, index) {
            var id = item.getAttribute('data-id');
            var parentId = '';
            var level = 0;
            
            // Ebeveyn kontrolü
            var parentList = item.parentNode;
            if (parentList.classList.contains('dd-list') && !parentList.parentNode.classList.contains('menu-container')) {
                var parentItem = parentList.parentNode;
                if (parentItem.hasAttribute('data-id')) {
                    parentId = parentItem.getAttribute('data-id');
                    level = 1; // Şimdilik sadece bir seviye derinlik destekle
                }
            }
            
            // Form inputlarını ekle
            var idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'menu_ids[]';
            idInput.value = id;
            menuOrderInputs.appendChild(idInput);
            
            var parentInput = document.createElement('input');
            parentInput.type = 'hidden';
            parentInput.name = 'parent_ids[]';
            parentInput.value = parentId;
            menuOrderInputs.appendChild(parentInput);
            
            var levelInput = document.createElement('input');
            levelInput.type = 'hidden';
            levelInput.name = 'menu_levels[]';
            levelInput.value = level;
            menuOrderInputs.appendChild(levelInput);
        });
    }
    
    // Sayfa yüklendiğinde form inputlarını oluştur
    updateMenuOrder();
}

// Silme onayı
function confirmDeleteMenu(id, title) {
    if (confirm('"' + title + '" menüsünü silmek istediğinizden emin misiniz? Alt menüleri de silinecektir.')) {
        var form = document.getElementById('deleteMenuForm');
        document.getElementById('delete_menu_id').value = id;
        form.submit();
    }
}
</script> 