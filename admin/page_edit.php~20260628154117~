<?php
require_once __DIR__ . '/includes/require_admin_web.php';
require_once __DIR__ . '/../includes/mynak_pages_id_guard.php';

mynak_pages_id_repair_non_positive($conn);

$page_title = "Sayfa Ekle/Düzenle";
$success_message = '';
$error_message = '';

// Düzenleme mi yoksa yeni sayfa mı?
$is_edit = false;
$page = [
    'title' => '',
    'content' => '',
    'slug' => '',
    'status' => 0,
    'seo_title' => '',
    'meta_description' => '',
    'meta_keywords' => '',
    'og_title' => '',
    'og_description' => '',
    'og_image' => '',
    'canonical_url' => '',
    'focus_keyword' => '',
    'seo_score' => 0
];

// Sayfa ID değişkeni
$id = 0;

// Sayfa düzenleme (id=0 geçersiz — bozuk kayıt düzenlenmesin)
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    if ($id > 0) {
        $stmt = $conn->prepare('SELECT * FROM pages WHERE id = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $page = $result->fetch_assoc();
                $is_edit = true;
            }
            $stmt->close();
        }
    }
}

// Kayıt sonrası yönlendirme mesajı
if (isset($_GET['saved']) && (int) $_GET['saved'] === 1) {
    $success_message = isset($_GET['draft']) && (int) $_GET['draft'] === 1
        ? 'Sayfa taslak olarak kaydedildi. Yayınlamak için "Yayınla" butonunu kullanın.'
        : 'Sayfa yayınlandı.';
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = $_POST['content'];
    $meta_description = trim($_POST['meta_description']);
    $meta_keywords = trim($_POST['meta_keywords']);
    
    // Yeni SEO alanları
    $seo_title = trim($_POST['seo_title'] ?? '');
    $og_title = trim($_POST['og_title'] ?? '');
    $og_description = trim($_POST['og_description'] ?? '');
    $og_image = trim($_POST['og_image'] ?? '');
    $canonical_url = trim($_POST['canonical_url'] ?? '');
    $focus_keyword = trim($_POST['focus_keyword'] ?? '');
    
    // SEO Score hesapla
    $seo_score = 0;
    if (!empty($seo_title) && mb_strlen($seo_title) >= 30 && mb_strlen($seo_title) <= 60) $seo_score += 15;
    if (!empty($meta_description) && mb_strlen($meta_description) >= 120 && mb_strlen($meta_description) <= 160) $seo_score += 25;
    if (!empty($meta_keywords)) $seo_score += 10;
    if (!empty($focus_keyword)) $seo_score += 15;
    if (!empty($og_title)) $seo_score += 10;
    if (!empty($og_description)) $seo_score += 10;
    if (mb_strlen($title) >= 30 && mb_strlen($title) <= 70) $seo_score += 15;
    $seo_score = min($seo_score, 100);
    
    // İçeriği işle - resimleri düzenle
    require_once '../includes/functions.php';
    $content = icerik_donustur($content);
    
    $status = isset($_POST['status']) ? (int) $_POST['status'] : 0;
    $status = $status === 1 ? 1 : 0;
    if (isset($_POST['save_publish'])) {
        $status = 1;
    } elseif (isset($_POST['save_draft'])) {
        $status = 0;
    }
    
    // Slug oluştur
    $slug = isset($_POST['slug']) && !empty($_POST['slug']) 
        ? trim($_POST['slug']) 
        : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    
    // Validasyon
    if (empty($title)) {
        $error_message = "Sayfa başlığı boş olamaz.";
    } else {
        try {
            $slugCheckId = $id > 0 ? $id : -1;
            $check_stmt = $conn->prepare('SELECT id FROM pages WHERE slug = ? AND id != ? AND id > 0 LIMIT 1');
            if (!$check_stmt) {
                $error_message = 'Slug kontrolü yapılamadı.';
            } else {
                $check_stmt->bind_param('si', $slug, $slugCheckId);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $slugTaken = $check_result && $check_result->num_rows > 0;
                $check_stmt->close();
            }

            if (!empty($slugTaken)) {
                $error_message = 'Bu URL adresi (slug) zaten kullanılıyor. Lütfen başka bir değer girin.';
            } else {
                if ($is_edit && $id > 0) {
                    // Güncelleme
                    $update_sql = "UPDATE pages SET title = ?, content = ?, slug = ?, status = ?, meta_description = ?, meta_keywords = ?, seo_title = ?, og_title = ?, og_description = ?, og_image = ?, canonical_url = ?, focus_keyword = ?, seo_score = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("sssissssssssii", $title, $content, $slug, $status, $meta_description, $meta_keywords, $seo_title, $og_title, $og_description, $og_image, $canonical_url, $focus_keyword, $seo_score, $id);
                    
                    if ($update_stmt->execute()) {
                        $success_message = $status === 1
                            ? 'Sayfa yayınlandı ve güncellendi.'
                            : 'Sayfa taslak olarak kaydedildi.';
                        // Güncel verileri al
                        $page['title'] = $title;
                        $page['content'] = $content;
                        $page['slug'] = $slug;
                        $page['status'] = $status;
                        $page['meta_description'] = $meta_description;
                        $page['meta_keywords'] = $meta_keywords;
                        $page['seo_title'] = $seo_title;
                        $page['og_title'] = $og_title;
                        $page['og_description'] = $og_description;
                        $page['og_image'] = $og_image;
                        $page['canonical_url'] = $canonical_url;
                        $page['focus_keyword'] = $focus_keyword;
                        $page['seo_score'] = $seo_score;
                    } else {
                        $error_message = "Sayfa güncellenirken bir hata oluştu: " . $update_stmt->error;
                    }
                } else {
                    // Yeni ekleme
                    $insert_sql = "INSERT INTO pages (title, content, slug, status, meta_description, meta_keywords, seo_title, og_title, og_description, og_image, canonical_url, focus_keyword, seo_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bind_param("sssissssssssi", $title, $content, $slug, $status, $meta_description, $meta_keywords, $seo_title, $og_title, $og_description, $og_image, $canonical_url, $focus_keyword, $seo_score);
                    
                    if ($insert_stmt->execute()) {
                        $new_id = mynak_pages_resolve_insert_id($conn, (int) $insert_stmt->insert_id, $slug);
                        $insert_stmt->close();
                        if ($new_id > 0) {
                            while (ob_get_level() > 0) {
                                ob_end_clean();
                            }
                            header('Location: page_edit.php?id=' . $new_id . '&saved=1&draft=' . ($status === 0 ? '1' : '0'));
                            exit;
                        }
                        $error_message = 'Sayfa kaydedildi ancak kayıt kimliği alınamadı. Sayfalar listesini kontrol edin.';
                    } else {
                        $error_message = "Sayfa eklenirken bir hata oluştu: " . $insert_stmt->error;
                    }
                }
            }
        } catch (Exception $e) {
            $error_message = "İşlem sırasında bir hata oluştu: " . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<style>
.toolbar-container {
    padding: 10px;
    background: #f8f9fa;
    border: 1px solid #ced4da;
    border-bottom: none;
    border-radius: 0.25rem 0.25rem 0 0;
}
#content {
    border-top-left-radius: 0;
    border-top-right-radius: 0;
}
.quill-loading {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    color: white;
    font-size: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}
.ck-editor__editable {
    min-height: 400px;
}
.form-label {
    font-weight: 500;
}
.tox-tinymce {
    border: 1px solid #d2d6de;
    border-radius: 0.25rem;
}
/* SEO Card Styles */
.seo-card {
    border: 1px solid #e3f2fd;
    background: #f8fbff;
}
.seo-card-header {
    background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
    border-bottom: 1px solid #90caf9;
    transition: background 0.3s ease;
}
.seo-card-header:hover {
    background: linear-gradient(135deg, #bbdefb 0%, #e1bee7 100%);
}
</style>

<div class="container-fluid">
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><?php echo $is_edit ? 'Sayfayı Düzenle: ' . htmlspecialchars($page['title']) : 'Yeni Sayfa Ekle'; ?></h6>
            <a href="pages.php" class="btn btn-secondary btn-sm">
                <i class='bx bx-arrow-back'></i> Listeye Dön
            </a>
        </div>
        <div class="card-body">
            <form method="POST" id="pageForm">
                <div class="form-group mb-3">
                    <label for="title" class="form-label">Sayfa Başlığı <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($page['title']); ?>" required>
                </div>
                
                <div class="form-group mb-3">
                    <label for="slug" class="form-label">URL Adresi (Slug)</label>
                    <div class="input-group">
                        <span class="input-group-text"><?php echo SITE_URL; ?>/</span>
                        <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($page['slug']); ?>" placeholder="otomatik-olusturulur">
                    </div>
                    <small class="form-text text-muted">
                        <strong>Clean URL:</strong> <?php echo SITE_URL; ?>/<span id="slug-preview"><?php echo htmlspecialchars($page['slug']); ?></span><br>
                        Boş bırakırsanız başlıktan otomatik oluşturulur. Sadece küçük harfler, rakamlar ve tire (-) kullanın.
                    </small>
                </div>
                
                <div class="form-group mb-3">
                    <label for="content" class="form-label">Sayfa İçeriği</label>
                    <textarea id="content" name="content" class="form-control"><?php echo htmlspecialchars($page['content']); ?></textarea>
                </div>
                
                <!-- SEO Meta Bilgileri -->
                <div class="card mb-4 seo-card">
                    <div class="card-header seo-card-header" data-bs-toggle="collapse" data-bs-target="#seoCollapse" style="cursor: pointer;">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="bx bx-search-alt me-2" style="color: #1976d2;"></i>
                                SEO Ayarları
                                <?php 
                                $seo_score = $page['seo_score'] ?? 0;
                                $score_class = $seo_score >= 80 ? 'bg-success' : ($seo_score >= 50 ? 'bg-warning' : 'bg-danger');
                                ?>
                                <span class="badge <?php echo $score_class; ?> ms-2"><?php echo $seo_score; ?>%</span>
                            </h6>
                            <i class="bx bx-chevron-down"></i>
                        </div>
                    </div>
                    <div class="collapse show" id="seoCollapse">
                        <div class="card-body">
                            <!-- Focus Keyword -->
                            <div class="form-group mb-3">
                                <label for="focus_keyword" class="form-label">
                                    <i class="bx bx-key text-warning me-1"></i> Odak Anahtar Kelime
                                </label>
                                <input type="text" class="form-control" id="focus_keyword" name="focus_keyword" 
                                    value="<?php echo htmlspecialchars($page['focus_keyword'] ?? ''); ?>" 
                                    placeholder="Ana hedef anahtar kelimeniz...">
                                <small class="text-muted">İçeriğinizin optimize edileceği ana anahtar kelime</small>
                            </div>
                            
                            <!-- SEO Title -->
                            <div class="form-group mb-3">
                                <label for="seo_title" class="form-label">
                                    <i class="bx bx-heading text-primary me-1"></i> SEO Başlığı
                                    <span class="badge bg-secondary ms-2">Google için</span>
                                </label>
                                <input type="text" class="form-control" id="seo_title" name="seo_title" maxlength="70"
                                    value="<?php echo htmlspecialchars($page['seo_title'] ?? ''); ?>" 
                                    placeholder="Arama sonuçlarında görünecek başlık...">
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-muted">Boş bırakırsanız sayfa başlığı kullanılır</small>
                                    <small><span id="seoTitleCount">0</span>/60 karakter</small>
                                </div>
                            </div>
                            
                            <!-- Meta Description -->
                            <div class="form-group mb-3">
                                <label for="meta_description" class="form-label">
                                    <i class="bx bx-align-left text-info me-1"></i> Meta Açıklaması 
                                    <span class="badge bg-primary ms-2">SEO</span>
                                </label>
                                <textarea id="meta_description" name="meta_description" class="form-control" rows="3" maxlength="160" placeholder="Bu sayfa hakkında 150-160 karakter arasında açıklama yazın..."><?php echo htmlspecialchars($page['meta_description']); ?></textarea>
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-muted">Google arama sonuçlarında görünecek açıklama</small>
                                    <small><span id="meta-char-count">0</span>/160 karakter</small>
                                </div>
                            </div>
                            
                            <!-- Meta Keywords -->
                            <div class="form-group mb-3">
                                <label for="meta_keywords" class="form-label">
                                    <i class="bx bx-purchase-tag text-success me-1"></i> Meta Anahtar Kelimeleri 
                                </label>
                                <input type="text" id="meta_keywords" name="meta_keywords" class="form-control" maxlength="500" placeholder="anahtar kelime, nakliyat, evden eve, izmir..." value="<?php echo htmlspecialchars($page['meta_keywords']); ?>">
                                <small class="text-muted">Virgülle ayırarak yazın. Maksimum 8-10 anahtar kelime önerilir.</small>
                            </div>
                            
                            <!-- Open Graph Ayarları -->
                            <div class="border rounded p-3 mb-3 bg-light">
                                <h6 class="mb-3"><i class="bx bxl-facebook text-primary me-2"></i>Sosyal Medya Paylaşım Ayarları</h6>
                                
                                <div class="form-group mb-3">
                                    <label for="og_title" class="form-label">OG Başlık</label>
                                    <input type="text" class="form-control" id="og_title" name="og_title" maxlength="95"
                                        value="<?php echo htmlspecialchars($page['og_title'] ?? ''); ?>" 
                                        placeholder="Sosyal medyada görünecek başlık...">
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="og_description" class="form-label">OG Açıklama</label>
                                    <textarea class="form-control" id="og_description" name="og_description" 
                                        rows="2" maxlength="200"
                                        placeholder="Sosyal medyada görünecek açıklama..."><?php echo htmlspecialchars($page['og_description'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group mb-0">
                                    <label for="og_image" class="form-label">OG Görsel URL</label>
                                    <input type="text" class="form-control" id="og_image" name="og_image" 
                                        value="<?php echo htmlspecialchars($page['og_image'] ?? ''); ?>" 
                                        placeholder="https://...">
                                </div>
                            </div>
                            
                            <!-- Canonical URL -->
                            <div class="form-group mb-3">
                                <label for="canonical_url" class="form-label">
                                    <i class="bx bx-link text-secondary me-1"></i> Canonical URL
                                </label>
                                <input type="url" class="form-control" id="canonical_url" name="canonical_url" 
                                    value="<?php echo htmlspecialchars($page['canonical_url'] ?? ''); ?>" 
                                    placeholder="https://...">
                                <small class="text-muted">Yinelenen içerik için orijinal URL (genellikle boş bırakılır)</small>
                            </div>
                            
                            <!-- AI ile Otomatik Oluştur -->
                            <div class="mt-3 pt-3 border-top">
                                <button type="button" class="btn btn-outline-primary" id="generateSeoBtn">
                                    <i class="bx bx-magic-wand me-1"></i> AI ile SEO İçeriği Oluştur
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group mb-3">
                    <label for="status" class="form-label">Yayın Durumu</label>
                    <select id="status" name="status" class="form-select" style="max-width: 280px;">
                        <option value="0" <?php echo (int) ($page['status'] ?? 0) === 0 ? 'selected' : ''; ?>>Taslak</option>
                        <option value="1" <?php echo (int) ($page['status'] ?? 0) === 1 ? 'selected' : ''; ?>>Yayında</option>
                    </select>
                    <small class="text-muted d-block mt-1">Taslak sayfalar sitede ve site haritasında görünmez.</small>
                </div>
                
                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" name="save_draft" value="1" class="btn btn-outline-secondary">
                        <i class='bx bx-save'></i> Taslak Olarak Kaydet
                    </button>
                    <button type="submit" name="save_publish" value="1" class="btn btn-primary">
                        <i class='bx bx-upload'></i> Yayınla
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- TinyMCE -->
<script src="https://cdn.tiny.cloud/1/4n94ins65nytwfoc5usihu3atd4bq7xoye0tou5lng48xawf/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script src="assets/js/tinymce-config.js"></script>

<script>
// Başlıktan slug oluştur
document.getElementById('title').addEventListener('input', function() {
    const slugField = document.getElementById('slug');
    const slugPreview = document.getElementById('slug-preview');
    
    // Eğer slug alanı boşsa veya daha önce kullanıcı tarafından değiştirilmediyse
    if (slugField.value === '' || slugField.getAttribute('data-auto-slug') === 'true') {
        const titleValue = this.value;
        const slugValue = titleValue
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
        
        slugField.value = slugValue;
        if (slugPreview) slugPreview.textContent = slugValue;
        slugField.setAttribute('data-auto-slug', 'true');
    }
});

// Slug alanı manuel değiştirildiğinde
document.getElementById('slug').addEventListener('input', function() {
    const slugPreview = document.getElementById('slug-preview');
    if (slugPreview) slugPreview.textContent = this.value;
    this.setAttribute('data-auto-slug', 'false');
});

// TinyMCE başlat
document.addEventListener('DOMContentLoaded', function() {
    tinymce.init(TinyMCEConfig.pageConfig());

    const pageForm = document.getElementById('pageForm');
    if (pageForm) {
        pageForm.addEventListener('submit', function() {
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }
        });
        pageForm.querySelectorAll('button[type="submit"]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const statusSelect = document.getElementById('status');
                if (statusSelect && this.name === 'save_draft') {
                    statusSelect.value = '0';
                }
                if (statusSelect && this.name === 'save_publish') {
                    statusSelect.value = '1';
                }
            });
        });
    }
    
    // Meta açıklaması karakter sayacı
    const metaDescTextarea = document.getElementById('meta_description');
    const charCountSpan = document.getElementById('meta-char-count');
    const seoTitleInput = document.getElementById('seo_title');
    const seoTitleCountSpan = document.getElementById('seoTitleCount');
    
    function updateCharCount() {
        if (metaDescTextarea && charCountSpan) {
            const currentLength = metaDescTextarea.value.length;
            charCountSpan.textContent = currentLength;
            
            // Renk kodlaması
            if (currentLength >= 120 && currentLength <= 160) {
                charCountSpan.style.color = '#28a745'; // Yeşil
            } else if (currentLength > 0 && currentLength < 120) {
                charCountSpan.style.color = '#ffc107'; // Sarı
            } else if (currentLength > 160) {
                charCountSpan.style.color = '#dc3545'; // Kırmızı
            }
        }
    }
    
    function updateSeoTitleCount() {
        if (seoTitleInput && seoTitleCountSpan) {
            const length = seoTitleInput.value.length;
            seoTitleCountSpan.textContent = length;
            
            if (length >= 30 && length <= 60) {
                seoTitleCountSpan.style.color = '#28a745';
            } else if (length > 0) {
                seoTitleCountSpan.style.color = '#ffc107';
            }
        }
    }
    
    if (metaDescTextarea && charCountSpan) {
        updateCharCount();
        metaDescTextarea.addEventListener('input', updateCharCount);
        metaDescTextarea.addEventListener('paste', function() {
            setTimeout(updateCharCount, 10);
        });
    }
    
    if (seoTitleInput && seoTitleCountSpan) {
        updateSeoTitleCount();
        seoTitleInput.addEventListener('input', updateSeoTitleCount);
    }
    
    // AI ile SEO Oluştur butonu
    const generateSeoBtn = document.getElementById('generateSeoBtn');
    if (generateSeoBtn) {
        generateSeoBtn.addEventListener('click', function() {
            const title = document.getElementById('title').value;
            const content = tinymce.get('content') ? tinymce.get('content').getContent({format: 'text'}) : '';
            const focusKeyword = document.getElementById('focus_keyword')?.value || '';
            
            if (!title) {
                alert('Lütfen önce bir başlık girin.');
                return;
            }
            
            generateSeoBtn.disabled = true;
            generateSeoBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Oluşturuluyor...';
            
            fetch('ajax/generate_seo_content.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    title: title,
                    content: content.substring(0, 2000),
                    focus_keyword: focusKeyword,
                    type: 'page'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.seo_title) document.getElementById('seo_title').value = data.seo_title;
                    if (data.meta_description) document.getElementById('meta_description').value = data.meta_description;
                    if (data.meta_keywords) document.getElementById('meta_keywords').value = data.meta_keywords;
                    if (data.og_title) document.getElementById('og_title').value = data.og_title;
                    if (data.og_description) document.getElementById('og_description').value = data.og_description;
                    if (data.focus_keyword && !focusKeyword) document.getElementById('focus_keyword').value = data.focus_keyword;
                    
                    updateCharCount();
                    updateSeoTitleCount();
                    alert('SEO içerikleri başarıyla oluşturuldu!');
                } else {
                    alert('Hata: ' + (data.message || 'SEO içeriği oluşturulamadı.'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Bir hata oluştu. Lütfen tekrar deneyin.');
            })
            .finally(() => {
                generateSeoBtn.disabled = false;
                generateSeoBtn.innerHTML = '<i class="bx bx-magic-wand me-1"></i> AI ile SEO İçeriği Oluştur';
            });
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 