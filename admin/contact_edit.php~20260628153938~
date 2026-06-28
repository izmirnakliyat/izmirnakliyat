<?php
$page_title = 'İletişim Ayarları';
require_once 'includes/header.php';
require_once '../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Ayarları kaydetme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_contact') {
    
    // Form verilerini alıp güvenli hale getir
    $map_embed = $_POST['map_embed'] ?? '';
    $contact_title = trim($_POST['contact_title'] ?? '');
    $contact_description = trim($_POST['contact_description'] ?? '');
    $form_title = trim($_POST['form_title'] ?? '');
    $form_description = trim($_POST['form_description'] ?? '');
    
    // Ayarları güncelle
    $settings = [
        'contact_map_embed' => $map_embed,
        'contact_title' => $contact_title,
        'contact_description' => $contact_description,
        'contact_form_title' => $form_title,
        'contact_form_description' => $form_description
    ];
    
    $success = true;
    $error = "";
    
    foreach ($settings as $key => $value) {
        $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE name = ?");
        $stmt->bind_param("ss", $value, $key);
        
        if (!$stmt->execute()) {
            $success = false;
            $error .= $conn->error . "<br>";
        }
        
        // Eğer ayar yoksa ekle
        if ($stmt->affected_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO settings (name, value) VALUES (?, ?)");
            $stmt->bind_param("ss", $key, $value);
            
            if (!$stmt->execute()) {
                $success = false;
                $error .= $conn->error . "<br>";
            }
        }
    }
    
    if ($success) {
        $success_message = "İletişim sayfası ayarları başarıyla güncellendi.";
    } else {
        $error_message = "Ayarlar güncellenirken bir hata oluştu: " . $error;
    }
}

// Mevcut ayarları getir
$settings = [];
$result = $conn->query("SELECT name, value FROM settings WHERE name LIKE 'contact_%'");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['name']] = $row['value'];
    }
}
$settings = mynak_apply_contact_defaults_to_settings_array($settings);

// Varsayılan değerler
$map_embed = $settings['contact_map_embed'] ?? mynak_contact_default_map_embed_html();
$contact_title = $settings['contact_title'] ?? 'Bize Ulaşın';
$contact_description = $settings['contact_description'] ?? 'Aşağıdaki iletişim bilgilerimizden bize ulaşabilir veya formu doldurarak mesaj gönderebilirsiniz.';
$form_title = $settings['contact_form_title'] ?? 'Mesaj Gönderin';
$form_description = $settings['contact_form_description'] ?? 'Formu doldurarak bize hızlıca ulaşabilirsiniz. En kısa sürede sizinle iletişime geçeceğiz.';
?>

<div class="container-fluid">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">İletişim Sayfası Yönetimi</h5>
            <div>
                <a href="../iletisim.php" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bx bx-show"></i> Sayfayı Görüntüle
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_contact">
                
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Harita Ayarları</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="map_embed" class="form-label">Google Maps Embed Kodu</label>
                                    <textarea class="form-control" id="map_embed" name="map_embed" rows="5" placeholder="<iframe> kodunu buraya yapıştırın"><?php echo htmlspecialchars($map_embed); ?></textarea>
                                    <div class="form-text">Google Maps'ten aldığınız iframe kodunu buraya yapıştırın.</div>
                                </div>
                                <div class="mt-2 border p-2">
                                    <p class="mb-2">Harita Önizleme:</p>
                                    <div id="map_preview" class="border">
                                        <?php echo $map_embed; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="mb-0">İletişim Bölümü</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="contact_title" class="form-label">Başlık</label>
                                    <input type="text" class="form-control" id="contact_title" name="contact_title" value="<?php echo htmlspecialchars($contact_title); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="contact_description" class="form-label">Açıklama</label>
                                    <textarea class="form-control" id="contact_description" name="contact_description" rows="4"><?php echo htmlspecialchars($contact_description); ?></textarea>
                                </div>
                                <div class="form-text">
                                    Not: Telefon, e-posta ve adres bilgileri Site Ayarları sayfasından düzenlenmektedir.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="mb-0">Form Bölümü</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="form_title" class="form-label">Form Başlığı</label>
                                    <input type="text" class="form-control" id="form_title" name="form_title" value="<?php echo htmlspecialchars($form_title); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="form_description" class="form-label">Form Açıklaması</label>
                                    <textarea class="form-control" id="form_description" name="form_description" rows="4"><?php echo htmlspecialchars($form_description); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12 text-center">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i> Ayarları Kaydet
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Harita önizleme fonksiyonu
document.getElementById('map_embed').addEventListener('input', function() {
    document.getElementById('map_preview').innerHTML = this.value;
});
</script>

<?php require_once 'includes/footer.php'; ?> 