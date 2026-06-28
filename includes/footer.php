<?php
declare(strict_types=1);

require_once __DIR__ . '/pipeline/public_footer_context.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/db.php';
}

$__footer_site_settings = isset($site_settings) && is_array($site_settings) ? $site_settings : [];
extract(mynak_public_footer_context($conn, $__footer_site_settings), EXTR_OVERWRITE);
unset($__footer_site_settings);

$site_settings = isset($site_settings) && is_array($site_settings) ? $site_settings : [];

$footer_map_lazy = '';
if (!empty($footer_map) && is_string($footer_map)) {
    $footer_map_lazy = preg_replace_callback('/<iframe\b[^>]*>/i', static function (array $matches): string {
        $iframeTag = $matches[0];
        if (stripos($iframeTag, 'src=') === false) {
            return $iframeTag;
        }

        $iframeTag = preg_replace(
            '/\s+src=(["\'])(.*?)\1/i',
            ' data-src=$1$2$1 src=$1about:blank$1',
            $iframeTag,
            1
        ) ?? $iframeTag;

        if (stripos($iframeTag, 'data-lazy-map=') === false) {
            $iframeTag = rtrim($iframeTag, '>') . ' data-lazy-map="1">';
        }

        return $iframeTag;
    }, $footer_map, 1) ?? $footer_map;
}
?>
<style>
.footer-map-widget .footer-map-container {
    height: 200px;
    min-height: 200px;
}
.footer-map-widget .footer-map-container iframe {
    width: 100%;
    height: 100%;
    border-radius: 8px;
    border: 0;
    display: block;
}
@media (max-width: 991px) {
    .footer-map-widget .footer-map-container {
        height: 220px;
        min-height: 220px;
    }
    .footer-map-widget .footer-map-container iframe {
        height: 100%;
    }
}
</style>
<footer class="footer-section">
    <div class="map-pattern"></div>
    <div class="footer-wrapper">
        <div class="container">
            <div class="row gy-lg-0 gy-4">
                <div class="col-lg-2 col-md-6">
                    <div class="footer-widget">
                        <a href="<?php echo htmlspecialchars(mynak_public_path(''), ENT_QUOTES, 'UTF-8'); ?>" class="footer-logo">
                            <?php if (!empty($site_settings['logo_dark'])): ?>
                                <img src="<?php echo UPLOAD_PATH; ?>settings/<?php echo htmlspecialchars($site_settings['logo_dark']); ?>"
                                    <?php echo mynak_site_logo_dimension_attrs($site_settings, 'footer'); ?>
                                    <?php echo mynak_img_alt_attr('', function_exists('mynak_logo_alt_text') ? mynak_logo_alt_text() : (SITE_NAME . ' Logo'), UPLOAD_PATH . 'settings/' . ($site_settings['logo_dark'] ?? '')); ?> loading="lazy" decoding="async">
                            <?php else: ?>
                                <img src="<?php echo UPLOAD_PATH; ?>settings/<?php echo htmlspecialchars($site_settings['logo'] ?? 'logo.png'); ?>"
                                    <?php echo mynak_site_logo_dimension_attrs($site_settings, 'footer'); ?>
                                    <?php echo mynak_img_alt_attr('', function_exists('mynak_logo_alt_text') ? mynak_logo_alt_text() : ((string) ($site_settings['site_title'] ?? 'MY Nakliyat') . ' Logo'), UPLOAD_PATH . 'settings/' . ($site_settings['logo'] ?? 'logo.png')); ?> loading="lazy" decoding="async">
                            <?php endif; ?>
                        </a>
                        <p><?php echo !empty($site_settings['short_description']) ? htmlspecialchars($site_settings['short_description']) : 'MY Nakliyat ® Evden eve nakliyat, Ofis taşıma, Eşya Depolama, Parça eşya taşıma & Şehirler arası nakliyatı sağlayan Güvenilir Marka ödüllü İzmir nakliyat firmasıdır.'; ?>
                        </p>
                        <ul class="social-share">
                            <?php if (!empty($site_settings['facebook'])): ?>
                                <li><a href="https://facebook.com/<?php echo htmlspecialchars($site_settings['facebook']); ?>"
                                        target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-facebook-f"></i></a></li>
                            <?php endif; ?>
                            <?php if (!empty($site_settings['twitter'])): ?>
                                <li><a href="https://twitter.com/<?php echo htmlspecialchars($site_settings['twitter']); ?>"
                                        target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-x-twitter"></i></a></li>
                            <?php endif; ?>
                            <?php if (!empty($site_settings['instagram'])): ?>
                                <li><a href="https://instagram.com/<?php echo htmlspecialchars($site_settings['instagram']); ?>"
                                        target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-instagram"></i></a></li>
                            <?php endif; ?>
                            <?php if (!empty($site_settings['tiktok'])): ?>
                                <li><a href="https://tiktok.com/@<?php echo htmlspecialchars($site_settings['tiktok']); ?>"
                                        target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-tiktok"></i></a></li>
                            <?php endif; ?>
                            <?php if (!empty($site_settings['linkedin'])): ?>
                                <li><a href="https://linkedin.com/company/<?php echo htmlspecialchars($site_settings['linkedin']); ?>"
                                        target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-linkedin-in"></i></a></li>
                            <?php endif; ?>
                            <?php if (!empty($site_settings['youtube'])): ?>
                                <li><a href="https://youtube.com/<?php echo htmlspecialchars($site_settings['youtube']); ?>"
                                        target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-youtube"></i></a></li>
                            <?php endif; ?>
                            <?php if (!empty($site_settings['website'])): ?>
                                <li><a href="<?php echo htmlspecialchars(trim($site_settings['website'])); ?>"
                                        target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-globe"></i></a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                <?php foreach ($footer_categories as $category): ?>
                    <div class="col-lg-2 col-md-6">
                        <div class="footer-widget widget-links">
                            <div class="widget-title">
                                <?php
                                $ftitle = trim((string) ($category['title'] ?? ''));
                                if (isset($footer_category_title_fix[$ftitle])) {
                                    $ftitle = $footer_category_title_fix[$ftitle];
                                }
                                ?>
                                <h3><?php echo mynak_esc_html((string) $ftitle); ?></h3>
                            </div>
                            <?php if (!empty($category['menu_items'])): ?>
                                <ul class="footer-links">
                                    <?php foreach ($category['menu_items'] as $item): ?>
                                        <li>
                                            <?php $url = normalize_internal_link_url($item['url'] ?? ''); ?>
                                            <a href="<?php echo htmlspecialchars($url); ?>" <?php echo $item['target'] == '_blank' ? 'target="_blank" rel="noopener"' : ''; ?>>
                                                <?php echo mynak_esc_html((string) ($item['title'] ?? '')); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (!empty($footer_quick_links)): ?>
                <div class="col-lg-2 col-md-6">
                    <div class="footer-widget widget-links">
                        <div class="widget-title">
                            <h3>ÖNEMLİ LİNKLER</h3>
                        </div>
                        <ul class="footer-links">
                            <?php foreach ($footer_quick_links as $ql): ?>
                                <li>
                                    <a href="<?php echo htmlspecialchars($ql['href']); ?>"><?php echo mynak_esc_html((string) ($ql['label'] ?? '')); ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
                <?php $has_popular_footer = !empty($seo_hub_pages); ?>
                <div class="col-lg-4 col-md-12">
                    <div class="row footer-cluster g-4 g-lg-3 align-items-start">
                        <?php if ($has_popular_footer): ?>
                        <div class="col-md-6 col-lg-7">
                            <div class="footer-widget widget-links footer-widget--popular">
                                <div class="widget-title">
                                    <h3>POPÜLER SAYFALAR</h3>
                                </div>
                                <ul class="footer-links footer-links--popular footer-links--grid">
                                    <?php foreach ($seo_hub_pages as $hp): ?>
                                        <?php
                                        $full_title = trim((string) ($hp['title'] ?? ''));
                                        $link_label = footer_short_link_label($full_title, (string) ($hp['slug'] ?? ''), 52, false);
                                        ?>
                                        <li>
                                            <a href="<?php echo htmlspecialchars(normalize_internal_link_url('/' . $hp['slug'])); ?>"
                                                title="<?php echo htmlspecialchars($full_title !== '' ? $full_title : $link_label); ?>">
                                                <span class="footer-popular-link-text"><?php echo htmlspecialchars($link_label); ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="<?php echo $has_popular_footer ? 'col-md-6 col-lg-5' : 'col-12'; ?>">
                            <div class="footer-widget footer-widget--contact">
                                <div class="widget-title">
                                    <h3>İLETİŞİM</h3>
                                </div>
                                <ul class="footer-contact-info footer-contact-info--cards">
                                    <?php if (!empty($site_settings['address'])): ?>
                                        <li>
                                            <span class="footer-contact-icon" aria-hidden="true"><i class="fas fa-map-marker-alt"></i></span>
                                            <div class="footer-contact-body">
                                                <a href="<?php echo htmlspecialchars($footer_google_maps_url, ENT_QUOTES, 'UTF-8'); ?>"
                                                    target="_blank" rel="noopener noreferrer"
                                                    title="Google Haritalar’da aç">
                                                    <?php echo htmlspecialchars($site_settings['address']); ?>
                                                </a>
                                            </div>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (!empty($site_settings['email'])): ?>
                                        <li>
                                            <span class="footer-contact-icon" aria-hidden="true"><i class="fas fa-envelope"></i></span>
                                            <div class="footer-contact-body">
                                                <a href="mailto:<?php echo htmlspecialchars(trim($site_settings['email'])); ?>">
                                                    <?php echo htmlspecialchars($site_settings['email']); ?>
                                                </a>
                                            </div>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (!empty($site_settings['phone1'])): ?>
                                        <li>
                                            <span class="footer-contact-icon" aria-hidden="true"><i class="fas fa-phone"></i></span>
                                            <div class="footer-contact-body">
                                                <a href="<?php echo htmlspecialchars(footer_tel_uri($site_settings['phone1'])); ?>">
                                                    <?php echo htmlspecialchars($site_settings['phone1']); ?>
                                                </a>
                                            </div>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (!empty($site_settings['whatsapp'])): ?>
                                        <li>
                                            <span class="footer-contact-icon" aria-hidden="true"><i class="fab fa-whatsapp"></i></span>
                                            <div class="footer-contact-body">
                                                <a href="<?php echo htmlspecialchars(footer_whatsapp_wa_uri($site_settings['whatsapp'])); ?>"
                                                    target="_blank" rel="noopener noreferrer">
                                                    <?php echo htmlspecialchars(footer_whatsapp_display_label($site_settings['whatsapp'])); ?>
                                                </a>
                                            </div>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if (!empty($footer_map)): ?>
                <div class="col-lg-4 col-md-12">
                    <div class="footer-widget footer-map-widget">
                        <div class="widget-title">
                            <h3>KONUMUMUZ</h3>
                        </div>
                        <div class="footer-map-container">
                            <?php echo $footer_map_lazy !== '' ? $footer_map_lazy : $footer_map; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="running-truck">
            <div class="truck"></div>
            <div class="truck-2"></div>
            <div class="truck-3"></div>
        </div>
    </div>
    <div class="copyright-area">
        <?php $yil = date('Y'); ?>

        © <span id="currentYear"></span> <?php echo SITE_NAME; ?>, Tüm Hakları Saklıdır. Tasarım ve Kodlama: <a
            href="https://www.metropolweb.com/" target="_blank" rel="noopener noreferrer">MetropolWeb</a>

    </div>
