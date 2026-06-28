<?php
$page_title = 'Mobil Alt Menü Yönetimi';
require_once 'includes/header.php';
require_once '../config/db.php';

// Mobil menü öğesi ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_menu_item') {
    $title = trim($_POST['title']);
    $icon = trim($_POST['icon']);
    $link = trim($_POST['link']);
    $target = $_POST['target'];
    $bg_color = trim($_POST['bg_color']);
    $status = isset($_POST['status']) ? 1 : 0;
    $order_number = (int)$_POST['order_number'];
    
    // Mevcut öğe sayısını kontrol et
    $count_result = $conn->query("SELECT COUNT(*) as total FROM mobile_bottom_menu WHERE status = 1");
    $count = $count_result->fetch_assoc()['total'];
    
    if ($count >= 5 && $status === 1) {
        $error = "Maksimum 5 aktif menü öğesi eklenebilir. Lütfen önce bir öğeyi pasif yapın.";
    } else {
        $stmt = $conn->prepare("INSERT INTO mobile_bottom_menu (title, icon, link, target, bg_color, status, order_number) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssii", $title, $icon, $link, $target, $bg_color, $status, $order_number);
        
        if ($stmt->execute()) {
            $success = "Mobil alt menü öğesi başarıyla eklendi.";
        } else {
            $error = "Eklenirken bir hata oluştu: " . $conn->error;
        }
    }
}

// Mobil menü öğesi güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_menu_item') {
    $id = (int)$_POST['id'];
    $title = trim($_POST['title']);
    $icon = trim($_POST['icon']);
    $link = trim($_POST['link']);
    $target = $_POST['target'];
    $bg_color = trim($_POST['bg_color']);
    $status = isset($_POST['status']) ? 1 : 0;
    $order_number = (int)$_POST['order_number'];
    
    // Eğer yeni durumu aktif ise, aktif öğe sayısını kontrol et
    if ($status === 1) {
        $count_result = $conn->query("SELECT COUNT(*) as total FROM mobile_bottom_menu WHERE status = 1 AND id != $id");
        $count = $count_result->fetch_assoc()['total'];
        
        if ($count >= 5) {
            $error = "Maksimum 5 aktif menü öğesi eklenebilir. Lütfen önce bir öğeyi pasif yapın.";
        } else {
            $stmt = $conn->prepare("UPDATE mobile_bottom_menu SET title = ?, icon = ?, link = ?, target = ?, bg_color = ?, status = ?, order_number = ? WHERE id = ?");
            $stmt->bind_param("sssssiii", $title, $icon, $link, $target, $bg_color, $status, $order_number, $id);
            
            if ($stmt->execute()) {
                $success = "Mobil alt menü öğesi başarıyla güncellendi.";
            } else {
                $error = "Güncellenirken bir hata oluştu: " . $conn->error;
            }
        }
    } else {
        $stmt = $conn->prepare("UPDATE mobile_bottom_menu SET title = ?, icon = ?, link = ?, target = ?, bg_color = ?, status = ?, order_number = ? WHERE id = ?");
        $stmt->bind_param("sssssiii", $title, $icon, $link, $target, $bg_color, $status, $order_number, $id);
        
        if ($stmt->execute()) {
            $success = "Mobil alt menü öğesi başarıyla güncellendi.";
        } else {
            $error = "Güncellenirken bir hata oluştu: " . $conn->error;
        }
    }
}

// Mobil menü öğesi silme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_menu_item') {
    $id = (int)$_POST['id'];
    
    $stmt = $conn->prepare("DELETE FROM mobile_bottom_menu WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $success = "Mobil alt menü öğesi başarıyla silindi.";
    } else {
        $error = "Silinirken bir hata oluştu: " . $conn->error;
    }
}

// Mevcut menü öğelerini getir
$result = $conn->query("SELECT * FROM mobile_bottom_menu ORDER BY order_number ASC, id ASC");
$menu_items = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $menu_items[] = $row;
    }
}

