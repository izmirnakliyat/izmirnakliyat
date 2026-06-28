<?php
/**
 * Mobil alt CTA cubugu — sadece dar ekranda (max-width 767px).
 * 4 bolme: Ara | WhatsApp | Teklif | Yon Tarifi
 *
 * - Masaustunde gizli; mobilde sabit alt bant.
 * - Sağdaki mynak-phone-fab ve mynak-wa-fab bu genislikte gizlenir (cakisma onleme).
 * - GA4: mobile_sticky_call, mobile_sticky_whatsapp, mobile_sticky_quote, mobile_sticky_directions
 */
declare(strict_types=1);

if (!function_exists('mynak_public_path')) {
    require_once __DIR__ . '/../config/seo.php';
}
if (!function_exists('footer_tel_uri') || !function_exists('footer_whatsapp_wa_uri')) {
    require_once __DIR__ . '/functions.php';
}

$phoneRaw = isset($site_settings['phone1']) && is_string($site_settings['phone1'])
    ? trim($site_settings['phone1'])
    : '';
$waRaw = isset($site_settings['whatsapp']) && is_string($site_settings['whatsapp'])
    ? trim($site_settings['whatsapp'])
    : '';
$addrRaw = isset($site_settings['address']) && is_string($site_settings['address'])
    ? trim($site_settings['address'])
    : 'Seyhan Mah. 653/2 Sk. No:10 K:3 Buca / İzmir';

$telHref = '#';
if ($phoneRaw !== '' && function_exists('footer_tel_uri')) {
    $telHref = (string) footer_tel_uri($phoneRaw);
}
if ($telHref === '#' || $telHref === '') {
    $telHref = mynak_public_path('iletisim');
}

$waHref = '#';
if ($waRaw !== '' && function_exists('footer_whatsapp_wa_uri')) {
    $waHref = (string) footer_whatsapp_wa_uri($waRaw);
}
if ($waHref === '#' || $waHref === '') {
    $waHref = mynak_public_path('iletisim');
}

$quoteHref = mynak_public_path('teklif-alin');
$mapsHref = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($addrRaw);
$telHrefEsc = htmlspecialchars($telHref, ENT_QUOTES, 'UTF-8');
$waHrefEsc = htmlspecialchars($waHref, ENT_QUOTES, 'UTF-8');
$quoteHrefEsc = htmlspecialchars($quoteHref, ENT_QUOTES, 'UTF-8');
$mapsHrefEsc = htmlspecialchars($mapsHref, ENT_QUOTES, 'UTF-8');
?>
<style>
@media (min-width: 768px) {
    .mynak-mobile-cta-bar { display: none !important; }
}
@media (max-width: 767.98px) {
    .mynak-phone-fab,
    .mynak-wa-fab { display: none !important; }
    body.mynak-has-mobile-cta { padding-bottom: calc(58px + env(safe-area-inset-bottom, 0px)); }
    .mynak-mobile-cta-bar {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 10020;
        display: flex;
        background: linear-gradient(180deg, #1a2238 0%, #0f1424 100%);
        border-top: 1px solid rgba(255,255,255,.08);
        box-shadow: 0 -4px 20px rgba(0,0,0,.2);
        padding-bottom: env(safe-area-inset-bottom, 0px);
    }
    .mynak-mobile-cta-bar a {
        flex: 1 1 25%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        padding: 10px 4px 12px;
        color: #fff;
        text-decoration: none;
        font-size: 11px;
        font-weight: 600;
        line-height: 1.15;
        border-right: 1px solid rgba(255,255,255,.08);
        -webkit-tap-highlight-color: transparent;
    }
    .mynak-mobile-cta-bar a:last-child { border-right: 0; }
    .mynak-mobile-cta-bar a:active { background: rgba(255,255,255,.06); }
    .mynak-mobile-cta-bar a i { font-size: 18px; opacity: .95; }
    .mynak-mobile-cta-bar .mynak-mcta-call { color: #7ec8ff; }
    .mynak-mobile-cta-bar .mynak-mcta-wa { color: #25d366; }
    .mynak-mobile-cta-bar .mynak-mcta-quote { color: #ffd54f; }
    .mynak-mobile-cta-bar .mynak-mcta-map { color: #ffab91; }
}
</style>
<script>
(function() {
    if (typeof window.matchMedia === 'function' && window.matchMedia('(max-width: 767.98px)').matches) {
        document.body.classList.add('mynak-has-mobile-cta');
    }
})();
</script>
<nav class="mynak-mobile-cta-bar" role="navigation" aria-label="Hızlı iletişim">
    <a class="mynak-mcta-call" href="<?php echo $telHrefEsc; ?>"
       <?php if (str_starts_with((string) $telHref, 'tel:')): ?>data-mynak-tel="1"<?php endif; ?>>
        <i class="fas fa-phone-alt" aria-hidden="true"></i>
        <span>Ara</span>
    </a>
    <a class="mynak-mcta-wa" href="<?php echo $waHrefEsc; ?>" rel="noopener noreferrer" target="_blank">
        <i class="fab fa-whatsapp" aria-hidden="true"></i>
        <span>WhatsApp</span>
    </a>
    <a class="mynak-mcta-quote" href="<?php echo $quoteHrefEsc; ?>">
        <i class="fas fa-file-signature" aria-hidden="true"></i>
        <span>Teklif</span>
    </a>
    <a class="mynak-mcta-map" href="<?php echo $mapsHrefEsc; ?>" rel="noopener noreferrer" target="_blank">
        <i class="fas fa-directions" aria-hidden="true"></i>
        <span>Yol tarifi</span>
    </a>
</nav>
<script>
(function() {
    function push(name, el) {
        if (typeof window.dataLayer === 'undefined' || !el) return;
        el.addEventListener('click', function() {
            window.dataLayer.push({ event: name });
        });
    }
    var root = document.querySelector('.mynak-mobile-cta-bar');
    if (!root) return;
    var links = root.querySelectorAll('a');
    if (links[0]) push('mobile_sticky_call', links[0]);
    if (links[1]) push('mobile_sticky_whatsapp', links[1]);
    if (links[2]) push('mobile_sticky_quote', links[2]);
    if (links[3]) push('mobile_sticky_directions', links[3]);
})();
</script>
