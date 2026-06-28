<?php
$page_title = 'Ana Sayfa İçerik Yönetimi';
require_once 'includes/header.php';

$success = '';
$error = '';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kayan yazı güncelle
    $rt_status = isset($_POST['running_text_status']) ? 1 : 0;
    $rt_value = trim($_POST['running_text_value'] ?? '');
    // Statusu güncelle
    $conn->query("INSERT INTO homepage_sections_content (section_key, section_name, status) VALUES ('running_text', 'Ana Sayfa Kayan Yazı', $rt_status) ON DUPLICATE KEY UPDATE status = $rt_status");
    // Metni güncelle
    $stmt = $conn->prepare("INSERT INTO settings (name, value) VALUES ('homepage_running_text', ?) ON DUPLICATE KEY UPDATE value = ?");
    $stmt->bind_param('ss', $rt_value, $rt_value);
    $stmt->execute();
    // Diğer bölümler
    foreach ($_POST['sections'] as $section_key => $section_data) {
        $sub_heading = $section_data['sub_heading'];
        $main_heading = $section_data['main_heading'];
        $description = $section_data['description'];
        $button_text = $section_data['button_text'];
        $button_link = $section_data['button_link'];
        $status = isset($section_data['status']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE homepage_sections_content SET sub_heading = ?, main_heading = ?, description = ?, button_text = ?, button_link = ?, status = ? WHERE section_key = ?");
        $stmt->bind_param("sssssss", $sub_heading, $main_heading, $description, $button_text, $button_link, $status, $section_key);
        
        if ($stmt->execute()) {
            $success = "Ana sayfa içerikleri başarıyla güncellendi.";
        } else {
            $error = "Güncelleme sırasında bir hata oluştu: " . $stmt->error;
            break;
        }
    }
}

// Bölümleri getir
$sections = $conn->query("SELECT * FROM homepage_sections_content ORDER BY id ASC");
// Kayan yazı statusunu çek
$running_text_row = $conn->query("SELECT * FROM homepage_sections_content WHERE section_key = 'running_text' LIMIT 1");
$running_text_status = 1;
if ($running_text_row && $row = $running_text_row->fetch_assoc()) {
    $running_text_status = $row['status'];
}
// Kayan yazı metnini çek
$running_text_value = '';
$rt_result = $conn->query("SELECT value FROM settings WHERE name = 'homepage_running_text' LIMIT 1");
if ($rt_result && $rt_row = $rt_result->fetch_assoc()) {
    $running_text_value = $rt_row['value'];
}

