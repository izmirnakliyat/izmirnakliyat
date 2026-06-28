<?php
require_once __DIR__ . '/includes/require_admin_web.php';

// Debug bilgisi
error_reporting(E_ALL);
ini_set('display_errors', 1);

$success_message = '';
$error_message = '';

// Ana bölüm bilgilerini güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_main'])) {
    $main_title = trim($_POST['main_title']);
    $subtitle = trim($_POST['subtitle']);
    $description = trim($_POST['description']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($main_title) || empty($subtitle) || empty($description)) {
        $error_message = "Lütfen tüm alanları doldurun.";
    } else {
        $stmt = $conn->prepare("INSERT INTO features_section (main_title, subtitle, description, is_active) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE main_title = ?, subtitle = ?, description = ?, is_active = ?");
        $stmt->bind_param("sssisssi", $main_title, $subtitle, $description, $is_active, $main_title, $subtitle, $description, $is_active);
        
        if ($stmt->execute()) {
            $success_message = "Ana bölüm bilgileri başarıyla güncellendi!";
        } else {
            $error_message = "Güncelleme sırasında hata oluştu: " . $stmt->error;
        }
    }
}

// Özellik kartı ekle/güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_card'])) {
    $card_id = isset($_POST['card_id']) ? (int)$_POST['card_id'] : 0;
    $title = trim($_POST['card_title']);
    $description = trim($_POST['card_description']);
    $image_url = trim($_POST['card_image_url']);
    $order_number = (int)$_POST['card_order'];
    $is_active = isset($_POST['card_is_active']) ? 1 : 0;
    
    if (empty($title) || empty($description)) {
        $error_message = "Kart başlığı ve açıklaması zorunludur.";
    } else {
        if ($card_id > 0) {
            // Güncelle
            $stmt = $conn->prepare("UPDATE features_cards SET title = ?, description = ?, image_url = ?, order_number = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("sssiii", $title, $description, $image_url, $order_number, $is_active, $card_id);
        } else {
            // Yeni ekle
            $stmt = $conn->prepare("INSERT INTO features_cards (title, description, image_url, order_number, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssii", $title, $description, $image_url, $order_number, $is_active);
        }
        
        if ($stmt->execute()) {
            $success_message = "Özellik kartı başarıyla " . ($card_id > 0 ? "güncellendi" : "eklendi") . "!";
        } else {
            $error_message = "İşlem sırasında hata oluştu: " . $stmt->error;
        }
    }
}

// Kart sil
if (isset($_GET['delete_card']) && is_numeric($_GET['delete_card'])) {
    $card_id = (int)$_GET['delete_card'];
    $stmt = $conn->prepare("DELETE FROM features_cards WHERE id = ?");
    $stmt->bind_param("i", $card_id);
    
    if ($stmt->execute()) {
        $success_message = "Kart başarıyla silindi!";
    } else {
        $error_message = "Silme sırasında hata oluştu.";
    }
}

// Ana bölüm bilgilerini getir
$main_section = null;
$result = $conn->query("SELECT * FROM features_section ORDER BY id DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $main_section = $result->fetch_assoc();
}

// Özellik kartlarını getir
$cards = [];
$result = $conn->query("SELECT * FROM features_cards ORDER BY order_number ASC, id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $cards[] = $row;
    }
}

