<?php
// Session ve header bilgilerini en başta başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';
require_once '../config/db.php';
checkLogin();

$page_title = 'Referans/Sponsor Resmi Düzenle';
$success = false;
$error = null;

// Sponsor ID'si varsa düzenleme modu
$sponsor = null;
if (isset($_GET['id'])) {
    $sponsor_id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM sponsors WHERE id = ?");
    $stmt->bind_param("i", $sponsor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sponsor = $result->fetch_assoc();
    $page_title = 'Referans/Sponsor Resmi Düzenle: ' . $sponsor['name'];
}

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $link = $_POST['link'];
    $order_number = (int)$_POST['order_number'];
    $status = isset($_POST['status']) ? 1 : 0;
    
    // Resim yükleme işlemi
    $image_filename = $sponsor ? $sponsor['image'] : null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "../uploads/sponsors/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file = $_FILES['image'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        
        if (!in_array($file_ext, $allowed_exts)) {
            $error = "Sadece JPG, JPEG, PNG, WebP ve SVG formatları desteklenmektedir.";
        } else {
            // Eski resmi sil
            if ($sponsor && $sponsor['image']) {
                $old_image_path = $upload_dir . $sponsor['image'];
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
        if ($sponsor) {
            // Güncelleme
            $stmt = $conn->prepare("UPDATE sponsors SET name = ?, link = ?, order_number = ?, status = ?, image = ? WHERE id = ?");
            $stmt->bind_param("ssiisi", $name, $link, $order_number, $status, $image_filename, $sponsor_id);
        } else {
            // Yeni ekleme
            $stmt = $conn->prepare("INSERT INTO sponsors (name, link, order_number, status, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiis", $name, $link, $order_number, $status, $image_filename);
        }
        
        if ($stmt->execute()) {
            echo "<script>window.location.href = 'sponsors.php';</script>";
            exit;
        } else {
            $error = "Referans kaydedilirken bir hata oluştu: " . $stmt->error;
        }
    }
}

require_once 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><?php echo $sponsor ? 'Referans/Sponsor Resmi Düzenle' : 'Yeni Referans/Sponsor Resmi Ekle'; ?></h5>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="name" class="form-label">Ad *</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $sponsor ? htmlspecialchars($sponsor['name']) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="link" class="form-label">Link <span class="text-muted">(isteğe bağlı)</span></label>
                        <input type="url" class="form-control" id="link" name="link" value="<?php echo $sponsor ? htmlspecialchars($sponsor['link']) : ''; ?>" placeholder="https://example.com">
                        <small class="form-text text-muted">İsteğe bağlı. Resme tıklandığında açılacak link.</small>
                    </div>
                    <div class="mb-3">
                        <label for="order_number" class="form-label">Sıra Numarası</label>
                        <input type="number" class="form-control" id="order_number" name="order_number" value="<?php echo $sponsor ? (int)$sponsor['order_number'] : 0; ?>" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="status" name="status" <?php echo $sponsor && $sponsor['status'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="status">Aktif</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="image" class="form-label">Resim *</label>
                        <?php if ($sponsor && $sponsor['image']): ?>
                            <div class="mb-2">
                                <img src="../uploads/sponsors/<?php echo $sponsor['image']; ?>" 
                                     alt="Mevcut resim" 
                                     style="max-width: 200px; height: auto;">
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="image" name="image" accept=".jpg,.jpeg,.png,.webp,.svg" <?php echo !$sponsor ? 'required' : ''; ?>>
                        <small class="form-text text-muted">Desteklenen formatlar: JPG, JPEG, PNG, WebP, SVG</small>
                    </div>
                </div>
            </div>
            <div class="text-end">
                <a href="sponsors.php" class="btn btn-secondary">İptal</a>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 