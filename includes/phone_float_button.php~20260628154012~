<?php
/**
 * Telefon (Ara) Floating Button — sağ alt, WhatsApp FAB'ın hemen üstünde.
 *
 * Özellikler:
 *   - settings.phone1 kolonundaki numarayı kullanır (footer ile aynı kaynak)
 *   - footer_tel_uri() ile +90 normalize eder
 *   - Mobilde daha küçük, masaüstünde normal
 *   - GA4 dataLayer event (event: 'phone_click')
 *   - Numara yoksa hiç render etmez
 *
 * Gereksinimler:
 *   - $site_settings['phone1']
 *   - includes/functions.php içinde footer_tel_uri() helper
 *   - Font Awesome ≥5 (footer'da yüklü — `fas fa-phone` ikonu)
 *
 * Ekleme noktası:
 *   includes/footer.php sonuna (</body> öncesine) şu satır:
 *     <?php include __DIR__ . '/phone_float_button.php'; ?>
 */

declare(strict_types=1);

$mynakPhoneRaw = '';
if (isset($site_settings['phone1']) && is_string($site_settings['phone1'])) {
    $mynakPhoneRaw = trim($site_settings['phone1']);
}
if ($mynakPhoneRaw === '') {
    return;
}
if (!function_exists('footer_tel_uri')) {
    return;
}
$mynakPhoneUri = footer_tel_uri($mynakPhoneRaw);
if ($mynakPhoneUri === '#' || $mynakPhoneUri === '') {
    return;
}
?>
<style>
/* Telefon Float Button — izole, başka stilleri etkilemez. */
.mynak-phone-fab {
    position: fixed;
    right: 24px;
    bottom: 96px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1a8fff 0%, #0d6efd 100%);
    box-shadow: 0 4px 12px rgba(13, 110, 253, .4), 0 2px 4px rgba(0, 0, 0, .15);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    text-decoration: none;
    z-index: 9997;
    transition: transform .2s ease, box-shadow .2s ease;
}
.mynak-phone-fab:hover {
    transform: scale(1.08);
    box-shadow: 0 6px 20px rgba(13, 110, 253, .55), 0 3px 6px rgba(0, 0, 0, .2);
    color: #fff;
    text-decoration: none;
}
.mynak-phone-fab:focus-visible {
    outline: 3px solid #0a58ca;
    outline-offset: 3px;
}
.mynak-phone-fab .fas,
.mynak-phone-fab .fa-phone,
.mynak-phone-fab .fa-phone-alt {
    font-size: 26px;
    line-height: 1;
}
.mynak-phone-fab-label {
    position: absolute;
    right: 72px;
    top: 50%;
    transform: translateY(-50%);
    background: #111;
    color: #fff;
    font-size: 13px;
    font-weight: 500;
    padding: 6px 12px;
    border-radius: 6px;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity .2s ease;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}
.mynak-phone-fab-label::after {
    content: '';
    position: absolute;
    right: -5px;
    top: 50%;
    transform: translateY(-50%);
    border: 5px solid transparent;
    border-left-color: #111;
}
.mynak-phone-fab:hover .mynak-phone-fab-label,
.mynak-phone-fab:focus-visible .mynak-phone-fab-label {
    opacity: 1;
}
@media (max-width: 768px) {
    .mynak-phone-fab {
        right: 16px;
        bottom: 84px;
        width: 56px;
        height: 56px;
    }
    .mynak-phone-fab .fas,
    .mynak-phone-fab .fa-phone,
    .mynak-phone-fab .fa-phone-alt { font-size: 24px; }
    .mynak-phone-fab-label { display: none; }
}
@media print {
    .mynak-phone-fab { display: none !important; }
}
</style>
<a class="mynak-phone-fab"
   href="<?php echo htmlspecialchars($mynakPhoneUri, ENT_QUOTES, 'UTF-8'); ?>"
   aria-label="Telefonla ara: <?php echo htmlspecialchars($mynakPhoneRaw, ENT_QUOTES, 'UTF-8'); ?>"
   data-mynak-phone-fab>
    <i class="fas fa-phone-alt" aria-hidden="true"></i>
    <span class="mynak-phone-fab-label">Hemen ara</span>
</a>
<script>
(function () {
    if (window.__mynakPhoneFabInit) { return; }
    window.__mynakPhoneFabInit = true;
    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.querySelector('[data-mynak-phone-fab]');
        if (!btn) { return; }
        btn.addEventListener('click', function () {
            try {
                if (window.dataLayer && typeof window.dataLayer.push === 'function') {
                    window.dataLayer.push({ event: 'phone_click', source: 'phone_fab' });
                }
            } catch (e) {}
        });
    });
})();
</script>
