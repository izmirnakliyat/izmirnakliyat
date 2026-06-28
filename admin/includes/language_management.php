<?php
// Dil işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'language') {
        switch ($_POST['lang_action']) {
            case 'settings':
                $show_languages = isset($_POST['show_languages']) ? 1 : 0;
                $primary_language = $_POST['primary_language'];
                
                // Mevcut ayarları güncelle
                $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE name = 'show_languages'");
                $stmt->bind_param("s", $show_languages);
                $stmt->execute();
                
                $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE name = 'primary_language'");
                $stmt->bind_param("s", $primary_language);
                $stmt->execute();
                
                $success = "Dil ayarları başarıyla güncellendi.";
                break;
                
            case 'update':
                $language_ids = $_POST['language_id'] ?? [];
                $language_codes = $_POST['language_code'] ?? [];
                $language_names = $_POST['language_name'] ?? [];
                $language_flags = $_POST['language_flag'] ?? [];
                $language_status = $_POST['language_status'] ?? [];
                
                // Her dili güncelle
                foreach ($language_ids as $index => $id) {
                    $code = $language_codes[$index];
                    $name = $language_names[$index];
                    $flag = $language_flags[$index];
                    $status = isset($language_status[$index]) ? 1 : 0;
                    
                    $stmt = $conn->prepare("UPDATE languages SET code = ?, name = ?, flag = ?, status = ? WHERE id = ?");
                    $stmt->bind_param("sssii", $code, $name, $flag, $status, $id);
                    $stmt->execute();
                }
                
                $success = "Diller başarıyla güncellendi.";
                break;
                
            case 'add':
                $code = $_POST['code'];
                $name = $_POST['name'];
                $flag = $_POST['flag'];
                $status = isset($_POST['status']) ? 1 : 0;
                
                $stmt = $conn->prepare("INSERT INTO languages (code, name, flag, status) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $code, $name, $flag, $status);
                
                if ($stmt->execute()) {
                    $success = "Dil başarıyla eklendi.";
                } else {
                    $error = "Dil eklenirken bir hata oluştu: " . $conn->error;
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                
                // Önce birincil dil olup olmadığını kontrol et
                $primary_language_result = $conn->query("SELECT value FROM settings WHERE name = 'primary_language'");
                $primary_language = $primary_language_result->fetch_assoc()['value'];
                
                // Dil kodunu al
                $lang_code_result = $conn->query("SELECT code FROM languages WHERE id = $id");
                $lang_code = $lang_code_result->fetch_assoc()['code'];
                
                if ($primary_language === $lang_code) {
                    $error = "Bu dil birincil dil olarak ayarlanmış. Silmeden önce başka bir dili birincil dil olarak ayarlayın.";
                } else {
                    $stmt = $conn->prepare("DELETE FROM languages WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    
                    if ($stmt->execute()) {
                        $success = "Dil başarıyla silindi.";
                    } else {
                        $error = "Dil silinirken bir hata oluştu: " . $conn->error;
                    }
                }
                break;
        }
    }
}

// Dil ayarlarını getir
$show_languages = '0';
$primary_language = 'tr';

$settings_result = $conn->query("SELECT name, value FROM settings WHERE name IN ('show_languages', 'primary_language')");
while ($row = $settings_result->fetch_assoc()) {
    if ($row['name'] === 'show_languages') {
        $show_languages = $row['value'];
    } elseif ($row['name'] === 'primary_language') {
        $primary_language = $row['value'];
    }
}

// Dilleri getir
$languages = [];
$languages_result = $conn->query("SELECT * FROM languages ORDER BY id ASC");
if ($languages_result) {
    while ($row = $languages_result->fetch_assoc()) {
        $languages[] = $row;
    }
}

// Popüler dil listesi
$popular_languages = [
    ['code' => 'tr', 'name' => 'Türkçe', 'flag' => 'https://cdn.countryflags.com/thumbs/turkey/flag-400.png'],
    ['code' => 'en', 'name' => 'English', 'flag' => 'https://cdn.countryflags.com/thumbs/united-kingdom/flag-400.png'],
    ['code' => 'ar', 'name' => 'العربية', 'flag' => 'https://cdn.countryflags.com/thumbs/saudi-arabia/flag-400.png'],
    ['code' => 'de', 'name' => 'Deutsch', 'flag' => 'https://cdn.countryflags.com/thumbs/germany/flag-400.png'],
    ['code' => 'es', 'name' => 'Español', 'flag' => 'https://cdn.countryflags.com/thumbs/spain/flag-400.png'],
    ['code' => 'fr', 'name' => 'Français', 'flag' => 'https://cdn.countryflags.com/thumbs/france/flag-400.png'],
    ['code' => 'it', 'name' => 'Italiano', 'flag' => 'https://cdn.countryflags.com/thumbs/italy/flag-400.png'],
    ['code' => 'ja', 'name' => '日本語', 'flag' => 'https://cdn.countryflags.com/thumbs/japan/flag-400.png'],
    ['code' => 'ko', 'name' => '한국어', 'flag' => 'https://cdn.countryflags.com/thumbs/south-korea/flag-400.png'],
    ['code' => 'ru', 'name' => 'Русский', 'flag' => 'https://cdn.countryflags.com/thumbs/russia/flag-400.png'],
    ['code' => 'zh', 'name' => '中文', 'flag' => 'https://cdn.countryflags.com/thumbs/china/flag-400.png'],
];
?>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <!-- Genel Dil Ayarları -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Genel Dil Ayarları</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="language">
                    <input type="hidden" name="lang_action" value="settings">
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="showLanguages" name="show_languages" <?php echo $show_languages == '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="showLanguages">Dil Seçeneklerini Göster</label>
                        <div class="form-text">Sitede dil seçeneklerinin gösterilip gösterilmeyeceğini belirler.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Birincil Dil</label>
                        <select name="primary_language" class="form-select">
                            <?php foreach ($languages as $language): ?>
                                <option value="<?php echo $language['code']; ?>" <?php echo $primary_language == $language['code'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($language['name']); ?> (<?php echo $language['code']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Sitenin varsayılan dili.</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Ayarları Kaydet</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Dil Listesi -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Dil Listesi</h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addLanguageModal">
                    <i class='bx bx-plus'></i> Yeni Dil
                </button>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="language">
                    <input type="hidden" name="lang_action" value="update">
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 50px">Bayrak</th>
                                    <th>Dil Adı</th>
                                    <th style="width: 60px">Kod</th>
                                    <th style="width: 80px">Durum</th>
                                    <th style="width: 100px">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($languages) > 0): ?>
                                    <?php foreach ($languages as $index => $language): ?>
                                        <tr>
                                            <td>
                                                <img src="<?php echo htmlspecialchars($language['flag']); ?>" alt="<?php echo htmlspecialchars($language['name']); ?>" width="30">
                                                <input type="hidden" name="language_id[]" value="<?php echo $language['id']; ?>">
                                                <input type="hidden" name="language_flag[]" value="<?php echo htmlspecialchars($language['flag']); ?>">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" name="language_name[]" value="<?php echo htmlspecialchars($language['name']); ?>">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" name="language_code[]" value="<?php echo htmlspecialchars($language['code']); ?>" maxlength="2">
                                            </td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="language_status[]" value="<?php echo $index; ?>" <?php echo $language['status'] ? 'checked' : ''; ?>>
                                                </div>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteLanguage(<?php echo $language['id']; ?>, '<?php echo htmlspecialchars($language['name']); ?>')">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Henüz dil eklenmemiş.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (count($languages) > 0): ?>
                        <button type="submit" class="btn btn-primary">Dilleri Güncelle</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Dil Silme Form - Gizli -->
<form id="deleteLanguageForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="language">
    <input type="hidden" name="lang_action" value="delete">
    <input type="hidden" name="id" id="delete_language_id">
</form>

<!-- Dil Ekleme Modal -->
<div class="modal fade" id="addLanguageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Dil Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="language">
                    <input type="hidden" name="lang_action" value="add">
                    
                    <div class="mb-3">
                        <label class="form-label">Popüler Diller</label>
                        <select id="popularLanguages" class="form-select">
                            <option value="">Hazır dil seçin (opsiyonel)</option>
                            <?php foreach ($popular_languages as $language): ?>
                                <option value="<?php echo $language['code']; ?>" 
                                    data-name="<?php echo htmlspecialchars($language['name']); ?>"
                                    data-flag="<?php echo htmlspecialchars($language['flag']); ?>">
                                    <?php echo htmlspecialchars($language['name']); ?> (<?php echo $language['code']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Dil Kodu</label>
                        <input type="text" name="code" id="languageCode" class="form-control" maxlength="2" required>
                        <div class="form-text">2 karakterlik ISO dil kodu (örn. tr, en, de)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Dil Adı</label>
                        <input type="text" name="name" id="languageName" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Bayrak URL</label>
                        <input type="text" name="flag" id="languageFlag" class="form-control" required>
                        <div class="form-text">Ülke bayrağının URL'si</div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="languageStatus" name="status" checked>
                        <label class="form-check-label" for="languageStatus">Aktif</label>
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
    // Popüler dil seçimi
    if (document.getElementById('popularLanguages')) {
        document.getElementById('popularLanguages').addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            
            if (selectedOption.value) {
                document.getElementById('languageCode').value = selectedOption.value;
                document.getElementById('languageName').value = selectedOption.getAttribute('data-name');
                document.getElementById('languageFlag').value = selectedOption.getAttribute('data-flag');
            }
        });
    }
});

// Silme onayı
function confirmDeleteLanguage(id, name) {
    if (confirm('"' + name + '" dilini silmek istediğinizden emin misiniz?')) {
        var form = document.getElementById('deleteLanguageForm');
        document.getElementById('delete_language_id').value = id;
        form.submit();
    }
}
</script> 