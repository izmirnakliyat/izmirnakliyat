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
?>
<div class="card mt-4" style="max-width:700px;margin:auto;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Yeni HTML Blok Ekle</h5>
        <a href="modules_html_block.php" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Geri Dön</a>
    </div>
    <div class="card-body">
        <?php if ($success): ?>
            <div class="alert alert-success">Kısa Kod: <code>[blok:html id=<?php echo $conn->insert_id; ?>]</code></div>
        <?php endif; ?>
        <?php if ($mesaj): ?>
            <?php echo $mesaj; ?>
        <?php endif; ?>
        <form method="post" class="needs-validation" novalidate>
            <div class="mb-3">
                <label class="form-label">Blok Adı</label>
                <input type="text" name="blok_adi" class="form-control" required>
                <div class="invalid-feedback">Blok adı gereklidir.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Yükseklik (px)</label>
                <input type="number" name="yukseklik" class="form-control" value="300" min="50" max="2000" required>
                <div class="invalid-feedback">Geçerli bir yükseklik girin.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">HTML İçerik</label>
                <textarea name="html_icerik" class="form-control" rows="8" required></textarea>
                <div class="invalid-feedback">İçerik gereklidir.</div>
            </div>
            <button type="submit" class="btn btn-primary"><i class='bx bx-save'></i> Kaydet</button>
        </form>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?> 