</footer>
<!--/.footer-section-->

<div id="scrollup">
    <button id="scroll-top" class="scroll-to-top"><i class="fa-regular fa-arrow-up"></i></button>
</div>
<!--/.scrollup-->

<!--jQuery Lib-->
<script defer src="<?php echo ASSET_PATH; ?>js/vendor/jquary-3.6.0.min.js"></script>
<script defer src="<?php echo ASSET_PATH; ?>js/vendor/modernizr-2.8.3-respond-1.4.2.min.js"></script>
<script defer src="<?php echo ASSET_PATH; ?>js/vendor/bootstrap.min.js"></script>
<script defer src="<?php echo ASSET_PATH; ?>js/vendor/popper.min.js"></script>
<script defer src="<?php echo ASSET_PATH; ?>lib/gsap/gsap.min.js"></script>
<script defer src="<?php echo ASSET_PATH; ?>lib/gsap/ScrollTrigger.min.js"></script>
<script defer src="<?php echo ASSET_PATH; ?>lib/gsap/split-type.min.js"></script>
<script defer src="<?php echo ASSET_PATH; ?>js/vendor/lenis.min.js"></script>
<script defer src="<?php echo ASSET_PATH; ?>js/vendor/odometer.min.js"></script>
<script defer src="<?php echo ASSET_PATH; ?>js/vendor/jquery.nice-select.min.js"></script>
<script defer src="<?php echo ASSET_PATH; ?>js/vendor/waypoints.min.js"></script>
<script defer src="<?php echo ASSET_PATH; ?>js/vendor/venobox.min.js"></script>
<script defer src="<?php echo ASSET_PATH; ?>js/vendor/swiper.min.js"></script>
<script defer src="<?php echo ASSET_PATH; ?>js/vendor/wow.min.js"></script>
<script defer src="<?php echo ASSET_PATH; ?>js/mailchimp.min.js"></script>
<script defer src="<?php echo ASSET_PATH; ?>js/quote-form.min.js"></script>
<script defer src="<?php echo ASSET_PATH; ?>js/main.min.js"></script>
<script defer src="<?php echo ASSET_PATH; ?>js/popup-forms.min.js"></script>

