<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/pipeline/lead_magnet_tasinma_checklist.php';

$landing = mynak_abs_url_from_public_path(mynak_public_path('tasinma-kontrol-listesi'));

if (!mynak_lead_checklist_is_allowed()) {
    header('Location: ' . $landing, true, 303);
    exit;
}

if (($_GET['indir'] ?? '') === 'html') {
    if (!mynak_lead_checklist_is_allowed()) {
        header('Location: ' . $landing, true, 303);
        exit;
    }
    if (headers_sent() === false) {
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: attachment; filename="MY-Nakliyat-Tasinma-Kontrol-Listesi.html"');
    }
    echo mynak_lead_magnet_checklist_full_document_for_download();
    exit;
}

$site_settings = mynak_site_settings_bootstrap($conn);
$page_title = 'Kontrol listeniz' . ' - ' . (!empty($site_settings['site_title']) ? (string) $site_settings['site_title'] : '');
$allow_indexing = true;
$meta_robots = 'noindex, follow';
$page_meta_description = 'Taşınma kontrol listesi — Yazdır, HTML indir veya PDF için yazdırma menüsünü kullanın.';
$hide_title_suffix = true;

require_once __DIR__ . '/includes/header.php';
$body = mynak_lead_magnet_checklist_body_html();
?>

<main id="content">
    <section class="page-banner" style="background: #f1f4f8; padding: 2.5rem 0 1.5rem;">
        <div class="container text-center">
            <h1 class="h2 mb-2" style="color: #1a3a5c;">Taşınma kontrol listesi</h1>
            <p class="text-muted mb-0">Aşağıdaki listeyi yazdırabilir, PDF olarak kaydedebilir veya tam sayfa HTML indirebilirsiniz.</p>
        </div>
    </section>
    <section class="py-4">
        <div class="container">
            <div class="d-flex flex-wrap gap-2 justify-content-center mb-4">
                <a class="btn btn-primary" href="<?php echo htmlspecialchars(mynak_public_path('tasinma-kontrol-listesi-icerik') . '?indir=html', ENT_QUOTES, 'UTF-8'); ?>">HTML dosyasını indir</a>
                <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Yazdır / PDF’ye kaydet</button>
                <a class="btn btn-outline-primary" href="<?php echo htmlspecialchars(mynak_public_path('teklif-alin'), ENT_QUOTES, 'UTF-8'); ?>">Ücretsiz teklif</a>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5 page-text" id="mynak-print-area">
                    <?php echo $body; ?>
                </div>
            </div>
            <p class="text-center text-muted small mt-4">Listenin PDF’si: çoğu tarayıcıda <strong>Yazdır</strong> açıp hedef olarak <strong>PDF kaydet / Microsoft Print to PDF</strong> seçin.</p>
        </div>
    </section>
</main>
<style>
@media print {
    .page-banner, .footer-section, .btn, .main-header, .mobile-sticky-cta, .whatsapp-float, .phone-float { display: none !important; }
    #mynak-print-area { box-shadow: none !important; }
    body { background: #fff; }
}
</style>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
