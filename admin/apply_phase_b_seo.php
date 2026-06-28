<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/require_admin_web.php';
require_once dirname(__DIR__) . '/includes/mynak_phase_b_seo_apply.php';

$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch = isset($_POST['batch']) ? (int) $_POST['batch'] : 0;
    $apply = isset($_POST['apply']);
    if ($batch < 1 || $batch > 2) {
        $error = 'Geçersiz batch (yalnızca 1 veya 2).';
    } else {
        try {
            $run = mynak_phase_b_run_batch($conn, $batch, $apply);
            $result = [
                'apply' => $apply,
                'batch' => $batch,
                'ok' => $run['ok'],
                'output' => $run['output'],
                'rollback_path' => $run['rollback_path'] ?? '',
            ];
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bx bx-search-alt"></i> Phase B — SEO Title &amp; Meta</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning small mb-3">
                        <strong>Önce admin girişi gerekir.</strong> Bu sayfa oturum açıkken çalışır.
                        <code>fiyat.php</code> zaten deploy edildiyse /fiyat title günceldir.
                        Burada yalnızca <code>pages</code> ve <code>blog_posts</code> SEO alanları güncellenir.
                    </div>

                    <?php if ($error !== null): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>

                    <?php if (is_array($result)): ?>
                        <div class="alert alert-<?php echo $result['ok'] ? ($result['apply'] ? 'success' : 'info') : 'danger'; ?>">
                            <pre class="mb-0 small" style="white-space: pre-wrap; max-height: 420px; overflow: auto;"><?php echo htmlspecialchars($result['output'], ENT_QUOTES, 'UTF-8'); ?></pre>
                        </div>
                        <?php if (!empty($result['rollback_path'])): ?>
                            <p class="small text-muted">Rollback: <code><?php echo htmlspecialchars($result['rollback_path'], ENT_QUOTES, 'UTF-8'); ?></code></p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <p class="text-muted small">Batch 1 = pages · Batch 2 = blog_posts · Sırayla uygulayın.</p>

                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <form method="post" class="d-inline">
                            <input type="hidden" name="batch" value="1">
                            <button type="submit" name="preview" value="1" class="btn btn-outline-primary btn-sm">Batch 1 önizleme</button>
                            <button type="submit" name="apply" value="1" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Batch 1 (pages) canlı DB güncellenecek. Onaylıyor musunuz?');">Batch 1 uygula</button>
                        </form>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="batch" value="2">
                            <button type="submit" name="preview" value="1" class="btn btn-outline-primary btn-sm">Batch 2 önizleme</button>
                            <button type="submit" name="apply" value="1" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Batch 2 (blog) canlı DB güncellenecek. Onaylıyor musunuz?');">Batch 2 uygula</button>
                        </form>
                        <a href="seo_management.php" class="btn btn-outline-secondary btn-sm">SEO Yönetimi</a>
                    </div>

                    <hr class="my-3">
                    <p class="small text-muted mb-1"><strong>Alternatif:</strong> phpMyAdmin → SQL →
                        <code>scripts/phase_b_batch1_batch2_production.sql</code></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
