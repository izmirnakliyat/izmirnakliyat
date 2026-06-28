<?php
require_once 'includes/header.php';
require_once '../config/db.php';
$success = false;
$mesaj = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blok_adi = trim($_POST['blok_adi']);
    $yukseklik = intval($_POST['yukseklik']);
    $html_icerik = trim($_POST['html_icerik']);
    $stmt = $conn->prepare("INSERT INTO html_blocks (blok_adi, yukseklik, html_icerik) VALUES (?, ?, ?)");
    $stmt->bind_param('sis', $blok_adi, $yukseklik, $html_icerik);
    if ($stmt->execute()) {
        $success = true;
        $mesaj = '<div class="alert alert-success mt-3">Kısa Kod: <code>[blok:html id=' . $conn->insert_id . ']</code></div>';
    } else {
        $mesaj = '<div class="alert alert-danger mt-3">Kayıt başarısız: ' . $conn->error . '</div>';
    }
}
if (isset($_GET['silme']) && $_GET['silme'] === 'ok') {
    $mesaj = '<div class="alert alert-success">Blok başarıyla silindi.</div>';
} elseif (isset($_GET['silme']) && $_GET['silme'] === 'hata') {
    $mesaj = '<div class="alert alert-danger">Blok silinirken hata oluştu.</div>';
}
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">HTML Blokları</h5>
        <a href="modules_html_add.php" class="btn btn-primary">
            <i class='bx bx-plus'></i> Yeni HTML Blok
        </a>
    </div>
    <div class="card-body">
        <?php if ($mesaj): ?>
            <?php echo $mesaj; ?>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Blok Adı</th>
                        <th>Yükseklik</th>
                        <th>Kısa Kod</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $result = $conn->query("SELECT * FROM html_blocks ORDER BY id DESC");
                    if ($result->num_rows > 0):
                    while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['blok_adi']); ?></td>
                        <td><?php echo $row['yukseklik']; ?>px</td>
                        <td><code>[blok:html id=<?php echo $row['id']; ?>]</code></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary preview-btn me-1" data-id="<?php echo $row['id']; ?>"><i class='bx bx-show'></i></button>
                            <a href="modules_html_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-warning me-1"><i class='bx bx-edit'></i></a>
                            <a href="modules_html_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bu blok silinecek. Emin misiniz?');"><i class='bx bx-trash'></i></a>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="5" class="text-center">Henüz blok eklenmemiş.</td></tr>
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
                <h5 class="modal-title" id="previewModalLabel">HTML Blok Önizleme</h5>
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
        fetch('modules_html_preview.php?id=' + id)
            .then(res => res.text())
            .then(html => { body.innerHTML = html; });
        modal.show();
    });
});
</script>
<?php require_once 'includes/footer.php'; ?> 