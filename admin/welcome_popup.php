<?php
$page_title = "Hoş Geldin Popup Yönetimi";
require_once 'includes/header.php';

// Yetki kontrolü
if (!hasPermission('popup_view')) {
    echo "<div class='alert alert-danger'>Bu sayfayı görüntüleme yetkiniz bulunmamaktadır.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Veritabanı tablosunu oluştur (yoksa)
$tableCheck = $conn->query("SHOW TABLES LIKE 'welcome_popup'");
if ($tableCheck->num_rows == 0) {
    $createTable = "CREATE TABLE welcome_popup (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL DEFAULT 'Hoş Geldiniz!',
        description TEXT,
        image VARCHAR(255),
        button_text VARCHAR(100) DEFAULT 'Tamam',
        button_link VARCHAR(255),
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        show_delay INT(11) DEFAULT 2000,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->query($createTable);
    
    // Varsayılan veri ekle
    $defaultData = "INSERT INTO welcome_popup (title, description, button_text, is_active, show_delay) VALUES 
    ('MY Nakliyat\\'a Hoş Geldiniz!', 'Güvenli ve profesyonel nakliyat hizmetlerine hoş geldiniz. Eşyalarınızı güvenle taşımak için buradayız.', 'Tamam', 1, 2000)";
    $conn->query($defaultData);
}

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_popup'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $button_text = trim($_POST['button_text']);
    $button_link = trim($_POST['button_link']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $show_delay = (int)$_POST['show_delay'];
    
    // Resim yükleme
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/popup/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = 'welcome_popup_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = $new_filename;
            }
        }
    }
    
    // Mevcut popup'ı kontrol et
    $existing = $conn->query("SELECT id FROM welcome_popup LIMIT 1");
    
    if ($existing->num_rows > 0) {
        // Güncelle
        $row = $existing->fetch_assoc();
        $popup_id = $row['id'];
        
        if ($image_path) {
            $stmt = $conn->prepare("UPDATE welcome_popup SET title = ?, description = ?, image = ?, button_text = ?, button_link = ?, is_active = ?, show_delay = ? WHERE id = ?");
            $stmt->bind_param("sssssiii", $title, $description, $image_path, $button_text, $button_link, $is_active, $show_delay, $popup_id);
        } else {
            $stmt = $conn->prepare("UPDATE welcome_popup SET title = ?, description = ?, button_text = ?, button_link = ?, is_active = ?, show_delay = ? WHERE id = ?");
            $stmt->bind_param("sssssii", $title, $description, $button_text, $button_link, $is_active, $show_delay, $popup_id);
        }
    } else {
        // Yeni ekle
        if ($image_path) {
            $stmt = $conn->prepare("INSERT INTO welcome_popup (title, description, image, button_text, button_link, is_active, show_delay) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssii", $title, $description, $image_path, $button_text, $button_link, $is_active, $show_delay);
        } else {
            $stmt = $conn->prepare("INSERT INTO welcome_popup (title, description, button_text, button_link, is_active, show_delay) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $title, $description, $button_text, $button_link, $is_active, $show_delay);
        }
    }
    
    if ($stmt->execute()) {
        $success_message = "Hoş geldin popup'ı başarıyla güncellendi!";
    } else {
        $error_message = "Popup güncellenirken bir hata oluştu: " . $conn->error;
    }
}

// Mevcut popup verilerini getir
$popup_data = null;
$result = $conn->query("SELECT * FROM welcome_popup ORDER BY id DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $popup_data = $result->fetch_assoc();
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Hoş Geldin Popup Yönetimi</h4>
                    <p class="card-text">Siteye ilk giren kullanıcılar için gösterilecek popup'ı düzenleyin.</p>
                </div>
                <div class="card-body">
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success">
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group mb-3">
                                    <label for="title">Popup Başlığı</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($popup_data['title'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="description">Popup Açıklaması</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($popup_data['description'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="button_text">Buton Metni</label>
                                            <input type="text" class="form-control" id="button_text" name="button_text" 
                                                   value="<?php echo htmlspecialchars($popup_data['button_text'] ?? 'Tamam'); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="button_link">Buton Linki (Opsiyonel)</label>
                                            <input type="text" class="form-control" id="button_link" name="button_link" 
                                                   value="<?php echo htmlspecialchars($popup_data['button_link'] ?? ''); ?>" 
                                                   placeholder="Örn: kayit.php">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="show_delay">Gösterim Gecikmesi (ms)</label>
                                    <input type="number" class="form-control" id="show_delay" name="show_delay" 
                                           value="<?php echo $popup_data['show_delay'] ?? 2000; ?>" min="0" max="10000">
                                    <small class="form-text text-muted">Popup'ın kaç milisaniye sonra gösterileceği (1000ms = 1 saniye)</small>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           <?php echo ($popup_data['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        Popup'ı Aktif Et
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="image">Popup Görseli</label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                    <small class="form-text text-muted">Önerilen boyut: 400x300px</small>
                                    
                                    <?php if (!empty($popup_data['image'])): ?>
                                        <div class="mt-2">
                                            <img src="../uploads/popup/<?php echo htmlspecialchars($popup_data['image']); ?>" 
                                                 alt="Mevcut Popup Görseli" class="img-thumbnail" style="max-width: 200px;">
                                            <p class="text-muted small">Mevcut görsel</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6>Önizleme</h6>
                                        <div class="welcome-popup-preview">
                                            <div class="popup-preview-content">
                                                <h5><?php echo htmlspecialchars($popup_data['title'] ?? 'Hoş Geldiniz!'); ?></h5>
                                                <p><?php echo htmlspecialchars($popup_data['description'] ?? 'Popup açıklaması burada görünecek.'); ?></p>
                                                <button class="btn btn-primary btn-sm"><?php echo htmlspecialchars($popup_data['button_text'] ?? 'Tamam'); ?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" name="update_popup" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Popup'ı Güncelle
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Geri Dön
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.welcome-popup-preview {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    margin-top: 10px;
}

.popup-preview-content h5 {
    color: var(--primary-color);
    margin-bottom: 10px;
    font-size: 16px;
}

.popup-preview-content p {
    font-size: 14px;
    margin-bottom: 15px;
    color: #666;
}

.popup-preview-content .btn {
    font-size: 12px;
    padding: 5px 15px;
}
</style>

<script>
// Önizleme güncelleme
document.addEventListener('DOMContentLoaded', function() {
    const titleInput = document.getElementById('title');
    const descriptionInput = document.getElementById('description');
    const buttonTextInput = document.getElementById('button_text');
    const previewTitle = document.querySelector('.popup-preview-content h5');
    const previewDescription = document.querySelector('.popup-preview-content p');
    const previewButton = document.querySelector('.popup-preview-content .btn');
    
    function updatePreview() {
        previewTitle.textContent = titleInput.value || 'Hoş Geldiniz!';
        previewDescription.textContent = descriptionInput.value || 'Popup açıklaması burada görünecek.';
        previewButton.textContent = buttonTextInput.value || 'Tamam';
    }
    
    titleInput.addEventListener('input', updatePreview);
    descriptionInput.addEventListener('input', updatePreview);
    buttonTextInput.addEventListener('input', updatePreview);
});
</script>

<?php require_once 'includes/footer.php'; ?> 