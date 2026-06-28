<?php
// Session ve header bilgilerini en başta başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
checkLogin();

$page_title = 'Slayt Düzenle';
$success = false;
$error = null;

// Slayt ID'si varsa düzenleme modu
$slide = null;
if (isset($_GET['id'])) {
    $slide_id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM slides WHERE id = ?");
    $stmt->bind_param("i", $slide_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $slide = $result->fetch_assoc();
    $page_title = 'Slayt Düzenle: ' . $slide['title'];
}

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $subtitle = $_POST['subtitle'];
    $button1_text = $_POST['button1_text'];
    $button1_link = $_POST['button1_link'];
    $button2_text = $_POST['button2_text'];
    $button2_link = $_POST['button2_link'];
    $bg_color = $_POST['bg_color'];
    $order_number = (int)$_POST['order_number'];
    $status = isset($_POST['status']) ? 1 : 0;
    
    // 1. Resim yükleme işlemi
    $image_filename = $slide ? $slide['image'] : null;
    // Medya kütüphanesinden seçildi mi?
    $image_media = trim($_POST['image_media'] ?? '');
    if (!empty($image_media)) {
        $image_filename = 'media/' . ltrim($image_media, '/');
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "../uploads/slides/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file = $_FILES['image'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
        
        if (!in_array($file_ext, $allowed_exts)) {
            $error = "Sadece JPG, JPEG, PNG, WebP ve AVIF formatları desteklenmektedir.";
        } else {
            // Eski resmi sil
            if ($slide && $slide['image']) {
                $old_image_path = $upload_dir . $slide['image'];
                if (file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
                mynak_slide_delete_derivatives($upload_dir, (string) $slide['image']);
            }
            
            // Yeni resim için temel dosya adı oluştur
            $base_filename = uniqid();
            $original_upload_path = $upload_dir . $base_filename . '.' . $file_ext;
            
            // Önce orijinal dosyayı yükle
            if (move_uploaded_file($file['tmp_name'], $original_upload_path)) {
                // Ana dosya adı olarak orijinal uzantıyla kaydet
                $image_filename = $base_filename . '.' . $file_ext;
                
                $img_resource = mynak_slide_load_gd_resource($original_upload_path, $file_ext);
                if ($img_resource) {
                    if (function_exists('imagewebp')) {
                        imagewebp($img_resource, $upload_dir . $base_filename . '.webp', 80);
                    }
                    if (function_exists('imageavif')) {
                        imageavif($img_resource, $upload_dir . $base_filename . '.avif', 80);
                    }
                    mynak_slide_generate_mob_webp($img_resource, $upload_dir, $base_filename);
                    imagedestroy($img_resource);
                }
            } else {
                $error = "Resim yüklenirken bir hata oluştu.";
            }
        }
    }
    
    // 2. Resim yükleme işlemi
    $image2_filename = $slide ? $slide['image2'] : null;
    if (isset($_FILES['image2']) && $_FILES['image2']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "../uploads/slides/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file = $_FILES['image2'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
        
        if (!in_array($file_ext, $allowed_exts)) {
            $error = "2. resim için sadece JPG, JPEG, PNG, WebP ve AVIF formatları desteklenmektedir.";
        } else {
            // Eski 2. resmi sil
            if ($slide && $slide['image2']) {
                $old_image_path = $upload_dir . $slide['image2'];
                if (file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
                mynak_slide_delete_derivatives($upload_dir, (string) $slide['image2']);
            }
            
            // Yeni resim için temel dosya adı oluştur
            $base_filename = uniqid() . '_2';
            $original_upload_path = $upload_dir . $base_filename . '.' . $file_ext;
            
            // Önce orijinal dosyayı yükle
            if (move_uploaded_file($file['tmp_name'], $original_upload_path)) {
                // Ana dosya adı olarak orijinal uzantıyla kaydet
                $image2_filename = $base_filename . '.' . $file_ext;
                
                $img_resource = mynak_slide_load_gd_resource($original_upload_path, $file_ext);
                if ($img_resource) {
                    if (function_exists('imagewebp')) {
                        imagewebp($img_resource, $upload_dir . $base_filename . '.webp', 80);
                    }
                    if (function_exists('imageavif')) {
                        imageavif($img_resource, $upload_dir . $base_filename . '.avif', 80);
                    }
                    mynak_slide_generate_mob_webp($img_resource, $upload_dir, $base_filename);
                    imagedestroy($img_resource);
                }
            } else {
                $error = "2. resim yüklenirken bir hata oluştu.";
            }
        }
    }
    
    if (!isset($error)) {
        if ($slide) {
            // Güncelleme
            $stmt = $conn->prepare("UPDATE slides SET title = ?, subtitle = ?, button1_text = ?, button1_link = ?, button2_text = ?, button2_link = ?, bg_color = ?, order_number = ?, status = ?, image = ?, image2 = ? WHERE id = ?");
            $stmt->bind_param("ssssssssissi", $title, $subtitle, $button1_text, $button1_link, $button2_text, $button2_link, $bg_color, $order_number, $status, $image_filename, $image2_filename, $slide_id);
        } else {
            // Yeni ekleme
            $stmt = $conn->prepare("INSERT INTO slides (title, subtitle, button1_text, button1_link, button2_text, button2_link, bg_color, order_number, status, image, image2) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssiss", $title, $subtitle, $button1_text, $button1_link, $button2_text, $button2_link, $bg_color, $order_number, $status, $image_filename, $image2_filename);
        }
        
        if ($stmt->execute()) {
            echo "<script>window.location.href = 'slides.php';</script>";
            exit;
        } else {
            $error = "Slayt kaydedilirken bir hata oluştu: " . $stmt->error;
        }
    }
}

require_once 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><?php echo $slide ? 'Slayt Düzenle' : 'Yeni Slayt Ekle'; ?></h5>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="title" class="form-label">Başlık</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo $slide ? htmlspecialchars($slide['title']) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="subtitle" class="form-label">Alt Başlık</label>
                        <textarea class="form-control" id="subtitle" name="subtitle" rows="3"><?php echo $slide ? htmlspecialchars($slide['subtitle']) : ''; ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="button1_text" class="form-label">1. Buton Metni</label>
                        <input type="text" class="form-control" id="button1_text" name="button1_text" value="<?php echo $slide ? htmlspecialchars($slide['button1_text']) : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="button1_link" class="form-label">1. Buton Linki</label>
                        <input type="text" class="form-control" id="button1_link" name="button1_link" value="<?php echo $slide ? htmlspecialchars($slide['button1_link']) : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="button2_text" class="form-label">2. Buton Metni</label>
                        <input type="text" class="form-control" id="button2_text" name="button2_text" value="<?php echo $slide ? htmlspecialchars($slide['button2_text']) : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="button2_link" class="form-label">2. Buton Linki</label>
                        <input type="text" class="form-control" id="button2_link" name="button2_link" value="<?php echo $slide ? htmlspecialchars($slide['button2_link']) : ''; ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="image" class="form-label">1. Görsel (Ana Görsel)</label>
                        <?php if ($slide && $slide['image']): ?>
                            <div class="mb-2">
                                <picture>
                                    <?php
                                    // Ana görsel dosyasının yolu
                                    $image_filename = pathinfo($slide['image'], PATHINFO_FILENAME);
                                    $image_ext = strtolower(pathinfo($slide['image'], PATHINFO_EXTENSION));
                                    
                                    // AVIF, WebP ve orijinal görsel yolları
                                    $avif_path = "../uploads/slides/" . $image_filename . ".avif";
                                    $webp_path = "../uploads/slides/" . $image_filename . ".webp";
                                    $original_path = "../uploads/slides/" . $slide['image'];
                                    
                                    // Önce AVIF dene, sonra WebP, en son orijinal görseli göster
                                    if (file_exists($avif_path)): ?>
                                        <source srcset="<?php echo $avif_path; ?>" type="image/avif">
                                    <?php endif; ?>
                                    
                                    <?php if (file_exists($webp_path)): ?>
                                        <source srcset="<?php echo $webp_path; ?>" type="image/webp">
                                    <?php endif; ?>
                                    
                                    <img src="<?php echo $original_path; ?>" 
                                         alt="Mevcut görsel" 
                                         style="max-width: 200px; height: auto;">
                                </picture>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="file" class="form-control" id="image" name="image" accept=".jpg,.jpeg,.png,.webp,.avif" <?php echo $slide ? '' : ''; ?>>
                            <button type="button" class="btn btn-outline-primary text-nowrap btn-media-pick" data-target="image" data-preview="slide-img-preview">
                                <i class="bx bx-images me-1"></i> Medya Kütüphanesi
                            </button>
                        </div>
                        <input type="hidden" id="image_media" name="image_media" value="">
                        <div id="slide-img-preview" class="mt-2" style="display:none;">
                            <img src="" alt="" style="max-height:120px; border-radius:6px; border:2px solid #0d6efd;">
                            <p class="text-success small mt-1 mb-0" id="slide-img-preview-name"></p>
                        </div>
                        <small class="form-text text-muted">Desteklenen formatlar: JPG, JPEG, PNG, WebP, AVIF (Maksimum dosya boyutu: 5MB)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image2" class="form-label">2. Görsel (Sağdan Gelen Resim)</label>
                        <?php if ($slide && $slide['image2']): ?>
                            <div class="mb-2">
                                <picture>
                                    <?php
                                    // 2. görsel dosyasının yolu
                                    $image2_filename = pathinfo($slide['image2'], PATHINFO_FILENAME);
                                    $image2_ext = strtolower(pathinfo($slide['image2'], PATHINFO_EXTENSION));
                                    
                                    // AVIF, WebP ve orijinal görsel yolları
                                    $avif2_path = "../uploads/slides/" . $image2_filename . ".avif";
                                    $webp2_path = "../uploads/slides/" . $image2_filename . ".webp";
                                    $original2_path = "../uploads/slides/" . $slide['image2'];
                                    
                                    // Önce AVIF dene, sonra WebP, en son orijinal görseli göster
                                    if (file_exists($avif2_path)): ?>
                                        <source srcset="<?php echo $avif2_path; ?>" type="image/avif">
                                    <?php endif; ?>
                                    
                                    <?php if (file_exists($webp2_path)): ?>
                                        <source srcset="<?php echo $webp2_path; ?>" type="image/webp">
                                    <?php endif; ?>
                                    
                                    <img src="<?php echo $original2_path; ?>" 
                                         alt="Mevcut 2. görsel" 
                                         style="max-width: 200px; height: auto;">
                                </picture>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="image2" name="image2" accept=".jpg,.jpeg,.png,.webp,.avif">
                        <small class="form-text text-muted">Sağdan gelen animasyonlu resim için kullanılır. Desteklenen formatlar: JPG, JPEG, PNG, WebP, AVIF</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bg_color" class="form-label">Arkaplan Rengi</label>
                        <input type="color" class="form-control form-control-color" id="bg_color" name="bg_color" value="<?php echo $slide ? htmlspecialchars($slide['bg_color']) : '#ffffff'; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="order_number" class="form-label">Sıra Numarası</label>
                        <input type="number" class="form-control" id="order_number" name="order_number" value="<?php echo $slide ? (int)$slide['order_number'] : 0; ?>" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="status" name="status" <?php echo $slide && $slide['status'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="status">Aktif</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-end">
                <a href="slides.php" class="btn btn-secondary">İptal</a>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
// Medya Kütüphanesi — Slayt Resim Seçici
document.querySelectorAll('.btn-media-pick').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var targetId  = this.getAttribute('data-target');
        var previewId = this.getAttribute('data-preview');
        MediaPicker.open(function(item) {
            document.getElementById(targetId + '_media').value = item.filename;
            document.getElementById(targetId).value = '';
            var preview = document.getElementById(previewId);
            if (preview) {
                preview.style.display = 'block';
                preview.querySelector('img').src = item.url;
                var nameEl = document.getElementById(previewId + '-name');
                if (nameEl) nameEl.textContent = '✓ ' + item.original_name;
            }
        });
    });
});
document.getElementById('image').addEventListener('change', function() {
    if (this.files.length > 0) document.getElementById('image_media').value = '';
});
</script>

<?php require_once 'includes/footer.php'; ?> 