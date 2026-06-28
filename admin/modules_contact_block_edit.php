<?php
require_once 'includes/header.php';
require_once '../config/db.php';
if (!isset($_GET['id'])) { echo '<div class="alert alert-danger">Blok ID bulunamadı.</div>'; exit; }
$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM contact_blocks WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
if (!$row = $result->fetch_assoc()) {
    echo '<div class="alert alert-danger">Blok bulunamadı.</div>';
    exit;
}
$blok_adi = htmlspecialchars($row['blok_adi']);
$form_baslik = htmlspecialchars($row['form_baslik']);
$form_aciklama = htmlspecialchars($row['form_aciklama']);
$alanlar = explode(',', $row['alanlar']);
$gonder_metni = htmlspecialchars($row['gonder_metni']);
$basarili_mesaji = htmlspecialchars($row['basarili_mesaji']);
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blok_adi = trim($_POST['blok_adi']);
    $form_baslik = trim($_POST['form_baslik']);
    $form_aciklama = trim($_POST['form_aciklama']);
    $alanlar = isset($_POST['alanlar']) ? $_POST['alanlar'] : [];
    $gonder_metni = trim($_POST['gonder_metni']);
    $basarili_mesaji = trim($_POST['basarili_mesaji']);
    $alanlar_str = implode(',', $alanlar);
    $stmt2 = $conn->prepare("UPDATE contact_blocks SET blok_adi=?, form_baslik=?, form_aciklama=?, alanlar=?, gonder_metni=?, basarili_mesaji=? WHERE id=?");
    $stmt2->bind_param('ssssssi', $blok_adi, $form_baslik, $form_aciklama, $alanlar_str, $gonder_metni, $basarili_mesaji, $id);
    if ($stmt2->execute()) {
        $success = true;
    }
}
?>
<div class="container mt-5" style="max-width:600px;">
    <h2>İletişim Blok Düzenle</h2>
    <?php if ($success): ?><div class="alert alert-success">İletişim blok başarıyla güncellendi.</div><?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Blok Adı</label>
            <input type="text" name="blok_adi" class="form-control" value="<?php echo htmlspecialchars($blok_adi); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Form Başlığı</label>
            <input type="text" name="form_baslik" class="form-control" value="<?php echo htmlspecialchars($form_baslik); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Form Açıklaması</label>
            <textarea name="form_aciklama" class="form-control" rows="3"><?php echo htmlspecialchars($form_aciklama); ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Form Alanları</label><br>
            <?php
            $alan_ops = ['ad'=>'Ad Soyad','email'=>'E-posta','telefon'=>'Telefon','konu'=>'Konu','mesaj'=>'Mesaj'];
            foreach($alan_ops as $key=>$label): ?>
                <div class="form-check form-check-inline">
                    <input type="checkbox" name="alanlar[]" value="<?php echo $key; ?>" class="form-check-input" id="alan_<?php echo $key; ?>" <?php if(in_array($key,$alanlar)) echo 'checked'; ?>>
                    <label class="form-check-label" for="alan_<?php echo $key; ?>"><?php echo $label; ?></label>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mb-3">
            <label class="form-label">Gönder Butonu Metni</label>
            <input type="text" name="gonder_metni" class="form-control" value="<?php echo htmlspecialchars($gonder_metni); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Başarılı Mesajı</label>
            <input type="text" name="basarili_mesaji" class="form-control" value="<?php echo htmlspecialchars($basarili_mesaji); ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Kaydet</button>
        <a href="modules_contact_block.php" class="btn btn-secondary">Geri Dön</a>
    </form>
</div>
<?php require_once 'includes/footer.php'; ?> 