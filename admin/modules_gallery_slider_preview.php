<?php
require_once __DIR__ . '/includes/require_admin_web.php';

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
$resimler = json_decode($row['resimler'], true);
$kacli = intval($row['kacli']);
$blok_adi = htmlspecialchars($row['blok_adi']);
?>
<style>
.gallery-slider-preview {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.07);
    padding: 40px 20px 30px;
    margin: 0 auto;
    max-width: 900px;
}
.gallery-slider-title {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 25px;
    text-align: center;
}
.gallery-slider-container {
    position: relative;
    overflow: hidden;
}
.gallery-slider-row {
    display: flex;
    gap: 24px;
    transition: transform 0.5s cubic-bezier(.77,0,.18,1);
}
.gallery-slider-item {
    background: #f8f9fa;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    flex: 0 0 calc(100% / <?php echo $kacli; ?> - 16px);
    max-width: calc(100% / <?php echo $kacli; ?> - 16px);
    text-align: center;
    padding: 18px 10px 10px;
    transition: box-shadow 0.2s;
}
.gallery-slider-item img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.gallery-slider-nav {
    display: flex;
    justify-content: center;
    gap: 16px;
    margin-top: 18px;
}
.gallery-slider-nav button {
    background: #0056b3;
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    font-size: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}
.gallery-slider-nav button:hover {
    background: #003d82;
}
@media (max-width: 991px) {
    .gallery-slider-item img { height: 120px; }
    .gallery-slider-item { padding: 10px 4px 8px; }
}
@media (max-width: 767px) {
    .gallery-slider-row { gap: 8px; }
    .gallery-slider-item { flex: 0 0 90vw; max-width: 90vw; }
    .gallery-slider-item img { height: 100px; }
}
</style>
<div class="gallery-slider-preview">
    <div class="gallery-slider-title"><?php echo $blok_adi; ?></div>
    <div class="gallery-slider-container">
        <div class="gallery-slider-row" id="gallerySliderRow">
            <?php foreach($resimler as $img): ?>
                <?php 
                if (strpos($img, 'uploads/') !== 0) {
                    $img = 'uploads/gallery/' . ltrim($img, '/');
                }
                $display_img = resim_url_duzelt($img);
                ?>
                <div class="gallery-slider-item">
                    <img src="<?php echo $display_img; ?>" alt="Galeri Resmi">
                </div>
            <?php endforeach; ?>
        </div>
        <?php if(count($resimler) > $kacli): ?>
        <div class="gallery-slider-nav mt-3">
            <button type="button" id="galleryPrevBtn" aria-label="Önceki"><i class="fas fa-chevron-left"></i></button>
            <button type="button" id="galleryNextBtn" aria-label="Sonraki"><i class="fas fa-chevron-right"></i></button>
        </div>
        <?php endif; ?>
    </div>
</div>
<script>
(function(){
    const row = document.getElementById('gallerySliderRow');
    const items = row.querySelectorAll('.gallery-slider-item');
    const prev = document.getElementById('galleryPrevBtn');
    const next = document.getElementById('galleryNextBtn');
    const kacli = <?php echo $kacli; ?>;
    let current = 0;

    // Oklar sadece varsa slider fonksiyonlarını ekle
    if (prev && next) {
        function updateSlider() {
            const itemWidth = items[0].offsetWidth + 24; // gap
            row.style.transform = 'translateX(-' + (current * itemWidth) + 'px)';
        }

        prev.onclick = function() {
            current = Math.max(current - 1, 0);
            updateSlider();
        };

        next.onclick = function() {
            current = Math.min(current + 1, items.length - kacli);
            updateSlider();
        };

        // Responsive: reset pozisyon
        window.addEventListener('resize', updateSlider);
        updateSlider();
    }
})();
</script> 