<?php
require_once __DIR__ . '/includes/require_admin_web.php';

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
?>
<style>
.contact-block-preview {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.07);
    padding: 40px 20px 30px;
    margin: 0 auto;
    max-width: 600px;
}
.contact-block-title {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 15px;
    text-align: center;
}
.contact-block-desc {
    font-size: 15px;
    color: #666;
    margin-bottom: 25px;
    text-align: center;
}
.contact-form-preview .form-group {
    margin-bottom: 18px;
}
.contact-form-preview label {
    font-weight: 600;
    margin-bottom: 6px;
    display: block;
    font-size: 15px;
    color: #444;
}
.contact-form-preview .form-control {
    width: 100%;
    height: 48px;
    padding: 10px 15px;
    border: 1px solid #e1e5ee;
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s;
    box-sizing: border-box;
}
.contact-form-preview textarea.form-control {
    height: auto;
    resize: vertical;
}
.contact-form-preview .form-control:focus {
    border-color: #0056b3;
    box-shadow: 0 0 0 0.2rem rgba(0, 86, 179, 0.15);
    outline: none;
}
.contact-form-preview .submit-btn {
    padding: 12px 30px;
    font-size: 16px;
    font-weight: 600;
    background: #0056b3;
    color: #fff;
    border: none;
    border-radius: 8px;
    transition: all 0.3s;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
}
.contact-form-preview .submit-btn:hover {
    background: #003d82;
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 86, 179, 0.15);
}
.contact-form-preview .submit-btn i {
    margin-right: 8px;
}
</style>
<div class="contact-block-preview">
    <div class="contact-block-title"><?php echo $form_baslik; ?></div>
    <?php if ($form_aciklama): ?><div class="contact-block-desc"><?php echo $form_aciklama; ?></div><?php endif; ?>
    <form class="contact-form-preview">
        <?php if (in_array('ad', $alanlar)): ?>
        <div class="form-group">
            <label>Ad Soyad</label>
            <input type="text" class="form-control" placeholder="Ad Soyad">
        </div>
        <?php endif; ?>
        <?php if (in_array('email', $alanlar)): ?>
        <div class="form-group">
            <label>E-posta</label>
            <input type="email" class="form-control" placeholder="E-posta">
        </div>
        <?php endif; ?>
        <?php if (in_array('telefon', $alanlar)): ?>
        <div class="form-group">
            <label>Telefon</label>
            <input type="tel" class="form-control" placeholder="Telefon">
        </div>
        <?php endif; ?>
        <?php if (in_array('konu', $alanlar)): ?>
        <div class="form-group">
            <label>Konu</label>
            <input type="text" class="form-control" placeholder="Konu">
        </div>
        <?php endif; ?>
        <?php if (in_array('mesaj', $alanlar)): ?>
        <div class="form-group">
            <label>Mesaj</label>
            <textarea class="form-control" rows="5" placeholder="Mesajınız"></textarea>
        </div>
        <?php endif; ?>
        <button type="button" class="submit-btn"><i class="far fa-paper-plane"></i> <?php echo $gonder_metni; ?></button>
    </form>
</div> 