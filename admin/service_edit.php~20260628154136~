<?php
$page_title = 'Hizmet Düzenle';
require_once 'includes/header.php';

/**
 * SEO Score hesaplama fonksiyonu
 */
function calculateServiceSeoScore($title, $description, $seo_title, $meta_description, $focus_keyword) {
    $score = 0;
    
    // Başlık kontrolü (15 puan)
    if (!empty($title)) {
        $title_len = mb_strlen($title);
        if ($title_len >= 20 && $title_len <= 70) $score += 15;
        elseif ($title_len > 0) $score += 8;
    }
    
    // SEO Başlık kontrolü (15 puan)
    if (!empty($seo_title)) {
        $seo_len = mb_strlen($seo_title);
        if ($seo_len >= 30 && $seo_len <= 60) $score += 15;
        elseif ($seo_len > 0) $score += 8;
    }
    
    // Meta Description kontrolü (25 puan)
    if (!empty($meta_description)) {
        $desc_len = mb_strlen($meta_description);
        if ($desc_len >= 120 && $desc_len <= 160) $score += 25;
        elseif ($desc_len >= 80) $score += 15;
        elseif ($desc_len > 0) $score += 8;
    }
    
    // Focus keyword kontrolü (20 puan)
    if (!empty($focus_keyword)) {
        $score += 10;
        if (!empty($title) && stripos($title, $focus_keyword) !== false) {
            $score += 10;
        }
    }
    
    // Açıklama uzunluğu kontrolü (15 puan)
    if (!empty($description)) {
        $word_count = str_word_count(strip_tags($description));
        if ($word_count >= 50) $score += 15;
        elseif ($word_count >= 20) $score += 8;
    }
    
    // Slug kontrolü (10 puan)
    // Slug varsa ekstra puan
    
    return min($score, 100);
}

