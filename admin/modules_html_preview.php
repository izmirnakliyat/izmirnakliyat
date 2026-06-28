<?php
require_once __DIR__ . '/includes/require_admin_web.php';

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
?>
<style>
.html-block-preview {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.07);
    padding: 40px 20px 30px;
    margin: 0 auto;
    max-width: 700px;
    min-height: 100px;
}
.html-block-title {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 18px;
    text-align: center;
}
.html-block-content {
    width: 100%;
    min-height: 60px;
    max-height: <?php echo $yukseklik; ?>px;
    overflow: auto;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 18px 12px;
    font-size: 16px;
}
</style>
<div class="html-block-preview">
    <div class="html-block-title"><?php echo $blok_adi; ?></div>
    <div class="html-block-content"><?php echo $html_icerik; ?></div>
</div> 