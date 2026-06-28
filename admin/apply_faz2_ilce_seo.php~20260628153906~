<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/require_admin_web.php';

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['run'])) {
    $apply = isset($_POST['apply']) || (isset($_GET['run']) && $_GET['run'] === 'apply');
    define('MYNAK_FAZ2_ILCE_WEB', true);
    define('MYNAK_FAZ2_ILCE_APPLY', $apply);

    ob_start();
    require dirname(__DIR__) . '/scripts/apply_faz2_ilce_seo.php';
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
                    <h5 class="mb-0"><i class="bx bx-map"></i> FAZ 2 — İlçe SEO Uygulama</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        30 İzmir ilçesi cluster URL'leri (evden eve, asansör, vinç): SEO title, meta description, H1 hizalama.
                        Service JSON-LD <code>areaServed</code> ilçe bazlı kod güncellemesi ayrı dosyada — FTP ile yüklenmeli.
                    </p>

                    <?php if (is_array($result)): ?>
                        <div class="alert alert-<?php echo $result['apply'] ? 'success' : 'info'; ?>">
                            <pre class="mb-0 small" style="white-space: pre-wrap;"><?php echo htmlspecialchars($result['output'], ENT_QUOTES, 'UTF-8'); ?></pre>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="mt-3 d-flex flex-wrap gap-2">
                        <button type="submit" name="preview" value="1" class="btn btn-outline-primary">Önizleme (dry-run)</button>
                        <button type="submit" name="apply" value="1" class="btn btn-danger"
                                onclick="return confirm('Canlı veritabanındaki ilçe SEO alanları güncellenecek. Devam?');">
                            Uygula (canlı DB)
                        </button>
                        <a href="seo_management.php" class="btn btn-outline-secondary">SEO Yönetimine Dön</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