// Hizmet ID'si varsa düzenleme
$service = null;
if (isset($_GET['id'])) {
    $service_id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $service = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ust_baslik = $_POST['ust_baslik'] ?? '';
    $ana_baslik = $_POST['ana_baslik'] ?? '';
    $aciklama = $_POST['aciklama'] ?? '';
    $icerik = $_POST['icerik'] ?? null;
    if (is_string($icerik) && trim($icerik) === '') {
        $icerik = null;
    }
    $bg_color = $_POST['bg_color'] ?? '';
    $order_number = isset($_POST['order_number']) ? (int)$_POST['order_number'] : 0;
    $status = isset($_POST['status']) ? 1 : 0;
    $link = !empty($_POST['link']) ? $_POST['link'] : null;
    $foto = $service ? $service['foto'] : null;
    
    // SEO Alanları
    $seo_title = trim($_POST['seo_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $meta_keywords = trim($_POST['meta_keywords'] ?? '');
    $focus_keyword = trim($_POST['focus_keyword'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    
    // Slug oluştur
    if (empty($slug) && !empty($ana_baslik)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', 
            str_replace(['ı','ğ','ü','ş','ö','ç','İ','Ğ','Ü','Ş','Ö','Ç'], 
                        ['i','g','u','s','o','c','i','g','u','s','o','c'], $ana_baslik))));
        $slug = trim($slug, '-');
    }
    
    // SEO Score hesapla — detay sayfa içeriği (icerik) varsa onu değerlendir; yoksa aciklama'ya düş.
    // Not: 2026-04 migration'dan sonra uzun içerik services.icerik'te tutuluyor; aciklama kısa kart özeti.
    $seoScoreBody = (is_string($icerik) && trim($icerik) !== '') ? $icerik : $aciklama;
    $seo_score = calculateServiceSeoScore($ana_baslik, $seoScoreBody, $seo_title, $meta_description, $focus_keyword);

    // Fotoğraf yükleme
    $foto_from_media = trim($_POST['foto_media'] ?? '');
    if (!empty($foto_from_media)) {
        $foto = 'media/' . ltrim($foto_from_media, '/');
    } elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "../uploads/services/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file = $_FILES['foto'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
        if (!in_array($file_ext, $allowed_exts)) {
            $error = "Sadece JPG, JPEG, PNG, WebP ve AVIF formatları desteklenmektedir.";
        } else {
            // Eski foto sil
            if ($service && $service['foto']) {
                $old_foto_path = $upload_dir . $service['foto'];
                if (file_exists($old_foto_path)) {
                    unlink($old_foto_path);
                }
            }
            $new_filename = uniqid() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $foto = $new_filename;
            } else {
                $error = "Fotoğraf yüklenirken bir hata oluştu.";
            }
        }
    }

    if (!isset($error)) {
        if ($service) {
            $stmt = $conn->prepare("UPDATE services SET ust_baslik=?, ana_baslik=?, aciklama=?, icerik=?, foto=?, bg_color=?, order_number=?, status=?, link=?, seo_title=?, meta_description=?, meta_keywords=?, focus_keyword=?, slug=?, seo_score=? WHERE id=?");
            $stmt->bind_param("ssssssiiisssssii", $ust_baslik, $ana_baslik, $aciklama, $icerik, $foto, $bg_color, $order_number, $status, $link, $seo_title, $meta_description, $meta_keywords, $focus_keyword, $slug, $seo_score, $service_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO services (ust_baslik, ana_baslik, aciklama, icerik, foto, bg_color, order_number, status, link, seo_title, meta_description, meta_keywords, focus_keyword, slug, seo_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssiiisssssi", $ust_baslik, $ana_baslik, $aciklama, $icerik, $foto, $bg_color, $order_number, $status, $link, $seo_title, $meta_description, $meta_keywords, $focus_keyword, $slug, $seo_score);
        }
        if ($stmt->execute()) {
            $success = "Hizmet başarıyla kaydedildi!";
            // Güncel verileri yükle
            if (!$service) {
                $service_id = $conn->insert_id;
            }
            $stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
            $stmt->bind_param("i", $service_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $service = $result->fetch_assoc();
        } else {
            $error = "Hizmet kaydedilirken bir hata oluştu: " . $conn->error;
        }
    }
}
?>
<style>
.seo-card {
    border: 1px solid #e3f2fd;
    background: #f8fbff;
}
.seo-card-header {
    background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
    border-bottom: 1px solid #90caf9;
    cursor: pointer;
    transition: background 0.3s ease;
}
.seo-card-header:hover {
    background: linear-gradient(135deg, #bbdefb 0%, #e1bee7 100%);
}
.google-preview {
    font-family: Arial, sans-serif;
    background: #fff;
    border: 1px solid #dfe1e5;
    border-radius: 8px;
    padding: 15px;
}
.google-preview .preview-title {
    color: #1a0dab;
    font-size: 18px;
    margin-bottom: 3px;
    cursor: pointer;
}
.google-preview .preview-url {
    color: #006621;
    font-size: 13px;
    margin-bottom: 3px;
}
.google-preview .preview-description {
    color: #545454;
    font-size: 13px;
    line-height: 1.5;
}
</style>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?php echo $service ? 'Hizmeti Düzenle' : 'Yeni Hizmet Ekle'; ?></h5>
        <a href="services.php" class="btn btn-secondary btn-sm">
            <i class="bx bx-arrow-back"></i> Listeye Dön
        </a>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" id="serviceForm">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="ust_baslik" class="form-label">Üst Başlık</label>
                        <input type="text" class="form-control" id="ust_baslik" name="ust_baslik" value="<?php echo htmlspecialchars($service['ust_baslik'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="ana_baslik" class="form-label">Ana Başlık <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="ana_baslik" name="ana_baslik" value="<?php echo htmlspecialchars($service['ana_baslik'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="slug" class="form-label">URL (Slug)</label>
                        <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($service['slug'] ?? ''); ?>" placeholder="Otomatik oluşturulur">
                        <small class="text-muted">Boş bırakırsanız başlıktan otomatik oluşturulur</small>
                    </div>
                    <div class="mb-3">
                        <label for="aciklama" class="form-label">Açıklama <small class="text-muted">(kısa özet — ana sayfa kartı)</small></label>
                        <textarea class="form-control" id="aciklama" name="aciklama" rows="4" maxlength="500"><?php echo htmlspecialchars($service['aciklama'] ?? ''); ?></textarea>
                        <small class="text-muted">Bu metin yalnızca ana sayfa hizmet kartında görünür. Kısa, düz metin olmalı (HTML yok, ideal 120–250 karakter).</small>
                    </div>
                    <div class="mb-3">
                        <label for="icerik" class="form-label">Detay Sayfa İçeriği <small class="text-muted">(linke tıklandığında gösterilir)</small></label>
                        <textarea class="form-control font-monospace" id="icerik" name="icerik" rows="25" style="font-size: 13px;"><?php echo htmlspecialchars($service['icerik'] ?? ''); ?></textarea>
                        <small class="text-muted">HTML desteklenir (<code>&lt;h2&gt;</code>, <code>&lt;p&gt;</code>, <code>&lt;ul&gt;</code> vs.). Boş bırakılırsa detay sayfasında yukarıdaki kısa açıklama kullanılır.</small>
                    </div>
                    <div class="mb-3">
                        <label for="link" class="form-label">Link (isteğe bağlı)</label>
                        <input type="text" class="form-control" id="link" name="link" value="<?php echo htmlspecialchars($service['link'] ?? ''); ?>" placeholder="https://...">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="foto" class="form-label">Fotoğraf</label>
                        <?php if ($service && ($service['foto'] ?? null)): ?>
                            <div class="mb-2">
                                <img src="../uploads/services/<?php echo htmlspecialchars($service['foto']); ?>" alt="Mevcut fotoğraf" style="max-width: 200px; height: auto; border-radius: 8px;">
                            </div>
                        <?php endif; ?>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="file" class="form-control" id="foto" name="foto" accept=".jpg,.jpeg,.png,.webp,.avif">
                            <button type="button" class="btn btn-outline-primary text-nowrap" id="btn-pick-foto">
                                <i class="bx bx-images me-1"></i> Medya Kütüphanesi
                            </button>
                        </div>
                        <input type="hidden" id="foto_media" name="foto_media" value="">
                        <div id="foto-preview" class="mt-2" style="display:none;">
                            <img src="" alt="" style="max-height:100px; border-radius:6px; border:2px solid #0d6efd;">
                            <p class="text-success small mt-1 mb-0" id="foto-preview-name"></p>
                        </div>
                        <small class="form-text text-muted">Desteklenen formatlar: JPG, JPEG, PNG, WebP, AVIF</small>
                    </div>
                    <div class="mb-3">
                        <label for="bg_color" class="form-label">Arkaplan Rengi</label>
                        <input type="color" class="form-control form-control-color" id="bg_color" name="bg_color" value="<?php echo htmlspecialchars($service['bg_color'] ?? '#ffffff'); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="order_number" class="form-label">Sıra Numarası</label>
                        <input type="number" class="form-control" id="order_number" name="order_number" value="<?php echo isset($service['order_number']) ? (int)$service['order_number'] : 0; ?>" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="status" name="status" <?php echo (!$service || ($service['status'] ?? 0)) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="status">Aktif</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- SEO Ayarları -->
            <div class="card seo-card mb-4">
                <div class="card-header seo-card-header" data-bs-toggle="collapse" data-bs-target="#seoCollapse">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="bx bx-search-alt me-2" style="color: #1976d2;"></i>
                            SEO Ayarları
                            <?php 
                            $seo_score = $service['seo_score'] ?? 0;
                            $score_class = $seo_score >= 80 ? 'bg-success' : ($seo_score >= 50 ? 'bg-warning' : 'bg-danger');
                            ?>
                            <span class="badge <?php echo $score_class; ?> ms-2" id="seoScoreBadge"><?php echo $seo_score; ?>%</span>
                        </h6>
                        <i class="bx bx-chevron-down"></i>
                    </div>
                </div>
                <div class="collapse show" id="seoCollapse">
                    <div class="card-body">
                        <!-- Focus Keyword -->
                        <div class="mb-3">
                            <label for="focus_keyword" class="form-label">
                                <i class="bx bx-key text-warning me-1"></i> Odak Anahtar Kelime
                            </label>
                            <input type="text" class="form-control" id="focus_keyword" name="focus_keyword" 
                                value="<?php echo htmlspecialchars($service['focus_keyword'] ?? ''); ?>" 
                                placeholder="Ana hedef anahtar kelimeniz...">
                            <small class="text-muted">Bu hizmetin optimize edileceği ana anahtar kelime</small>
                        </div>
                        
                        <!-- SEO Title -->
                        <div class="mb-3">
                            <label for="seo_title" class="form-label">
                                <i class="bx bx-heading text-primary me-1"></i> SEO Başlığı
                                <span class="badge bg-secondary ms-2">Google için</span>
                            </label>
                            <input type="text" class="form-control" id="seo_title" name="seo_title" maxlength="70"
                                value="<?php echo htmlspecialchars($service['seo_title'] ?? ''); ?>" 
                                placeholder="Arama sonuçlarında görünecek başlık...">
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Boş bırakırsanız ana başlık kullanılır</small>
                                <small><span id="seoTitleCount">0</span>/60 karakter</small>
                            </div>
                        </div>
                        
                        <!-- Meta Description -->
                        <div class="mb-3">
                            <label for="meta_description" class="form-label">
                                <i class="bx bx-align-left text-info me-1"></i> Meta Açıklaması 
                                <span class="badge bg-primary ms-2">SEO</span>
                            </label>
                            <textarea class="form-control" id="meta_description" name="meta_description" 
                                rows="3" maxlength="160"
                                placeholder="Google arama sonuçlarında görünecek açıklama (150-160 karakter)..."><?php echo htmlspecialchars($service['meta_description'] ?? ''); ?></textarea>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Tıklama oranınızı artıracak çekici bir açıklama yazın</small>
                                <small><span id="metaDescCount">0</span>/160 karakter</small>
                            </div>
                        </div>
                        
                        <!-- Meta Keywords -->
                        <div class="mb-3">
                            <label for="meta_keywords" class="form-label">
                                <i class="bx bx-purchase-tag text-success me-1"></i> Meta Anahtar Kelimeler
                            </label>
                            <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" 
                                value="<?php echo htmlspecialchars($service['meta_keywords'] ?? ''); ?>" 
                                placeholder="kelime1, kelime2, kelime3...">
                            <small class="text-muted">Virgülle ayırarak yazın (maksimum 8-10 kelime önerilir)</small>
                        </div>
                        
                        <!-- Google Önizleme -->
                        <div class="google-preview mt-4">
                            <h6 class="mb-3"><i class="bx bxl-google text-danger me-2"></i>Google Arama Önizlemesi</h6>
                            <div class="preview-title" id="previewTitle">
                                <?php echo htmlspecialchars($service['seo_title'] ?? $service['ana_baslik'] ?? 'Hizmet Başlığı'); ?>
                            </div>
                            <div class="preview-url">
                                <?php echo SITE_URL; ?>/<span id="previewSlug"><?php echo htmlspecialchars($service['slug'] ?? 'hizmet-url'); ?></span>
                            </div>
                            <div class="preview-description" id="previewDescription">
                                <?php echo htmlspecialchars($service['meta_description'] ?? 'Meta açıklaması buraya gelecek...'); ?>
                            </div>
                        </div>
                        
                        <!-- AI Butonları -->
                        <div class="mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-outline-primary" id="generateSeoBtn">
                                <i class="bx bx-magic-wand me-1"></i> AI ile SEO İçeriği Oluştur
                            </button>
                            <button type="button" class="btn btn-outline-secondary ms-2" id="analyzeSeoBtn">
                                <i class="bx bx-bar-chart-alt-2 me-1"></i> SEO Analizi Yap
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-end">
                <a href="services.php" class="btn btn-secondary">İptal</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bx bx-save me-1"></i> Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const seoTitleInput = document.getElementById('seo_title');
    const metaDescInput = document.getElementById('meta_description');
    const anaBaslikInput = document.getElementById('ana_baslik');
    const slugInput = document.getElementById('slug');
    const focusKeywordInput = document.getElementById('focus_keyword');
    const aciklamaInput = document.getElementById('aciklama');
    
    const seoTitleCount = document.getElementById('seoTitleCount');
    const metaDescCount = document.getElementById('metaDescCount');
    const previewTitle = document.getElementById('previewTitle');
    const previewSlug = document.getElementById('previewSlug');
    const previewDescription = document.getElementById('previewDescription');
    
    // SEO Title sayacı ve önizleme
    function updateSeoTitle() {
        const length = seoTitleInput.value.length;
        seoTitleCount.textContent = length;
        previewTitle.textContent = seoTitleInput.value || anaBaslikInput.value || 'Hizmet Başlığı';
        
        if (length >= 30 && length <= 60) {
            seoTitleCount.style.color = '#28a745';
        } else if (length > 0) {
            seoTitleCount.style.color = '#ffc107';
        } else {
            seoTitleCount.style.color = '#6c757d';
        }
    }
    
    // Meta Description sayacı ve önizleme
    function updateMetaDesc() {
        const length = metaDescInput.value.length;
        metaDescCount.textContent = length;
        previewDescription.textContent = metaDescInput.value || 'Meta açıklaması buraya gelecek...';
        
        if (length >= 120 && length <= 160) {
            metaDescCount.style.color = '#28a745';
        } else if (length > 0) {
            metaDescCount.style.color = '#ffc107';
        } else {
            metaDescCount.style.color = '#6c757d';
        }
    }
    
    // Slug önizleme
    function updateSlugPreview() {
        previewSlug.textContent = slugInput.value || 'hizmet-url';
    }
    
    // Ana başlık değiştiğinde
    function updateTitlePreview() {
        if (!seoTitleInput.value) {
            previewTitle.textContent = anaBaslikInput.value || 'Hizmet Başlığı';
        }
    }
    
    // Event listeners
    if (seoTitleInput) {
        seoTitleInput.addEventListener('input', updateSeoTitle);
        updateSeoTitle();
    }
    if (metaDescInput) {
        metaDescInput.addEventListener('input', updateMetaDesc);
        updateMetaDesc();
    }
    if (slugInput) {
        slugInput.addEventListener('input', updateSlugPreview);
    }
    if (anaBaslikInput) {
        anaBaslikInput.addEventListener('input', updateTitlePreview);
    }
    
    // AI ile SEO Oluştur
    const generateSeoBtn = document.getElementById('generateSeoBtn');
    if (generateSeoBtn) {
        generateSeoBtn.addEventListener('click', function() {
            const title = anaBaslikInput.value;
            const description = aciklamaInput.value;
            const focusKeyword = focusKeywordInput.value;
            
            if (!title) {
                alert('Lütfen önce Ana Başlık girin.');
                return;
            }
            
            generateSeoBtn.disabled = true;
            generateSeoBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Oluşturuluyor...';
            
            fetch('ajax/generate_seo_content.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    title: title,
                    content: description,
                    focus_keyword: focusKeyword,
                    type: 'service'
                })
            })
            .then(response => {
                // Önce text olarak al
                return response.text().then(text => {
                    console.log('Server response:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Raw response:', text);
                        throw new Error('Sunucu geçersiz yanıt döndürdü: ' + text.substring(0, 200));
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    if (data.seo_title) seoTitleInput.value = data.seo_title;
                    if (data.meta_description) metaDescInput.value = data.meta_description;
                    if (data.meta_keywords) document.getElementById('meta_keywords').value = data.meta_keywords;
                    if (data.focus_keyword && !focusKeyword) focusKeywordInput.value = data.focus_keyword;
                    
                    updateSeoTitle();
                    updateMetaDesc();
                    alert('SEO içerikleri başarıyla oluşturuldu!');
                } else {
                    alert('Hata: ' + (data.message || 'SEO içeriği oluşturulamadı.'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Hata: ' + error.message);
            })
            .finally(() => {
                generateSeoBtn.disabled = false;
                generateSeoBtn.innerHTML = '<i class="bx bx-magic-wand me-1"></i> AI ile SEO İçeriği Oluştur';
            });
        });
    }
    
    // SEO Analizi
    const analyzeSeoBtn = document.getElementById('analyzeSeoBtn');
    if (analyzeSeoBtn) {
        analyzeSeoBtn.addEventListener('click', function() {
            const title = anaBaslikInput.value;
            const seoTitle = seoTitleInput.value;
            const metaDesc = metaDescInput.value;
            const focusKeyword = focusKeywordInput.value;
            const description = aciklamaInput.value;
            
            let issues = [];
            let score = 0;
            
            // Başlık kontrolü
            if (!title) {
                issues.push('❌ Ana başlık eksik');
            } else {
                const titleLen = title.length;
                if (titleLen < 20) issues.push('⚠️ Başlık kısa (' + titleLen + ' karakter)');
                else if (titleLen > 70) issues.push('⚠️ Başlık uzun (' + titleLen + ' karakter)');
                else { issues.push('✅ Başlık uzunluğu ideal'); score += 15; }
            }
            
            // SEO başlık kontrolü
            if (!seoTitle) {
                issues.push('⚠️ SEO başlığı eksik');
            } else {
                const seoLen = seoTitle.length;
                if (seoLen >= 30 && seoLen <= 60) {
                    issues.push('✅ SEO başlığı ideal (' + seoLen + ' karakter)');
                    score += 15;
                } else {
                    issues.push('⚠️ SEO başlığı uygun değil (' + seoLen + ' karakter)');
                }
            }
            
            // Meta description kontrolü
            if (!metaDesc) {
                issues.push('❌ Meta açıklaması eksik');
            } else {
                const descLen = metaDesc.length;
                if (descLen >= 120 && descLen <= 160) {
                    issues.push('✅ Meta açıklaması ideal (' + descLen + ' karakter)');
                    score += 25;
                } else {
                    issues.push('⚠️ Meta açıklaması uygun değil (' + descLen + ' karakter)');
                    score += 10;
                }
            }
            
            // Focus keyword kontrolü
            if (!focusKeyword) {
                issues.push('⚠️ Odak anahtar kelime belirlenmemiş');
            } else {
                score += 10;
                if (title.toLowerCase().includes(focusKeyword.toLowerCase())) {
                    issues.push('✅ Odak kelime başlıkta var');
                    score += 10;
                } else {
                    issues.push('⚠️ Odak kelime başlıkta yok');
                }
            }
            
            // Açıklama uzunluğu
            const wordCount = description.split(/\s+/).filter(w => w.length > 0).length;
            if (wordCount < 20) {
                issues.push('⚠️ Açıklama kısa (' + wordCount + ' kelime)');
            } else {
                issues.push('✅ Açıklama yeterli (' + wordCount + ' kelime)');
                score += 15;
            }
            
            score = Math.min(score, 100);
            
            alert('SEO Analiz Sonucu\n\nTahmini Skor: ' + score + '%\n\n' + issues.join('\n'));
        });
    }
});
</script>

<script>
document.getElementById('btn-pick-foto').addEventListener('click', function() {
    MediaPicker.open(function(item) {
        document.getElementById('foto_media').value = item.filename;
        document.getElementById('foto').value = '';
        var preview = document.getElementById('foto-preview');
        preview.style.display = 'block';
        preview.querySelector('img').src = item.url;
        document.getElementById('foto-preview-name').textContent = '✓ ' + item.original_name;
    });
});
document.getElementById('foto').addEventListener('change', function() {
    if (this.files.length > 0) document.getElementById('foto_media').value = '';
});
</script>

<?php require_once 'includes/footer.php'; ?> 