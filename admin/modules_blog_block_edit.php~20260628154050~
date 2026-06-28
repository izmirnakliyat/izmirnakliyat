<?php
require_once 'includes/header.php';
require_once '../config/db.php';
if (!isset($_GET['id'])) { echo '<div class="alert alert-danger">Blok ID bulunamadı.</div>'; exit; }
$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM blog_blocks WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
if (!$row = $result->fetch_assoc()) {
    echo '<div class="alert alert-danger">Blok bulunamadı.</div>';
    exit;
}
$blok_adi = htmlspecialchars($row['blok_adi']);
$gosterim_tipi = $row['gosterim_tipi'];
$kategori_id = $row['kategori_id'];
$yazi_sayisi = intval($row['yazi_sayisi']);
$success = false;
// Kategorileri getir
$kategoriler = $conn->query("SELECT id, ad FROM blog_categories ORDER BY ad ASC");
$kategori_list = [];
while($kat = $kategoriler->fetch_assoc()) {
    $kategori_list[] = $kat;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blok_adi = trim($_POST['blok_adi']);
    $gosterim_tipi = $_POST['gosterim_tipi'];
    $kategori_id = $gosterim_tipi == 'kategori' ? intval($_POST['kategori_id']) : null;
    $yazi_sayisi = intval($_POST['yazi_sayisi']);
    $stmt2 = $conn->prepare("UPDATE blog_blocks SET blok_adi=?, gosterim_tipi=?, kategori_id=?, yazi_sayisi=? WHERE id=?");
    $stmt2->bind_param('ssiii', $blok_adi, $gosterim_tipi, $kategori_id, $yazi_sayisi, $id);
    if ($stmt2->execute()) {
        $success = true;
    }
}
?>
<div class="card mt-4" style="max-width:700px;margin:auto;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Blog Blok Düzenle</h5>
        <a href="modules_blog_block.php" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Geri Dön</a>
    </div>
    <div class="card-body">
        <?php if ($success): ?><div class="alert alert-success">Blog blok başarıyla güncellendi.</div><?php endif; ?>
        <form method="post" class="needs-validation" novalidate>
            <div class="mb-3">
                <label class="form-label">Blok Adı</label>
                <input type="text" name="blok_adi" class="form-control" value="<?php echo htmlspecialchars($blok_adi); ?>" required>
                <div class="invalid-feedback">Blok adı gereklidir.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Gösterim Tipi</label>
                <select name="gosterim_tipi" id="gosterimTipi" class="form-select" required>
                    <option value="son" <?php if($gosterim_tipi=='son') echo 'selected'; ?>>Son Yazılar</option>
                    <option value="kategori" <?php if($gosterim_tipi=='kategori') echo 'selected'; ?>>Kategori Bazlı</option>
                </select>
            </div>
            <div class="mb-3" id="kategoriSecim" style="display:<?php echo $gosterim_tipi=='kategori'?'block':'none'; ?>;">
                <label class="form-label">Kategori Seç</label>
                <select name="kategori_id" class="form-select">
                    <option value="">Kategori Seçiniz</option>
                    <?php foreach($kategori_list as $kat): ?>
                        <option value="<?php echo $kat['id']; ?>" <?php if($kategori_id==$kat['id']) echo 'selected'; ?>><?php echo htmlspecialchars($kat['ad']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Yazı Sayısı</label>
                <select name="yazi_sayisi" class="form-select">
                    <option value="3" <?php if($yazi_sayisi==3) echo 'selected'; ?>>3</option>
                    <option value="4" <?php if($yazi_sayisi==4) echo 'selected'; ?>>4</option>
                    <option value="6" <?php if($yazi_sayisi==6) echo 'selected'; ?>>6</option>
                    <option value="8" <?php if($yazi_sayisi==8) echo 'selected'; ?>>8</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class='bx bx-save'></i> Kaydet</button>
            <a href="modules_blog_block.php" class="btn btn-secondary">Geri Dön</a>
        </form>
    </div>
</div>
<script>
document.getElementById('gosterimTipi').addEventListener('change', function() {
    document.getElementById('kategoriSecim').style.display = this.value === 'kategori' ? 'block' : 'none';
});
</script>
<?php require_once 'includes/footer.php'; ?>
