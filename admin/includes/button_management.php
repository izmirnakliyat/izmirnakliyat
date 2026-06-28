<?php
// Buton işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'button') {
        switch ($_POST['button_action']) {
            case 'add':
                $title = trim($_POST['title']);
                $type = $_POST['type']; // 'link', 'popup' veya 'popup_form'
                $url = trim($_POST['url']);
                $target = $_POST['target'];
                $popup_id = ($type === 'popup' && !empty($_POST['popup_id'])) ? (int)$_POST['popup_id'] : null;
                $form_id = ($type === 'popup_form' && !empty($_POST['form_id'])) ? (int)$_POST['form_id'] : null;
                $status = isset($_POST['status']) ? 1 : 0;

                // Tablo sütunlarını kontrol et ve gerekirse ekle
                $check_form_id = $conn->query("SHOW COLUMNS FROM header_buttons LIKE 'form_id'");
                if ($check_form_id->num_rows == 0) {
                    $conn->query("ALTER TABLE header_buttons ADD COLUMN form_id INT NULL AFTER popup_id");
                }

                $stmt = $conn->prepare("INSERT INTO header_buttons (title, type, url, target, popup_id, form_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssiii", $title, $type, $url, $target, $popup_id, $form_id, $status);
                if ($stmt->execute()) {
                    $success = "Buton başarıyla eklendi.";
                } else {
                    $error = "Buton eklenirken bir hata oluştu: " . $conn->error;
                }
                break;
            case 'edit':
                $id = (int)$_POST['id'];
                $title = trim($_POST['title']);
                $type = $_POST['type']; // 'link', 'popup' veya 'popup_form'
                $url = trim($_POST['url']);
                $target = $_POST['target'];
                $popup_id = ($type === 'popup' && !empty($_POST['popup_id'])) ? (int)$_POST['popup_id'] : null;
                $form_id = ($type === 'popup_form' && !empty($_POST['form_id'])) ? (int)$_POST['form_id'] : null;
                $status = isset($_POST['status']) ? 1 : 0;

                // Tablo sütunlarını kontrol et ve gerekirse ekle
                $check_form_id = $conn->query("SHOW COLUMNS FROM header_buttons LIKE 'form_id'");
                if ($check_form_id->num_rows == 0) {
                    $conn->query("ALTER TABLE header_buttons ADD COLUMN form_id INT NULL AFTER popup_id");
                }

                $stmt = $conn->prepare("UPDATE header_buttons SET title = ?, type = ?, url = ?, target = ?, popup_id = ?, form_id = ?, status = ? WHERE id = ?");
                $stmt->bind_param("ssssiiii", $title, $type, $url, $target, $popup_id, $form_id, $status, $id);
                if ($stmt->execute()) {
                    $success = "Buton başarıyla güncellendi.";
                } else {
                    $error = "Buton güncellenirken bir hata oluştu: " . $conn->error;
                }
                break;
            case 'delete':
                $id = (int)$_POST['id'];
                $stmt = $conn->prepare("DELETE FROM header_buttons WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $success = "Buton başarıyla silindi.";
                } else {
                    $error = "Buton silinirken bir hata oluştu: " . $conn->error;
                }
                break;
        }
    }
}

// Buton yükleme işlemi - düzenleme için
$edit_button = null;
if (isset($_GET['edit_button']) && !empty($_GET['edit_button'])) {
    $edit_id = (int)$_GET['edit_button'];
    $stmt = $conn->prepare("SELECT * FROM header_buttons WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_button = $result->fetch_assoc();
    }
}

// Popup listesini getir
$popups = [];
$popup_result = $conn->query("SELECT id, title FROM popups WHERE status = 1 ORDER BY id ASC");
if ($popup_result) {
    while ($row = $popup_result->fetch_assoc()) {
        $popups[] = $row;
    }
}

// Form listesini getir (popup formlar için)
$popup_forms = [];
$forms_result = $conn->query("SELECT id, title FROM forms WHERE status = 1 AND settings LIKE '%\"popup_enabled\":true%' ORDER BY id ASC");
if ($forms_result) {
    while ($row = $forms_result->fetch_assoc()) {
        $popup_forms[] = $row;
    }
}

