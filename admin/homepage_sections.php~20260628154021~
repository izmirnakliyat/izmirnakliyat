<?php
require_once __DIR__ . '/includes/require_admin_web.php';

// Debug bilgisi
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Tablo varlığını kontrol et
$table_check = $conn->query("SHOW TABLES LIKE 'homepage_sections'");
if ($table_check->num_rows == 0) {
    echo "<div class='alert alert-warning'>⚠️ Veritabanı tablosu bulunamadı. Lütfen önce kurulum dosyasını çalıştırın: <a href='../install_homepage_sections.php'>Kurulum Dosyası</a></div>";
}

// Bölüm durumunu güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_sections'])) {
    $sections = [
        'hero_slider' => isset($_POST['hero_slider']) ? 1 : 0,
        'campus_cards' => isset($_POST['campus_cards']) ? 1 : 0,
        'about_section' => isset($_POST['about_section']) ? 1 : 0,
        'gallery_section' => isset($_POST['gallery_section']) ? 1 : 0,
        'blog_section' => isset($_POST['blog_section']) ? 1 : 0,
        'features_section' => isset($_POST['features_section']) ? 1 : 0,
        'kids_section' => isset($_POST['kids_section']) ? 1 : 0,
        'team_section' => isset($_POST['team_section']) ? 1 : 0
    ];
    
    foreach ($sections as $section_name => $status) {
        $stmt = $conn->prepare("INSERT INTO homepage_sections (section_name, is_active) VALUES (?, ?) ON DUPLICATE KEY UPDATE is_active = ?");
        $stmt->bind_param("sii", $section_name, $status, $status);
        $stmt->execute();
    }
    
    $success_message = "Ana sayfa bölümleri başarıyla güncellendi!";
}

// Mevcut bölüm durumlarını getir
$sections_status = [];
$result = $conn->query("SELECT section_name, is_active FROM homepage_sections");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sections_status[$row['section_name']] = $row['is_active'];
    }
}

// Varsayılan değerler (eğer veritabanında yoksa)
$default_sections = [
    'hero_slider' => 1,
    'campus_cards' => 1,
    'about_section' => 1,
    'gallery_section' => 1,
    'blog_section' => 1,
    'features_section' => 1,
    'kids_section' => 1,
    'team_section' => 1
];

foreach ($default_sections as $section => $default_status) {
    if (!isset($sections_status[$section])) {
        $sections_status[$section] = $default_status;
    }
}

$page_title = "Ana Sayfa Bölümleri";
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Ana Sayfa Bölümleri Yönetimi</h4>
                    <p class="card-text">Ana sayfada hangi bölümlerin görüneceğini kontrol edin.</p>
                </div>
                <div class="card-body">
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success">
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="section-item">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="hero_slider" name="hero_slider" 
                                               <?php echo $sections_status['hero_slider'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="hero_slider">
                                            <strong>Hero Slider (Ana Banner)</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted">Ana sayfanın üst kısmındaki slayt gösterisi</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="section-item">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="campus_cards" name="campus_cards" 
                                               <?php echo $sections_status['campus_cards'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="campus_cards">
                                            <strong>Kampüs Kartları</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted">Anaokul, İlkokul, Ortaokul kampüs kartları</small>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="section-item">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="about_section" name="about_section" 
                                               <?php echo $sections_status['about_section'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="about_section">
                                            <strong>Hakkımızda Bölümü</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted">Okul hakkında bilgi ve video bölümü</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="section-item">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="gallery_section" name="gallery_section" 
                                               <?php echo $sections_status['gallery_section'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="gallery_section">
                                            <strong>Galeri Bölümü</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted">Blog bölümünün üstünde gösterilen fotoğraf galerisi</small>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="section-item">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="blog_section" name="blog_section" 
                                               <?php echo $sections_status['blog_section'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="blog_section">
                                            <strong>Blog Bölümü</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted">Son blog yazılarının gösterildiği bölüm</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="section-item">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="features_section" name="features_section" 
                                               <?php echo $sections_status['features_section'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="features_section">
                                            <strong>Özellikler Bölümü</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted">"Okutmak Genlerimizde Var" özellik kartları</small>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="section-item">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="kids_section" name="kids_section" 
                                               <?php echo $sections_status['kids_section'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="kids_section">
                                            <strong>Çocuklar Bölümü</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted">"Çocuk zihni yakılması gereken bir meşaledir" bölümü</small>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="section-item">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="team_section" name="team_section" 
                                               <?php echo $sections_status['team_section'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="team_section">
                                            <strong>Eğitmen Kadrosu</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted">Öğretmen ve eğitmen kadrosunun gösterildiği bölüm</small>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" name="update_sections" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Değişiklikleri Kaydet
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
.section-item {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 15px;
    border: 1px solid #e9ecef;
}

.section-item:hover {
    background: #e9ecef;
    transition: background-color 0.3s ease;
}

.form-check-input:checked {
    background-color: #007bff;
    border-color: #007bff;
}

.form-check-label {
    font-size: 16px;
    margin-left: 10px;
}

.text-muted {
    display: block;
    margin-top: 5px;
    margin-left: 35px;
}
</style>

<?php require_once 'includes/footer.php'; ?> 