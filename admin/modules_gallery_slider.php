<?php
require_once 'includes/header.php';
require_once '../config/db.php';

$success = '';
$error = '';

// Silme işlemi
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($conn->query("DELETE FROM gallery_slider_blocks WHERE id = $id")) {
        $success = "Galeri blok başarıyla silindi.";
    } else {
        $error = "Galeri blok silinirken bir hata oluştu: " . $conn->error;
    }
}

// Blokları getir
$blocks = $conn->query("SELECT * FROM gallery_slider_blocks ORDER BY created_at DESC");
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Galeri Slider Blokları</h5>
        <a href="modules_gallery_slider_add.php" class="btn btn-primary">
            <i class='bx bx-plus'></i> Yeni Galeri Blok Ekle
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
                        <th>Blok Adı</th>
                        <th>Kaçlı</th>
                        <th>Resimler</th>
                        <th>Kısa Kod</th>
                        <th>Oluşturulma Tarihi</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($blocks->num_rows > 0): ?>
                        <?php while ($block = $blocks->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $block['id']; ?></td>
                                <td><?php echo htmlspecialchars($block['blok_adi']); ?></td>
                                <td><?php echo (int)$block['kacli']; ?></td>
                                <td>
                                    <?php $resimler = json_decode($block['resimler'], true); if ($resimler): ?>
                                        <?php foreach($resimler as $img): ?>
                                            <img src="<?php echo strpos($img, 'uploads/') === 0 ? $img : '../uploads/gallery/' . ltrim($img, '/'); ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:4px;margin-right:2px;">
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td><code>[blok:galeri_slider id=<?php echo $block['id']; ?>]</code></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($block['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary preview-btn me-1" data-id="<?php echo $block['id']; ?>">
                                        <i class='bx bx-show'></i>
                                    </button>
                                    <a href="modules_gallery_slider_edit.php?id=<?php echo $block['id']; ?>" class="btn btn-sm btn-outline-warning me-1">
                                        <i class='bx bx-edit'></i>
                                    </a>
                                    <a href="modules_gallery_slider_delete.php?id=<?php echo $block['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bu blok silinecek. Emin misiniz?');">
                                        <i class='bx bx-trash'></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center">Henüz blok eklenmemiş.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Galeri Blok Önizleme</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body" id="previewModalBody">
                <div class="text-center text-muted">Yükleniyor...</div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.preview-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.getAttribute('data-id');
        var modal = new bootstrap.Modal(document.getElementById('previewModal'));
        var body = document.getElementById('previewModalBody');
        body.innerHTML = '<div class="text-center text-muted">Yükleniyor...</div>';
        fetch('modules_gallery_slider_preview.php?id=' + id)
            .then(res => res.text())
            .then(html => { body.innerHTML = html; });
        modal.show();
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 