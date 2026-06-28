<?php
require_once 'includes/header.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
if (!isset($_GET['id'])) { echo '<div class="alert alert-danger">Blok ID bulunamadı.</div>'; exit; }
$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM gallery_slider_blocks WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
if (!$row = $result->fetch_assoc()) {
    echo '<div class="alert alert-danger">Blok bulunamadı.</div>';
    exit;
}
$blok_adi = htmlspecialchars($row['blok_adi']);
$kacli = intval($row['kacli']);
$resimler = json_decode($row['resimler'], true);
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blok_adi = trim($_POST['blok_adi']);
    $kacli = intval($_POST['kacli']);
    $yeni_resimler = $resimler;
    // Silinecek resimler
    if (!empty($_POST['remove_img'])) {
        foreach ($_POST['remove_img'] as $del) {
            if (($key = array_search($del, $yeni_resimler)) !== false) {
                unset($yeni_resimler[$key]);
            }
        }
        $yeni_resimler = array_values($yeni_resimler);
    }
    // Yeni yüklenen resimler
    if (!empty($_FILES['resimler']['name'][0])) {
        $upload_dir = '../uploads/gallery_slider/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        foreach ($_FILES['resimler']['tmp_name'] as $i => $tmp_name) {
            $name = basename($_FILES['resimler']['name'][$i]);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $yeni_ad = 'slider_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            $hedef = $upload_dir . $yeni_ad;
            if (move_uploaded_file($tmp_name, $hedef)) {
                $yeni_resimler[] = 'uploads/gallery_slider/' . $yeni_ad;
            }
        }
    }
    $resimler_json = json_encode(array_values($yeni_resimler), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $stmt2 = $conn->prepare("UPDATE gallery_slider_blocks SET blok_adi=?, kacli=?, resimler=? WHERE id=?");
    $stmt2->bind_param('sisi', $blok_adi, $kacli, $resimler_json, $id);
    if ($stmt2->execute()) {
        $success = true;
        $resimler = array_values($yeni_resimler);
    }
}
?>
<div class="container mt-5" style="max-width:700px;">
    <h2>Galeri Slider Blok Düzenle</h2>
    <?php if ($success): ?><div class="alert alert-success">Blok başarıyla güncellendi.</div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Blok Adı</label>
            <input type="text" name="blok_adi" class="form-control" value="<?php echo htmlspecialchars($blok_adi); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Kaçlı Gösterim?</label>
            <input type="number" name="kacli" class="form-control" value="<?php echo (int)$kacli; ?>" min="1" max="6" required>
            <div class="invalid-feedback">1-6 arası bir sayı girin.</div>
        </div>
        <div class="mb-3">
            <label class="form-label">Mevcut Resimler</label>
            <div class="row g-2">
                <?php foreach($resimler as $img): ?>
                <?php 
                $original_img = $img; // Orijinal yolu sakla
                if (strpos($img, 'uploads/') !== 0) {
                    $img = 'uploads/gallery/' . ltrim($img, '/');
                }
                $display_img = resim_url_duzelt($img);
                ?>
                <div class="col-4 position-relative">
                    <img src="<?php echo $display_img; ?>" class="img-fluid rounded border" style="height:90px;object-fit:cover;">
                    <label class="form-check-label position-absolute top-0 end-0 m-1">
                        <input type="checkbox" name="remove_img[]" value="<?php echo htmlspecialchars($original_img); ?>"> <span class="badge bg-danger">Sil</span>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Yeni Resimler Ekle</label>
            <input type="file" name="resimler[]" class="form-control" multiple accept="image/*">
        </div>
        <button type="submit" class="btn btn-primary">Kaydet</button>
        <a href="modules_gallery_slider.php" class="btn btn-secondary">Geri Dön</a>
    </form>
</div>
<?php require_once 'includes/footer.php'; ?> 