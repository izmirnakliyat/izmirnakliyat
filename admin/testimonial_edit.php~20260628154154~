<?php
// Session ve header bilgilerini en başta başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';
require_once '../config/db.php';
checkLogin();

$page_title = 'Müşteri Yorumu Düzenle';
$success = false;
$error = null;

// Yorum ID'si varsa düzenleme modu
$testimonial = null;
if (isset($_GET['id'])) {
    $testimonial_id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM testimonials WHERE id = ?");
    $stmt->bind_param("i", $testimonial_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $testimonial = $result->fetch_assoc();
    $page_title = 'Müşteri Yorumu Düzenle: ' . $testimonial['name'];
}

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $position = $_POST['position'];
    $company = $_POST['company'];
    $content = $_POST['content'];
    $rating = (int)$_POST['rating'];
    $order_number = (int)$_POST['order_number'];
    $status = isset($_POST['status']) ? 1 : 0;
    
    // Resim yükleme işlemi
    $image_filename = $testimonial ? $testimonial['image'] : null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "../uploads/testimonials/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file = $_FILES['image'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (!in_array($file_ext, $allowed_exts)) {
            $error = "Sadece JPG, JPEG, PNG ve WebP formatları desteklenmektedir.";
        } else {
            // Eski resmi sil
            if ($testimonial && $testimonial['image']) {
                $old_image_path = $upload_dir . $testimonial['image'];
                if (file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
            }
            
            // Yeni resim için dosya adı oluştur
            $image_filename = uniqid() . '.' . $file_ext;
            $upload_path = $upload_dir . $image_filename;
            
            if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                $error = "Resim yüklenirken bir hata oluştu.";
            }
        }
    }
    
    if (!isset($error)) {
        if ($testimonial) {
            // Güncelleme
            $stmt = $conn->prepare("UPDATE testimonials SET name = ?, position = ?, company = ?, content = ?, rating = ?, order_number = ?, status = ?, image = ? WHERE id = ?");
            $stmt->bind_param("ssssiiisi", $name, $position, $company, $content, $rating, $order_number, $status, $image_filename, $testimonial_id);
        } else {
            // Yeni ekleme
            $stmt = $conn->prepare("INSERT INTO testimonials (name, position, company, content, rating, order_number, status, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiiis", $name, $position, $company, $content, $rating, $order_number, $status, $image_filename);
        }
        
        if ($stmt->execute()) {
            echo "<script>window.location.href = 'testimonials.php';</script>";
            exit;
        } else {
            $error = "Yorum kaydedilirken bir hata oluştu: " . $stmt->error;
        }
    }
}

require_once 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><?php echo $testimonial ? 'Müşteri Yorumu Düzenle' : 'Yeni Müşteri Yorumu Ekle'; ?></h5>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="name" class="form-label">Ad Soyad *</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $testimonial ? htmlspecialchars($testimonial['name']) : ''; ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="position" class="form-label">Pozisyon</label>
                                <input type="text" class="form-control" id="position" name="position" value="<?php echo $testimonial ? htmlspecialchars($testimonial['position']) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="company" class="form-label">Şirket</label>
                                <input type="text" class="form-control" id="company" name="company" value="<?php echo $testimonial ? htmlspecialchars($testimonial['company']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="content" class="form-label">Yorum *</label>
                        <textarea class="form-control" id="content" name="content" rows="4" required><?php echo $testimonial ? htmlspecialchars($testimonial['content']) : ''; ?></textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="image" class="form-label">Fotoğraf</label>
                        <?php if ($testimonial && $testimonial['image']): ?>
                            <div class="mb-2">
                                <img src="../uploads/testimonials/<?php echo $testimonial['image']; ?>" 
                                     alt="Mevcut fotoğraf" 
                                     style="max-width: 150px; height: auto; border-radius: 50%;">
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">
                        <small class="form-text text-muted">Desteklenen formatlar: JPG, JPEG, PNG, WebP</small>
                    </div>
                    <div class="mb-3">
                        <label for="rating" class="form-label">Puan</label>
                        <select class="form-control" id="rating" name="rating">
                            <option value="1" <?php echo $testimonial && $testimonial['rating'] == 1 ? 'selected' : ''; ?>>1 Yıldız</option>
                            <option value="2" <?php echo $testimonial && $testimonial['rating'] == 2 ? 'selected' : ''; ?>>2 Yıldız</option>
                            <option value="3" <?php echo $testimonial && $testimonial['rating'] == 3 ? 'selected' : ''; ?>>3 Yıldız</option>
                            <option value="4" <?php echo $testimonial && $testimonial['rating'] == 4 ? 'selected' : ''; ?>>4 Yıldız</option>
                            <option value="5" <?php echo $testimonial && $testimonial['rating'] == 5 ? 'selected' : ''; ?>>5 Yıldız</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="order_number" class="form-label">Sıra Numarası</label>
                        <input type="number" class="form-control" id="order_number" name="order_number" value="<?php echo $testimonial ? (int)$testimonial['order_number'] : 0; ?>" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="status" name="status" <?php echo $testimonial && $testimonial['status'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="status">Aktif</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-end">
                <a href="testimonials.php" class="btn btn-secondary">İptal</a>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 