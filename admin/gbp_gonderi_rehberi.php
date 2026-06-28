<?php
declare(strict_types=1);

$page_title = 'GBP gönderi rehberi';
require_once 'includes/header.php';
require_once dirname(__DIR__) . '/includes/pipeline/mynak_gbp_post_guide_tr.php';

$publicUrl = function_exists('mynak_abs_url_from_public_path') && function_exists('mynak_public_path')
    ? mynak_abs_url_from_public_path(mynak_public_path('rehber-google-isletme-gonderileri'))
    : '';
?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">Google İşletme Profili — gönderi &amp; otomasyon rehberi</h5>
                <?php if ($publicUrl !== ''): ?>
                    <a href="<?php echo htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">Sitede aç (SEO)</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <p class="text-muted small">Bu metin, <code>rehber-google-isletme-gonderileri</code> public sayfası ile aynı kaynaktan gelir. GBP duyuruları için haftalık manuel yayıncılık; tam otomasyon GMB API / üçüncü parti araç gerektirir.</p>
                <hr>
                <div class="mynak-gbp-admin-html">
                    <?php echo mynak_gbp_post_guide_tr_html(); ?>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
.mynak-gbp-rehber h2 { font-size: 1.2rem; }
.mynak-gbp-rehber h3 { font-size: 1.05rem; margin-top: 1rem; }
</style>
<?php require_once 'includes/footer.php'; ?>
