<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/pipeline/mynak_gbp_post_guide_tr.php';

$site_settings = mynak_site_settings_bootstrap($conn);
$page_title = 'Google İşletme Profili (GBP) gönderi rehberi' . ' - ' . (!empty($site_settings['site_title']) ? (string) $site_settings['site_title'] : '');
$allow_indexing = true;
$page_meta_description = 'Google Harita / İşletme profili duyuruları: sıklık, içerik şablonları, foto uyumu, Clarity/GA4 ile ölçüm. MY Nakliyat İzmir.';
$hide_title_suffix = true;

require_once __DIR__ . '/includes/header.php';
?>

<main id="content">
    <section class="page-banner" style="background: #f0f4f8; padding: 2.5rem 0 1.5rem;">
        <div class="container text-center">
            <h1 class="h2 mb-2" style="color: #1a3a5c;">Google İşletme Profili: gönderi rehberi</h1>
            <p class="text-muted mb-0 col-lg-8 mx-auto">Duyuru planı, metin ve görsel; ana sayfa galerisiyle kare tekrarını azaltacak şekilde seçim.</p>
        </div>
    </section>
    <section class="page-content py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 p-md-5 page-text mynak-gbp-rehber-wrap">
                            <?php echo mynak_gbp_post_guide_tr_html(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<style>
.mynak-gbp-rehber h2 { font-size: 1.35rem; margin-bottom: 1rem; }
.mynak-gbp-rehber h3 { font-size: 1.1rem; margin-top: 1.5rem; }
.mynak-gbp-rehber ul, .mynak-gbp-rehber ol { margin-bottom: 1rem; }
</style>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
