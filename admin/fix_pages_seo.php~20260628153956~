<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/require_admin_web.php';

$helpersPath = dirname(__DIR__) . '/includes/mynak_seo_length_helpers.php';
if (!is_readable($helpersPath)) {
    require_once __DIR__ . '/includes/header.php';
    $page_title = 'SEO Düzeltme — dosya eksik';
    ?>
    <div class="container-fluid py-4">
        <div class="alert alert-danger">
            <strong>includes/mynak_seo_length_helpers.php</strong> sunucuda yok.
            FTP: <code>public_html/includes/mynak_seo_length_helpers.php</code>
        </div>
        <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}
require_once $helpersPath;

if (!function_exists('mynak_seo_apply_bulk_title_meta_fixes')) {
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container-fluid py-4"><div class="alert alert-danger">SEO yardımcı fonksiyonları yüklenemedi.</div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$result = null;
$resultError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_fix'])) {
    @set_time_limit(300);
    try {
        $fixPages = !empty($_POST['fix_pages']);
        $fixBlogs = !empty($_POST['fix_blogs']);
        if (!$fixPages && !$fixBlogs) {
            throw new RuntimeException('En az bir hedef seçin (sayfalar veya blog).');
        }

        $stats = mynak_seo_apply_bulk_title_meta_fixes($conn, $fixPages, $fixBlogs);

        $metricsPath = __DIR__ . '/includes/dashboard_seo_metrics.php';
        if (is_readable($metricsPath)) {
            require_once $metricsPath;
            if (function_exists('mynak_dashboard_seo_metrics_get')) {
                mynak_dashboard_seo_metrics_get($conn, true, 0);
            }
        }

        $result = [
            'ok' => true,
            'pages_fixed' => $stats['pages_fixed'],
            'blogs_fixed' => $stats['blogs_fixed'],
            'preview' => $stats['preview'],
        ];
    } catch (Throwable $e) {
        error_log('fix_pages_seo: ' . $e->getMessage());
        $resultError = $e->getMessage();
    }
}

require_once __DIR__ . '/includes/header.php';
$page_title = 'Sayfa + Blog SEO Düzeltme';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bx bx-wrench"></i> Sayfa ve blog SEO (title / meta) otomatik düzelt</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Dashboard kuralları: <strong>seo_title 30–60</strong> karakter,
                        <strong>meta_description 120–160</strong> karakter.
                    </p>

                    <?php if ($resultError !== ''): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($resultError, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>

                    <?php if ($result !== null && !empty($result['ok'])): ?>
                        <div class="alert alert-success">
                            <strong><?php echo (int) $result['pages_fixed']; ?></strong> sayfa,
                            <strong><?php echo (int) $result['blogs_fixed']; ?></strong> blog yazısı güncellendi.
                            <a href="dashboard.php?refresh_seo=1">Dashboard</a> → SEO yenile ile kontrol edin.
                        </div>
                        <?php if (!empty($result['preview'])): ?>
                            <ul class="small mb-3">
                                <?php foreach ($result['preview'] as $p): ?>
                                    <li>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($p['type'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <code><?php echo htmlspecialchars($p['slug'], ENT_QUOTES, 'UTF-8'); ?></code>
                                        — <?php echo htmlspecialchars($p['seo_title'], ENT_QUOTES, 'UTF-8'); ?>
                                        (meta <?php echo (int) $p['meta_len']; ?> kr)
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    <?php endif; ?>

                    <form method="post" onsubmit="return confirm('Seçili kayıtların seo_title ve meta_description alanları güncellenecek. Devam?');">
                        <input type="hidden" name="run_fix" value="1">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="fix_pages" value="1" id="fix_pages" checked>
                                <label class="form-check-label" for="fix_pages">Aktif sayfalar (<code>pages</code>, status=1)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="fix_blogs" value="1" id="fix_blogs" checked>
                                <label class="form-check-label" for="fix_blogs">Yayında blog yazıları (<code>blog_posts</code>, durum=3)</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-play me-1"></i> Seçilenleri düzelt
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary ms-2">Dashboard</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
