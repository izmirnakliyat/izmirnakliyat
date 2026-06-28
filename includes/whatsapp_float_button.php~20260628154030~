<?php
/**
 * WhatsApp Floating Button — her public sayfanın sağ altında yüzer.
 *
 * Dinamik özellikler:
 *   - Sayfa tipine (service / blog / page / home) göre preset mesaj
 *   - settings.whatsapp kolonundaki numarayı kullanır (footer ile aynı kaynak)
 *   - WhatsApp kurulu değilse wa.me/ üzerinden web sürümü açılır
 *   - Mobilde daha küçük, masaüstünde normal
 *   - Pulse animasyonu (3s'de bir, dikkat çeker ama rahatsız etmez)
 *   - GA4 dataLayer event (event: 'whatsapp_click', page_type)
 *
 * Gereksinimler:
 *   - $site_settings['whatsapp']  (DB'den gelir)
 *   - $page['type'], $page['title'], $page['slug']  (front controller set eder)
 *   - Font Awesome ≥5 (footer'da yüklü — `fab fa-whatsapp` ikonu)
 *   - `includes/functions.php` içinde `footer_whatsapp_wa_uri()` helper'ı
 *
 * Ekleme noktası:
 *   includes/footer.php sonuna (</body> öncesine) şu satır:
 *     <?php include __DIR__ . '/whatsapp_float_button.php'; ?>
 */

declare(strict_types=1);

// Guard: numara yoksa hiç render etme
$mynakWhatsappRaw = '';
if (isset($site_settings['whatsapp']) && is_string($site_settings['whatsapp'])) {
    $mynakWhatsappRaw = trim($site_settings['whatsapp']);
}
if ($mynakWhatsappRaw === '') {
    return;
}
if (!function_exists('footer_whatsapp_wa_uri')) {
    return;
}

$mynakWhatsappUri = footer_whatsapp_wa_uri($mynakWhatsappRaw);
if ($mynakWhatsappUri === '#' || $mynakWhatsappUri === '') {
    return;
}

// Sayfa bağlamına göre preset mesaj üret.
// Tespit stratejisi (öncelik sırasıyla):
//  1) URI'den home / blog-list gibi bilindik yolları yakala → kesin sinyal
//  2) $page['type'] = service / page  → front controller'ın standart çıkışı (güvenilir)
//  3) $blog['baslik'] dolu + home/blog-list değil → blog detay (SEO URL'de /blog/ prefix yok)
//  4) Fallback: generic
$mynakUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
$mynakUriPath = (string) (parse_url($mynakUri, PHP_URL_PATH) ?: $mynakUri);
$mynakUriPath = preg_replace('#^/mynakliyat#', '', $mynakUriPath) ?: '/';
$mynakUriPath = '/' . ltrim($mynakUriPath, '/');
$mynakUriPath = rtrim($mynakUriPath, '/');
if ($mynakUriPath === '') { $mynakUriPath = '/'; }

$mynakIsHome = ($mynakUriPath === '/' || $mynakUriPath === '/index.php');
$mynakIsBlogList = ($mynakUriPath === '/blog');

$mynakPageType = 'home';
$mynakPageTitle = '';

if ($mynakIsHome) {
    $mynakPageType = 'home';
} elseif ($mynakIsBlogList) {
    $mynakPageType = 'blog-list';
} elseif (isset($page) && is_array($page) && !empty($page['title']) && !empty($page['type'])) {
    $mynakPageType = (string) $page['type'];
    $mynakPageTitle = trim((string) $page['title']);
} elseif (isset($blog) && is_array($blog) && !empty($blog['baslik'])) {
    $mynakPageType = 'blog';
    $mynakPageTitle = trim((string) $blog['baslik']);
}
unset($mynakUri, $mynakUriPath, $mynakIsHome, $mynakIsBlogList);