<script>
window.googleTranslateElementInit = function () {
    try {
        new google.translate.TranslateElement({
            pageLanguage: 'tr',
            includedLanguages: 'tr,en,ar,fr,de,es,ru',
            autoDisplay: false,
            layout: google.translate.TranslateElement.InlineLayout.HORIZONTAL
        }, 'google_translate_element');
    } catch (e) {}
};
(function () {
    var interactionLoaded = false;
    var interactionEvents = ['scroll', 'touchstart', 'mousemove', 'pointerdown'];

    function loadGoogleAssetsOnInteraction() {
        if (interactionLoaded) {
            return;
        }
        interactionLoaded = true;
        interactionEvents.forEach(function (evt) {
            window.removeEventListener(evt, loadGoogleAssetsOnInteraction, false);
        });

        var mapFrames = document.querySelectorAll('iframe[data-lazy-map="1"][data-src]');
        mapFrames.forEach(function (frame) {
            var realSrc = frame.getAttribute('data-src');
            if (!realSrc) {
                return;
            }
            frame.setAttribute('src', realSrc);
        });

        if (document.querySelector('script[data-mynak-gt]')) {
            return;
        }
        var s = document.createElement('script');
        s.src = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
        s.async = true;
        s.setAttribute('data-mynak-gt', '1');
        document.body.appendChild(s);
    }
    interactionEvents.forEach(function (evt) {
        window.addEventListener(evt, loadGoogleAssetsOnInteraction, { passive: true });
    });
})();
</script>

<!-- SEO için body bitiş scriptleri -->
<?php echo isset($body_end_scripts) ? $body_end_scripts : ''; ?>

<?php /* WhatsApp Floating Button — izole, sayfa tipine göre preset mesaj. Numara yoksa render etmez. */ ?>
<?php include __DIR__ . '/whatsapp_float_button.php'; ?>

<?php /* Telefon (Ara) Floating Button — izole, mavi, WhatsApp FAB'ın üstünde. Numara yoksa render etmez. */ ?>
<?php include __DIR__ . '/phone_float_button.php'; ?>

<?php /* Mobil alt CTA: Ara | WhatsApp | Teklif | Yol tarifi — dar ekranda FAB'lar gizlenir */ ?>
<?php include __DIR__ . '/mobile_sticky_cta_bar.php'; ?>
<!-- Google tag: body sonunda (LCP/ana iş parçacığı; head'de yok) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-DNCQRMSQ5E"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag() { dataLayer.push(arguments); }
    gtag('js', new Date());
    gtag('config', 'G-DNCQRMSQ5E');
</script>
</body>

</html>
<!-- SEO için HTML sonrası scriptleri -->
<?php echo isset($body_after_footer_scripts) ? $body_after_footer_scripts : ''; ?>