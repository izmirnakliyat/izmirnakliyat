<?php
$page_title = "Galeri Düzenle";
require_once 'includes/header.php';

// Yetki kontrolü
if ((!isset($_GET['id']) && !hasPermission('gallery_add')) || (isset($_GET['id']) && !hasPermission('gallery_edit'))) {
    echo "<div class='alert alert-danger'>Bu işlemi gerçekleştirme yetkiniz bulunmamaktadır.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Klasör kontrolü ve oluşturma
$upload_dir = "../uploads/gallery/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Düzenleme için veri çekme
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$gallery = null;

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM gallery WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $gallery = $result->fetch_assoc();
    
    if (!$gallery) {
        echo "<div class='alert alert-danger'>Galeri bulunamadı.</div>";
        require_once 'includes/footer.php';
        exit;
    }
}

// Form işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $status = isset($_POST['status']) ? 1 : 0;
    $order_number = isset($_POST['order_number']) ? (int)$_POST['order_number'] : 0;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $bg_color = isset($_POST['bg_color']) ? trim($_POST['bg_color']) : '#f8f9fa';
    
    $error = '';
    $image_name = $gallery ? $gallery['image'] : '';
    
    // Resim yükleme işlemi
    if (!empty($_FILES['image']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $error = "Sadece JPG, PNG, GIF ve WEBP formatlarında resim yükleyebilirsiniz.";
        } else if ($_FILES['image']['error'] != 0) {
            $error = "Dosya yüklenirken bir hata oluştu. Hata kodu: " . $_FILES['image']['error'];
        } else {
            // Eski resmi sil (eğer düzenleme ise)
            if ($id > 0 && !empty($image_name)) {
                $old_image_path = $upload_dir . $image_name;
                if (file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
            }
            
            // Yeni resmi yükle
            $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = time() . '_' . uniqid() . '.' . $file_ext;
            $target_file = $upload_dir . $image_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                // Resim başarıyla yüklendi
            } else {
                $error = "Dosya yüklenirken bir hata oluştu.";
                $image_name = $gallery ? $gallery['image'] : '';
            }
        }
    } else if ($id === 0) {
        // Yeni eklemede resim zorunlu
        $error = "Lütfen bir resim seçiniz.";
    }
    
    if (empty($error)) {
        if ($id > 0) {
            // Güncelleme
            $stmt = $conn->prepare("UPDATE gallery SET title = ?, image = ?, status = ?, order_number = ?, description = ?, bg_color = ? WHERE id = ?");
            $stmt->bind_param("ssisssi", $title, $image_name, $status, $order_number, $description, $bg_color, $id);
        } else {
            // Yeni ekleme
            $stmt = $conn->prepare("INSERT INTO gallery (title, image, status, order_number, description, bg_color) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssisss", $title, $image_name, $status, $order_number, $description, $bg_color);
        }
        
        if ($stmt->execute()) {
            $success_message = ($id > 0) ? "Galeri başarıyla güncellendi." : "Galeri başarıyla eklendi.";
            echo "<div class='alert alert-success'>$success_message</div>";
            
            // Yeni ekleme ise form alanlarını temizle
            if ($id === 0) {
                $gallery = null;
                // Yönlendirme
                echo "<script>setTimeout(function() { window.location.href = 'gallery.php'; }, 2000);</script>";
            } else {
                // Güncellenmiş veriyi çek
                $stmt = $conn->prepare("SELECT * FROM gallery WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $gallery = $result->fetch_assoc();
            }
        } else {
            echo "<div class='alert alert-danger'>İşlem sırasında bir hata oluştu: " . $conn->error . "</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>$error</div>";
    }
}
?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">
            <?php echo $id > 0 ? 'Galeri Düzenle' : 'Yeni Galeri Ekle'; ?>
        </h6>
        <a href="gallery.php" class="btn btn-sm btn-secondary">
            <i class="bx bx-arrow-back"></i> Geri Dön
        </a>
    </div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="title" class="form-label">Başlık (Opsiyonel)</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($gallery['title'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Resim</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*" <?php echo $id === 0 ? 'required' : ''; ?>>
                        <small class="text-muted">JPG, PNG, GIF veya WEBP formatında bir resim seçin</small>
                        
                        <?php if ($id > 0 && !empty($gallery['image'])): ?>
                        <div class="mt-2">
                            <p>Mevcut Resim:</p>
                            <img src="../uploads/gallery/<?php echo htmlspecialchars($gallery['image']); ?>" alt="Mevcut Resim" style="max-width: 200px; max-height: 200px;">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="order_number" class="form-label">Sıralama</label>
                        <input type="number" class="form-control" id="order_number" name="order_number" value="<?php echo $gallery['order_number'] ?? 0; ?>" min="0">
                        <small class="text-muted">Düşük numaralar önce gösterilir</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bg_color" class="form-label">Arka Plan Rengi</label>
                        <input type="color" class="form-control form-control-color w-100" id="bg_color" name="bg_color" value="<?php echo htmlspecialchars($gallery['bg_color'] ?? '#f8f9fa'); ?>">
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="status" name="status" <?php echo (!isset($gallery['status']) || $gallery['status'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="status">Aktif</label>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Açıklama (Opsiyonel)</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($gallery['description'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bx bx-save"></i> <?php echo $id > 0 ? 'Güncelle' : 'Kaydet'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 