// Font Awesome ikonlarını getir (popüler ikonlar)
$icons = [
    'fas fa-home' => 'Ev',
    'fas fa-info-circle' => 'Bilgi',
    'fas fa-envelope' => 'Zarf',
    'fas fa-phone' => 'Telefon',
    'fas fa-map-marker-alt' => 'Konum',
    'fas fa-user' => 'Kullanıcı',
    'fas fa-users' => 'Kullanıcılar',
    'fas fa-cog' => 'Ayarlar',
    'fas fa-search' => 'Arama',
    'fas fa-shopping-cart' => 'Sepet',
    'fas fa-heart' => 'Kalp',
    'fas fa-star' => 'Yıldız',
    'fas fa-book' => 'Kitap',
    'fas fa-graduation-cap' => 'Mezuniyet',
    'fas fa-school' => 'Okul',
    'fas fa-calendar' => 'Takvim',
    'fas fa-image' => 'Resim',
    'fas fa-video' => 'Video',
    'fas fa-music' => 'Müzik',
    'fas fa-file' => 'Dosya',
    'fas fa-download' => 'İndir',
    'fas fa-upload' => 'Yükle',
    'fas fa-link' => 'Link',
    'fas fa-share' => 'Paylaş',
    'fas fa-comment' => 'Yorum',
    'fas fa-comments' => 'Yorumlar',
    'fas fa-bell' => 'Bildirim',
    'fas fa-clipboard' => 'Pano',
    'fab fa-facebook-f' => 'Facebook',
    'fab fa-twitter' => 'Twitter',
    'fab fa-instagram' => 'Instagram',
    'fab fa-youtube' => 'YouTube',
    'fab fa-linkedin-in' => 'LinkedIn',
    'fab fa-whatsapp' => 'WhatsApp',
    'fab fa-telegram' => 'Telegram',
    'fas fa-paper-plane' => 'Gönder'
];
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Site Yönetimi / </span> Mobil Alt Menü Yönetimi
    </h4>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Mobil Alt Menü Öğeleri</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMenuItemModal">
                        <i class='bx bx-plus'></i> Yeni Menü Öğesi Ekle
                    </button>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Bilgi:</strong> Bu menü sadece mobil cihazlarda gösterilecektir. En fazla 5 aktif menü öğesi ekleyebilirsiniz.
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 50px">#</th>
                                    <th style="width: 70px">Sıra</th>
                                    <th style="width: 70px">İkon</th>
                                    <th>Başlık</th>
                                    <th>Link</th>
                                    <th style="width: 80px">Hedef</th>
                                    <th style="width: 100px">Renk</th>
                                    <th style="width: 80px">Durum</th>
                                    <th style="width: 120px">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($menu_items) > 0): ?>
                                    <?php foreach ($menu_items as $item): ?>
                                        <tr>
                                            <td><?php echo $item['id']; ?></td>
                                            <td><?php echo $item['order_number']; ?></td>
                                            <td class="text-center">
                                                <i class="<?php echo htmlspecialchars($item['icon']); ?>" style="font-size: 24px;"></i>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['title']); ?></td>
                                            <td><?php echo htmlspecialchars($item['link']); ?></td>
                                            <td><?php echo $item['target'] === '_blank' ? 'Yeni Sekme' : 'Aynı Sekme'; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="color-preview" style="width: 24px; height: 24px; border-radius: 4px; background-color: <?php echo htmlspecialchars($item['bg_color']); ?>; margin-right: 8px;"></div>
                                                    <?php echo htmlspecialchars($item['bg_color']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $item['status'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $item['status'] ? 'Aktif' : 'Pasif'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-primary" onclick="editMenuItem(<?php echo $item['id']; ?>)">
                                                        <i class="bx bx-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-danger" onclick="confirmDelete(<?php echo $item['id']; ?>, '<?php echo addslashes(htmlspecialchars($item['title'])); ?>')">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">Henüz menü öğesi eklenmemiş.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Mobil Alt Menü Önizleme</h5>
                </div>
                <div class="card-body">
                    <div class="mobile-bottom-menu-preview">
                        <div class="preview-device">
                            <div class="device-header"></div>
                            <div class="device-content"></div>
                            <div class="device-bottom-menu">
                                <?php 
                                $active_items = array_filter($menu_items, function($item) {
                                    return $item['status'] == 1;
                                });
                                usort($active_items, function($a, $b) {
                                    return $a['order_number'] - $b['order_number'];
                                });
                                
                                foreach ($active_items as $item): 
                                ?>
                                <div class="bottom-menu-item" style="background-color: <?php echo htmlspecialchars($item['bg_color']); ?>">
                                    <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                                    <span><?php echo htmlspecialchars($item['title']); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Yeni Menü Öğesi Ekleme Modalı -->
<div class="modal fade" id="addMenuItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_menu_item">
                
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Menü Öğesi Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Başlık</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="icon" class="form-label">İkon</label>
                        <select class="form-select" id="icon" name="icon" required>
                            <?php foreach ($icons as $icon => $label): ?>
                                <option value="<?php echo $icon; ?>"><?php echo $label; ?> - <i class="<?php echo $icon; ?>"></i></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="link" class="form-label">Link</label>
                        <input type="text" class="form-control" id="link" name="link" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="target" class="form-label">Hedef</label>
                        <select class="form-select" id="target" name="target">
                            <option value="_self">Aynı Sekme</option>
                            <option value="_blank">Yeni Sekme</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bg_color" class="form-label">Arkaplan Rengi</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="bg_color_picker" value="#0056b3">
                            <input type="text" class="form-control" id="bg_color" name="bg_color" value="#0056b3" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="order_number" class="form-label">Sıra Numarası</label>
                        <input type="number" class="form-control" id="order_number" name="order_number" min="1" value="1" required>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="status" name="status" checked>
                        <label class="form-check-label" for="status">Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Menü Öğesi Düzenleme Modalı -->
<div class="modal fade" id="editMenuItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit_menu_item">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Menü Öğesini Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Başlık</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_icon" class="form-label">İkon</label>
                        <select class="form-select" id="edit_icon" name="icon" required>
                            <?php foreach ($icons as $icon => $label): ?>
                                <option value="<?php echo $icon; ?>"><?php echo $label; ?> - <i class="<?php echo $icon; ?>"></i></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_link" class="form-label">Link</label>
                        <input type="text" class="form-control" id="edit_link" name="link" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_target" class="form-label">Hedef</label>
                        <select class="form-select" id="edit_target" name="target">
                            <option value="_self">Aynı Sekme</option>
                            <option value="_blank">Yeni Sekme</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_bg_color" class="form-label">Arkaplan Rengi</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="edit_bg_color_picker" value="#0056b3">
                            <input type="text" class="form-control" id="edit_bg_color" name="bg_color" value="#0056b3" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_order_number" class="form-label">Sıra Numarası</label>
                        <input type="number" class="form-control" id="edit_order_number" name="order_number" min="1" value="1" required>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="edit_status" name="status">
                        <label class="form-check-label" for="edit_status">Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Silme Onay Modalı -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete_menu_item">
                <input type="hidden" name="id" id="delete_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Menü Öğesini Sil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="delete_message">Bu menü öğesini silmek istediğinizden emin misiniz?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-danger">Sil</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.mobile-bottom-menu-preview {
    padding: 20px;
    display: flex;
    justify-content: center;
}

.preview-device {
    width: 320px;
    height: 540px;
    border: 10px solid #333;
    border-radius: 20px;
    position: relative;
    overflow: hidden;
    background: #fff;
}

.device-header {
    height: 60px;
    background: #f5f5f5;
    border-bottom: 1px solid #ddd;
}

.device-content {
    height: 420px;
    background: #f9f9f9;
}

.device-bottom-menu {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 60px;
    background: #fff;
    display: flex;
    justify-content: space-around;
    align-items: center;
    border-top: 1px solid #ddd;
}

.bottom-menu-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    color: #fff;
    font-size: 12px;
    text-align: center;
}

.bottom-menu-item i {
    font-size: 20px;
    margin-bottom: 4px;
}

.bottom-menu-item:hover {
    /* Removing transform effect */
}

select option i {
    margin-right: 10px;
}

.color-preview {
    border: 1px solid #ddd;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Renk seçici bağlantısı
    document.getElementById('bg_color_picker').addEventListener('input', function() {
        document.getElementById('bg_color').value = this.value;
    });
    
    document.getElementById('bg_color').addEventListener('input', function() {
        document.getElementById('bg_color_picker').value = this.value;
    });
    
    document.getElementById('edit_bg_color_picker').addEventListener('input', function() {
        document.getElementById('edit_bg_color').value = this.value;
    });
    
    document.getElementById('edit_bg_color').addEventListener('input', function() {
        document.getElementById('edit_bg_color_picker').value = this.value;
    });
    
    // Select'teki ikonları göster
    const iconSelects = document.querySelectorAll('select#icon, select#edit_icon');
    iconSelects.forEach(select => {
        for (let i = 0; i < select.options.length; i++) {
            const option = select.options[i];
            const icon = option.value;
            option.innerHTML = `${option.text.split(' - ')[0]} <i class="${icon}"></i>`;
        }
    });
});

