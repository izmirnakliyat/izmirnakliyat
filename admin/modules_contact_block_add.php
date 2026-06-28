<?php
require_once 'includes/header.php';
require_once '../config/db.php';
$success = false;
$blok_adi = '';
$form_baslik = '';
$form_aciklama = '';
$alanlar = ['ad','email','telefon','konu','mesaj'];
$gonder_metni = 'Gönder';
$basarili_mesaji = 'Mesajınız başarıyla gönderildi.';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blok_adi = trim($_POST['blok_adi']);
    $form_baslik = trim($_POST['form_baslik']);
    $form_aciklama = trim($_POST['form_aciklama']);
    $alanlar = isset($_POST['alanlar']) ? $_POST['alanlar'] : [];
    $gonder_metni = trim($_POST['gonder_metni']);
    $basarili_mesaji = trim($_POST['basarili_mesaji']);
    $alanlar_str = implode(',', $alanlar);
    $stmt = $conn->prepare("INSERT INTO contact_blocks (blok_adi, form_baslik, form_aciklama, alanlar, gonder_metni, basarili_mesaji) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssssss', $blok_adi, $form_baslik, $form_aciklama, $alanlar_str, $gonder_metni, $basarili_mesaji);
    if ($stmt->execute()) {
        $success = true;
        $blok_adi = '';
        $form_baslik = '';
        $form_aciklama = '';
        $alanlar = ['ad','email','telefon','konu','mesaj'];
        $gonder_metni = 'Gönder';
        $basarili_mesaji = 'Mesajınız başarıyla gönderildi.';
    }
}
?>
<div class="card mt-4" style="max-width:700px;margin:auto;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Yeni İletişim Blok Ekle</h5>
        <a href="modules_contact_block.php" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Geri Dön</a>
    </div>
    <div class="card-body">
        <?php if ($success): ?>
            <div class="alert alert-success">Kısa Kod: <code>[blok:iletisim id=<?php echo $conn->insert_id; ?>]</code></div>
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
                <label class="form-label">Form Başlığı</label>
                <input type="text" name="form_baslik" class="form-control" required>
                <div class="invalid-feedback">Form başlığı gereklidir.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Form Açıklaması</label>
                <input type="text" name="form_aciklama" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">Alanlar</label><br>
                <?php foreach(['ad','email','telefon','konu','mesaj'] as $alan): ?>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="alanlar[]" value="<?php echo $alan; ?>" id="alan_<?php echo $alan; ?>" checked>
                        <label class="form-check-label" for="alan_<?php echo $alan; ?>"><?php echo ucfirst($alan); ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Gönder Buton Metni</label>
                <input type="text" name="gonder_metni" class="form-control" value="Gönder" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Başarılı Mesajı</label>
                <input type="text" name="basarili_mesaji" class="form-control" value="Mesajınız başarıyla gönderildi.">
            </div>
            <button type="submit" class="btn btn-primary"><i class='bx bx-save'></i> Kaydet</button>
        </form>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?> 