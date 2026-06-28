<?php
require_once '../config/db.php';
require_once 'includes/auto_blog_functions.php';

$categories = get_all_categories();
$success = '';
$error = '';

// UTF-8 encoding kontrolü
ensure_utf8_encoding();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cover = '';
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];
        if (in_array($ext, $allowed)) {
            $target_dir = '../uploads/auto_blog_covers/';
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $filename = 'cover_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            $target_path = $target_dir . $filename;
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_path)) {
                $cover = 'uploads/auto_blog_covers/' . $filename;
            }
        }
    }
    // Türkçe karakterleri korumak için UTF-8 encoding
    $keywords = mb_convert_encoding($_POST['keywords'], 'UTF-8', 'auto');
    $manual_command = !empty($_POST['manual_command']) ? mb_convert_encoding($_POST['manual_command'], 'UTF-8', 'auto') : '';
    
    $data = [
        'category_id' => intval($_POST['category_id']),
        'keywords' => $keywords,
        'manual_command' => $manual_command,
        'cover_image' => $cover,
        'min_words' => intval($_POST['min_words']),
        'max_words' => intval($_POST['max_words']),
        'post_count_per_period' => intval($_POST['post_count_per_period']),
        'period_type' => $_POST['period_type'],
        'post_time' => $_POST['post_time'],
        'active' => isset($_POST['active']) ? 1 : 0
    ];
    if (add_auto_blog_setting($data)) {
        header('Location: auto_blog.php?success=1');
        exit;
    } else {
        $error = 'Kayıt başarısız!';
    }
}
include 'includes/header.php';
?>
<div class="container-fluid" style="max-width:600px;">
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Yeni Otomatik Blog Ayarı Ekle</h5>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data" accept-charset="UTF-8">
                <div class="mb-3">
                    <label class="form-label">Kategori</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Seçiniz</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['ad']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Anahtar Kelimeler (virgülle)</label>
                    <input type="text" name="keywords" class="form-control" accept-charset="UTF-8">
                    <div class="form-text">Otomatik mod için anahtar kelimeler girin (örn: nakliyat, evden eve, istanbul)</div>
                </div>
                
                <!-- Manuel Komut Alanı -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="bx bx-edit"></i> Manuel Komut 
                        <span class="badge bg-success">YENİ</span>
                    </label>
                    <textarea name="manual_command" class="form-control" rows="4" placeholder="Örnek: Evden eve nakliyat anahtar kelime temalı İzmir için düşünerek her gün birbirinden farklı 3 makale yaz" accept-charset="UTF-8"></textarea>
                    <div class="form-text">
                        <strong>Manuel Komut:</strong> AI'ya özel talimat verebilirsiniz. Bu alan doldurulursa anahtar kelimeler yerine bu komut kullanılır.
                        <br><strong>Örnek komutlar:</strong>
                        <br>• "Nakliyat sektörü için İstanbul odaklı SEO uyumlu 5 makale yaz"
                        <br>• "Evden eve taşımacılık hakkında pratik ipuçları içeren makaleler oluştur"
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Kapak Fotoğrafı</label>
                    <input type="file" name="cover_image" class="form-control" accept="image/*">
                </div>
                <div class="mb-3">
                    <label class="form-label">Kelime Aralığı</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="number" name="min_words" min="100" max="5000" value="300" class="form-control" style="max-width:100px;"> -
                        <input type="number" name="max_words" min="100" max="5000" value="800" class="form-control" style="max-width:100px;">
                    </div>
                </div>
                <div class="mb-3 row g-2 align-items-center">
                    <div class="col">
                        <label class="form-label">Paylaşım Sıklığı</label>
                        <select name="period_type" class="form-select">
                            <option value="daily">Günlük</option>
                            <option value="weekly">Haftalık</option>
                            <option value="hourly">Saatlik</option>
                        </select>
                    </div>
                    <div class="col">
                        <label class="form-label">Kaç Adet</label>
                        <input type="number" name="post_count_per_period" min="1" max="10" value="1" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Paylaşım Saat(ler)i (virgülle)</label>
                    <input type="text" name="post_time" class="form-control" placeholder="10:00,14:00">
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" name="active" value="1" class="form-check-input" id="activeCheck" checked>
                    <label class="form-check-label" for="activeCheck">Aktif</label>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                    <a href="auto_blog.php" class="btn btn-secondary">İptal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const keywordsInput = document.querySelector('input[name="keywords"]');
    const manualCommandInput = document.querySelector('textarea[name="manual_command"]');
    
    // Form submit validation
    form.addEventListener('submit', function(e) {
        const keywordsValue = keywordsInput.value.trim();
        const manualCommandValue = manualCommandInput.value.trim();
        
        // En az birisi dolu olmalı
        if (!keywordsValue && !manualCommandValue) {
            e.preventDefault();
            alert('Lütfen "Anahtar Kelimeler" veya "Manuel Komut" alanlarından en az birini doldurun.');
            return false;
        }
    });
    
    // Real-time validation feedback
    function updateValidation() {
        const keywordsValue = keywordsInput.value.trim();
        const manualCommandValue = manualCommandInput.value.trim();
        
        if (keywordsValue && manualCommandValue) {
            // İkisi de dolu - Manuel komut öncelikli
            keywordsInput.classList.remove('is-invalid');
            manualCommandInput.classList.remove('is-invalid');
            manualCommandInput.classList.add('is-valid');
        } else if (manualCommandValue) {
            // Sadece manuel komut dolu
            keywordsInput.classList.remove('is-invalid', 'is-valid');
            manualCommandInput.classList.remove('is-invalid');
            manualCommandInput.classList.add('is-valid');
        } else if (keywordsValue) {
            // Sadece anahtar kelimeler dolu
            keywordsInput.classList.remove('is-invalid');
            keywordsInput.classList.add('is-valid');
            manualCommandInput.classList.remove('is-invalid', 'is-valid');
        } else {
            // İkisi de boş
            keywordsInput.classList.add('is-invalid');
            manualCommandInput.classList.add('is-invalid');
        }
    }
    
    keywordsInput.addEventListener('input', updateValidation);
    manualCommandInput.addEventListener('input', updateValidation);
});
</script>

<?php include 'includes/footer.php'; ?> 