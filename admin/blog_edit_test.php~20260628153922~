<?php
require_once '../includes/functions.php';
require_once '../config/db.php';
require_once 'includes/header.php';

$page_title = "Blog Yazısı Ekle/Düzenle - TEST";
$success_message = '';
$error_message = '';

// Başarı mesajını göster (redirect sonrası)
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = isset($_GET['id']) ? "Blog yazısı başarıyla güncellendi." : "Blog yazısı başarıyla eklendi.";
}

// Blog yazısı düzenleme
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM blog_posts WHERE id = $id");
    $blog = $result->fetch_assoc();
}

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $baslik = trim($_POST['baslik']);
    $icerik = $_POST['icerik'];
    $kategori_id = (int)$_POST['kategori_id'];
    $etiketler = $_POST['etiketler'];
    $durum = isset($_POST['durum']) ? 1 : 0;
    
    // Basit log
    $log_file = __DIR__ . '/../logs/blog_edit_test.log';
    file_put_contents($log_file, "=== TEST: " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
    file_put_contents($log_file, "Başlık: " . $baslik . "\n", FILE_APPEND);
    file_put_contents($log_file, "İçerik uzunluğu: " . strlen($icerik) . "\n", FILE_APPEND);
    
    // Slug işle
    $slug = trim($_POST['slug']);
    if ($slug === '') {
        $slug = slug_olustur($baslik);
    }
    
    if (isset($_GET['id'])) {
        // Güncelleme
        $id = (int)$_GET['id'];
        file_put_contents($log_file, "Güncellenecek ID: " . $id . "\n", FILE_APPEND);
        
        $sql = "UPDATE blog_posts SET baslik = ?, icerik = ?, kategori_id = ?, etiketler = ?, durum = ?, slug = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ssisis", $baslik, $icerik, $kategori_id, $etiketler, $durum, $slug, $id);
            
            if ($stmt->execute()) {
                file_put_contents($log_file, "Güncelleme başarılı!\n", FILE_APPEND);
                $success_message = "Blog yazısı başarıyla güncellendi.";
                
                // Güncel verileri yükle
                $result = $conn->query("SELECT * FROM blog_posts WHERE id = $id");
                $blog = $result->fetch_assoc();
                
                // Redirect
                header("Location: blog_edit_test.php?id=" . $id . "&success=1");
                exit;
            } else {
                file_put_contents($log_file, "Güncelleme hatası: " . $stmt->error . "\n", FILE_APPEND);
                $error_message = "Güncelleme hatası: " . $stmt->error;
            }
        } else {
            file_put_contents($log_file, "SQL hazırlama hatası: " . $conn->error . "\n", FILE_APPEND);
            $error_message = "SQL hatası: " . $conn->error;
        }
    }
    
    file_put_contents($log_file, "=== TEST TAMAMLANDI ===\n\n", FILE_APPEND);
}
?>

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
            <h6 class="m-0 font-weight-bold text-primary"><?php echo isset($_GET['id']) ? 'Blog Yazısını Düzenle - TEST' : 'Yeni Blog Yazısı Ekle - TEST'; ?></h6>
            <a href="blog_posts.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Listeye Dön
            </a>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group mb-3">
                    <label for="baslik" class="form-label">Başlık</label>
                    <input type="text" class="form-control" id="baslik" name="baslik" value="<?php echo htmlspecialchars($blog['baslik'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group mb-3">
                    <label for="slug" class="form-label">Slug (URL)</label>
                    <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($blog['slug'] ?? ''); ?>" placeholder="Otomatik oluşturulur">
                </div>
                
                <div class="form-group mb-3">
                    <label for="icerik" class="form-label">İçerik</label>
                    <textarea id="icerik" name="icerik" class="form-control" rows="10"><?php echo htmlspecialchars($blog['icerik'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group mb-3">
                    <label for="kategori_id" class="form-label">Kategori</label>
                    <select class="form-control" id="kategori_id" name="kategori_id" required>
                        <option value="">Kategori Seçin</option>
                        <?php
                        $categories = $conn->query("SELECT * FROM blog_categories ORDER BY ad ASC");
                        while ($category = $categories->fetch_assoc()):
                        ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo (isset($blog['kategori_id']) && $blog['kategori_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['ad']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group mb-3">
                    <label for="etiketler" class="form-label">Etiketler (virgülle ayırın)</label>
                    <input type="text" class="form-control" id="etiketler" name="etiketler" value="<?php echo htmlspecialchars($blog['etiketler'] ?? ''); ?>">
                </div>
                
                <div class="form-group mb-3">
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="durum" name="durum" <?php echo (!isset($blog['durum']) || $blog['durum'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="durum">Aktif</label>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Kaydet - TEST
                    </button>
                    <a href="blog_posts.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> İptal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
