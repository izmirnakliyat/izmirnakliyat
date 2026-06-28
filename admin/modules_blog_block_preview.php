<?php
require_once __DIR__ . '/includes/require_admin_web.php';

require_once '../includes/functions.php';
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
// Yazıları getir
$sql = "SELECT p.*, c.ad as kategori_adi FROM blog_posts p LEFT JOIN blog_categories c ON p.kategori_id = c.id WHERE p.durum=3";
if ($gosterim_tipi == 'kategori' && $kategori_id) {
    $sql .= " AND p.kategori_id = " . intval($kategori_id);
}
$sql .= " ORDER BY p.created_at DESC LIMIT $yazi_sayisi";
$yazilar = $conn->query($sql);
?>
<style>
.blog-slider-preview {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.07);
    padding: 40px 20px 30px;
    margin: 0 auto;
    max-width: 900px;
}
.blog-slider-title {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 25px;
    text-align: center;
}
.blog-slider-container {
    position: relative;
    overflow: hidden;
}
.blog-slider-row {
    display: flex;
    gap: 24px;
    transition: transform 0.5s cubic-bezier(.77,0,.18,1);
}
.blog-slider-item {
    background: #f8f9fa;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    flex: 0 0 calc(100% / <?php echo $yazi_sayisi; ?> - 16px);
    max-width: calc(100% / <?php echo $yazi_sayisi; ?> - 16px);
    text-align: left;
    padding: 18px 10px 10px;
    transition: box-shadow 0.2s;
    display: flex;
    flex-direction: column;
    min-width: 220px;
}
.blog-slider-item img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.blog-slider-item .blog-category {
    font-size: 13px;
    color: #0056b3;
    font-weight: 600;
    margin-bottom: 4px;
}
.blog-slider-item h3 {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 8px 0;
}
.blog-slider-item p {
    font-size: 14px;
    color: #444;
    margin-bottom: 10px;
}
.blog-slider-item .read-more-link {
    color: #0056b3;
    font-weight: 600;
    text-decoration: none;
    font-size: 14px;
    margin-top: auto;
}
.blog-slider-nav {
    display: flex;
    justify-content: center;
    gap: 16px;
    margin-top: 18px;
}
.blog-slider-nav button {
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
.blog-slider-nav button:hover {
    background: #003d82;
}
@media (max-width: 991px) {
    .blog-slider-item img { height: 80px; }
    .blog-slider-item { padding: 10px 4px 8px; }
}
@media (max-width: 767px) {
    .blog-slider-row { gap: 8px; }
    .blog-slider-item { flex: 0 0 90vw; max-width: 90vw; }
    .blog-slider-item img { height: 60px; }
}
</style>
<div class="blog-slider-preview">
    <div class="blog-slider-title"><?php echo $blok_adi; ?></div>
    <div class="blog-slider-container">
        <div class="blog-slider-row" id="blogSliderRow">
            <?php while($yazi = $yazilar->fetch_assoc()): ?>
                <?php 
                $img = $yazi['kapak_foto'] ?: 'uploads/blog/default.jpg';
                if (strpos($img, 'http') !== 0 && strpos($img, 'uploads/') !== 0) {
                    $img = 'uploads/blog/' . ltrim($img, '/');
                }
                $img = resim_url_duzelt($img);
                ?>
                <div class="blog-slider-item">
                    <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($yazi['baslik']); ?>">
                    <div class="blog-category"><?php echo htmlspecialchars($yazi['kategori_adi'] ?? 'Genel'); ?></div>
                    <h3><?php echo htmlspecialchars($yazi['baslik']); ?></h3>
                    <p><?php echo mb_substr(strip_tags($yazi['icerik']),0,100).'...'; ?></p>
                    <a href="#" class="read-more-link">Devamını Oku</a>
                </div>
            <?php endwhile; ?>
        </div>
        <?php if($yazilar->num_rows > $yazi_sayisi): ?>
        <div class="blog-slider-nav mt-3">
            <button type="button" id="blogPrevBtn" aria-label="Önceki"><i class="fas fa-chevron-left"></i></button>
            <button type="button" id="blogNextBtn" aria-label="Sonraki"><i class="fas fa-chevron-right"></i></button>
        </div>
        <?php endif; ?>
    </div>
</div>
<script>
(function(){
    const row = document.getElementById('blogSliderRow');
    const items = row.querySelectorAll('.blog-slider-item');
    const prev = document.getElementById('blogPrevBtn');
    const next = document.getElementById('blogNextBtn');
    const kacli = <?php echo $yazi_sayisi; ?>;
    let current = 0;
    function updateSlider() {
        const itemWidth = items[0].offsetWidth + 24;
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
    window.addEventListener('resize', updateSlider);
    updateSlider();
})();
</script> 