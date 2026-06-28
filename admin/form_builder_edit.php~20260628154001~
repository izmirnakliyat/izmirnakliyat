<?php
$page_title = 'Form Düzenleyici';
require_once __DIR__ . '/includes/require_admin_web.php';

$form_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$form_data = null;

// Mevcut form verilerini getir
if ($form_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM forms WHERE id = ?");
    $stmt->bind_param("i", $form_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $form_data = $result->fetch_assoc();
}

// Form kaydetme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $name = $_POST['name'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $fields = $_POST['fields'] ?? '';
    $settings = $_POST['settings'] ?? '';
    $email_notifications = $_POST['email_notifications'] ?? '';
    $success_message = $_POST['success_message'] ?? '';
    $redirect_url = $_POST['redirect_url'] ?? '';
    $status = isset($_POST['status']) ? 1 : 0;

    if (empty($name) || empty($title)) {
        $error = "Form adı ve başlığı zorunludur.";
    } else {
        if ($form_id > 0) {
            // Güncelleme
            $stmt = $conn->prepare("UPDATE forms SET name=?, title=?, description=?, fields=?, settings=?, email_notifications=?, success_message=?, redirect_url=?, status=? WHERE id=?");
            $stmt->bind_param("ssssssssii", $name, $title, $description, $fields, $settings, $email_notifications, $success_message, $redirect_url, $status, $form_id);
        } else {
            // Yeni ekleme
            $stmt = $conn->prepare("INSERT INTO forms (name, title, description, fields, settings, email_notifications, success_message, redirect_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssi", $name, $title, $description, $fields, $settings, $email_notifications, $success_message, $redirect_url, $status);
        }

        if ($stmt->execute()) {
            if ($form_id == 0) {
                $form_id = $conn->insert_id;
            }
            $success = "Form başarıyla " . ($form_id > 0 ? "güncellendi." : "oluşturuldu.");
            
            // Verileri yeniden yükle
            $stmt = $conn->prepare("SELECT * FROM forms WHERE id = ?");
            $stmt->bind_param("i", $form_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $form_data = $result->fetch_assoc();
            
            // Debug: Kaydedilen verileri kontrol et
            error_log("Form kaydedildi - ID: " . $form_id . ", Fields: " . $fields . ", Settings: " . $settings);
        } else {
            $error = "Form kaydedilirken bir hata oluştu: " . $stmt->error;
            error_log("Form kaydetme hatası: " . $stmt->error);
        }
    }
}

$page_title = $form_id > 0 ? 'Form Düzenle' : 'Yeni Form Oluştur';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin Panel</title>
    
    <!-- Modern Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Core Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    
    <!-- Admin Theme -->
    <link href="assets/css/modern-admin.css" rel="stylesheet">
    
    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body data-theme="light">
    
    <!-- Sidebar -->
    <?php 
    require_once 'includes/permissions.php';
    include 'includes/sidebar.php'; 
    ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle d-md-none me-3" id="sidebarToggle">
                    <i class="bx bx-menu"></i>
                </button>
                <h1 class="page-title"><?php echo $page_title; ?></h1>
            </div>
            <div class="topbar-right">
                <a href="../" target="_blank" class="btn btn-primary">
                    <i class="bx bx-world"></i> Siteye Git
                </a>
                <a href="logout.php" class="btn btn-outline-danger">
                    <i class="bx bx-log-out"></i> Çıkış
                </a>
            </div>
        </div>

<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Form Yönetimi /</span> 
        <?php echo $form_id > 0 ? 'Form Düzenle' : 'Yeni Form Oluştur'; ?>
    </h4>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" id="formBuilderForm">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="fields" id="fields_data" value="<?php echo htmlspecialchars($form_data['fields'] ?? '[]'); ?>">
        <input type="hidden" name="settings" id="settings_data" value="<?php echo htmlspecialchars($form_data['settings'] ?? '{}'); ?>">

        <div class="row">
            <!-- Sol Panel - Form Bilgileri -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Form Bilgileri</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Form Adı <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>" required>
                            <small class="text-muted">Sadece harf, rakam ve tire kullanın</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Form Başlığı <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($form_data['title'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Açıklama</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="success_message" class="form-label">Başarı Mesajı</label>
                            <textarea class="form-control" id="success_message" name="success_message" rows="2"><?php echo htmlspecialchars($form_data['success_message'] ?? 'Formunuz başarıyla gönderildi.'); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="redirect_url" class="form-label">Yönlendirme URL'si</label>
                            <input type="url" class="form-control" id="redirect_url" name="redirect_url" 
                                   value="<?php echo htmlspecialchars($form_data['redirect_url'] ?? ''); ?>">
                            <small class="text-muted">Başarılı gönderimden sonra yönlendirilecek sayfa</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email_notifications" class="form-label">Email Bildirimleri</label>
                            <input type="email" class="form-control" id="email_notifications" name="email_notifications" 
                                   value="<?php echo htmlspecialchars($form_data['email_notifications'] ?? ''); ?>">
                            <small class="text-muted">Form gönderildiğinde bilgilendirilecek email</small>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="status" name="status" 
                                   <?php echo (!$form_data || $form_data['status']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="status">Aktif</label>
                        </div>
                    </div>
                </div>

                <!-- Form Görünüm Ayarları -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bx bx-layout me-2"></i>Form Görünüm Tipi</h5>
                    </div>
                    <div class="card-body">
                        <!-- Form Tipi Seçimi -->
                        <div class="mb-3">
                            <div class="form-check mb-2">
                                <input type="radio" class="form-check-input" id="form_type_embed" name="form_type" value="embed" checked>
                                <label class="form-check-label fw-bold" for="form_type_embed">
                                    <i class="bx bx-code-block text-primary"></i> Gömülü Form (Sayfa İçi)
                                </label>
                                <div class="text-muted small ms-4">Sayfa içerisinde direkt görünür. Kısa kod ile eklenebilir.</div>
                            </div>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" id="form_type_popup" name="form_type" value="popup">
                                <label class="form-check-label fw-bold" for="form_type_popup">
                                    <i class="bx bx-window-alt text-warning"></i> Popup Form
                                </label>
                                <div class="text-muted small ms-4">Modal pencerede açılır. Butona tıklama veya zamanlama ile tetiklenir.</div>
                            </div>
                        </div>
                        
                        <!-- Gömülü Form Ayarları -->
                        <div id="embed_settings" class="border-top pt-3 mt-3">
                            <h6 class="text-muted mb-3"><i class="bx bx-cog"></i> Gömülü Form Ayarları</h6>
                            
                            <!-- Adım Adım Form Seçeneği -->
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="embed_step_by_step" name="embed_step_by_step">
                                <label class="form-check-label fw-bold" for="embed_step_by_step">
                                    <i class="bx bx-list-ol text-success"></i> Adım adım form (her soru ayrı ekran)
                                </label>
                                <div class="text-muted small ms-4">Her form alanı ayrı bir adımda gösterilir. İleri/Geri butonları ile geçiş yapılır.</div>
                            </div>
                            
                            <hr class="my-3">
                            
                            <div class="mb-3">
                                <label for="embed_style" class="form-label">Form Stili</label>
                                <select class="form-control" id="embed_style" name="embed_style">
                                    <option value="default">Varsayılan</option>
                                    <option value="card">Kartlı (Gölgeli)</option>
                                    <option value="bordered">Kenarlıklı</option>
                                    <option value="minimal">Minimal</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="embed_width" class="form-label">Form Genişliği</label>
                                <select class="form-control" id="embed_width" name="embed_width">
                                    <option value="full">Tam Genişlik (100%)</option>
                                    <option value="large">Geniş (800px)</option>
                                    <option value="medium" selected>Orta (600px)</option>
                                    <option value="small">Dar (400px)</option>
                                </select>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="embed_show_title" name="embed_show_title" checked>
                                <label class="form-check-label" for="embed_show_title">Form başlığını göster</label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="embed_show_description" name="embed_show_description" checked>
                                <label class="form-check-label" for="embed_show_description">Form açıklamasını göster</label>
                            </div>
                            
                            <div class="mb-3">
                                <label for="embed_button_text" class="form-label">Gönder Butonu Metni</label>
                                <input type="text" class="form-control" id="embed_button_text" name="embed_button_text" value="Gönder">
                            </div>
                            
                            <div class="mb-3">
                                <label for="embed_button_style" class="form-label">Buton Stili</label>
                                <select class="form-control" id="embed_button_style" name="embed_button_style">
                                    <option value="primary">Mavi (Primary)</option>
                                    <option value="success">Yeşil (Success)</option>
                                    <option value="danger">Kırmızı (Danger)</option>
                                    <option value="warning">Sarı (Warning)</option>
                                    <option value="dark">Koyu (Dark)</option>
                                </select>
                            </div>
                            
                            <!-- Kısa Kod Önizleme -->
                            <?php if ($form_id > 0): ?>
                            <div class="alert alert-info mt-3">
                                <h6 class="alert-heading"><i class="bx bx-code"></i> Kısa Kod</h6>
                                <p class="mb-2">Bu formu sayfalara eklemek için aşağıdaki kısa kodu kullanın:</p>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-white" value="[form id=<?php echo $form_id; ?>]" id="shortcode_preview" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyShortcode()">
                                        <i class="bx bx-copy"></i> Kopyala
                                    </button>
                                </div>
                                <small class="text-muted mt-2 d-block">
                                    Bu kodu sayfa içeriğine veya blog yazılarına ekleyebilirsiniz.
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Popup Ayarları -->
                        <div id="popup_settings" class="border-top pt-3 mt-3" style="display: none;">
                            <h6 class="text-muted mb-3"><i class="bx bx-cog"></i> Popup Ayarları</h6>
                            
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="step_by_step" name="step_by_step">
                                <label class="form-check-label" for="step_by_step">Adım adım form (her soru ayrı ekran)</label>
                            </div>
                            
                            <div class="mb-3">
                                <label for="popup_trigger" class="form-label">Popup Tetikleme</label>
                                <select class="form-control" id="popup_trigger" name="popup_trigger">
                                    <option value="button">Butona Tıklama</option>
                                    <option value="timer">Zamanlı (sayfa açıldıktan sonra)</option>
                                    <option value="scroll">Scroll (sayfa %50 scroll edildiğinde)</option>
                                    <option value="exit">Çıkış İsteği (mouse sayfa dışına çıktığında)</option>
                                </select>
                            </div>
                            
                            <div class="mb-3" id="timer_settings" style="display: none;">
                                <label for="popup_delay" class="form-label">Gecikme (saniye)</label>
                                <input type="number" class="form-control" id="popup_delay" name="popup_delay" value="5" min="1">
                            </div>
                            
                            <div class="mb-3">
                                <label for="popup_button_text" class="form-label">Popup Buton Metni</label>
                                <input type="text" class="form-control" id="popup_button_text" name="popup_button_text" value="Form Doldur">
                                <small class="text-muted">Buton tetikleme seçildiğinde kullanılacak</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="popup_width" class="form-label">Popup Genişliği</label>
                                <select class="form-control" id="popup_width" name="popup_width">
                                    <option value="small">Küçük (400px)</option>
                                    <option value="medium" selected>Orta (600px)</option>
                                    <option value="large">Büyük (800px)</option>
                                    <option value="full">Tam Ekran</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Elemanları -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Form Elemanları</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-elements">
                            <div class="element-item" data-type="text">
                                <i class="bx bx-text"></i> Metin Alanı
                            </div>
                            <div class="element-item" data-type="email">
                                <i class="bx bx-envelope"></i> Email
                            </div>
                            <div class="element-item" data-type="tel">
                                <i class="bx bx-phone"></i> Telefon
                            </div>
                            <div class="element-item" data-type="textarea">
                                <i class="bx bx-message-square-dots"></i> Çok Satırlı Metin
                            </div>
                            <div class="element-item" data-type="select">
                                <i class="bx bx-list-ul"></i> Seçim Listesi
                            </div>
                            <div class="element-item" data-type="radio">
                                <i class="bx bx-radio-circle"></i> Radyo Buton
                            </div>
                            <div class="element-item" data-type="checkbox">
                                <i class="bx bx-check-square"></i> Onay Kutusu
                            </div>
                            <div class="element-item" data-type="file">
                                <i class="bx bx-cloud-upload"></i> Dosya Yükleme
                            </div>
                            <div class="element-item" data-type="date">
                                <i class="bx bx-calendar"></i> Tarih
                            </div>
                            <div class="element-item" data-type="number">
                                <i class="bx bx-hash"></i> Sayı
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sağ Panel - Form Önizleme -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Form Önizleme</h5>
                        <div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearForm()">
                                <i class="bx bx-trash"></i> Temizle
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bx bx-save"></i> Kaydet
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="form-preview" class="form-preview">
                            <div class="drop-zone">
                                <p class="text-muted text-center py-5">
                                    <i class="bx bx-plus" style="font-size: 2rem;"></i><br>
                                    Sol panelden form elemanlarını buraya sürükleyin
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Element Ayarları Modal -->
<div class="modal fade" id="elementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Element Ayarları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="elementModalBody">
                <!-- Dynamic content -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="saveElementSettings()">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<style>
.form-elements .element-item {
    display: flex;
    align-items: center;
    padding: 12px;
    margin-bottom: 8px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    cursor: grab;
    transition: all 0.2s;
}

.form-elements .element-item:hover {
    background: #e9ecef;
    transform: translateY(-1px);
}

.form-elements .element-item i {
    margin-right: 8px;
    font-size: 1.1rem;
    color: #6c757d;
}

.form-preview {
    min-height: 400px;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    position: relative;
}

.drop-zone {
    min-height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.form-field-container {
    position: relative;
    margin-bottom: 20px;
    padding: 15px;
    border: 1px solid #e3e6f0;
    border-radius: 6px;
    background: #fff;
}

.form-field-container:hover {
    border-color: #4e73df;
}

.field-controls {
    position: absolute;
    top: -10px;
    right: -10px;
    display: none;
}

.form-field-container:hover .field-controls {
    display: block;
}

.field-controls button {
    margin-left: 5px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: none;
    background: #4e73df;
    color: white;
    cursor: pointer;
}

.field-controls button:hover {
    background: #2e59d9;
}

.sortable-placeholder {
    border: 2px dashed #4e73df;
    margin: 10px 0;
    height: 60px;
    background: rgba(78, 115, 223, 0.1);
    border-radius: 6px;
}
</style>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.css">
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
let formFields = [];
let currentEditingField = null;

// Form verilerini yükle
<?php if ($form_data && !empty($form_data['fields'])): ?>
try {
    formFields = <?php echo json_encode(json_decode($form_data['fields'], true) ?: []); ?>;
    renderFormPreview();
} catch (e) {
    console.error('Fields parse error:', e);
    formFields = [];
}
<?php endif; ?>

// Popup ayarlarını yükle
<?php 
if ($form_data && !empty($form_data['settings']) && $form_data['settings'] !== '{}') {
    $settings = json_decode($form_data['settings'], true);
    if ($settings && count($settings) > 0) {
?>
try {
    const formSettings = <?php echo json_encode($settings); ?>;
    if (formSettings && Object.keys(formSettings).length > 0) {
        loadPopupSettings(formSettings);
    }
} catch (e) {
    console.error('Settings parse error:', e);
}
<?php 
    }
} 
?>

function loadFormSettings(settings) {
    // Form tipi ayarla (popup veya embed)
    const formType = settings.form_type || 'embed';
    const formTypeEmbed = document.getElementById('form_type_embed');
    const formTypePopup = document.getElementById('form_type_popup');
    
    if (formType === 'popup') {
        if (formTypePopup) formTypePopup.checked = true;
    } else {
        if (formTypeEmbed) formTypeEmbed.checked = true;
    }
    
    // Görünüm ayarlarını güncelle
    toggleFormTypeSettings();
    
    // Gömülü form ayarları
    if (settings.embed_step_by_step !== undefined) {
        const embedStepByStep = document.getElementById('embed_step_by_step');
        if (embedStepByStep) embedStepByStep.checked = settings.embed_step_by_step;
    }
    if (settings.embed_style) {
        const embedStyle = document.getElementById('embed_style');
        if (embedStyle) embedStyle.value = settings.embed_style;
    }
    if (settings.embed_width) {
        const embedWidth = document.getElementById('embed_width');
        if (embedWidth) embedWidth.value = settings.embed_width;
    }
    if (settings.embed_show_title !== undefined) {
        const embedShowTitle = document.getElementById('embed_show_title');
        if (embedShowTitle) embedShowTitle.checked = settings.embed_show_title;
    }
    if (settings.embed_show_description !== undefined) {
        const embedShowDesc = document.getElementById('embed_show_description');
        if (embedShowDesc) embedShowDesc.checked = settings.embed_show_description;
    }
    if (settings.embed_button_text) {
        const embedButtonText = document.getElementById('embed_button_text');
        if (embedButtonText) embedButtonText.value = settings.embed_button_text;
    }
    if (settings.embed_button_style) {
        const embedButtonStyle = document.getElementById('embed_button_style');
        if (embedButtonStyle) embedButtonStyle.value = settings.embed_button_style;
    }
    
    // Popup ayarları (eski settings.popup_enabled için geriye uyumluluk)
    if (settings.popup_enabled || formType === 'popup') {
        if (settings.step_by_step) {
            const stepByStep = document.getElementById('step_by_step');
            if (stepByStep) stepByStep.checked = true;
        }
        
        if (settings.popup_trigger) {
            const popupTrigger = document.getElementById('popup_trigger');
            if (popupTrigger) {
                popupTrigger.value = settings.popup_trigger;
                toggleTimerSettings();
            }
        }
        
        if (settings.popup_delay) {
            const popupDelay = document.getElementById('popup_delay');
            if (popupDelay) popupDelay.value = settings.popup_delay;
        }
        
        if (settings.popup_button_text) {
            const popupButtonText = document.getElementById('popup_button_text');
            if (popupButtonText) popupButtonText.value = settings.popup_button_text;
        }
        
        if (settings.popup_width) {
            const popupWidth = document.getElementById('popup_width');
            if (popupWidth) popupWidth.value = settings.popup_width;
        }
    }
}

// Geriye uyumluluk için eski fonksiyon
function loadPopupSettings(settings) {
    loadFormSettings(settings);
}

// Form tipi değiştiğinde ayarları göster/gizle
function toggleFormTypeSettings() {
    const formTypeEmbed = document.getElementById('form_type_embed');
    const embedSettings = document.getElementById('embed_settings');
    const popupSettings = document.getElementById('popup_settings');
    
    if (formTypeEmbed && formTypeEmbed.checked) {
        if (embedSettings) embedSettings.style.display = 'block';
        if (popupSettings) popupSettings.style.display = 'none';
    } else {
        if (embedSettings) embedSettings.style.display = 'none';
        if (popupSettings) popupSettings.style.display = 'block';
    }
}

// Kısa kodu kopyala
function copyShortcode() {
    const shortcodeInput = document.getElementById('shortcode_preview');
    if (shortcodeInput) {
        shortcodeInput.select();
        document.execCommand('copy');
        
        // Toast bildirimi
        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed top-0 end-0 m-3';
        toast.style.zIndex = '9999';
        toast.innerHTML = '<div class="d-flex"><div class="toast-body">Kısa kod kopyalandı!</div></div>';
        document.body.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        setTimeout(() => { document.body.removeChild(toast); }, 3000);
    }
}

// Form ayarları event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Form tipi radio buttons
    const formTypeEmbed = document.getElementById('form_type_embed');
    const formTypePopup = document.getElementById('form_type_popup');
    const popupTrigger = document.getElementById('popup_trigger');
    
    if (formTypeEmbed) {
        formTypeEmbed.addEventListener('change', toggleFormTypeSettings);
    }
    if (formTypePopup) {
        formTypePopup.addEventListener('change', toggleFormTypeSettings);
    }
    
    // Popup trigger dropdown
    if (popupTrigger) {
        popupTrigger.addEventListener('change', toggleTimerSettings);
    }
    
    // Sayfa yüklendiğinde ayarları uygula
    toggleFormTypeSettings();
    
    const elementsContainer = document.querySelector('.form-elements');
    const previewContainer = document.querySelector('#form-preview');

    // Sortable for form elements (drag source)
    new Sortable(elementsContainer, {
        group: {
            name: 'formElements',
            pull: 'clone',
            put: false
        },
        animation: 150,
        sort: false
    });

    // Sortable for form preview (drop target)
    new Sortable(previewContainer, {
        group: 'formElements',
        animation: 150,
        onAdd: function(evt) {
            const elementType = evt.item.dataset.type;
            addFormField(elementType);
            evt.item.remove(); // Remove the cloned element
        },
        onUpdate: function(evt) {
            updateFieldOrder();
        }
    });
});

function addFormField(type) {
    const field = {
        id: 'field_' + Date.now(),
        type: type,
        label: getDefaultLabel(type),
        placeholder: '',
        required: false,
        options: type === 'select' || type === 'radio' ? ['Seçenek 1', 'Seçenek 2'] : [],
        validation: {},
        attributes: {}
    };

    formFields.push(field);
    renderFormPreview();
    
    // Immediately open settings for the new field
    editField(formFields.length - 1);
}

function getDefaultLabel(type) {
    const labels = {
        'text': 'Metin Alanı',
        'email': 'E-posta Adresi',
        'tel': 'Telefon Numarası',
        'textarea': 'Mesaj',
        'select': 'Seçim Listesi',
        'radio': 'Seçenekler',
        'checkbox': 'Onay Kutusu',
        'file': 'Dosya Yükleme',
        'date': 'Tarih',
        'number': 'Sayı'
    };
    return labels[type] || 'Form Alanı';
}

function renderFormPreview() {
    const preview = document.getElementById('form-preview');
    
    if (formFields.length === 0) {
        preview.innerHTML = `
            <div class="drop-zone">
                <p class="text-muted text-center py-5">
                    <i class="bx bx-plus" style="font-size: 2rem;"></i><br>
                    Sol panelden form elemanlarını buraya sürükleyin
                </p>
            </div>
        `;
        return;
    }

    let html = '';
    formFields.forEach((field, index) => {
        html += `
            <div class="form-field-container" data-field-id="${field.id}">
                <div class="field-controls">
                    <button type="button" onclick="editField(${index})" title="Düzenle">
                        <i class="bx bx-edit"></i>
                    </button>
                    <button type="button" onclick="removeField(${index})" title="Sil">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
                ${renderField(field)}
            </div>
        `;
    });

    preview.innerHTML = html;
    updateFormData();
}

function renderField(field) {
    let html = `<label class="form-label">${field.label}`;
    if (field.required) html += ' <span class="text-danger">*</span>';
    html += '</label>';

                switch (field.type) {
        case 'text':
        case 'email':
        case 'tel':
        case 'date':
        case 'number':
            html += `<input type="${field.type}" class="form-control" placeholder="${field.placeholder}" disabled>`;
            break;
        case 'textarea':
            html += `<textarea class="form-control" placeholder="${field.placeholder}" rows="3" disabled></textarea>`;
            break;
        case 'select':
            html += `<select class="form-control" disabled>`;
            html += '<option value="">Seçiniz...</option>';
            field.options.forEach(option => {
                html += `<option value="${option}">${option}</option>`;
            });
            html += '</select>';
            break;
        case 'radio':
            field.options.forEach((option, i) => {
                html += `
                    <div class="form-check">
                        <input type="radio" class="form-check-input" name="${field.id}" value="${option}" disabled>
                        <label class="form-check-label">${option}</label>
                    </div>
                `;
            });
            break;
        case 'checkbox':
            html += `
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" disabled>
                    <label class="form-check-label">${field.placeholder || field.label}</label>
                </div>
            `;
            break;
        case 'file':
            html += `<input type="file" class="form-control" disabled>`;
            break;
    }

    return html;
}

function editField(index) {
    currentEditingField = index;
    const field = formFields[index];
    
    let modalContent = `
        <div class="mb-3">
            <label class="form-label">Etiket</label>
            <input type="text" class="form-control" id="field_label" value="${field.label}">
        </div>
    `;

    if (['text', 'email', 'tel', 'textarea', 'checkbox'].includes(field.type)) {
        modalContent += `
            <div class="mb-3">
                <label class="form-label">Placeholder</label>
                <input type="text" class="form-control" id="field_placeholder" value="${field.placeholder}">
            </div>
        `;
    }

    if (['select', 'radio'].includes(field.type)) {
        modalContent += `
            <div class="mb-3">
                <label class="form-label">Seçenekler (her satıra bir seçenek)</label>
                <textarea class="form-control" id="field_options" rows="4">${field.options.join('\n')}</textarea>
            </div>
        `;
    }

    modalContent += `
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="field_required" ${field.required ? 'checked' : ''}>
            <label class="form-check-label">Zorunlu alan</label>
        </div>
    `;

    document.getElementById('elementModalBody').innerHTML = modalContent;
    const modal = new bootstrap.Modal(document.getElementById('elementModal'));
    modal.show();
}

function saveElementSettings() {
    if (currentEditingField === null) return;

    const field = formFields[currentEditingField];
    field.label = document.getElementById('field_label').value;
    field.required = document.getElementById('field_required').checked;

    if (document.getElementById('field_placeholder')) {
        field.placeholder = document.getElementById('field_placeholder').value;
    }

    if (document.getElementById('field_options')) {
        field.options = document.getElementById('field_options').value
            .split('\n')
            .filter(option => option.trim())
            .map(option => option.trim());
    }

    renderFormPreview();
    bootstrap.Modal.getInstance(document.getElementById('elementModal')).hide();
    currentEditingField = null;
}

function removeField(index) {
    if (confirm('Bu alanı silmek istediğinizden emin misiniz?')) {
        formFields.splice(index, 1);
        renderFormPreview();
    }
}

function clearForm() {
    if (confirm('Tüm form alanlarını silmek istediğinizden emin misiniz?')) {
        formFields = [];
        renderFormPreview();
    }
}

function updateFieldOrder() {
    const containers = document.querySelectorAll('.form-field-container');
    const newOrder = [];
    
    containers.forEach(container => {
        const fieldId = container.dataset.fieldId;
        const field = formFields.find(f => f.id === fieldId);
        if (field) newOrder.push(field);
    });
    
    formFields = newOrder;
    updateFormData();
}

function updateFormData() {
    document.getElementById('fields_data').value = JSON.stringify(formFields);
    updatePopupSettings();
}

function updateFormSettings() {
    try {
        // Form tipi belirleme
        const formTypePopup = document.getElementById('form_type_popup');
        const isPopup = formTypePopup ? formTypePopup.checked : false;
        
        const settings = {
            // Form tipi (embed veya popup)
            form_type: isPopup ? 'popup' : 'embed',
            popup_enabled: isPopup, // Geriye uyumluluk için
            
            // Gömülü form ayarları
            embed_step_by_step: document.getElementById('embed_step_by_step') ? document.getElementById('embed_step_by_step').checked : false,
            embed_style: document.getElementById('embed_style') ? document.getElementById('embed_style').value : 'default',
            embed_width: document.getElementById('embed_width') ? document.getElementById('embed_width').value : 'medium',
            embed_show_title: document.getElementById('embed_show_title') ? document.getElementById('embed_show_title').checked : true,
            embed_show_description: document.getElementById('embed_show_description') ? document.getElementById('embed_show_description').checked : true,
            embed_button_text: document.getElementById('embed_button_text') ? document.getElementById('embed_button_text').value : 'Gönder',
            embed_button_style: document.getElementById('embed_button_style') ? document.getElementById('embed_button_style').value : 'primary',
            
            // Popup ayarları
            step_by_step: document.getElementById('step_by_step') ? document.getElementById('step_by_step').checked : false,
            popup_trigger: document.getElementById('popup_trigger') ? document.getElementById('popup_trigger').value : 'button',
            popup_delay: document.getElementById('popup_delay') ? (parseInt(document.getElementById('popup_delay').value) || 5) : 5,
            popup_button_text: document.getElementById('popup_button_text') ? document.getElementById('popup_button_text').value : 'Form Doldur',
            popup_width: document.getElementById('popup_width') ? document.getElementById('popup_width').value : 'medium'
        };
        
        const settingsField = document.getElementById('settings_data');
        if (settingsField) {
            settingsField.value = JSON.stringify(settings);
        }
    } catch (error) {
        console.error('Form settings error:', error);
        const settingsField = document.getElementById('settings_data');
        if (settingsField) {
            settingsField.value = JSON.stringify({});
        }
    }
}

// Geriye uyumluluk için eski fonksiyon adı
function updatePopupSettings() {
    updateFormSettings();
}

function toggleTimerSettings() {
    const triggerElement = document.getElementById('popup_trigger');
    const timerSettings = document.getElementById('timer_settings');
    
    if (triggerElement && timerSettings) {
        const trigger = triggerElement.value;
        timerSettings.style.display = trigger === 'timer' ? 'block' : 'none';
    }
}

// Form submit
document.getElementById('formBuilderForm').addEventListener('submit', function(e) {
    // Form verilerini güncelle
    updateFormData();
    
    // Debug: Form verilerini kontrol et (console hatası varsa kaldırıldı)
    try {
        if (typeof console !== 'undefined') {
            console.log('Form fields:', formFields);
            console.log('Settings data:', document.getElementById('settings_data') ? document.getElementById('settings_data').value : 'NULL');
        }
    } catch (e) {
        // Console hatası
    }
    
    // Form adını slug-friendly yap
    const nameField = document.getElementById('name');
    if (nameField) {
        nameField.value = nameField.value
            .toLowerCase()
            .replace(/[^a-z0-9]/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    }
    
    // Form validasyonu
    const titleField = document.getElementById('title');
    if (!nameField || !titleField || !nameField.value.trim() || !titleField.value.trim()) {
        e.preventDefault();
        alert('Form adı ve başlığı zorunludur!');
        return false;
    }
});
</script>

    </div>
    <!-- End Main Content -->
    
    <!-- Sidebar Toggle Script -->
    <script>
    document.getElementById('sidebarToggle')?.addEventListener('click', function() {
        document.body.classList.toggle('sidebar-collapsed');
    });
    </script>
    
</body>
</html> 