// Menü öğesi düzenleme
function editMenuItem(id) {
    // AJAX ile menü öğesi verilerini al
    fetch(`ajax/get_mobile_menu_item.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Form alanlarını doldur
                document.getElementById('edit_id').value = data.item.id;
                document.getElementById('edit_title').value = data.item.title;
                document.getElementById('edit_icon').value = data.item.icon;
                document.getElementById('edit_link').value = data.item.link;
                document.getElementById('edit_target').value = data.item.target;
                document.getElementById('edit_bg_color').value = data.item.bg_color;
                document.getElementById('edit_bg_color_picker').value = data.item.bg_color;
                document.getElementById('edit_order_number').value = data.item.order_number;
                document.getElementById('edit_status').checked = data.item.status == 1;
                
                // Modalı göster
                new bootstrap.Modal(document.getElementById('editMenuItemModal')).show();
            } else {
                alert('Menü öğesi bulunamadı!');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Zaman kazanmak için AJAX başarısız olursa, forma manuel doldurma kodu ekleyelim
            const menuItems = <?php echo json_encode($menu_items); ?>;
            const item = menuItems.find(item => item.id == id);
            
            if (item) {
                document.getElementById('edit_id').value = item.id;
                document.getElementById('edit_title').value = item.title;
                document.getElementById('edit_icon').value = item.icon;
                document.getElementById('edit_link').value = item.link;
                document.getElementById('edit_target').value = item.target;
                document.getElementById('edit_bg_color').value = item.bg_color;
                document.getElementById('edit_bg_color_picker').value = item.bg_color;
                document.getElementById('edit_order_number').value = item.order_number;
                document.getElementById('edit_status').checked = item.status == 1;
                
                // Modalı göster
                new bootstrap.Modal(document.getElementById('editMenuItemModal')).show();
            } else {
                alert('Menü öğesi bulunamadı!');
            }
        });
}

// Silme onayı
function confirmDelete(id, title) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_message').textContent = `"${title}" başlıklı menü öğesini silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.`;
    new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
}
</script>

<?php require_once 'includes/footer.php'; ?>