$page_title = "Özellikler Bölümü Yönetimi";
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Özellikler Bölümü Yönetimi</h4>
                    <p class="card-text">"Okutmak Genlerimizde Var" bölümünün içeriklerini düzenleyin.</p>
                </div>
                <div class="card-body">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Ana Bölüm Bilgileri -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5>Ana Bölüm Bilgileri</h5>
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="main_title" class="form-label">Ana Başlık</label>
                                            <input type="text" class="form-control" id="main_title" name="main_title" 
                                                   value="<?php echo htmlspecialchars($main_section['main_title'] ?? 'Okutmak'); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="subtitle" class="form-label">Alt Başlık</label>
                                            <input type="text" class="form-control" id="subtitle" name="subtitle" 
                                                   value="<?php echo htmlspecialchars($main_section['subtitle'] ?? 'Genlerimizde Var'); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Açıklama</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($main_section['description'] ?? 'Markamızın genleri vizyonumuzu oluşturuyor. Bütünsel gelişim modeliyle bütünsel başarıyı hedefliyoruz.'); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                               <?php echo ($main_section['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">
                                            Bölümü Aktif Et
                                        </label>
                                    </div>
                                </div>
                                <button type="submit" name="update_main" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Ana Bölümü Güncelle
                                </button>
                            </form>
                        </div>
                    </div>

                    <hr>

                    <!-- Özellik Kartları -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Özellik Kartları</h5>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCardModal">
                                    <i class="fas fa-plus"></i> Yeni Kart Ekle
                                </button>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Sıra</th>
                                            <th>Başlık</th>
                                            <th>Açıklama</th>
                                            <th>Resim</th>
                                            <th>Durum</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cards as $card): ?>
                                        <tr>
                                            <td><?php echo $card['order_number']; ?></td>
                                            <td><?php echo htmlspecialchars($card['title']); ?></td>
                                            <td><?php echo htmlspecialchars($card['description']); ?></td>
                                            <td>
                                                <?php if ($card['image_url']): ?>
                                                    <img src="<?php echo htmlspecialchars($card['image_url']); ?>" 
                                                         alt="<?php echo htmlspecialchars($card['title']); ?>" 
                                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                                <?php else: ?>
                                                    <span class="text-muted">Resim yok</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $card['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <?php echo $card['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        onclick="editCard(<?php echo $card['id']; ?>, '<?php echo htmlspecialchars($card['title']); ?>', '<?php echo htmlspecialchars($card['description']); ?>', '<?php echo htmlspecialchars($card['image_url']); ?>', <?php echo $card['order_number']; ?>, <?php echo $card['is_active']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?delete_card=<?php echo $card['id']; ?>" class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Bu kartı silmek istediğinizden emin misiniz?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Kart Ekleme/Düzenleme Modal -->
<div class="modal fade" id="addCardModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Yeni Kart Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" id="card_id" name="card_id" value="0">
                    <div class="mb-3">
                        <label for="card_title" class="form-label">Başlık</label>
                        <input type="text" class="form-control" id="card_title" name="card_title" required>
                    </div>
                    <div class="mb-3">
                        <label for="card_description" class="form-label">Açıklama</label>
                        <input type="text" class="form-control" id="card_description" name="card_description" required>
                    </div>
                    <div class="mb-3">
                        <label for="card_image_url" class="form-label">Resim URL</label>
                        <input type="url" class="form-control" id="card_image_url" name="card_image_url" placeholder="https://...">
                    </div>
                    <div class="mb-3">
                        <label for="card_order" class="form-label">Sıra</label>
                        <input type="number" class="form-control" id="card_order" name="card_order" value="1" min="1">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="card_is_active" name="card_is_active" checked>
                            <label class="form-check-label" for="card_is_active">
                                Kartı Aktif Et
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="update_card" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCard(id, title, description, imageUrl, order, isActive) {
    document.getElementById('modalTitle').textContent = 'Kartı Düzenle';
    document.getElementById('card_id').value = id;
    document.getElementById('card_title').value = title;
    document.getElementById('card_description').value = description;
    document.getElementById('card_image_url').value = imageUrl;
    document.getElementById('card_order').value = order;
    document.getElementById('card_is_active').checked = isActive == 1;
    
    new bootstrap.Modal(document.getElementById('addCardModal')).show();
}

// Modal kapandığında formu temizle
document.getElementById('addCardModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modalTitle').textContent = 'Yeni Kart Ekle';
    document.getElementById('card_id').value = '0';
    document.getElementById('card_title').value = '';
    document.getElementById('card_description').value = '';
    document.getElementById('card_image_url').value = '';
    document.getElementById('card_order').value = '1';
    document.getElementById('card_is_active').checked = true;
});
</script>

<?php require_once 'includes/footer.php'; ?> 