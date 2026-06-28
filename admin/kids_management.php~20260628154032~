<?php
require_once __DIR__ . '/includes/require_admin_web.php';

// Debug bilgisi
error_reporting(E_ALL);
ini_set('display_errors', 1);

$success_message = '';
$error_message = '';

// Mevcut bölüm bilgilerini getir (POST işleminden önce)
$kids_section = null;
$result = $conn->query("SELECT * FROM kids_section ORDER BY id DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $kids_section = $result->fetch_assoc();
}

// Bölüm bilgilerini güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_kids'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $button_text = trim($_POST['button_text']);
    $button_link = trim($_POST['button_link']);
    $background_color = trim($_POST['background_color']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $image = $kids_section['image'] ?? null;
    
    if (empty($title) || empty($description)) {
        $error_message = "Başlık ve açıklama alanları zorunludur.";
    } else {
        // Basit güncelleme - sadece en son kaydı güncelle
        $update_sql = "UPDATE kids_section SET title = ?, description = ?, button_text = ?, button_link = ?, background_color = ?, is_active = ? WHERE id = (SELECT id FROM (SELECT id FROM kids_section ORDER BY id DESC LIMIT 1) AS temp)";
        
        $stmt = $conn->prepare($update_sql);
        if ($stmt) {
            $stmt->bind_param("sssssi", $title, $description, $button_text, $button_link, $background_color, $is_active);
            
            if ($stmt->execute()) {
                $success_message = "Çocuklar bölümü başarıyla güncellendi!";
                
                // Güncellenmiş veriyi yeniden yükle
                $result = $conn->query("SELECT * FROM kids_section ORDER BY id DESC LIMIT 1");
                if ($result && $result->num_rows > 0) {
                    $kids_section = $result->fetch_assoc();
                }
            } else {
                $error_message = "Güncelleme sırasında hata oluştu: " . $stmt->error;
            }
        } else {
            $error_message = "SQL hazırlama hatası: " . $conn->error;
        }
    }
}

