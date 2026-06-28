<?php
require_once 'includes/header.php';
require_once '../config/db.php';
$success = false;
$blok_adi = '';
$gosterim_tipi = 'son';
$kategori_id = '';
$yazi_sayisi = 3;
$error = '';
// Kategorileri getir (durum sütunu yoksa kaldırıldı)
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
    $stmt = $conn->prepare("INSERT INTO blog_blocks (blok_adi, gosterim_tipi, kategori_id, yazi_sayisi) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssii', $blok_adi, $gosterim_tipi, $kategori_id, $yazi_sayisi);
    if ($stmt->execute()) {
        $success = true;
        $blok_adi = '';
        $gosterim_tipi = 'son';
        $kategori_id = '';
        $yazi_sayisi = 3;
    }
}
?>
<div class="card mt-4" style="max-width:700px;margin:auto;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Yeni Blog Blok Ekle</h5>
        <a href="modules_blog_block.php" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Geri Dön</a>
    </div>
    <div class="card-body">
        <?php if ($success): ?>
            <div class="alert alert-success">Kısa Kod: <code>[blok:blog id=<?php echo $conn->insert_id; ?>]</code></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post" class="needs-validation" novalidate>
            <div class="mb-3">
                <label class="form-label">Blok Adı</label>
                <input type="text" name="blok_adi" class="form-control" required>
                <div class="invalid-feedback">Blok adı gereklidir.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Gösterim Tipi</label>
                <select name="gosterim_tipi" id="gosterimTipi" class="form-select" required>
                    <option value="son">Son Yazılar</option>
                    <option value="kategori">Kategori Bazlı</option>
                </select>
            </div>
            <div class="mb-3" id="kategoriSecim" style="display:none;">
                <label class="form-label">Kategori Seç</label>
                <select name="kategori_id" class="form-select">
                    <option value="">Kategori Seçiniz</option>
                    <?php foreach($kategori_list as $kat): ?>
                        <option value="<?php echo $kat['id']; ?>"><?php echo htmlspecialchars($kat['ad']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Yazı Sayısı</label>
                <select name="yazi_sayisi" class="form-select">
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="6">6</option>
                    <option value="8">8</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class='bx bx-save'></i> Kaydet</button>
        </form>
    </div>
</div>
<script>
document.getElementById('gosterimTipi').addEventListener('change', function() {
    document.getElementById('kategoriSecim').style.display = this.value === 'kategori' ? 'block' : 'none';
});
</script>
<?php require_once 'includes/footer.php'; ?> 