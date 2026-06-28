<?php
require_once 'includes/header.php';
require_once '../config/db.php';
if (!isset($_GET['id'])) { echo '<div class="alert alert-danger">Blok ID bulunamadı.</div>'; exit; }
$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM html_blocks WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
if (!$row = $result->fetch_assoc()) {
    echo '<div class="alert alert-danger">Blok bulunamadı.</div>';
    exit;
}
$blok_adi = htmlspecialchars($row['blok_adi']);
$yukseklik = intval($row['yukseklik']);
$html_icerik = $row['html_icerik'];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blok_adi = trim($_POST['blok_adi']);
    $yukseklik = intval($_POST['yukseklik']);
    $html_icerik = trim($_POST['html_icerik']);
    $stmt2 = $conn->prepare("UPDATE html_blocks SET blok_adi=?, yukseklik=?, html_icerik=? WHERE id=?");
    $stmt2->bind_param('sisi', $blok_adi, $yukseklik, $html_icerik, $id);
    if ($stmt2->execute()) {
        $success = true;
    }
}
?>
<div class="card mt-4" style="max-width:700px;margin:auto;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">HTML Blok Düzenle</h5>
        <a href="modules_html_block.php" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Geri Dön</a>
    </div>
    <div class="card-body">
        <?php if ($success): ?>
            <div class="alert alert-success">Blok başarıyla güncellendi.</div>
        <?php endif; ?>
        <form method="post" class="needs-validation" novalidate>
            <div class="mb-3">
                <label class="form-label">Blok Adı</label>
                <input type="text" name="blok_adi" class="form-control" value="<?php echo htmlspecialchars($blok_adi); ?>" required>
                <div class="invalid-feedback">Blok adı gereklidir.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Yükseklik (px)</label>
                <input type="number" name="yukseklik" class="form-control" value="<?php echo (int)$yukseklik; ?>" min="50" max="2000" required>
                <div class="invalid-feedback">Geçerli bir yükseklik girin.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">HTML İçerik</label>
                <textarea name="html_icerik" class="form-control" rows="8" required><?php echo htmlspecialchars($html_icerik); ?></textarea>
                <div class="invalid-feedback">İçerik gereklidir.</div>
            </div>
            <button type="submit" class="btn btn-primary"><i class='bx bx-save'></i> Kaydet</button>
        </form>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?> 