$mynakPresetMessage = 'Merhaba, MY Nakliyat hizmetleri hakkında bilgi almak istiyorum.';
switch ($mynakPageType) {
    case 'service':
        if ($mynakPageTitle !== '') {
            $mynakPresetMessage = 'Merhaba, "' . $mynakPageTitle . '" hizmeti için teklif almak istiyorum.';
        } else {
            $mynakPresetMessage = 'Merhaba, nakliyat hizmeti için teklif almak istiyorum.';
        }
        break;
    case 'blog':
        if ($mynakPageTitle !== '') {
            $mynakPresetMessage = 'Merhaba, "' . $mynakPageTitle . '" yazınızı okudum, birkaç sorum olacak.';
        }
        break;
    case 'page':
        if ($mynakPageTitle !== '') {
            $mynakPresetMessage = 'Merhaba, ' . $mynakPageTitle . ' hakkında bilgi almak istiyorum.';
        }
        break;
}

$mynakWhatsappFinalUri = $mynakWhatsappUri . (strpos($mynakWhatsappUri, '?') === false ? '?' : '&')
    . 'text=' . rawurlencode($mynakPresetMessage);
?>
<style>
/* WhatsApp Float Button — izole, başka stilleri etkilemez. */
.mynak-wa-fab {
    position: fixed;
    right: 24px;
    bottom: 24px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #25D366;
    box-shadow: 0 4px 12px rgba(37, 211, 102, .4), 0 2px 4px rgba(0, 0, 0, .15);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    text-decoration: none;
    z-index: 9998;
    transition: transform .2s ease, box-shadow .2s ease;
    animation: mynakWaPulse 3s ease-in-out infinite;
}
.mynak-wa-fab:hover {
    transform: scale(1.08);
    box-shadow: 0 6px 20px rgba(37, 211, 102, .55), 0 3px 6px rgba(0, 0, 0, .2);
    color: #fff;
    text-decoration: none;
}
.mynak-wa-fab:focus-visible {
    outline: 3px solid #128C7E;
    outline-offset: 3px;
}
.mynak-wa-fab .fab,
.mynak-wa-fab .fa-whatsapp {
    font-size: 32px;
    line-height: 1;
}
.mynak-wa-fab-label {
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
.mynak-wa-fab-label::after {
    content: '';
    position: absolute;
    right: -5px;
    top: 50%;
    transform: translateY(-50%);
    border: 5px solid transparent;
    border-left-color: #111;
}
.mynak-wa-fab:hover .mynak-wa-fab-label,
.mynak-wa-fab:focus-visible .mynak-wa-fab-label {
    opacity: 1;
}
@keyframes mynakWaPulse {
    0%, 100% { box-shadow: 0 4px 12px rgba(37, 211, 102, .4), 0 0 0 0 rgba(37, 211, 102, .45); }
    50%      { box-shadow: 0 4px 12px rgba(37, 211, 102, .4), 0 0 0 18px rgba(37, 211, 102, 0); }
}
@media (max-width: 768px) {
    .mynak-wa-fab {
        right: 16px;
        bottom: 16px;
        width: 56px;
        height: 56px;
    }
    .mynak-wa-fab .fab,
    .mynak-wa-fab .fa-whatsapp { font-size: 28px; }
    .mynak-wa-fab-label { display: none; }
}
@media (prefers-reduced-motion: reduce) {
    .mynak-wa-fab { animation: none; }
}
/* Print: gizle */
@media print {
    .mynak-wa-fab { display: none !important; }
}
</style>
<a class="mynak-wa-fab"
   href="<?php echo htmlspecialchars($mynakWhatsappFinalUri, ENT_QUOTES, 'UTF-8'); ?>"
   target="_blank"
   rel="noopener noreferrer nofollow"
   aria-label="WhatsApp üzerinden iletişime geç"
   data-mynak-wa-fab
   data-mynak-page-type="<?php echo htmlspecialchars($mynakPageType, ENT_QUOTES, 'UTF-8'); ?>">
    <i class="fab fa-whatsapp" aria-hidden="true"></i>
    <span class="mynak-wa-fab-label">WhatsApp'tan yazın</span>
</a>
<script>
(function () {
    var el = document.querySelector('[data-mynak-wa-fab]');
    if (!el) return;
    el.addEventListener('click', function () {
        try {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                event: 'whatsapp_click',
                page_type: el.getAttribute('data-mynak-page-type') || 'unknown',
                location: 'float_button',
            });
        } catch (e) {}
    }, { passive: true });
})();
</script>
<?php
unset($mynakWhatsappRaw, $mynakWhatsappUri, $mynakWhatsappFinalUri, $mynakPageType, $mynakPageTitle, $mynakPresetMessage);
