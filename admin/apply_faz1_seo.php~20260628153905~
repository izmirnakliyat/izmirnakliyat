<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/require_admin_web.php';

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['run'])) {
    $apply = isset($_POST['apply']) || (isset($_GET['run']) && $_GET['run'] === 'apply');
    define('MYNAK_FAZ1_SEO_WEB', true);
    define('MYNAK_FAZ1_SEO_APPLY', $apply);

    ob_start();
    require dirname(__DIR__) . '/scripts/apply_faz1_seo_snippets.php';
    $output = trim((string) ob_get_clean());

    $result = [
        'apply' => $apply,
        'output' => $output,
        'report_path' => $reportPath ?? '',
    ];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bx bx-search-alt"></i> FAZ 1 SEO Snippet Uygulama</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        P0 meta title, meta description, global ayarlar ve H1 güncellemelerini canlı veritabanına uygular.
                        Önce <strong>Önizleme (dry-run)</strong> ile raporu kontrol edin; ardından <strong>Uygula</strong> ile kaydedin.
                        Yedek: <code>logs/backups/faz1_seo_*</code>
                    </p>

                    <?php if (is_array($result)): ?>
                        <div class="alert alert-<?php echo $result['apply'] ? 'success' : 'info'; ?>">
                            <pre class="mb-0 small" style="white-space: pre-wrap;"><?php echo htmlspecialchars($result['output'], ENT_QUOTES, 'UTF-8'); ?></pre>
                        </div>
                        <?php if ($result['report_path'] !== ''): ?>
                            <p class="small text-muted mb-0">Rapor: <code><?php echo htmlspecialchars($result['report_path'], ENT_QUOTES, 'UTF-8'); ?></code></p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <form method="post" class="mt-3 d-flex flex-wrap gap-2">
                        <button type="submit" name="preview" value="1" class="btn btn-outline-primary">
                            <i class="bx bx-show"></i> Önizleme (dry-run)
                        </button>
                        <button type="submit" name="apply" value="1" class="btn btn-danger"
                                onclick="return confirm('Canlı veritabanı güncellenecek. Devam edilsin mi?');">
                            <i class="bx bx-check"></i> Uygula (canlı DB)
                        </button>
                        <a href="seo_management.php" class="btn btn-outline-secondary">SEO Yönetimine Dön</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
