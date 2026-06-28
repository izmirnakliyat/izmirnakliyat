<?php
require_once __DIR__ . '/../includes/mynak_pages_id_guard.php';
require_once 'includes/header.php';

$page_title = "Sayfalar";
$success = '';
$error = '';

$repaired = mynak_pages_id_repair_non_positive($conn);
if ($repaired > 0) {
    $success = $repaired . ' bozuk sayfa kaydı (id=0) otomatik onarıldı.';
}

// Silme işlemi
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($id > 0) {
        $stmt = $conn->prepare('DELETE FROM pages WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $success = 'Sayfa başarıyla silindi.';
            } else {
                $error = 'Sayfa silinirken bir hata oluştu: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Durum değiştir
if (isset($_POST['toggle_status'])) {
    $page_id = (int) $_POST['page_id'];
    if ($page_id > 0) {
        $stmt = $conn->prepare("UPDATE pages SET status = IF(status = 1, 0, 1) WHERE id = ?");
        $stmt->bind_param("i", $page_id);
        if ($stmt->execute()) {
            $success = "Durum güncellendi.";
        } else {
            $error = "Durum güncellenirken bir hata oluştu: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Sayfaları getir
$pages = $conn->query("SELECT * FROM pages ORDER BY created_at DESC");
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Sayfalar</h5>
        <a href="page_edit.php" class="btn btn-primary">
            <i class='bx bx-plus'></i> Yeni Sayfa Ekle
        </a>
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
                        <th>Başlık</th>
                        <th>Slug</th>
                        <th>Link</th>
                        <th>Durum</th>
                        <th>Oluşturulma Tarihi</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pages->num_rows > 0): ?>
                        <?php while ($page = $pages->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $page['id']; ?></td>
                                <td><?php echo htmlspecialchars($page['title']); ?></td>
                                <td><?php echo htmlspecialchars($page['slug']); ?></td>
                                <td>
                                    <div class="input-group">
                                        <input type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars(SITE_URL . '/' . $page['slug']); ?>" readonly>
                                        <button class="btn btn-outline-secondary btn-sm copy-link" type="button" data-url="<?php echo htmlspecialchars(SITE_URL . '/' . $page['slug']); ?>">
                                            <i class='bx bx-copy'></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Clean URL formatında</small>
                                </td>
                                <td>
                                    <?php if ((int) $page['status'] === 1): ?>
                                        <span class="badge bg-success">Yayında</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Taslak</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($page['created_at'])); ?></td>
                                <td>
                                    <a href="page_edit.php?id=<?php echo $page['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class='bx bx-edit'></i>
                                    </a>
                                    <a href="?delete=<?php echo $page['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu sayfayı silmek istediğinizden emin misiniz?');">
                                        <i class='bx bx-trash'></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center">Henüz sayfa eklenmemiş.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Link kopyalama butonu
    const copyButtons = document.querySelectorAll('.copy-link');
    
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            
            // Geçici bir textarea oluştur
            const textarea = document.createElement('textarea');
            textarea.value = url;
            document.body.appendChild(textarea);
            
            // Metni seç ve kopyala
            textarea.select();
            document.execCommand('copy');
            
            // Textarea'yı temizle
            document.body.removeChild(textarea);
            
            // Kullanıcıya geri bildirim
            const originalHTML = this.innerHTML;
            this.innerHTML = '<i class="bx bx-check"></i>';
            
            setTimeout(() => {
                this.innerHTML = originalHTML;
            }, 2000);
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 