// Galeri kapak fotoğrafı yükleme işlemi
$gallery_cover_image = '';
$cover_result = $conn->query("SELECT value FROM settings WHERE name = 'gallery_cover_image' LIMIT 1");
if ($cover_result && $cover_row = $cover_result->fetch_assoc()) {
    $gallery_cover_image = $cover_row['value'];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['gallery_cover_image']) && $_FILES['gallery_cover_image']['error'] === 0) {
    $upload_dir = '../uploads/settings/';
    if (!file_exists($upload_dir)) { mkdir($upload_dir, 0777, true); }
    $ext = pathinfo($_FILES['gallery_cover_image']['name'], PATHINFO_EXTENSION);
    $filename = 'gallery_cover_' . time() . '.' . $ext;
    $target = $upload_dir . $filename;
    if (move_uploaded_file($_FILES['gallery_cover_image']['tmp_name'], $target)) {
        $gallery_cover_image = 'uploads/settings/' . $filename;
        $stmt = $conn->prepare("INSERT INTO settings (name, value) VALUES ('gallery_cover_image', ?) ON DUPLICATE KEY UPDATE value = ?");
        $stmt->bind_param('ss', $gallery_cover_image, $gallery_cover_image);
        $stmt->execute();
    }
}
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Ana Sayfa İçerik Yönetimi</h5>
        <p class="text-muted mb-0">Ana sayfadaki bölümlerin başlık, alt başlık ve açıklama metinlerini düzenleyin.</p>
    </div>
    <div class="card-body">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <?php if ($sections && $sections->num_rows > 0): ?>
                <?php while ($section = $sections->fetch_assoc()): ?>
                    <?php if ($section['section_key'] === 'running_text') continue; ?>
                    <div class="section-item mb-4 p-4 border rounded">
                        <h6 class="mb-3"><?php echo htmlspecialchars($section['section_name']); ?></h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Alt Başlık (Sub Heading)</label>
                                    <input type="text" class="form-control" name="sections[<?php echo $section['section_key']; ?>][sub_heading]" value="<?php echo htmlspecialchars($section['sub_heading'] ?? ''); ?>">
                                    <small class="text-muted">Örnek: "Our Logistics Services!"</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Durum</label>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="sections[<?php echo $section['section_key']; ?>][status]" value="1" <?php echo $section['status'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Aktif</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Ana Başlık (Main Heading)</label>
                            <textarea class="form-control" name="sections[<?php echo $section['section_key']; ?>][main_heading]" rows="2"><?php echo htmlspecialchars($section['main_heading'] ?? ''); ?></textarea>
                            <small class="text-muted">HTML etiketleri kullanabilirsiniz. Örnek: "Offering Cost Effecient<br>Transport <span class='hl'>Shipping!</span>"</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Açıklama (Description)</label>
                            <textarea class="form-control" name="sections[<?php echo $section['section_key']; ?>][description]" rows="2"><?php echo htmlspecialchars($section['description'] ?? ''); ?></textarea>
                            <small class="text-muted">HTML etiketleri kullanabilirsiniz. Örnek: "logistics company specializes in managing the transportation<br>storage and distribution of goods."</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Buton Metni</label>
                                    <input type="text" class="form-control" name="sections[<?php echo $section['section_key']; ?>][button_text]" value="<?php echo htmlspecialchars($section['button_text'] ?? ''); ?>">
                                    <small class="text-muted">Örnek: "View All Gallery"</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Buton Linki</label>
                                    <input type="text" class="form-control" name="sections[<?php echo $section['section_key']; ?>][button_link]" value="<?php echo htmlspecialchars($section['button_link'] ?? ''); ?>">
                                    <small class="text-muted">Örnek: "galeri.php" veya "https://example.com"</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if ($section['section_key'] === 'services'): ?>
                        <!-- Kayan Yazı Alanı Hizmetler altına -->
                        <div class="section-item mb-4 p-4 border rounded">
                            <h6 class="mb-3">Ana Sayfa Kayan Yazı</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Durum</label>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="running_text_status" value="1" <?php echo $running_text_status ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Aktif</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kayan Yazılar</label>
                                <textarea class="form-control" name="running_text_value" rows="2"><?php echo htmlspecialchars($running_text_value); ?></textarea>
                                <small class="text-muted">Maddeleri <strong>|</strong> ile ayırın; şeritte aralarında beyaz kamyon ikonu ile sürekli kayar. Örnek: <code>Yazı 1 | Yazı 2 | Yazı 3</code></small>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">Henüz bölüm içeriği eklenmemiş.</div>
            <?php endif; ?>
            
            <div class="section-item mb-4 p-4 border rounded">
                <h6 class="mb-3">Galeri Kapak Fotoğrafı</h6>
                <?php if ($gallery_cover_image): ?>
                    <div class="mb-2"><img src="../<?php echo htmlspecialchars($gallery_cover_image); ?>" alt="Galeri Kapak" style="max-width:300px;max-height:180px;border-radius:8px;"></div>
                <?php endif; ?>
                <input type="file" name="gallery_cover_image" accept="image/*" class="form-control">
                <small class="text-muted">Yalnızca 1 fotoğraf yükleyin. Bu fotoğraf hem ana sayfa hem galeri üstünde kullanılacak.</small>
            </div>
            
            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class='bx bx-save'></i> Değişiklikleri Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 