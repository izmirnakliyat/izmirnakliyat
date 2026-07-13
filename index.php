<?php
declare(strict_types=1);
/**
 * Ön uç giriş noktası: bootstrap → config → controller verisi → layout (header) → saf görünüm.
 */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/front_controller.php';
require_once __DIR__ . '/includes/controllers/HomePageController.php';

require_once __DIR__ . '/includes/mynak_wp_legacy_query_redirect.php';
mynak_public_try_blog_detay_legacy_redirect($conn);
mynak_public_try_wp_legacy_query_redirect($conn);

mynak_public_front_controller_maybe_dispatch($conn);

$allow_indexing = true;
extract(mynak_home_index_view_model($conn), EXTR_SKIP);

$page_title = 'İzmir Evden Eve Nakliyat | Sigortalı Taşıma | MY Nakliyat';
$page_meta_description = 'İzmir\'de evden eve ve şehirlerarası nakliyat hizmeti sunuyoruz. Sigortalı, asansörlü profesyonel taşıma ve ücretsiz ekspertiz. Hemen teklif alın.';

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* Hero slider — tam genişlik; görsel akışa girmesin */
.slider-section {
  width: 100%;
  clear: both;
  overflow: hidden;
}
.main-slider {
  width: 100%;
  overflow: hidden;
}
.main-slider:not(.swiper-initialized) .swiper-slide:not(:first-child) {
  display: none;
}
.slider-section .slider-img picture,
.slider-section .slider-img img {
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  max-width: none;
}
.slider-section .slider-img picture {
  display: block;
}
.slider-section .slider-img-wrap {
  overflow: hidden;
}
.slider-content {
    display: flex;
  flex-direction: column;
}
.slider-caption.medium { order: 1; }
.slider-caption.big { order: 2; }
.slider-caption.small { order: 3; }
.slider-btn { order: 4; }
.slider-caption.small .inner-layer div {
  max-width: 800px;
  word-break: break-word;
}
.service-section {
  margin-top: 0px;
}
/* Hizmet kutuları aynı yükseklikte olsun */
.service-section .row {
  align-items: stretch;
  row-gap: 32px;
}
.service-item {
  height: 100%;
  display: flex;
  flex-direction: column;
}
.post-card .post-thumb-link {
    display: block;
    text-decoration: none;
    color: inherit;
}
.post-card .post-content .post-excerpt-link {
    text-decoration: none;
    color: inherit;
    display: block;
}
.post-card .post-content .post-excerpt-link:hover {
    color: inherit;
}
.home-seo-h1 {
    font-size: clamp(1.05rem, 2.4vw, 1.35rem);
    font-weight: 600;
    line-height: 1.35;
    color: #222;
}
.sponsor-section .sponsor-carousel .swiper-slide {
    display: flex;
    align-items: center;
    justify-content: center;
}
.sponsor-section .sponsor-carousel .swiper-slide .sponsor-square-box {
    width: 140px;
    height: 140px;
    background: #fff !important;
    border: 1px solid #eee;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    margin: 0 auto;
    overflow: hidden;
    position: relative;
    z-index: 2;
}
.sponsor-section .sponsor-carousel .swiper-slide .sponsor-square-box img {
    width: auto !important;
    height: auto !important;
    max-width: 90% !important;
    max-height: 90% !important;
    object-fit: contain !important;
    object-position: center !important;
    display: block !important;
    margin: 0 auto !important;
    background: transparent !important;
    box-shadow: none !important;
    border: none !important;
    padding: 0 !important;
}
.mynak-home-videos .mynak-video-card {
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    border: 1px solid #eee;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    height: 100%;
    display: flex;
    flex-direction: column;
}
.mynak-home-videos .mynak-video-skeleton {
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 1rem;
    color: #888;
    font-size: 0.9rem;
    background: linear-gradient(135deg, #f0f0f0 0%, #e8e8e8 100%);
}
.mynak-home-videos .mynak-video-meta {
    padding: 1rem 1rem 1.25rem;
    flex: 1;
}
.mynak-home-videos .mynak-video-meta h3 {
    font-size: 1.05rem;
    margin-bottom: 0.35rem;
    color: #222;
}
/* Kayan yazı: tek satır, klon üst üste binmesin (common-style.min yedek) */
.running-text .scroller {
    overflow: hidden;
    width: 100%;
}
.running-text .scroller__inner {
    flex-wrap: nowrap !important;
    align-items: center;
    width: max-content;
    gap: 60px;
    min-height: 1.5em;
}
.running-text .scroller__inner > li {
    flex: 0 0 auto;
}
.running-text .scroller[data-animated="true"] .scroller__inner {
    animation: mynak-scroll var(--_animation-duration, 60s) linear infinite;
}
.running-text .scroller[data-speed="slow"] {
    --_animation-duration: 60s;
}
@keyframes mynak-scroll {
    to { transform: translate(calc(-50% - 0.5rem)); }
}
/* Proje / galeri bölümü — kapak görseli tam genişlik (main.min.css'te img kuralı eksikti) */
.project-section .bg-half {
  min-height: 500px;
  overflow: hidden;
}
.project-section .bg-half img {
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  max-width: none;
  object-fit: cover;
  object-position: center;
}
.project-section .container {
  position: relative;
  z-index: 1;
}
</style>

<main id="content">

<div class="container">
    <h1 class="home-seo-h1 text-center py-3 mb-0"><?php echo htmlspecialchars('İzmir Profesyonel Evden Eve Nakliyat & Şehirler Arası Taşımacılık | MY Nakliyat ®', ENT_QUOTES, 'UTF-8'); ?></h1>
</div>

<div class="slider-section">
    <div class="main-slider">
        <div class="swiper-wrapper">
            <?php
            if ($hero_slides !== []):
                $slide_index = 0;
                foreach ($hero_slides as $slide):
                    $is_first_hero_slide = ($slide_index === 0);
                    $is_second_hero_slide = ($slide_index === 1);
                    $slide_index++;
            ?>
            <div class="swiper-slide">
                <div class="slider-img-wrap">
                    <div class="slider-img">
                        <?php
                            $image_basename = pathinfo($slide['image'], PATHINFO_FILENAME);
                            $slide_dir = mynak_slide_detect_upload_dir((string) $slide['image']);
                            $img_src = mynak_slide_upload_public_path($slide_dir, (string) $slide['image']);
                        ?>
                        <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars(mynak_public_image_alt((string) ($slide['title'] ?? ''), '', $img_src, 'İzmir evden eve nakliyat')); ?>"
                            sizes="100vw"<?php
                        if ($is_first_hero_slide) {
                            echo ' fetchpriority="high" decoding="async" loading="eager"';
                        } elseif ($is_second_hero_slide) {
                            echo ' loading="eager" decoding="async"';
                        } else {
                            echo ' loading="lazy" decoding="async"';
                        }
                        ?>>
                    </div>
                    <?php
                        $truck_bg_style = '';
                        if (!empty($slide['image2'])) {
                            $truck_dir = 'slides/';
                            if (file_exists(__DIR__ . '/uploads/slides/' . $slide['image2'])) {
                                $truck_dir = 'slides/';
                            } elseif (file_exists(__DIR__ . '/uploads/blog/' . $slide['image2'])) {
                                $truck_dir = 'blog/';
                            }
                            if (file_exists(__DIR__ . '/uploads/' . $truck_dir . $slide['image2'])) {
                                $truck_bg_style = "background-image: url('uploads/{$truck_dir}" . htmlspecialchars($slide['image2']) . "');";
                            }
                        }
                    ?>
                    <div class="slider-truck" data-animation="truck-animation-right" data-duration="1.5s" data-delay="0.5s"<?php echo $truck_bg_style ? ' style="' . $truck_bg_style . '"' : ''; ?>></div>
                </div>
                <div class="slider-content-wrap d-flex align-items-center text-left">
                    <div class="container">
                        <div class="slider-content">
                            <div class="slider-caption big">
                                <div class="inner-layer">
                                    <div data-animation="fade-in-bottom" data-delay="0.5s"><?php echo mynak_esc_html((string) ($slide['title'] ?? '')); ?></div>
                                </div>
                            </div>
                            <div class="slider-caption small">
                                <div class="inner-layer">
                                    <div data-animation="fade-in-bottom" data-delay="0.7s" data-duration="1s">
                                        <?php echo !empty($slide['subtitle']) ? mynak_esc_html((string) $slide['subtitle']) : ''; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="slider-btn">
                                <?php if (!empty($slide['button1_text']) && !empty($slide['button1_link'])): ?>
                                    <a href="<?php echo htmlspecialchars($slide['button1_link']); ?>" class="default-btn" data-animation="fade-in-bottom" data-delay="0.9s"><?php echo mynak_esc_html((string) ($slide['button1_text'] ?? '')); ?></a>
                                <?php endif; ?>
                                <?php if (!empty($slide['button2_text']) && !empty($slide['button2_link'])): ?>
                                    <a href="<?php echo htmlspecialchars($slide['button2_link']); ?>" class="default-btn btn-outline" data-animation="fade-in-bottom" data-delay="1.1s"><?php echo mynak_esc_html((string) ($slide['button2_text'] ?? '')); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
        <div class="slider-pagination"></div><!-- Carousel Dots -->
    </div>
</div>
<!--/.slider-section-->

<!-- Mynak Trust Strip — tasarim bozmadan ince guven bandi (E-E-A-T + AI sinyali) -->
<section class="mynak-trust-strip" aria-label="Müşteri güven göstergeleri">
    <div class="container">
        <div class="mynak-trust-strip-inner">
            <a href="<?php echo htmlspecialchars(rtrim((string) SITE_URL, '/') . '/hakkimizda', ENT_QUOTES, 'UTF-8'); ?>" class="mynak-trust-strip-item" title="Güvenilir Marka Ödüllü">
                <i class="bi bi-award-fill" aria-hidden="true"></i>
                <span><strong>Güvenilir Marka Ödüllü</strong> Nakliye Firması</span>
            </a>
            <span class="mynak-trust-strip-sep" aria-hidden="true">•</span>
            <a href="<?php echo htmlspecialchars($home_settings['google_maps_url'] ?? ('https://search.google.com/local/reviews?placeid=' . urlencode((string) ($home_settings['google_place_id'] ?? ''))), ENT_QUOTES, 'UTF-8'); ?>" class="mynak-trust-strip-item" target="_blank" rel="noopener" title="Google'da yorumlar">
                <span class="mynak-trust-strip-stars" aria-label="5 yıldız">
                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                </span>
                <span><strong>5,0</strong> Google • <strong>270+</strong> Yorum</span>
            </a>
            <span class="mynak-trust-strip-sep" aria-hidden="true">•</span>
            <a href="<?php echo htmlspecialchars(rtrim((string) SITE_URL, '/') . '/belgelerimiz', ENT_QUOTES, 'UTF-8'); ?>" class="mynak-trust-strip-item" title="Belgeler ve sigorta">
                <i class="bi bi-shield-check" aria-hidden="true"></i>
                <span><strong>Sigortalı</strong> Taşıma + Yazılı Sözleşme</span>
            </a>
            <span class="mynak-trust-strip-sep" aria-hidden="true">•</span>
            <a href="<?php echo htmlspecialchars(rtrim((string) SITE_URL, '/') . '/sehirler-arasi-nakliyat', ENT_QUOTES, 'UTF-8'); ?>" class="mynak-trust-strip-item" title="Türkiye geneli hizmet">
                <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                <span><strong>İzmir + 81 İl</strong> Hizmet Ağı</span>
            </a>
        </div>
    </div>
</section>
<style>
.mynak-trust-strip {
    background: linear-gradient(90deg, #fffbe6 0%, #fff8d6 50%, #fffbe6 100%);
    border-top: 1px solid #f1d785;
    border-bottom: 1px solid #f1d785;
    padding: 10px 0;
    font-size: 14px;
    line-height: 1.4;
}
.mynak-trust-strip-inner {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 8px 14px;
    color: #4a3a00;
}
.mynak-trust-strip-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #4a3a00;
    text-decoration: none;
    transition: color .15s ease;
}
.mynak-trust-strip-item:hover { color: #1a73e8; }
.mynak-trust-strip-item i { font-size: 16px; color: #e08e00; }
.mynak-trust-strip-stars { color: #f5b400; letter-spacing: 1px; font-size: 13px; }
.mynak-trust-strip-stars i { color: #f5b400; font-size: 13px; }
.mynak-trust-strip-sep { color: #c79a2e; opacity: .55; }
@media (max-width: 768px) {
    .mynak-trust-strip { font-size: 12.5px; padding: 8px 0; }
    .mynak-trust-strip-sep { display: none; }
    .mynak-trust-strip-inner { gap: 6px 10px; }
}
</style>

<?php if (isset($section_content['services'])): ?>
<section class="service-section bg-grey padding">
    <div class="map-pattern"></div>
    <div class="container">
        <div class="section-heading text-center mb-40">
            <p class="sub-heading is-border border-anim mb-2"><?php echo mynak_esc_html((string) ($section_content['services']['sub_heading'] ?? '')); ?><span class="sh-underline"><img class="sh-truck" src="<?php echo htmlspecialchars(seo_asset_url('img/truck.svg')); ?>" <?php echo mynak_sh_truck_img_attrs(); ?>></span></p>
            <h2 class="text-anim" data-effect="fade-in-right" data-split="char" data-delay="0.3" data-duration="1"><?php echo mynak_section_heading_inner_html((string) $section_content['services']['main_heading']); ?></h2>
            <p class="text-anim" data-effect="fade-in-bottom" data-ease="power4.out"><?php echo $section_content['services']['description']; ?></p>
                </div>
        <div class="row gy-lg-0 gy-4">
            <?php if (!empty($services) && is_array($services)): $delay = 100; ?>
                <?php foreach ($services as $service): ?>
                    <div class="col-lg-4 col-md-6">
                        <?php
                        // Kart linki: admin "Hizmetler" bölümündeki yazılı link (services.link) önceliklidir;
                        // boşsa hizmet slug'ından kendi detay sayfasına düşer. İkisi de boşsa kart bağlantısız.
                        $svcLink = trim((string) ($service['link'] ?? ''));
                        $svcSlug = trim((string) ($service['slug'] ?? ''), '/');
                        $svcCardUrl = $svcLink !== ''
                            ? $svcLink
                            : ($svcSlug !== '' ? mynak_abs_url_from_public_path(mynak_public_path($svcSlug)) : '');
                        $svcRel = ($svcLink !== '' && strpos($svcLink, 'http') === 0) ? ' rel="noopener"' : '';
                        ?>
                        <?php if ($svcCardUrl !== ''): ?>
                            <a href="<?php echo htmlspecialchars($svcCardUrl, ENT_QUOTES, 'UTF-8'); ?>" class="service-link-wrapper"<?php echo $svcRel; ?>>
                        <?php endif; ?>
                        <div class="service-item wow fade-in-bottom" data-wow-delay="<?php echo $delay; ?>ms">
                            <div class="service-thumb">
                                <?php if (!empty($service['foto'])): ?>
                                    <img src="<?php echo SITE_URL; ?>/uploads/services/<?php echo htmlspecialchars($service['foto']); ?>" alt="<?php echo htmlspecialchars(mynak_public_image_alt((string) ($service['ana_baslik'] ?? ''), 'İzmir nakliyat hizmeti — MY Nakliyat')); ?>" loading="lazy" decoding="async">
                                <?php endif; ?>
                    </div>
                            <div class="service-content">
                                <h3 class="text-primary fw-bold fs-4"><?php echo mynak_esc_html((string) ($service['ust_baslik'] ?? '')); ?></h3>
                                <div class="service-title2" style="font-weight:600;font-size:18px;margin-bottom:8px;">
                                    <?php echo mynak_esc_html((string) ($service['ana_baslik'] ?? '')); ?>
                    </div>
                                <p><?php echo mynak_esc_html((string) ($service['aciklama'] ?? '')); ?></p>
                </div>
                </div>
                        <?php if ($svcCardUrl !== ''): ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php $delay += 200; endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>
<!--/.service-section-->

<?php if (!empty($running_texts)): ?>
<section class="running-text">
    <div class="container">
        <div class="scroller" data-speed="slow" data-mynak-scroller="running-text">
            <ul class="text-anim scroller__inner" role="list">
                <?php foreach ($running_texts as $text): ?>
                    <li>
                        <span class="running-text__label"><?php echo mynak_esc_html((string) $text); ?></span>
                        <span class="running-text__sep" aria-hidden="true">
                            <img src="<?php echo htmlspecialchars(seo_asset_url('img/truck.svg')); ?>" <?php echo mynak_sh_truck_img_attrs(); ?> decoding="async">
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (isset($home_videos) && is_array($home_videos)): ?>
<?php
if (!function_exists('mynak_video_watch_page_url')) {
    require_once __DIR__ . '/includes/mynak_youtube_video_pages.php';
}
$homeVideoSiteUrl = rtrim((string) SITE_URL, '/');
?>
<section class="mynak-home-videos padding" aria-label="Video rehberleri">
    <div class="container">
        <div class="section-heading text-center mb-40">
            <p class="sub-heading is-border border-anim mb-2">İzleyin<span class="sh-underline"><img class="sh-truck" src="<?php echo htmlspecialchars(seo_asset_url('img/truck.svg')); ?>" <?php echo mynak_sh_truck_img_attrs(); ?>></span></p>
            <h2 class="text-anim">Video rehberleri</h2>
        </div>
        <div class="row g-4">
            <?php
            foreach ($home_videos as $hv):
                $yid = isset($hv['youtube_id']) ? trim((string) $hv['youtube_id']) : '';
                if ($yid === '') {
                    continue;
                }
                $vtitle = isset($hv['name']) && trim((string) $hv['name']) !== ''
                    ? trim((string) $hv['name'])
                    : 'MY Nakliyat Video Rehberi';
                $vdesc = isset($hv['description']) ? trim((string) $hv['description']) : '';
                $embedHtml = mynak_home_youtube_embed_iframe($yid, $vtitle);
                if ($embedHtml === '') {
                    continue;
                }
                $watchHref = mynak_video_watch_page_url($homeVideoSiteUrl, $yid);
            ?>
            <div class="col-md-6 col-lg-4">
                <article class="mynak-video-card">
                    <a href="<?php echo htmlspecialchars($watchHref, ENT_QUOTES, 'UTF-8'); ?>" class="d-block text-decoration-none">
                        <div class="mynak-video-frame">
                            <?php echo $embedHtml; ?>
                        </div>
                    </a>
                    <div class="mynak-video-meta">
                        <h3><a href="<?php echo htmlspecialchars($watchHref, ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none text-dark"><?php echo htmlspecialchars($vtitle, ENT_QUOTES, 'UTF-8'); ?></a></h3>
                        <?php if ($vdesc !== ''): ?>
                            <p class="small text-muted mb-0"><?php echo htmlspecialchars($vdesc, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?php echo htmlspecialchars(mynak_abs_url_from_public_path(mynak_public_path('shorts')), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-primary">
                Kısa videoları izle
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (isset($section_content['projects'])): ?>
<section class="project-section padding">
    <div class="bg-half">
        <?php if ($gallery_cover_image): ?>
            <img src="<?php echo htmlspecialchars($gallery_cover_image); ?>" alt="<?php echo htmlspecialchars(mynak_public_image_alt('', 'İzmir nakliyat projeleri galeri kapak görseli')); ?>" loading="lazy" decoding="async">
                        <?php endif; ?>
                    </div>
    <div class="container">
        <div class="section-heading-wrap mb-40">
            <div class="section-heading white">
                <p class="sub-heading is-border border-anim mb-2"><?php echo mynak_esc_html((string) ($section_content['projects']['sub_heading'] ?? '')); ?><span class="sh-underline"><img class="sh-truck" src="<?php echo htmlspecialchars(seo_asset_url('img/truck.svg')); ?>" <?php echo mynak_sh_truck_img_attrs(); ?>></span></p>
                <h2 class="text-anim" data-effect="fade-in-right" data-split="char" data-delay="0.3" data-duration="1"><?php echo mynak_section_heading_inner_html((string) $section_content['projects']['main_heading']); ?></h2>
                <p class="text-anim" data-effect="fade-in-bottom" data-ease="power4.out"><?php echo $section_content['projects']['description']; ?></p>
                </div>
            <?php if (!empty($section_content['projects']['button_text'])): ?>
                <a href="<?php echo htmlspecialchars($section_content['projects']['button_link']); ?>" class="default-btn wow fade-in-right" data-wow-delay="100ms"><?php echo mynak_esc_html((string) ($section_content['projects']['button_text'] ?? '')); ?></a>
            <?php endif; ?>
            </div>
        <div class="swiper-outside">
            <div class="project-carousel">
                <div class="swiper-wrapper">
                    <?php
                    if ($projects_gallery_images !== []):
                        $delay = 200;
                        foreach ($projects_gallery_images as $image):
                    ?>
                    <div class="swiper-slide">
                        <div class="project-item wow fade-in-bottom" data-wow-delay="<?php echo $delay; ?>ms">
                            <div class="project-thumb project-view">
                                <a class="venobox" href="<?php echo SITE_URL; ?>/uploads/gallery/<?php echo htmlspecialchars($image['image']); ?>" data-gall="projects">
                                    <img src="<?php echo SITE_URL; ?>/uploads/gallery/<?php echo htmlspecialchars($image['image']); ?>" alt="<?php echo htmlspecialchars(mynak_public_image_alt((string) ($image['title'] ?? ''), 'İzmir nakliyat taşıma projesi galeri fotoğrafı')); ?>" loading="lazy" decoding="async">
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php $delay += 200; endforeach; endif; ?>
                </div>
                <div class="carousel-pagination"></div><!-- Carousel Dots -->
            </div>
            <!-- Carousel Arrows -->
            <div class="swiper-nav swiper-next"><i class="fa-regular fa-long-arrow-right"></i></div>
            <div class="swiper-nav swiper-prev"><i class="fa-regular fa-long-arrow-left"></i></div>
        </div>
    </div>
</section>
<?php endif; ?>
<!--/.project-section-->

<!--/.testimonial-section (kaldırıldı, Google yorumlar bölümü kullanılıyor)-->

<?php if ($home_google_reviews !== null):
    $reviews_rows = $home_google_reviews['reviews_rows'];
    $gr_marquee_sec = (int) ($home_google_reviews['gr_marquee_sec'] ?? 60);
    $avatar_colors = $home_google_reviews['avatar_colors'];
    $g_maps_url = $home_google_reviews['g_maps_url'];
    $g_total = $home_google_reviews['g_total'];
?>
<section class="google-reviews-section" id="musteri-yorumlari" aria-label="Müşteri yorumları ve Google puanı">
    <div class="container">
        <div class="gr-header">
            <div class="gr-google-icon">
                <svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
            </div>
            <h2 class="gr-title">Gerçek Harita Yorumlarımız</h2>
        </div>

        <div class="gr-marquee" role="region" aria-label="Google harita müşteri yorumları — kayan bant">
            <div class="gr-marquee-fade" aria-hidden="true"></div>
            <div class="gr-marquee-viewport">
                <div class="gr-marquee-track" style="--gr-marquee-duration: <?= $gr_marquee_sec; ?>s;">
                    <?php foreach ([1, 2] as $gr_marquee_copy): ?>
                    <div class="gr-marquee-group"<?= $gr_marquee_copy === 2 ? ' aria-hidden="true"' : ''; ?>>
                        <?php foreach ($reviews_rows as $i => $gr):
                            $color = $avatar_colors[$i % count($avatar_colors)];
                            $nameParts = explode(' ', trim((string) $gr['author_name']));
                            $initials = mb_strtoupper(mb_substr($nameParts[0] ?? ' ', 0, 1, 'UTF-8'), 'UTF-8');
                            if (isset($nameParts[1])) {
                                $initials .= mb_strtoupper(mb_substr($nameParts[1], 0, 1, 'UTF-8'), 'UTF-8');
                            }
                            $reviewCount = (int) ($gr['review_count'] ?? 0);
                            $isGuide = (int) ($gr['is_local_guide'] ?? 0);
                        ?>
                        <article class="gr-card gr-marquee-item">
                            <div class="gr-card-top">
                                <div class="gr-author">
                                    <div class="gr-avatar" style="background:<?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>;"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="gr-author-info">
                                        <span class="gr-author-name"><?= htmlspecialchars($gr['author_name']) ?></span>
                                        <span class="gr-author-meta">
                                            <?php if ($isGuide): ?>
                                                <i class="fas fa-map-marker-alt" style="color:#4285F4;" aria-hidden="true"></i> Yerel Rehber
                                                <?php if ($reviewCount > 0): ?> · <?= (int) $reviewCount; ?> yorum<?php endif; ?>
                                            <?php elseif ($reviewCount > 0): ?>
                                                <?= (int) $reviewCount; ?> yorum
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="gr-google-badge" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" width="20" height="20"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                                </div>
                            </div>
                            <div class="gr-rating-row">
                                <div class="gr-stars" aria-label="<?= (int) $gr['rating']; ?> / 5 yıldız">
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                        <i class="fas fa-star <?= $s <= (int) $gr['rating'] ? 'gr-star-filled' : 'gr-star-empty' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="gr-time"><?= htmlspecialchars((string) $gr['relative_time'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <p class="gr-text"><?= nl2br(htmlspecialchars((string) mb_strimwidth((string) $gr['text'], 0, 220, '...', 'UTF-8'), ENT_QUOTES, 'UTF-8')); ?></p>
                            <?php
                            $photos = [];
                            if (!empty($gr['review_photos'])) {
                                $photos = json_decode((string) $gr['review_photos'], true) ?: [];
                            }
                            if ($photos !== []):
                            ?>
                            <div class="gr-photos">
                                <?php foreach (array_slice($photos, 0, 3) as $photo): ?>
                                    <img src="<?= htmlspecialchars((string) $photo, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars(mynak_public_image_alt('', 'Google müşteri yorumu fotoğrafı')); ?>" class="gr-photo" loading="lazy" decoding="async" width="90" height="68">
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </article>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="gr-footer">
            <a href="<?= htmlspecialchars($g_maps_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="gr-maps-link">
                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                Haritalarda <?= htmlspecialchars((string) $g_total, ENT_QUOTES, 'UTF-8'); ?>+ Fotoğraflı Yorumu İncele
            </a>
        </div>
    </div>
</section>

<style>
.google-reviews-section {
    padding: 80px 0;
    background: #f8fafb;
}
.gr-header {
    text-align: center;
    margin-bottom: 40px;
}
.gr-google-icon {
    margin-bottom: 12px;
}
.gr-title {
    font-size: 28px;
    font-weight: 700;
    color: #202124;
    margin-bottom: 0;
}
.gr-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 20px;
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: box-shadow 0.3s;
}
.gr-card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
.gr-card-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}
.gr-author {
    display: flex;
    align-items: center;
    gap: 10px;
}
.gr-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 15px;
    flex-shrink: 0;
    letter-spacing: 0.5px;
}
.gr-author-info {
    display: flex;
    flex-direction: column;
    gap: 1px;
}
.gr-author-name {
    font-weight: 600;
    font-size: 14px;
    color: #202124;
    line-height: 1.3;
}
.gr-author-meta {
    font-size: 11px;
    color: #5f6368;
    line-height: 1.3;
}
.gr-google-badge {
    flex-shrink: 0;
    opacity: 0.85;
    margin-top: 2px;
}
.gr-rating-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}
.gr-stars {
    display: flex;
    gap: 1px;
}
.gr-star-filled {
    color: #FBBC05;
    font-size: 13px;
}
.gr-star-empty {
    color: #dadce0;
    font-size: 13px;
}
.gr-time {
    color: #5f6368;
    font-size: 12px;
}
.gr-text {
    color: #3c4043;
    font-size: 13px;
    line-height: 1.6;
    flex-grow: 1;
    margin: 0 0 8px 0;
}
.gr-photos {
    display: flex;
    gap: 6px;
    margin-top: auto;
    padding-top: 8px;
}
.gr-photo {
    width: 90px;
    height: 68px;
    object-fit: cover;
    border-radius: 8px;
    cursor: pointer;
}
.gr-footer {
    text-align: center;
    margin-top: 36px;
}
.gr-maps-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #1a73e8;
    font-weight: 600;
    font-size: 15px;
    text-decoration: none;
    padding: 12px 28px;
    border: 2px solid #1a73e8;
    border-radius: 30px;
    transition: all 0.3s;
}
.gr-maps-link:hover {
    background: #1a73e8;
    color: #fff;
}
.gr-maps-link i {
    color: #EA4335;
}
.gr-maps-link:hover i {
    color: #fff;
}
.gr-marquee {
    position: relative;
    margin: 0 -12px;
}
@media (min-width: 576px) {
    .gr-marquee { margin: 0 -24px; }
}
.gr-marquee-fade {
    pointer-events: none;
    position: absolute;
    inset: 0;
    z-index: 2;
    background: linear-gradient(90deg, #f8fafb 0%, transparent 8%, transparent 92%, #f8fafb 100%);
}
.gr-marquee-viewport {
    overflow: hidden;
    width: 100%;
}
.gr-marquee-track {
    display: flex;
    width: max-content;
    animation: gr-marquee-scroll var(--gr-marquee-duration, 50s) linear infinite;
    will-change: transform;
}
.gr-marquee:hover .gr-marquee-track,
.gr-marquee:focus-within .gr-marquee-track {
    animation-play-state: paused;
}
.gr-marquee-group {
    display: flex;
    flex-direction: row;
    align-items: stretch;
    gap: 20px;
    padding-right: 20px;
    flex-shrink: 0;
}
.gr-marquee-item {
    flex: 0 0 auto;
    width: min(340px, calc(100vw - 56px));
    min-height: 0;
}
@keyframes gr-marquee-scroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
@media (prefers-reduced-motion: reduce) {
    .gr-marquee-track {
        animation: none;
    }
    .gr-marquee-viewport {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scroll-snap-type: x proximity;
        padding-bottom: 8px;
    }
    .gr-marquee-item {
        scroll-snap-align: start;
    }
}
@media (max-width: 768px) {
    .google-reviews-section { padding: 50px 0; }
    .gr-title { font-size: 22px; }
    .gr-photo { width: 70px; height: 52px; }
    .gr-marquee-item { width: min(300px, calc(100vw - 40px)); }
}
</style>
<?php endif; ?>

<?php if ($home_sponsors !== []): ?>
<div class="sponsor-section">
    <div class="container">
        <div class="sponsor-carousel-wrapper">
            <div class="sponsor-carousel">
                <div class="swiper-wrapper">
                    <?php foreach ($home_sponsors as $sponsor): ?>
                    <div class="swiper-slide">
                        <div class="sponsor-square-box">
                        <?php if (!empty($sponsor['link'])): ?>
                            <a href="<?php echo htmlspecialchars($sponsor['link']); ?>" target="_blank">
                                <img src="uploads/sponsors/<?php echo htmlspecialchars($sponsor['image']); ?>" alt="<?php echo htmlspecialchars(mynak_public_image_alt((string) ($sponsor['name'] ?? ''), 'MY Nakliyat referans ve iş ortağı logosu')); ?>" loading="lazy" decoding="async">
                            </a>
                        <?php else: ?>
                            <img src="uploads/sponsors/<?php echo htmlspecialchars($sponsor['image']); ?>" alt="<?php echo htmlspecialchars(mynak_public_image_alt((string) ($sponsor['name'] ?? ''), 'MY Nakliyat referans ve iş ortağı logosu')); ?>" loading="lazy" decoding="async">
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        </div>
    </div>
</div>
<?php endif; ?>
<!--/.sponsor-section-->

<?php if (isset($section_content['blog'])): ?>
<section class="blog-section padding">
    <div class="container">
        <div class="section-heading-wrap mb-40">
            <div class="section-heading">
                <p class="sub-heading is-border border-anim mb-2"><?php echo mynak_esc_html((string) ($section_content['blog']['sub_heading'] ?? '')); ?><span class="sh-underline"><img class="sh-truck" src="<?php echo htmlspecialchars(seo_asset_url('img/truck.svg')); ?>" <?php echo mynak_sh_truck_img_attrs(); ?>></span></p>
                <h2 class="text-anim" data-effect="fade-in-right" data-split="char" data-delay="0.3" data-duration="1"><?php echo mynak_section_heading_inner_html((string) $section_content['blog']['main_heading']); ?></h2>
            </div>
            <?php if (!empty($section_content['blog']['button_text'])): ?>
                <?php
                $blogSectionBtnHref = normalize_internal_link_url($section_content['blog']['button_link']);
                if (function_exists('mynak_href_still_has_filesystem_leak') && mynak_href_still_has_filesystem_leak($blogSectionBtnHref) && function_exists('mynak_blog_href_path')) {
                    $blogSectionBtnHref = mynak_blog_href_path('');
                }
                ?>
                <a href="<?php echo htmlspecialchars($blogSectionBtnHref); ?>" class="default-btn wow fade-in-right" data-wow-delay="100ms"><?php echo mynak_esc_html((string) ($section_content['blog']['button_text'] ?? '')); ?></a>
            <?php endif; ?>
        </div>
        <div class="row gy-lg-0 gy-4">
            <?php
            if ($home_blog_posts !== []):
                $delay = 100;
                foreach ($home_blog_posts as $blog):
                ?>
            <div class="col-lg-4 col-md-6">
                <div class="post-card wow fade-in-bottom" data-wow-delay="<?php echo $delay; ?>ms">
                    <?php $homePostHref = htmlspecialchars(mynak_public_path($blog['slug']), ENT_QUOTES, 'UTF-8'); ?>
                    <a href="<?php echo $homePostHref; ?>" class="post-thumb-link">
                        <div class="post-thumb">
                            <?php if (!empty($blog['kapak_foto'])): ?>
                                <img src="uploads/blog/<?php echo htmlspecialchars($blog['kapak_foto']); ?>" alt="<?php echo mynak_esc_html(mynak_public_image_alt((string) $blog['baslik'], 'MY Nakliyat blog yazısı', 'uploads/blog/' . ($blog['kapak_foto'] ?? ''))); ?>" loading="lazy" decoding="async">
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="post-content-wrap">

                        <ul class="post-meta">
                            <li><i class="fa-regular fa-user"></i><a href="<?php echo htmlspecialchars(normalize_internal_link_url('/blog')); ?>">Admin</a></li>
                            <li><i class="fa-sharp fa-regular fa-bookmark"></i><a href="<?php
                            $katHref = !empty($blog['kategori_slug'])
                                ? '/blog/kategori/' . rawurlencode($blog['kategori_slug']) . '/'
                                : ('blog.php?kategori=' . (int) ($blog['kategori_id'] ?? 0));
                            echo htmlspecialchars(normalize_internal_link_url($katHref));
                            ?>"><?php echo mynak_esc_html((string) ($blog['kategori_adi'] ?? 'Genel')); ?></a></li>
                        </ul>
                        <div class="post-content">
                            <h3><a href="<?php echo $homePostHref; ?>" class="hover"><?php echo mynak_esc_html((string) $blog['baslik']); ?></a></h3>
                            <p><a href="<?php echo $homePostHref; ?>" class="post-excerpt-link"><?php echo mynak_esc_html((string) ($blog['excerpt_plain'] ?? '')); ?></a></p>
                        </div>
                    </div>
                </div>
            </div>
<?php
                $delay += 200;
                endforeach;
            endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>
<!--/.blog-section-->

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Kayan yazı: main.min.js hata verse bile çalışsın
    try {
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.querySelectorAll('.running-text .scroller').forEach(function (scroller) {
                if (scroller.getAttribute('data-animated') === 'true') {
                    return;
                }
                scroller.setAttribute('data-animated', 'true');
                var inner = scroller.querySelector('.scroller__inner');
                if (!inner) {
                    return;
                }
                Array.from(inner.children).forEach(function (item) {
                    if (item.classList.contains('scroller__clone')) {
                        return;
                    }
                    var clone = item.cloneNode(true);
                    clone.setAttribute('aria-hidden', 'true');
                    clone.classList.add('scroller__clone');
                    inner.appendChild(clone);
                });
            });
        }
    } catch (e) {
        console.warn('Inline scroller fix:', e);
    }

    // Slider: eğer Swiper yüklü ama slider init olmadıysa burada yap
    try {
        if (typeof Swiper !== 'undefined') {
            var sliderEl = document.querySelector('.main-slider');
            if (sliderEl && !sliderEl.swiper) {
                var sc = sliderEl.querySelectorAll('.swiper-slide').length;
                new Swiper('.main-slider', {
                    speed: 1500,
                    autoplay: sc > 1 ? { delay: 5000, disableOnInteraction: false } : false,
                    mousewheel: false,
                    loop: sc > 1,
                    effect: 'fade',
                    initialSlide: 0,
                    pagination: { el: '.slider-pagination', clickable: true },
                    navigation: false
                });
            }
        }
    } catch(e) { console.warn('Inline slider fix:', e); }

    // Project carousel: eğer init olmadıysa burada yap
    try {
        if (typeof Swiper !== 'undefined') {
            var projEl = document.querySelector('.project-carousel');
            if (projEl && !projEl.swiper) {
                new Swiper('.project-carousel', {
                    slidesPerView: 3, spaceBetween: 20, loop: true, speed: 400,
                    autoplay: { delay: 4000, disableOnInteraction: false },
                    pagination: { el: '.project-carousel .carousel-pagination', clickable: true },
                    navigation: { nextEl: '.swiper-next', prevEl: '.swiper-prev' },
                    breakpoints: {
                        320: { slidesPerView: 1, spaceBetween: 25 },
                        767: { slidesPerView: 2, spaceBetween: 30 },
                        1024: { slidesPerView: 4 }
                    }
                });
            }
        }
    } catch(e) { console.warn('Inline project carousel fix:', e); }
});
</script>
<?php require_once 'includes/footer.php'; ?>