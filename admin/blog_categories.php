<?php
require_once 'includes/header.php';

$page_title = "Blog Kategorileri";
$success = '';
$error = '';

// Hata raporlamayı aktif et
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kategori silme
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Önce bu kategoriye ait blog yazılarını kontrol et
    $check = $conn->query("SELECT COUNT(*) as count FROM blog_posts WHERE kategori_id = $id");
    $count = $check->fetch_assoc()['count'];
    
    if ($count > 0) {
        $error = "Bu kategoriye ait $count adet blog yazısı bulunmaktadır. Önce bu yazıları başka bir kategoriye taşıyın veya silin.";
    } else {
        if ($conn->query("DELETE FROM blog_categories WHERE id = $id")) {
            $success = "Kategori başarıyla silindi.";
        } else {
            $error = "Kategori silinirken bir hata oluştu: " . $conn->error;
        }
    }
}

// Kategori ekleme/düzenleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ad'])) {
    $ad = trim($_POST['ad']);
    
    if (empty($ad)) {
        $error = "Kategori adı boş olamaz.";
    } else {
        // Slug oluştur
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $ad)));
        
        try {
            if (!empty($_POST['id'])) {
                // Güncelleme
                $id = (int)$_POST['id'];
                $stmt = $conn->prepare("UPDATE blog_categories SET ad = ?, slug = ? WHERE id = ?");
                if (!$stmt) {
                    throw new Exception("Prepare statement hatası: " . $conn->error);
                }
                
                $stmt->bind_param("ssi", $ad, $slug, $id);
                if (!$stmt->execute()) {
                    throw new Exception("Execute hatası: " . $stmt->error);
                }
                
                $success = "Kategori başarıyla güncellendi.";
            } else {
                // Yeni ekleme
                $stmt = $conn->prepare("INSERT INTO blog_categories (ad, slug) VALUES (?, ?)");
                if (!$stmt) {
                    throw new Exception("Prepare statement hatası: " . $conn->error);
                }
                
                $stmt->bind_param("ss", $ad, $slug);
                if (!$stmt->execute()) {
                    throw new Exception("Execute hatası: " . $stmt->error);
                }
                
                $success = "Kategori başarıyla eklendi.";
            }
        } catch (Exception $e) {
            $error = "İşlem sırasında bir hata oluştu: " . $e->getMessage();
        }
    }
}

// Kategorileri getir
$categories = $conn->query("SELECT c.*, COUNT(p.id) as post_count 
                          FROM blog_categories c 
                          LEFT JOIN blog_posts p ON c.id = p.kategori_id 
                          GROUP BY c.id 
                          ORDER BY c.id DESC");
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Blog Kategorileri</h5>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
            <i class='bx bx-plus'></i> Yeni Kategori Ekle
        </button>
    </div>
    <div class="card-body">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kategori Adı</th>
                        <th>Slug</th>
                        <th>Yazı Sayısı</th>
                        <th>Oluşturulma Tarihi</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($categories->num_rows > 0): ?>
                        <?php while ($category = $categories->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td><?php echo htmlspecialchars($category['ad']); ?></td>
                                <td><?php echo htmlspecialchars($category['slug']); ?></td>
                                <td><?php echo $category['post_count']; ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($category['created_at'])); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary edit-category" 
                                            data-id="<?php echo $category['id']; ?>"
                                            data-ad="<?php echo htmlspecialchars($category['ad']); ?>"
                                            data-bs-toggle="modal" data-bs-target="#categoryModal">
                                        <i class='bx bx-edit'></i>
                                    </button>
                                    <?php if ($category['post_count'] == 0): ?>
                                        <a href="?delete=<?php echo $category['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu kategoriyi silmek istediğinizden emin misiniz?');">
                                            <i class='bx bx-trash'></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">Henüz kategori eklenmemiş.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Kategori Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="categoryForm">
                <input type="hidden" name="id" id="category_id">
                <div class="modal-header">
                    <h5 class="modal-title">Kategori Ekle/Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="ad">Kategori Adı</label>
                        <input type="text" class="form-control" id="ad" name="ad" required>
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
$(document).ready(function() {
    // Kategori düzenleme
    $(document).on('click', '.edit-category', function() {
        var id = $(this).data('id');
        var ad = $(this).data('ad');
        $('#category_id').val(id);
        $('#ad').val(ad);
    });
    
    // Modal kapandığında formu sıfırla
    $('#categoryModal').on('hidden.bs.modal', function() {
        $('#category_id').val('');
        $('#ad').val('');
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 