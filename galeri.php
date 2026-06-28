<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (!isset($site_settings) || !is_array($site_settings)) {
    $site_settings = mynak_site_settings_bootstrap($conn);
}
$page_title = 'Fotoğraf Galerisi' . ' - ' . (!empty($site_settings['site_title']) ? $site_settings['site_title'] : '');

// Sabit değerler tanımlama - ayarlar kısmı kaldırıldı
$gallery_title = "Fotoğraf Galerisi";
$gallery_bg_color = "#f8f9fa";
$gallery_header_height = 200;

$page_meta_description = 'MY Nakliyat fotoğraf galerisi: taşımacılık süreçlerimizi ve hizmet kalitemizi inceleyin.';

$allow_indexing = true;
require_once __DIR__ . '/includes/header.php';

// Galeri fotoğraflarını getir
$gallery_query = "SELECT * FROM gallery WHERE status = 1 ORDER BY order_number ASC, id DESC";
$gallery_result = $conn->query($gallery_query);

$gallery_cover_image = '';
$cover_result = $conn->query("SELECT value FROM settings WHERE name = 'gallery_cover_image' LIMIT 1");
if ($cover_result && $cover_row = $cover_result->fetch_assoc()) {
    $gallery_cover_image = $cover_row['value'];
}
?>

<!-- Galeri Sayfası Başlık Alanı -->
<section class="page-header" style="background-color: <?php echo $gallery_bg_color; ?>; height: <?php echo $gallery_header_height; ?>px; display: flex; align-items: center;<?php if ($gallery_cover_image): ?> background: url('<?php echo $gallery_cover_image; ?>') center center/cover no-repeat;<?php endif; ?>">
    <div class="container">
        <h1><?php echo $gallery_title; ?></h1>
        <div class="breadcrumb">
            <a href="<?php echo htmlspecialchars(mynak_public_path(''), ENT_QUOTES, 'UTF-8'); ?>">Ana Sayfa</a> / <span><?php echo htmlspecialchars($gallery_title); ?></span>
        </div>
    </div>
</section>

<!-- lightGallery CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery@2.7.1/css/lightgallery-bundle.min.css">

<style>
    /* Galeri Grid Stili */
    .gallery-container {
        padding: 40px 0;
    }
    
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr); /* Sabit olarak 4'lü grid */
        grid-gap: 15px;
        margin-bottom: 30px;
    }
    
    .gallery-item {
        overflow: hidden;
        border-radius: 8px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        aspect-ratio: 1/1;
    }
    
    .gallery-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15);
    }
    
    .gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform 0.5s ease;
    }
    
    .gallery-item:hover img {
        transform: scale(1.05);
    }
    
    /* lightGallery Özel Stiller */
    .lg-backdrop {
        background-color: rgba(0, 0, 0, 0.85);
    }
    
    .lg-toolbar, .lg-outer {
        background-color: transparent;
    }
    
    /* Galeri Açıklama Stili */
    .gallery-description {
        max-width: 800px;
        margin: 0 auto;
        padding: 15px;
        background-color: rgba(255, 255, 255, 0.8);
        border-radius: 8px;
    }
    
    .gallery-description p {
        color: #333;
        line-height: 1.6;
        margin-bottom: 0;
    }
    
    /* Responsive Ayarlar */
    @media (max-width: 992px) {
        .gallery-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .gallery-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 576px) {
        .gallery-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<section class="gallery-container">
    <div class="container">
        <div class="gallery-grid" id="lightgallery">
            <?php if ($gallery_result && $gallery_result->num_rows > 0): ?>
                <?php while ($image = $gallery_result->fetch_assoc()): ?>
                    <a class="gallery-item" 
                       href="uploads/gallery/<?php echo $image['image']; ?>"
                       data-lg-size="1600-1600"
                       <?php if(!empty($image['title'])): ?>
                       data-sub-html="<h4><?php echo $image['title']; ?></h4>"
                       <?php endif; ?>>
                        <img src="uploads/gallery/<?php echo htmlspecialchars($image['image'], ENT_QUOTES, 'UTF-8'); ?>" 
                             alt="<?php echo htmlspecialchars(mynak_public_image_alt((string) ($image['title'] ?? ''), '', 'uploads/gallery/' . ($image['image'] ?? ''), $gallery_title)); ?>"
                             loading="lazy" />
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <h3>Henüz galeri fotoğrafı eklenmemiş.</h3>
                    <p>Daha sonra tekrar ziyaret edin.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- lightGallery ve Gerekli Eklentiler -->
<script src="https://cdn.jsdelivr.net/npm/lightgallery@2.7.1/lightgallery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lightgallery@2.7.1/plugins/zoom/lg-zoom.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lightgallery@2.7.1/plugins/thumbnail/lg-thumbnail.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const galleryElement = document.getElementById('lightgallery');
    if (galleryElement) {
        lightGallery(galleryElement, {
            plugins: [lgZoom, lgThumbnail],
            speed: 500,
            download: false,
            counter: true,
            mousewheel: true,
            loop: true,
            mobileSettings: {
                controls: true,
                showCloseIcon: true,
                download: false
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 