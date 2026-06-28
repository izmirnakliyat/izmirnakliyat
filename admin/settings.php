<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mynak_sync_gbp_hours'])) {
    $gbpMsg = 'GBP senkron modülü yüklü değil (includes/mynak_gbp_sync.php).';
    $gbpOk = false;
    if (function_exists('mynak_sync_gbp_data') && function_exists('mynak_gbp_cache_file_path')) {
        $cachePath = mynak_gbp_cache_file_path();
        if (is_file($cachePath)) {
            @unlink($cachePath);
        }
        try {
            $gbpSyncResult = mynak_sync_gbp_data($conn, true);
            $gbpOk = !empty($gbpSyncResult['ok']);
            $gbpMsg = (string) ($gbpSyncResult['message'] ?? '');
        } catch (Throwable $e) {
            error_log('MyNakliyat GBP sync: ' . $e->getMessage());
            $gbpMsg = 'Senkron hatası: ' . $e->getMessage();
        }
    }
    header('Location: settings.php?gbp_sync=' . ($gbpOk ? '1' : '0') . '&msg=' . rawurlencode($gbpMsg));
    exit;
}

// Ayarları Kaydetme İşlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $success = true;
    $message = "";
    
    // Logo ve favicon yükleme işlemleri
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico'];
    $uploadPath = '../uploads/settings/';
    
    // Dizin yoksa oluştur
    if (!file_exists($uploadPath)) {
        mkdir($uploadPath, 0777, true);
        error_log("Settings upload directory created: " . $uploadPath);
    }
    
    // Dosya yükleme fonksiyonu
    function uploadFile($file, $fileInputName, $uploadPath, $oldFileName = '') {
        global $allowedExtensions;
        
        if (!empty($file[$fileInputName]['name'])) {
            $fileName = $file[$fileInputName]['name'];
            $fileTmpName = $file[$fileInputName]['tmp_name'];
            $fileSize = $file[$fileInputName]['size'];
            $fileError = $file[$fileInputName]['error'];
            
            error_log("Dosya yükleme girişimi: " . $fileInputName . " - " . $fileName);
            
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            if (in_array($fileExt, $allowedExtensions)) {
                        if ($fileError === 0) {
                    if ($fileSize <= 4000000) { // 4MB limit (düşük kalite kaybı yaşamamak için)
                        $fileNameNew = uniqid('', true) . '.' . $fileExt;
                        $fileDestination = $uploadPath . $fileNameNew;
                        
                                if (move_uploaded_file($fileTmpName, $fileDestination)) {
                            error_log("Dosya başarıyla yüklendi: " . $fileDestination);
                            // Eski dosyayı sil
                            if (!empty($oldFileName) && file_exists($uploadPath . $oldFileName)) {
                                unlink($uploadPath . $oldFileName);
                                error_log("Eski dosya silindi: " . $uploadPath . $oldFileName);
                            }
                            return $fileNameNew;
                        } else {
                            error_log("Dosya taşıma hatası: " . error_get_last()['message']);
                            return false;
                        }
                        } else {
                            error_log("Dosya boyutu çok büyük: " . $fileSize);
                        return false; // "Dosya boyutu çok büyük.";
                    }
                } else {
                    error_log("Dosya yükleme hatası kodu: " . $fileError);
                    return false; // "Dosya yüklenirken bir hata oluştu.";
                }
            } else {
                error_log("Geçersiz dosya türü: " . $fileExt);
                return false; // "Bu dosya türüne izin verilmiyor.";
            }
        }
        return '';
    }
    
    // Mevcut ayarları getir
    $existing_settings = [];
    $settings_result = $conn->query("SELECT name, value FROM settings WHERE name IN 
        ('logo_light', 'logo_dark', 'favicon', 'site_title', 'primary_color', 'secondary_color', 'text_color', 'phone1', 'phone2', 'whatsapp', 'address', 'email', 'short_description', 
         'instagram', 'twitter', 'facebook', 'youtube', 'linkedin', 'website', 'copyright_text')");
    
    if ($settings_result && $settings_result->num_rows > 0) {
        while ($row = $settings_result->fetch_assoc()) {
            $existing_settings[$row['name']] = $row['value'];
        }
    }
    
    // Logo ve favicon dosyalarını yükle
    $logo_light = uploadFile($_FILES, 'logo_light', $uploadPath, $existing_settings['logo_light'] ?? '');
    $logo_dark = uploadFile($_FILES, 'logo_dark', $uploadPath, $existing_settings['logo_dark'] ?? '');
    $favicon = uploadFile($_FILES, 'favicon', $uploadPath, $existing_settings['favicon'] ?? '');
    
    // Ayarları hazırla
    $settings = [
        'logo_light' => $logo_light !== false && $logo_light !== '' ? $logo_light : ($existing_settings['logo_light'] ?? ''),
        'logo_dark' => $logo_dark !== false && $logo_dark !== '' ? $logo_dark : ($existing_settings['logo_dark'] ?? ''),
        'favicon' => $favicon !== false && $favicon !== '' ? $favicon : ($existing_settings['favicon'] ?? ''),
        'site_title' => $conn->real_escape_string($_POST['site_title'] ?? ''),
        'primary_color' => $conn->real_escape_string($_POST['primary_color'] ?? ''),
        'secondary_color' => $conn->real_escape_string($_POST['secondary_color'] ?? ''),
        'text_color' => $conn->real_escape_string($_POST['text_color'] ?? ''),
        'phone1' => $conn->real_escape_string($_POST['phone1'] ?? ''),
        'phone2' => $conn->real_escape_string($_POST['phone2'] ?? ''),
        'whatsapp' => $conn->real_escape_string($_POST['whatsapp'] ?? ''),
        'address' => $conn->real_escape_string($_POST['address'] ?? ''),
        'email' => $conn->real_escape_string($_POST['email'] ?? ''),
        'short_description' => $conn->real_escape_string($_POST['short_description'] ?? ''),
        'instagram' => $conn->real_escape_string($_POST['instagram'] ?? ''),
        'twitter' => $conn->real_escape_string($_POST['twitter'] ?? ''),
        'facebook' => $conn->real_escape_string($_POST['facebook'] ?? ''),
        'youtube' => $conn->real_escape_string($_POST['youtube'] ?? ''),
        'linkedin' => $conn->real_escape_string($_POST['linkedin'] ?? ''),
        'website' => $conn->real_escape_string($_POST['website'] ?? ''),
        'copyright_text' => $conn->real_escape_string($_POST['copyright_text'] ?? ''),
        'clarity_enabled' => (isset($_POST['clarity_enabled']) && (string) $_POST['clarity_enabled'] === '1') ? '1' : '0',
        'clarity_project_id' => substr(preg_replace('/[^a-z0-9]/i', '', (string) ($_POST['clarity_project_id'] ?? '')), 0, 32),
        'mynak_home_youtube_ids' => trim((string) ($_POST['mynak_home_youtube_ids'] ?? '')),
        'mynak_youtube_shorts_ids' => trim((string) ($_POST['mynak_youtube_shorts_ids'] ?? '')),
    ];

    $homeYtRaw = (string) ($settings['mynak_home_youtube_ids'] ?? '');
    if ($homeYtRaw !== '') {
        $homeYtDecoded = json_decode($homeYtRaw, true);
        if (!is_array($homeYtDecoded)) {
            $success = false;
            $message = 'mynak_home_youtube_ids geçerli bir JSON dizisi olmalıdır. Örnek: [{"id":"VIDEO_ID","uploadDate":"2021-11-08"}] veya ["VIDEO_ID"]';
            $is_ajax_json_err = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if ($is_ajax_json_err) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
    }

    $shortsYtRaw = (string) ($settings['mynak_youtube_shorts_ids'] ?? '');
    if ($shortsYtRaw !== '') {
        $shortsYtDecoded = json_decode($shortsYtRaw, true);
        if (!is_array($shortsYtDecoded)) {
            $success = false;
            $message = 'mynak_youtube_shorts_ids geçerli bir JSON dizisi olmalıdır. Örnek: [{"id":"VIDEO_ID","title":"Baslik"}] veya shorts URL';
            $is_ajax_json_err = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if ($is_ajax_json_err) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
    }
    
    // Ayarları veritabanına kaydet
    if ($success) {
    foreach ($settings as $name => $value) {
        try {
            // Doğrudan güncelleme yap, kayıt yoksa ekle
            $sql = "INSERT INTO settings (name, value, description) VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE value = VALUES(value)";

            // Açıklamaları belirle
            $descriptions = [
                'logo_light' => 'Açık Logo (Beyaz Arka Plan için)',
                'logo_dark' => 'Koyu Logo (Koyu Arka Plan için)',
                'favicon' => 'Site Favicon',
                'site_title' => 'Site Başlığı',
                'primary_color' => 'Ana renk',
                'secondary_color' => 'İkincil renk',
                'text_color' => 'Metin renk',
                'phone1' => 'Ana telefon numarası',
                'phone2' => 'İkinci telefon numarası',
                'whatsapp' => 'WhatsApp numarası',
                'address' => 'Okul adresi',
                'email' => 'İletişim e-posta adresi',
                'short_description' => 'Footer bölümünde gösterilecek kısa okul açıklaması',
                'instagram' => 'Instagram kullanıcı adı',
                'twitter' => 'Twitter kullanıcı adı',
                'facebook' => 'Facebook sayfa adı',
                'youtube' => 'YouTube kanal ID veya kullanıcı adı',
                'linkedin' => 'LinkedIn şirket adı',
                'website' => 'Web sitesi URL',
                'copyright_text' => 'Footer telif hakkı metni',
                'clarity_enabled' => 'Microsoft Clarity (0/1)',
                'clarity_project_id' => 'Microsoft Clarity proje kısa kimliği',
                'mynak_home_youtube_ids' => 'Ana sayfa: YouTube video JSON (B5 vitrin + VideoObject)',
                'mynak_youtube_shorts_ids' => 'Google Kisa Videolar: YouTube Shorts JSON listesi',
            ];
            
            $description = isset($descriptions[$name]) ? $descriptions[$name] : '';
            
            // Güncelleme veya ekleme yapılacak
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $name, $value, $description);
            
            if (!$stmt->execute()) {
                $success = false;
                $message .= "Hata ($name): " . $stmt->error . "<br>";
                error_log("MyNakliyat - Ayar işleme hatası ($name): " . $stmt->error);
            } else {
                error_log("Ayar başarıyla kaydedildi: $name = $value");
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $success = false;
            $message .= "Hata ($name): " . $e->getMessage() . "<br>";
            error_log("MyNakliyat - Ayar işleme hatası ($name): " . $e->getMessage());
        }
    }
    }
    
    // AJAX isteği var mı kontrol et
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if ($is_ajax) {
        // AJAX yanıtı
        header('Content-Type: application/json');
        if ($success) {
            echo json_encode(['status' => 'success', 'message' => 'Ayarlar başarıyla kaydedildi!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $message]);
        }
        exit;
    } else {
        // Normal form submit - yönlendirme
        if ($success) {
            // Yönlendirme ile sayfayı yenile
            header("Location: settings.php?saved=1");
            exit;
        } else {
            $error_message = "Ayarlar kaydedilirken bir hata oluştu: " . $message;
            error_log($error_message);
        }
    }
}

$page_title = 'Site Ayarları';
require_once 'includes/header.php';

// Başarı mesajını URL parametresinden al
if (isset($_GET['saved']) && $_GET['saved'] == '1') {
    $success_message = "Ayarlar başarıyla kaydedildi!";
}

if (isset($_GET['gbp_sync'])) {
    $msgRaw = isset($_GET['msg']) ? rawurldecode((string) $_GET['msg']) : '';
    if ($_GET['gbp_sync'] === '1') {
        $success_message = 'Google işletme saatleri önbelleği yenilendi.' . ($msgRaw !== '' ? ' ' . htmlspecialchars($msgRaw) : '');
    } else {
        $error_message = $msgRaw !== '' ? htmlspecialchars($msgRaw) : 'Google veri çekme başarısız. API anahtarı ve Place ID için Google Yorumlar sayfasını kontrol edin.';
    }
}

// Mevcut ayarları getir - önbellek sorununu önlemek için DISTINCT kullanıyoruz
$site_settings = [];
$settings_result = $conn->query("SELECT DISTINCT name, value FROM settings");

if ($settings_result && $settings_result->num_rows > 0) {
    while ($row = $settings_result->fetch_assoc()) {
        $site_settings[$row['name']] = $row['value'];
    }
}
$site_settings = mynak_apply_contact_defaults_to_settings_array($site_settings);
?>

<div class="row">
    <div class="col-12">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Site Ayarları</h5>
                <p class="text-muted small mt-2 mb-0">Bu sayfa üzerinden sitenin genel ayarlarını yapabilirsiniz.</p>
            </div>
            <div class="card-body">
                <form method="post" action="settings.php" enctype="multipart/form-data" id="settingsForm">
                    
                    <!-- Genel Ayarlar Bölümü -->
                    <h4 class="mt-3 mb-4 border-bottom pb-2">Genel Ayarlar</h4>
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label for="site_title" class="form-label">Site Başlığı</label>
                            <input type="text" class="form-control" id="site_title" name="site_title" value="<?php echo htmlspecialchars($site_settings['site_title'] ?? 'İzmir Evden Eve Nakliyat - MY Nakliyat ® Resmi Sitesi'); ?>">
                            <div class="form-text">Tarayıcı başlık çubuğunda ve SEO için kullanılan site başlığı.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="copyright_text" class="form-label">Telif Hakkı Metni</label>
                            <input type="text" class="form-control" id="copyright_text" name="copyright_text" value="<?php echo htmlspecialchars($site_settings['copyright_text'] ?? '© ' . date('Y') . ' My Nakliyat. Tüm hakları saklıdır.'); ?>">
                            <div class="form-text">Footer bölümünde gösterilecek telif hakkı metni.</div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <label for="primary_color" class="form-label">Ana Renk</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bxs-color-fill"></i></span>
                                <input type="color" class="form-control form-control-color" id="primary_color" name="primary_color" value="<?php echo htmlspecialchars($site_settings['primary_color'] ?? '#0046AD'); ?>" title="Ana renk seçin">
                                <input type="text" class="form-control" id="primary_color_text" value="<?php echo htmlspecialchars($site_settings['primary_color'] ?? '#0046AD'); ?>" readonly>
                            </div>
                            <div class="form-text">Ana renk (--primary-color) site genelinde kullanılan temel renktir.</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="secondary_color" class="form-label">İkincil Renk</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bxs-color-fill"></i></span>
                                <input type="color" class="form-control form-control-color" id="secondary_color" name="secondary_color" value="<?php echo htmlspecialchars($site_settings['secondary_color'] ?? '#79B32A'); ?>" title="İkincil renk seçin">
                                <input type="text" class="form-control" id="secondary_color_text" value="<?php echo htmlspecialchars($site_settings['secondary_color'] ?? '#79B32A'); ?>" readonly>
                            </div>
                            <div class="form-text">İkincil renk (--secondary-color) vurgu ve diğer elementler için kullanılır.</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="text_color" class="form-label">Metin Rengi</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bxs-color-fill"></i></span>
                                <input type="color" class="form-control form-control-color" id="text_color" name="text_color" value="<?php echo htmlspecialchars($site_settings['text_color'] ?? '#333333'); ?>" title="Metin rengi seçin">
                                <input type="text" class="form-control" id="text_color_text" value="<?php echo htmlspecialchars($site_settings['text_color'] ?? '#333333'); ?>" readonly>
                            </div>
                            <div class="form-text">Metin rengi (--text-color) site genelinde kullanılan metin rengidir.</div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label for="logo_light" class="form-label">Açık Logo (Beyaz Arka Plan için)</label>
                            <input type="file" class="form-control" id="logo_light" name="logo_light" accept="image/*">
                            <?php if (!empty($site_settings['logo_light'])): ?>
                                <div class="mt-2">
                                    <img src="../uploads/settings/<?php echo $site_settings['logo_light']; ?>" alt="Açık Logo" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                            <?php endif; ?>
                            <div class="form-text">Önerilen boyut: 300x92px, PNG veya SVG formatı önerilir.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="logo_dark" class="form-label">Koyu Logo (Koyu Arka Plan için)</label>
                            <input type="file" class="form-control" id="logo_dark" name="logo_dark" accept="image/*">
                            <?php if (!empty($site_settings['logo_dark'])): ?>
                                <div class="mt-2">
                                    <img src="../uploads/settings/<?php echo $site_settings['logo_dark']; ?>" alt="Koyu Logo" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                            <?php endif; ?>
                            <div class="form-text">Önerilen boyut: 300x92px, PNG veya SVG formatı önerilir.</div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label for="favicon" class="form-label">Favicon</label>
                            <input type="file" class="form-control" id="favicon" name="favicon" accept="image/x-icon,image/png,image/jpeg,image/gif,image/svg+xml">
                            <?php if (!empty($site_settings['favicon'])): ?>
                                <div class="mt-2">
                                    <img src="../uploads/settings/<?php echo $site_settings['favicon']; ?>" alt="Favicon" class="img-thumbnail" style="max-height: 32px;">
                                </div>
                            <?php endif; ?>
                            <div class="form-text">Önerilen boyut: 32x32px, .ico veya .png formatı önerilir.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="short_description" class="form-label">Kısa Açıklama</label>
                            <textarea class="form-control" id="short_description" name="short_description" rows="3"><?php echo htmlspecialchars($site_settings['short_description'] ?? ''); ?></textarea>
                            <div class="form-text">Footer bölümünde gösterilecek kısa okul açıklaması.</div>
                        </div>
                    </div>
                    
                    <!-- İletişim Ayarları Bölümü -->
                    <h4 class="mt-5 mb-4 border-bottom pb-2">İletişim Ayarları</h4>
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label for="phone1" class="form-label">Telefon 1</label>
                            <input type="text" class="form-control" id="phone1" name="phone1" value="<?php echo htmlspecialchars($site_settings['phone1'] ?? ''); ?>">
                            <div class="form-text">Ana telefon numarası.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="phone2" class="form-label">Telefon 2</label>
                            <input type="text" class="form-control" id="phone2" name="phone2" value="<?php echo htmlspecialchars($site_settings['phone2'] ?? ''); ?>">
                            <div class="form-text">İkinci telefon numarası (opsiyonel).</div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label for="whatsapp" class="form-label">WhatsApp</label>
                            <input type="text" class="form-control" id="whatsapp" name="whatsapp" value="<?php echo htmlspecialchars($site_settings['whatsapp'] ?? ''); ?>">
                            <div class="form-text">WhatsApp numarası (başında ülke kodu ile, örn: 905xxxxxxxxx).</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">E-posta</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($site_settings['email'] ?? ''); ?>">
                            <div class="form-text">İletişim e-posta adresi.</div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-12 mb-3">
                            <label for="address" class="form-label">Adres</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($site_settings['address'] ?? ''); ?></textarea>
                            <div class="form-text">Tam adres bilgisi.</div>
                        </div>
                    </div>
                    
                    <!-- Sosyal Medya Ayarları Bölümü -->
                    <h4 class="mt-5 mb-4 border-bottom pb-2">Sosyal Medya Ayarları</h4>
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label for="instagram" class="form-label">Instagram</label>
                            <div class="input-group">
                                <span class="input-group-text">instagram.com/</span>
                                <input type="text" class="form-control" id="instagram" name="instagram" value="<?php echo htmlspecialchars($site_settings['instagram'] ?? ''); ?>">
                            </div>
                            <div class="form-text">Sadece kullanıcı adını yazın (örn: mynakliyat).</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="twitter" class="form-label">Twitter (X)</label>
                            <div class="input-group">
                                <span class="input-group-text">twitter.com/</span>
                                <input type="text" class="form-control" id="twitter" name="twitter" value="<?php echo htmlspecialchars($site_settings['twitter'] ?? ''); ?>">
                            </div>
                            <div class="form-text">Sadece kullanıcı adını yazın (örn: mynakliyat).</div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label for="facebook" class="form-label">Facebook</label>
                            <div class="input-group">
                                <span class="input-group-text">facebook.com/</span>
                                <input type="text" class="form-control" id="facebook" name="facebook" value="<?php echo htmlspecialchars($site_settings['facebook'] ?? ''); ?>">
                            </div>
                            <div class="form-text">Sadece sayfa adını yazın (örn: mynakliyat).</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="youtube" class="form-label">YouTube</label>
                            <div class="input-group">
                                <span class="input-group-text">youtube.com/</span>
                                <input type="text" class="form-control" id="youtube" name="youtube" value="<?php echo htmlspecialchars($site_settings['youtube'] ?? ''); ?>">
                            </div>
                            <div class="form-text">Kanal ID veya kullanıcı adını yazın.</div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label for="linkedin" class="form-label">LinkedIn</label>
                            <div class="input-group">
                                <span class="input-group-text">linkedin.com/company/</span>
                                <input type="text" class="form-control" id="linkedin" name="linkedin" value="<?php echo htmlspecialchars($site_settings['linkedin'] ?? ''); ?>">
                            </div>
                            <div class="form-text">Şirket adını yazın.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="website" class="form-label">Web Sitesi</label>
                            <input type="url" class="form-control" id="website" name="website" value="<?php echo htmlspecialchars($site_settings['website'] ?? ''); ?>">
                            <div class="form-text">Tam web sitesi URL'si (örn: https://mynakliyat.com)</div>
                        </div>
                    </div>

                    <h4 class="mt-5 mb-4 border-bottom pb-2">Microsoft Clarity (opsiyonel)</h4>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="clarity_enabled" name="clarity_enabled" value="1"<?php echo (!empty($site_settings['clarity_enabled']) && (string) $site_settings['clarity_enabled'] === '1') ? ' checked' : ''; ?>>
                                <label class="form-check-label" for="clarity_enabled">Clarity izlemeyi aç</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="clarity_project_id" class="form-label">Clarity proje kimliği</label>
                            <input type="text" class="form-control font-monospace" id="clarity_project_id" name="clarity_project_id" maxlength="32" value="<?php echo htmlspecialchars($site_settings['clarity_project_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="form-text">clarity.microsoft.com → Proje → Kurulum</div>
                        </div>
                    </div>

                    <h4 class="mt-5 mb-4 border-bottom pb-2">Google Kısa Videolar (YouTube Shorts)</h4>
                    <div class="mb-4">
                        <label for="mynak_youtube_shorts_ids" class="form-label">mynak_youtube_shorts_ids (JSON dizi)</label>
                        <textarea class="form-control font-monospace" id="mynak_youtube_shorts_ids" name="mynak_youtube_shorts_ids" rows="5" placeholder='[{"id":"PryjKN2ypC8","title":"Beydag nakliyat"},{"id":"JlGNofd-l_o","title":"Karsiyaka nakliyat"}]'><?php echo htmlspecialchars($site_settings['mynak_youtube_shorts_ids'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <div class="form-text">YouTube Shorts URL veya 11 karakterlik video ID. Her video icin <code>/video/VIDEO_ID</code> izleme sayfasi ve <code>/shorts</code> hub olusur.</div>
                    </div>

                    <h4 class="mt-5 mb-4 border-bottom pb-2">Ana sayfa YouTube vitrin (en fazla 3)</h4>
                    <div class="mb-4">
                        <label for="mynak_home_youtube_ids" class="form-label">mynak_home_youtube_ids (JSON dizi)</label>
                        <textarea class="form-control font-monospace" id="mynak_home_youtube_ids" name="mynak_home_youtube_ids" rows="6" placeholder='[{"id":"-50tZcxA9jE","uploadDate":"2021-11-08"}]'><?php echo htmlspecialchars($site_settings['mynak_home_youtube_ids'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <div class="form-text">VideoObject uploadDate: JSON nesnelerinde uploadDate verilebilir; yoksa YouTube sayfasindan otomatik alinir.</div>
                        <div class="form-text">11 karakterlik ID veya tam URL (watch, youtu.be, embed, shorts). Boş bırakırsanız ana sayfada iskelet kartlar kalır.</div>
                    </div>
                    
                    <div class="mt-4">
                        <input type="hidden" name="save_settings" value="1">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i> Ayarları Kaydet
                        </button>
                    </div>
                </form>

                    <h4 class="mt-5 mb-4 border-bottom pb-2">Google İşletme Profili (Places API)</h4>
                    <p class="text-muted small">
                        Çalışma saatleri ve ortalama puan, <strong>Google Places Details</strong> ile senkronize edilir (admin → <a href="google_reviews.php">Google Yorumlar</a> üzerindeki API Key ve Place ID).
                        Önbellek dosyası <code>cache/gbp_data.json</code> — klasörde HTTP erişimi kapalıdır; API anahtarı dosyada saklanmaz.
                    </p>
                    <form method="post" action="settings.php" class="mb-2" onsubmit="return confirm('Önbellek silinsin ve API\\'den taze veri çekilsin mi?');">
                        <input type="hidden" name="mynak_sync_gbp_hours" value="1">
                        <button type="submit" class="btn btn-outline-secondary">
                            <i class="bx bx-refresh me-1"></i> Google İşletme Saatlerini Şimdi Güncelle
                        </button>
                    </form>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Form submit önleme ve AJAX ile gönderme
        $('#settingsForm').submit(function(e) {
            e.preventDefault();
            
            // Form verilerini al
            var formData = new FormData(this);
            formData.append('_t', new Date().getTime());
            
            // Kaydetme düğmesini devre dışı bırak
            $('button[type="submit"]').prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Kaydediliyor...');
            
            $.ajax({
                type: "POST",
                url: "settings.php",
                data: formData,
                contentType: false,
                processData: false,
                cache: false,
                dataType: "json",
                headers: { "X-Requested-With": "XMLHttpRequest" },
                success: function(response) {
                    $('button[type="submit"]').prop('disabled', false).html('<i class="bx bx-save me-1"></i> Ayarları Kaydet');
                    if (!response || response.status !== 'success') {
                        var errMsg = (response && response.message) ? response.message : 'Kayıt başarısız.';
                        $('<div class="alert alert-danger">' + errMsg + '</div>')
                            .prependTo('.col-12');
                        return;
                    }
                    $('<div class="alert alert-success">' + (response.message || 'Ayarlar başarıyla kaydedildi!') + '</div>')
                        .prependTo('.col-12');
                    setTimeout(function() {
                        window.location.href = 'settings.php?saved=1&t=' + new Date().getTime();
                    }, 1000);
                },
                error: function(xhr, status, error) {
                    $('button[type="submit"]').prop('disabled', false).html('<i class="bx bx-save me-1"></i> Ayarları Kaydet');
                    var errMsg = error;
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    } else if (xhr.responseText && xhr.responseText.indexOf('<') === 0) {
                        errMsg = 'Sunucu HTML döndü (oturum süresi dolmuş veya PHP hatası). Sayfayı yenileyip tekrar giriş yapın.';
                    }
                    $('<div class="alert alert-danger">Bir hata oluştu: ' + errMsg + '</div>')
                        .prependTo('.col-12')
                        .delay(5000)
                        .fadeOut(500, function() {
                            $(this).remove();
                        });
                }
            });
        });
    });
</script>

<script>
    // Color picker scripts
    document.addEventListener('DOMContentLoaded', function() {
        // Function to update text input with color value
        function updateColorText(colorInput, textInput) {
            textInput.value = colorInput.value;
        }
        
        // Set up color pickers
        const primaryColorPicker = document.getElementById('primary_color');
        const primaryColorText = document.getElementById('primary_color_text');
        
        const secondaryColorPicker = document.getElementById('secondary_color');
        const secondaryColorText = document.getElementById('secondary_color_text');
        
        const textColorPicker = document.getElementById('text_color');
        const textColorText = document.getElementById('text_color_text');
        
        // Initial setup
        if(primaryColorPicker && primaryColorText) {
            updateColorText(primaryColorPicker, primaryColorText);
            primaryColorPicker.addEventListener('input', function() {
                updateColorText(primaryColorPicker, primaryColorText);
            });
        }
        
        if(secondaryColorPicker && secondaryColorText) {
            updateColorText(secondaryColorPicker, secondaryColorText);
            secondaryColorPicker.addEventListener('input', function() {
                updateColorText(secondaryColorPicker, secondaryColorText);
            });
        }
        
        if(textColorPicker && textColorText) {
            updateColorText(textColorPicker, textColorText);
            textColorPicker.addEventListener('input', function() {
                updateColorText(textColorPicker, textColorText);
            });
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>