$page_title = "Çocuklar Bölümü Yönetimi";
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Çocuklar Bölümü Yönetimi</h4>
                    <p class="card-text">"Çocuk zihni yakılması gereken bir meşaledir" bölümünün içeriklerini düzenleyin.</p>
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

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Başlık</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($kids_section['title'] ?? 'Çocuk zihni yakılması gereken bir meşaledir'); ?>" required>
                                    <div class="form-text">Ana başlık metni</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                               <?php echo ($kids_section['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">
                                            Bölümü Aktif Et
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Açıklama</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($kids_section['description'] ?? 'MY Nakliyat Kids programımızdaki misyonumuz güvenli taşımacılık alanında: Kaliteli hizmet herkesin hakkıdır ve çocuklu ailelerin taşınma sürecinde özel ihtiyaçlarına yönelik yaklaşımımız, güvenli ve stressiz bir taşınma deneyimi sağlar.'); ?></textarea>
                            <div class="form-text">Bölümün ana açıklama metni</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="button_text" class="form-label">Buton Metni</label>
                                    <input type="text" class="form-control" id="button_text" name="button_text" 
                                           value="<?php echo htmlspecialchars($kids_section['button_text'] ?? 'DAHA FAZLA BİLGİ'); ?>">
                                    <div class="form-text">Buton üzerinde görünecek metin</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="button_link" class="form-label">Buton Linki</label>
                                    <input type="url" class="form-control" id="button_link" name="button_link" 
                                           value="<?php echo htmlspecialchars($kids_section['button_link'] ?? '#'); ?>" placeholder="https://...">
                                    <div class="form-text">Butona tıklandığında gidilecek adres</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">Resim</label>
                            <?php if ($kids_section && ($kids_section['image'] ?? null)): ?>
                                <div class="mb-2">
                                    <img src="../uploads/kids/<?php echo htmlspecialchars($kids_section['image']); ?>" alt="Mevcut resim" style="max-width: 200px; height: auto; border-radius: 8px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="image" name="image" accept=".jpg,.jpeg,.png,.webp,.avif">
                            <div class="form-text">Desteklenen formatlar: JPG, JPEG, PNG, WebP, AVIF</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="background_color" class="form-label">Arka Plan Rengi</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="color" class="form-control form-control-color" id="background_color" name="background_color" 
                                           value="<?php echo htmlspecialchars($kids_section['background_color'] ?? '#f8f9fa'); ?>" title="Arka plan rengini seçin">
                                </div>
                                <div class="col-md-6">
                                    <select class="form-control" id="preset_colors" onchange="setPresetColor()">
                                        <option value="">Hazır Renkler</option>
                                        <option value="#f8f9fa">Açık Gri</option>
                                        <option value="#e3f2fd">Açık Mavi</option>
                                        <option value="#f3e5f5">Açık Mor</option>
                                        <option value="#e8f5e8">Açık Yeşil</option>
                                        <option value="#fff3e0">Açık Turuncu</option>
                                        <option value="#fce4ec">Açık Pembe</option>
                                        <option value="#f1f8e9">Çok Açık Yeşil</option>
                                        <option value="#e0f2f1">Açık Turkuaz</option>
                                        <option value="#fafafa">Beyaz</option>
                                        <option value="#f5f5f5">Çok Açık Gri</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-text">Bölümün arka plan rengini seçin</div>
                        </div>
                        
                        <div class="mb-3">
                            <button type="submit" name="update_kids" class="btn btn-primary">
                                <i class="fas fa-save"></i> Değişiklikleri Kaydet
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Geri Dön
                            </a>
                        </div>
                    </form>
                    
                    <!-- Önizleme -->
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h5>Önizleme</h5>
                            <div class="preview-section" id="preview-section" style="background: <?php echo htmlspecialchars($kids_section['background_color'] ?? '#f8f9fa'); ?>; padding: 20px; border-radius: 8px; margin-top: 15px;">
                                <h2 id="preview-title"><?php echo htmlspecialchars($kids_section['title'] ?? 'Çocuk zihni yakılması gereken bir meşaledir'); ?></h2>
                                <p id="preview-description"><?php echo htmlspecialchars($kids_section['description'] ?? 'MY Nakliyat Kids programımızdaki misyonumuz güvenli taşımacılık alanında: Kaliteli hizmet herkesin hakkıdır ve çocuklu ailelerin taşınma sürecinde özel ihtiyaçlarına yönelik yaklaşımımız, güvenli ve stressiz bir taşınma deneyimi sağlar.'); ?></p>
                                <a href="<?php echo htmlspecialchars($kids_section['button_link'] ?? '#'); ?>" class="btn btn-white" id="preview-button">
                                    <?php echo htmlspecialchars($kids_section['button_text'] ?? 'DAHA FAZLA BİLGİ'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Hazır renk seçici fonksiyonu
function setPresetColor() {
    const presetSelect = document.getElementById('preset_colors');
    const colorInput = document.getElementById('background_color');
    const previewSection = document.getElementById('preview-section');
    
    if (presetSelect.value) {
        colorInput.value = presetSelect.value;
        previewSection.style.background = presetSelect.value;
    }
}

// Renk değişikliklerini canlı olarak göster
document.addEventListener('DOMContentLoaded', function() {
    const colorInput = document.getElementById('background_color');
    const previewSection = document.getElementById('preview-section');
    
    colorInput.addEventListener('input', function() {
        previewSection.style.background = this.value;
    });
    
    // Form alanlarındaki değişiklikleri önizlemede göster
    const titleInput = document.getElementById('title');
    const descriptionInput = document.getElementById('description');
    const buttonTextInput = document.getElementById('button_text');
    const buttonLinkInput = document.getElementById('button_link');
    
    const previewTitle = document.getElementById('preview-title');
    const previewDescription = document.getElementById('preview-description');
    const previewButton = document.getElementById('preview-button');
    
    titleInput.addEventListener('input', function() {
        previewTitle.textContent = this.value;
    });
    
    descriptionInput.addEventListener('input', function() {
        previewDescription.textContent = this.value;
    });
    
    buttonTextInput.addEventListener('input', function() {
        previewButton.textContent = this.value;
    });
    
    buttonLinkInput.addEventListener('input', function() {
        previewButton.href = this.value;
    });
});
</script>

<style>
.preview-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-top: 20px;
}

.preview-section h2 {
    color: white;
    margin-bottom: 15px;
    font-size: 1.8rem;
}

.preview-section p {
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.6;
    margin-bottom: 20px;
}

.btn-white {
    background: white;
    color: #667eea;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-white:hover {
    background: #f8f9fa;
    color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}
</style>

<?php require_once 'includes/footer.php'; ?> 