// Butonları getir
$buttons = [];
$result = $conn->query("SELECT * FROM header_buttons ORDER BY id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $buttons[] = $row;
    }
}
?>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($edit_button): ?>
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Buton Düzenle</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="button">
                        <input type="hidden" name="button_action" value="edit">
                        <input type="hidden" name="id" value="<?php echo $edit_button['id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Başlık</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($edit_button['title']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Buton Türü</label>
                            <select name="type" class="form-select" id="buttonType">
                                <option value="link" <?php echo $edit_button['type'] == 'link' ? 'selected' : ''; ?>>Link</option>
                                <option value="popup" <?php echo $edit_button['type'] == 'popup' ? 'selected' : ''; ?>>Popup</option>
                                <option value="popup_form" <?php echo $edit_button['type'] == 'popup_form' ? 'selected' : ''; ?>>Popup Form</option>
                            </select>
                        </div>
                        
                        <div class="mb-3 link-fields" <?php echo ($edit_button['type'] == 'popup' || $edit_button['type'] == 'popup_form') ? 'style="display:none;"' : ''; ?>>
                            <label class="form-label">URL</label>
                            <input type="text" name="url" class="form-control" value="<?php echo htmlspecialchars($edit_button['url']); ?>">
                        </div>
                        
                        <div class="mb-3 link-fields" <?php echo ($edit_button['type'] == 'popup' || $edit_button['type'] == 'popup_form') ? 'style="display:none;"' : ''; ?>>
                            <label class="form-label">Hedef</label>
                            <select name="target" class="form-select">
                                <option value="_self" <?php echo $edit_button['target'] == '_self' ? 'selected' : ''; ?>>Aynı Pencere</option>
                                <option value="_blank" <?php echo $edit_button['target'] == '_blank' ? 'selected' : ''; ?>>Yeni Pencere</option>
                            </select>
                        </div>
                        
                        <div class="mb-3 popup-fields" <?php echo ($edit_button['type'] == 'link') ? 'style="display:none;"' : ''; ?>>
                            <div class="row">
                                <div class="col-md-6 popup-content" <?php echo ($edit_button['type'] != 'popup') ? 'style="display:none;"' : ''; ?>>
                                    <label class="form-label">Popup Seçimi</label>
                                    <select name="popup_id" class="form-select">
                                        <option value="">Popup Seçin</option>
                                        <?php foreach ($popups as $popup): ?>
                                            <option value="<?php echo $popup['id']; ?>" <?php echo $edit_button['popup_id'] == $popup['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($popup['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 form-content" <?php echo ($edit_button['type'] != 'popup_form') ? 'style="display:none;"' : ''; ?>>
                                    <label class="form-label">Popup Form Seçimi</label>
                                    <select name="form_id" class="form-select">
                                        <option value="">Form Seçin</option>
                                        <?php foreach ($popup_forms as $form): ?>
                                            <option value="<?php echo $form['id']; ?>" <?php echo (isset($edit_button['form_id']) && $edit_button['form_id'] == $form['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($form['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="buttonStatus" name="status" <?php echo $edit_button['status'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="buttonStatus">Aktif</label>
                        </div>
                        
                        <div class="d-flex">
                            <button type="submit" class="btn btn-primary me-2">Güncelle</button>
                            <a href="button_management.php" class="btn btn-secondary">İptal</a>
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
                <th>Tür</th>
                <th>URL/Popup</th>
                <th>Durum</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($buttons) > 0): ?>
                <?php foreach ($buttons as $button): ?>
                    <tr>
                        <td><?php echo $button['id']; ?></td>
                        <td><?php echo htmlspecialchars($button['title']); ?></td>
                        <td>
                            <?php 
                            if ($button['type'] == 'link') {
                                echo 'Link';
                            } elseif ($button['type'] == 'popup') {
                                echo 'Popup';
                            } elseif ($button['type'] == 'popup_form') {
                                echo 'Popup Form';
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            if ($button['type'] == 'link') {
                                echo htmlspecialchars($button['url']);
                                echo ' <small>(' . ($button['target'] == '_blank' ? 'Yeni Pencerede' : 'Aynı Pencerede') . ')</small>';
                            } elseif ($button['type'] == 'popup') {
                                // Popup adını bulalım
                                $popup_name = 'Seçili Değil';
                                foreach ($popups as $popup) {
                                    if ($popup['id'] == $button['popup_id']) {
                                        $popup_name = $popup['title'];
                                        break;
                                    }
                                }
                                echo 'Popup: ' . htmlspecialchars($popup_name);
                            } elseif ($button['type'] == 'popup_form') {
                                // Form adını bulalım
                                $form_name = 'Seçili Değil';
                                foreach ($popup_forms as $form) {
                                    if ($form['id'] == ($button['form_id'] ?? 0)) {
                                        $form_name = $form['title'];
                                        break;
                                    }
                                }
                                echo 'Form: ' . htmlspecialchars($form_name);
                            }
                            ?>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $button['status'] ? 'success' : 'danger'; ?>">
                                <?php echo $button['status'] ? 'Aktif' : 'Pasif'; ?>
                            </span>
                        </td>
                        <td>
                            <a href="button_management.php?edit_button=<?php echo $button['id']; ?>" class="btn btn-sm btn-info">
                                <i class="bx bx-edit"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteButton(<?php echo $button['id']; ?>, '<?php echo htmlspecialchars($button['title']); ?>')">
                                <i class="bx bx-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">Henüz buton eklenmemiş.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Buton Silme Form - Gizli -->
<form id="deleteButtonForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="button">
    <input type="hidden" name="button_action" value="delete">
    <input type="hidden" name="id" id="delete_button_id">
</form>

<!-- Buton Ekleme Modal -->
<div class="modal fade" id="addButtonModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Buton Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="button">
                    <input type="hidden" name="button_action" value="add">
                    
                    <div class="mb-3">
                        <label class="form-label">Başlık</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Buton Türü</label>
                        <select name="type" class="form-select" id="newButtonType">
                            <option value="link">Link</option>
                            <option value="popup">Popup</option>
                            <option value="popup_form">Popup Form</option>
                        </select>
                    </div>
                    
                    <div class="mb-3 new-link-fields">
                        <label class="form-label">URL</label>
                        <input type="text" name="url" class="form-control">
                    </div>
                    
                    <div class="mb-3 new-link-fields">
                        <label class="form-label">Hedef</label>
                        <select name="target" class="form-select">
                            <option value="_self">Aynı Pencere</option>
                            <option value="_blank">Yeni Pencere</option>
                        </select>
                    </div>
                    
                    <div class="mb-3 new-popup-fields" style="display:none;">
                        <div class="row">
                            <div class="col-md-6 new-popup-content">
                                <label class="form-label">Popup Seçimi</label>
                                <select name="popup_id" class="form-select">
                                    <option value="">Popup Seçin</option>
                                    <?php foreach ($popups as $popup): ?>
                                        <option value="<?php echo $popup['id']; ?>">
                                            <?php echo htmlspecialchars($popup['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 new-form-content" style="display:none;">
                                <label class="form-label">Popup Form Seçimi</label>
                                <select name="form_id" class="form-select">
                                    <option value="">Form Seçin</option>
                                    <?php foreach ($popup_forms as $form): ?>
                                        <option value="<?php echo $form['id']; ?>">
                                            <?php echo htmlspecialchars($form['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="newButtonStatus" name="status" checked>
                        <label class="form-check-label" for="newButtonStatus">Aktif</label>
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
document.addEventListener('DOMContentLoaded', function() {
    // Buton tipi değiştiğinde
    if (document.getElementById('buttonType')) {
        document.getElementById('buttonType').addEventListener('change', function() {
            toggleButtonFields(this.value);
        });
    }
    
    if (document.getElementById('newButtonType')) {
        document.getElementById('newButtonType').addEventListener('change', function() {
            toggleNewButtonFields(this.value);
        });
    }
    
    function toggleButtonFields(type) {
        if (type === 'link') {
            document.querySelectorAll('.link-fields').forEach(el => el.style.display = 'block');
            document.querySelectorAll('.popup-fields').forEach(el => el.style.display = 'none');
        } else {
            document.querySelectorAll('.link-fields').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.popup-fields').forEach(el => el.style.display = 'block');
            
            // Popup içeriği göster/gizle
            if (type === 'popup') {
                document.querySelectorAll('.popup-content').forEach(el => el.style.display = 'block');
                document.querySelectorAll('.form-content').forEach(el => el.style.display = 'none');
            } else if (type === 'popup_form') {
                document.querySelectorAll('.popup-content').forEach(el => el.style.display = 'none');
                document.querySelectorAll('.form-content').forEach(el => el.style.display = 'block');
            }
        }
    }
    
    function toggleNewButtonFields(type) {
        if (type === 'link') {
            document.querySelectorAll('.new-link-fields').forEach(el => el.style.display = 'block');
            document.querySelectorAll('.new-popup-fields').forEach(el => el.style.display = 'none');
        } else {
            document.querySelectorAll('.new-link-fields').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.new-popup-fields').forEach(el => el.style.display = 'block');
            
            // New popup içeriği göster/gizle
            if (type === 'popup') {
                document.querySelectorAll('.new-popup-content').forEach(el => el.style.display = 'block');
                document.querySelectorAll('.new-form-content').forEach(el => el.style.display = 'none');
            } else if (type === 'popup_form') {
                document.querySelectorAll('.new-popup-content').forEach(el => el.style.display = 'none');
                document.querySelectorAll('.new-form-content').forEach(el => el.style.display = 'block');
            }
        }
    }
});

// Silme onayı
function confirmDeleteButton(id, title) {
    if (confirm('"' + title + '" butonunu silmek istediğinizden emin misiniz?')) {
        var form = document.getElementById('deleteButtonForm');
        document.getElementById('delete_button_id').value = id;
        form.submit();
    }
}
</script> 