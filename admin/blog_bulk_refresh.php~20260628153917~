<?php
/**
 * Toplu blog içerik yenileme (AI) — yayında yazıları yeniler, editör kuyruğuna alır.
 */
declare(strict_types=1);

$page_title = 'Toplu blog içerik yenileme';

require_once __DIR__ . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once __DIR__ . '/includes/auto_blog_functions.php';
require_once dirname(__DIR__) . '/includes/blog_bulk_content_refresh_lib.php';
require_once dirname(__DIR__) . '/includes/mynak_ce_production.php';
require_once dirname(__DIR__) . '/includes/auto_blog_ce_adapter.php';

mynak_ce_ensure_settings($conn);

if (!hasPermission('blog_view')) {
    echo '<div class="alert alert-danger">Bu sayfaya erişim yetkiniz yok.</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$key_saved_msg = '';
$model_saved_msg = '';

$batchVersion = br_ce_batch_version($conn);
$batch_saved_msg = '';
$ceRerunCount = br_ce_rerun_candidate_count($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['bump_ce_batch_version'])) {
        $next = br_ce_batch_version_bump($conn);
        $batchVersion = $next;
        $batch_saved_msg = 'batch_version artırıldı: <strong>' . htmlspecialchars($next)
            . '</strong> — QC-fail / eski motor yazıları yeniden işlerken farklı varyant üretilir.';
    }
    if (isset($_POST['save_ce_batch_version'])) {
        $bv = trim((string) ($_POST['ce_batch_version'] ?? '1'));
        br_ce_batch_version_set($conn, $bv !== '' ? $bv : '1');
        $batchVersion = br_ce_batch_version($conn);
        $batch_saved_msg = 'Content Engine batch_version kaydedildi: ' . htmlspecialchars($batchVersion);
    }
    if (isset($_POST['save_openai_key'])) {
        $raw = trim((string) ($_POST['openai_api_key_input'] ?? ''));
        if ($raw !== '') {
            save_openai_api_key($raw);
            $key_saved_msg = 'OpenAI API anahtarı kaydedildi.';
        }
    }
    if (isset($_POST['save_openai_model_bulk'])) {
        $m = isset($_POST['openai_model']) ? (string) $_POST['openai_model'] : '';
        save_openai_model($m);
        $model_saved_msg = 'OpenAI modeli güncellendi.';
    }
    if (isset($_POST['save_ce_production_settings'])) {
        $cron = isset($_POST['auto_blog_cron_enabled']) ? '1' : '0';
        $abMax = max(3, min(5, (int) ($_POST['auto_blog_ce_daily_max'] ?? 5)));
        br_setting_set($conn, MYNAK_CE_CRON_ENABLED_SETTING, $cron);
        br_setting_set($conn, AB_CE_DAILY_MAX_SETTING, (string) $abMax);
        $batch_saved_msg = 'Production ayarları kaydedildi. Cron=' . ($cron === '1' ? 'AÇIK' : 'KAPALI')
            . ', Otomatik Blog günlük max=' . $abMax;
    }
}

$ceCronOn = mynak_ce_cron_enabled($conn);
$abCeMax = ab_ce_daily_max($conn);
$abQuota = ab_ce_quota_status($conn);

$today = date('Y-m-d');
$dailyMax = br_daily_max($conn);
$quotaDay = br_setting_get($conn, 'blog_bulk_refresh_quota_day', '');
$quotaUsed = (int) br_setting_get($conn, 'blog_bulk_refresh_quota_used', '0');
if ($quotaDay !== $today) {
    $quotaUsed = 0;
}
$quotaRemaining = max(0, $dailyMax - $quotaUsed);

$cPendingUpgrade = 0;
if ($q = $conn->query('SELECT COUNT(*) AS c FROM blog_posts WHERE durum = 3 AND seo_content_upgrade_2026_at IS NULL')) {
    $cPendingUpgrade = (int) (($q->fetch_assoc()['c'] ?? 0));
}

/**
 * Tek tuş / hızlı koşu için limit: kota kalanı, günlük üst sınır ve bekleyen yazı sayısı.
 */
function br_bulk_smart_limit(int $dailyMax, int $quotaRemaining, int $pending): int
{
    $fromQuota = $quotaRemaining > 0 ? min($dailyMax, $quotaRemaining) : 0;
    $cap = max(1, min($dailyMax, $fromQuota > 0 ? $fromQuota : $dailyMax));
    if ($pending > 0) {
        $cap = min($cap, $pending);
    }

    return max(1, $cap);
}

$smartLimit = br_bulk_smart_limit($dailyMax, $quotaRemaining, $cPendingUpgrade);

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_bulk_refresh'])) {
    @set_time_limit(600);
    @ini_set('max_execution_time', '600');

    $dry = !empty($_POST['dry_run']);
    $ignore = !empty($_POST['ignore_quota']);
    $limit = isset($_POST['limit']) ? (int) $_POST['limit'] : 1;
    $before = isset($_POST['before']) ? trim((string) $_POST['before']) : '2025-01-01';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $before)) {
        $before = '2025-01-01';
    }
    $postId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    $dateCutoff = !empty($_POST['use_date_cutoff']);

    $productionTest = !empty($_POST['production_test']);

    $result = blog_bulk_content_refresh_run($conn, [
        'dry_run' => $dry,
        'ignore_quota' => $ignore,
        'limit' => $limit,
        'before' => $before,
        'post_id' => $postId > 0 ? $postId : null,
        'date_cutoff' => $dateCutoff,
        'production_test' => $productionTest,
    ]);

    $quotaDay = br_setting_get($conn, 'blog_bulk_refresh_quota_day', '');
    $quotaUsed = (int) br_setting_get($conn, 'blog_bulk_refresh_quota_used', '0');
    if ($quotaDay !== $today) {
        $quotaUsed = 0;
    }
    $quotaRemaining = max(0, $dailyMax - $quotaUsed);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_preview'])) {
    @set_time_limit(120);
    $quotaDay = br_setting_get($conn, 'blog_bulk_refresh_quota_day', '');
    $quotaUsed = (int) br_setting_get($conn, 'blog_bulk_refresh_quota_used', '0');
    if ($quotaDay !== $today) {
        $quotaUsed = 0;
    }
    $quotaRemaining = max(0, $dailyMax - $quotaUsed);
    if ($q = $conn->query('SELECT COUNT(*) AS c FROM blog_posts WHERE durum = 3 AND seo_content_upgrade_2026_at IS NULL')) {
        $cPendingUpgrade = (int) (($q->fetch_assoc()['c'] ?? 0));
    }
    $pvLimit = min(50, max(1, $cPendingUpgrade ?: 1));
    $result = blog_bulk_content_refresh_run($conn, [
        'dry_run' => true,
        'ignore_quota' => true,
        'limit' => $pvLimit,
        'before' => '2025-01-01',
        'post_id' => null,
        'date_cutoff' => false,
    ]);
}

if ($q = $conn->query('SELECT COUNT(*) AS c FROM blog_posts WHERE durum = 3 AND seo_content_upgrade_2026_at IS NULL')) {
    $cPendingUpgrade = (int) (($q->fetch_assoc()['c'] ?? 0));
}
$quotaDay = br_setting_get($conn, 'blog_bulk_refresh_quota_day', '');
$quotaUsed = (int) br_setting_get($conn, 'blog_bulk_refresh_quota_used', '0');
if ($quotaDay !== $today) {
    $quotaUsed = 0;
}
$quotaRemaining = max(0, $dailyMax - $quotaUsed);
$smartLimit = br_bulk_smart_limit($dailyMax, $quotaRemaining, $cPendingUpgrade);

$openai_key = get_openai_api_key();
$openai_model = get_openai_model();
$openai_ok = $openai_key !== '';
$canQuickRun = $openai_ok && $cPendingUpgrade > 0 && $quotaRemaining > 0;
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1"><i class="bx bx-refresh text-primary"></i> Toplu blog içerik yenileme</h4>
            <p class="text-muted mb-0">
                Yayında (<code>durum = 3</code>) yazıların gövdesini yapay zekâ ile yeniler.
                <strong>Slug sabit;</strong> başlık ana niyet korunarak güncellenebilir (<code>YENİ_BAŞLIK</code>).
                <strong>QC PASS</strong> → <a href="blog_review.php">editör onayı</a> (<code>durum=1</code>).
                <strong>QC FAIL</strong> → <a href="blog_review.php?status=2">düzeltme kuyruğu</a> (<code>durum=2</code>, otomatik yayın yok).
                Güvenli tempo: günde <strong>3–5</strong> yazı (kota). 466 toplu üretim önerilmez.
            </p>
        </div>
        <a href="blog_posts.php" class="btn btn-outline-secondary btn-sm"><i class="bx bx-list-ul"></i> Blog listesi</a>
    </div>

    <?php if ($key_saved_msg): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($key_saved_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($model_saved_msg): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($model_saved_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($batch_saved_msg): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo $batch_saved_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="card mb-4 border-0 shadow-sm bg-light">
        <div class="card-body py-4">
            <div class="row align-items-center g-3">
                <div class="col-lg-7">
                    <h5 class="mb-2">Hızlı işlem</h5>
                    <p class="mb-2 text-muted">
                        Bekleyen: <strong><?php echo (int) $cPendingUpgrade; ?></strong> yazı
                        (yayında, bu araçla henüz güncellenmemiş).
                        Bugün kalan kota: <strong><?php echo (int) $quotaRemaining; ?></strong> /
                        <?php echo (int) $dailyMax; ?>.
                    </p>
                    <p class="small text-muted mb-0">
                        Tek tuş: en fazla <strong><?php echo (int) $smartLimit; ?></strong> yazı,
                        <strong>1'er 1'er</strong> işlenir (sunucu zaman aşımı önlenir). Tarih filtresi kapalıdır.
                    </p>
                    <div id="bulkQuickProgress" class="small mt-2 d-none">
                        <div class="progress" style="height: 8px;">
                            <div id="bulkQuickProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                        </div>
                        <div id="bulkQuickProgressText" class="text-muted mt-1"></div>
                        <pre id="bulkQuickLog" class="small bg-white border rounded p-2 mt-2 mb-0" style="max-height: 160px; overflow: auto; white-space: pre-wrap;"></pre>
                    </div>
                </div>
                <div class="col-lg-5 text-lg-end">
                    <form method="post" class="d-inline-block me-2 mb-2">
                        <button type="submit" name="quick_preview" value="1"
                                class="btn btn-outline-secondary btn-lg"
                                title="API kullanılmaz; kaç yazının seçileceğini gösterir">
                            <i class="bx bx-show"></i> Önizleme
                        </button>
                    </form>
                    <button type="button" id="bulkQuickRunBtn"
                            class="btn btn-success btn-lg px-4 mb-2"
                            data-limit="<?php echo (int) $smartLimit; ?>"
                        <?php echo $canQuickRun ? '' : ' disabled'; ?>>
                        <i class="bx bx-zap"></i> Şimdi yenile (<?php echo (int) $smartLimit; ?> yazı)
                    </button>
                    <?php if (!$openai_ok): ?>
                        <div class="small text-danger mt-1">OpenAI anahtarı gerekli (aşağıdaki ayarlar).</div>
                    <?php elseif ($cPendingUpgrade === 0): ?>
                        <div class="small text-muted mt-1">Bekleyen yazı yok veya hepsi bu araçla işlendi.</div>
                    <?php elseif ($quotaRemaining === 0): ?>
                        <div class="small text-warning mt-1">Günlük kota doldu. Yarın tekrar deneyin veya gelişmiş seçeneklerden kotayı yoksayın.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Günlük kota (<code>blog_bulk_refresh_daily_max</code>)</div>
                    <div class="fs-4 fw-bold"><?php echo (int) $dailyMax; ?> / gün</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Bugün kullanılan</div>
                    <div class="fs-4 fw-bold"><?php echo (int) $quotaUsed; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Bugün kalan</div>
                    <div class="fs-4 fw-bold"><?php echo (int) $quotaRemaining; ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($result !== null): ?>
        <?php if ($result['fatal']): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($result['fatal']); ?></div>
        <?php elseif ($result['dry_run']): ?>
            <div class="alert alert-info">
                <strong>Önizleme tamamlandı.</strong>
                <?php echo (int) $result['candidates']; ?> yazı seçildi; API çağrısı yapılmadı.
                <?php if ((int) $result['candidates'] === 0): ?>
                    Günlükte <strong>[TEŞHİS]</strong> satırlarına bakın.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-<?php echo $result['processed'] > 0 ? 'success' : 'secondary'; ?>">
                <strong>İşlem bitti.</strong>
                İşlenen: <?php echo (int) $result['processed']; ?> /
                aday: <?php echo (int) $result['candidates']; ?>.
                QC pass: <strong><?php echo (int) ($result['qc_passed'] ?? 0); ?></strong>,
                QC fail (needs_revision): <strong><?php echo (int) ($result['qc_failed'] ?? 0); ?></strong>.
                <?php if ((int) ($result['qc_passed'] ?? 0) > 0): ?>
                    <a href="blog_review.php">Editör onay kuyruğu</a>.
                <?php endif; ?>
                <?php if ((int) ($result['qc_failed'] ?? 0) > 0): ?>
                    <a href="blog_review.php?status=2">Düzeltme kuyruğu</a>.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($result['production_test']) && !empty($result['bucket_gaps'])): ?>
            <div class="alert alert-warning">
                <strong>Production test — eksik kovalar:</strong>
                <?php foreach ($result['bucket_gaps'] as $bk => $gap): ?>
                    <span class="badge bg-warning text-dark me-1"><?php echo htmlspecialchars($bk); ?>
                        (<?php echo (int) $gap['found']; ?>/<?php echo (int) $gap['needed']; ?>)</span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($result['qc_reports'])): ?>
            <div class="card mb-4">
                <div class="card-header">Production test / QC raporu</div>
                <div class="card-body table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                        <tr>
                            <th>ID</th><th>Bucket</th><th>Seed</th><th>Flow</th><th>Intro</th>
                            <th>Kelime</th><th>QC</th><th>Kuyruk</th><th>Not</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($result['qc_reports'] as $r): ?>
                            <tr class="<?php echo ($r['qc_pass'] ?? null) === false ? 'table-danger' : (($r['qc_pass'] ?? null) === true ? 'table-success' : ''); ?>">
                                <td><?php echo (int) ($r['post_id'] ?? 0); ?></td>
                                <td><?php echo htmlspecialchars((string) ($r['bucket'] ?? '—')); ?></td>
                                <td><?php echo (int) ($r['seed'] ?? 0); ?></td>
                                <td><code><?php echo htmlspecialchars((string) ($r['flow_type'] ?? '')); ?></code></td>
                                <td><code><?php echo htmlspecialchars((string) ($r['intro_type'] ?? '')); ?></code></td>
                                <td><?php
                                    $wr = $r['word_range'] ?? [];
                                    echo (int) ($wr[0] ?? 0) . '–' . (int) ($wr[1] ?? 0);
                                ?></td>
                                <td><?php
                                    if ($r['qc_pass'] === null) {
                                        echo 'önizleme';
                                    } else {
                                        echo ($r['qc_pass'] ? 'PASS' : 'FAIL');
                                    }
                                ?></td>
                                <td><?php echo htmlspecialchars((string) ($r['queue'] ?? '')); ?></td>
                                <td class="small"><?php echo nl2br(htmlspecialchars((string) ($r['editor_note'] ?? ''))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">Günlük</div>
            <div class="card-body">
                <pre class="small mb-0" style="max-height: 320px; overflow: auto; white-space: pre-wrap;"><?php
                    echo htmlspecialchars(implode("\n", $result['logs']));
                ?></pre>
            </div>
        </div>
    <?php endif; ?>

    <div class="accordion mb-4" id="bulkAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingAdvanced">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseAdvanced" aria-expanded="false" aria-controls="collapseAdvanced">
                    <i class="bx bx-slider-alt me-2"></i> Gelişmiş — limit, tarih filtresi, tek ID, kota
                </button>
            </h2>
            <div id="collapseAdvanced" class="accordion-collapse collapse" aria-labelledby="headingAdvanced"
                 data-bs-parent="#bulkAccordion">
                <div class="accordion-body">
                    <form method="post" class="row g-3" id="bulkAdvancedForm"
                          onsubmit="return window.mynakBulkAdvancedSubmit ? window.mynakBulkAdvancedSubmit(this) : confirm('Yapay zekâ üretimi maliyet ve süre oluşturur. Devam?');">
                        <input type="hidden" name="run_bulk_refresh" value="1">

                        <div class="col-md-3">
                            <label class="form-label">Bu koşuda en fazla</label>
                            <input type="number" name="limit" class="form-control" value="<?php echo (int) min($dailyMax, max(1, $quotaRemaining ?: $dailyMax)); ?>" min="1" max="50" required>
                            <small class="text-muted">Kota ile sınırlanır (günlük üst: <?php echo (int) $dailyMax; ?>).</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Oluşturma tarihi öncesi eşiği</label>
                            <input type="date" name="before" class="form-control" value="2025-01-01">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="use_date_cutoff" value="1" id="use_date_cutoff">
                                <label class="form-check-label" for="use_date_cutoff">Bu tarihi uygula (<code>created_at &lt; tarih</code>)</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tek yazı ID</label>
                            <input type="number" name="post_id" class="form-control" value="" min="0" placeholder="Boş = toplu">
                        </div>
                        <div class="col-md-3 d-flex flex-column justify-content-end gap-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="dry_run" value="1" id="dry_run">
                                <label class="form-check-label" for="dry_run">Önizleme (API yok)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="ignore_quota" value="1" id="ignore_quota">
                                <label class="form-check-label text-danger" for="ignore_quota"><strong>Kotayı yoksay</strong></label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><i class="bx bx-play"></i> Bu ayarlarla başlat</button>
                            <span class="text-muted small ms-2">CLI: <code>php scripts/blog_bulk_content_refresh.php --any-date</code></span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingEngine">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseEngine" aria-expanded="false" aria-controls="collapseEngine">
                    <i class="bx bx-chip me-2"></i> Content Engine — batch_version &amp; production test
                </button>
            </h2>
            <div id="collapseEngine" class="accordion-collapse collapse" aria-labelledby="headingEngine"
                 data-bs-parent="#bulkAccordion">
                <div class="accordion-body">
                    <p class="text-muted small">
                        <code>batch_version</code> seed’e eklenir: <code>crc32(id|slug|baslik|batch_version)</code>.
                        Aynı yazıyı farklı varyantla yeniden üretmek için sürümü artırın (ör. 1 → 2).
                    </p>
                    <form method="post" class="row g-3 align-items-end mb-2">
                        <div class="col-md-3">
                            <label class="form-label">batch_version</label>
                            <input type="text" name="ce_batch_version" class="form-control"
                                   value="<?php echo htmlspecialchars($batchVersion); ?>" required>
                        </div>
                        <div class="col-md-9 d-flex flex-wrap gap-2 align-items-end">
                            <button type="submit" name="save_ce_batch_version" value="1" class="btn btn-outline-primary">
                                Kaydet
                            </button>
                            <button type="submit" name="bump_ce_batch_version" value="1" class="btn btn-outline-warning"
                                    onclick="return confirm('batch_version +1 — yeniden işlemede yeni varyant. Devam?');">
                                +1 (re-run varyantı)
                            </button>
                        </div>
                    </form>
                    <p class="small text-muted mb-4">
                        QC-fail / eski motor (<code>durum=2</code>): <strong><?php echo (int) $ceRerunCount; ?></strong> yazı.
                        Yeniden üretmek: batch_version artırın, yazıyı tekrar <code>durum=3</code> yapın veya tek ID ile çalıştırın.
                    </p>
                    <h6 class="mb-2">10 yazılık production test</h6>
                    <p class="small text-muted mb-2">
                        3 ana hizmet · 2 şehirlerarası · 2 asansörlü/teknik · 1 depo · 1 parça · 1 ofis.
                        Her çıktıda <code>[QC]</code> raporu loglanır.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <form method="post" class="d-inline">
                            <input type="hidden" name="run_bulk_refresh" value="1">
                            <input type="hidden" name="production_test" value="1">
                            <input type="hidden" name="dry_run" value="1">
                            <input type="hidden" name="ignore_quota" value="1">
                            <input type="hidden" name="limit" value="10">
                            <button type="submit" class="btn btn-outline-secondary btn-sm">
                                Test önizleme (API yok)
                            </button>
                        </form>
                        <form method="post" class="d-inline"
                              onsubmit="return confirm('10 yazılık production test — API maliyeti oluşur. Devam?');">
                            <input type="hidden" name="run_bulk_refresh" value="1">
                            <input type="hidden" name="production_test" value="1">
                            <input type="hidden" name="ignore_quota" value="1">
                            <input type="hidden" name="limit" value="10">
                            <button type="submit" class="btn btn-warning btn-sm" <?php echo $openai_ok ? '' : ' disabled'; ?>>
                                Test üret (10 yazı + QC)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingProd">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseProd" aria-expanded="false" aria-controls="collapseProd">
                    <i class="bx bx-shield-quarter me-2"></i> Production Engine &amp; Cron
                </button>
            </h2>
            <div id="collapseProd" class="accordion-collapse collapse" aria-labelledby="headingProd"
                 data-bs-parent="#bulkAccordion">
                <div class="accordion-body">
                    <form method="post" class="row g-3">
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="auto_blog_cron_enabled" id="cronEn"
                                    <?php echo $ceCronOn ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cronEn">Cron üretimi (varsayılan kapalı)</label>
                            </div>
                            <p class="small text-muted mb-0">cPanel: <code>admin/cron_auto_blog.php</code> — QC PASS olmadan yayın yok.</p>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Otomatik Blog günlük max (3–5)</label>
                            <input type="number" name="auto_blog_ce_daily_max" class="form-control form-control-sm"
                                   min="3" max="5" value="<?php echo (int) $abCeMax; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Bugün AB kotası</label>
                            <div class="form-control form-control-sm bg-light">
                                <?php echo (int) $abQuota['used']; ?> / <?php echo (int) $abQuota['max']; ?>
                                (kalan <?php echo (int) $abQuota['remaining']; ?>)
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" name="save_ce_production_settings" value="1" class="btn btn-sm btn-primary w-100">Kaydet</button>
                        </div>
                    </form>
                    <p class="small text-muted mt-2 mb-0">
                        Toplu yenileme kotası ayrı (<code>blog_bulk_refresh_daily_max</code>).
                        <a href="blog_review.php">Editör kuyruğu</a> ·
                        <a href="auto_blog_settings.php">Otomatik Blog ayarları</a> ·
                        <a href="content_roadmap.php"><strong>Intent Roadmap</strong></a>
                    </p>
                </div>
            </div>
        </div>
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingApi">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseApi" aria-expanded="false" aria-controls="collapseApi">
                    <i class="bx bx-key me-2"></i> OpenAI API ve model
                    <span class="badge <?php echo $openai_ok ? 'bg-success' : 'bg-warning text-dark'; ?> ms-2"><?php echo $openai_ok ? 'Kayıtlı' : 'Eksik'; ?></span>
                </button>
            </h2>
            <div id="collapseApi" class="accordion-collapse collapse" aria-labelledby="headingApi"
                 data-bs-parent="#bulkAccordion">
                <div class="accordion-body">
                    <p class="text-muted small mb-3">
                        <a href="auto_blog.php">Otomatik Blog</a> ile aynı ayarlar (<code>settings</code>).
                    </p>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <form method="post" class="d-flex flex-column gap-2">
                                <label class="form-label mb-0 small fw-semibold">API anahtarı</label>
                                <div class="input-group input-group-sm">
                                    <input type="password" name="openai_api_key_input" class="form-control" autocomplete="off"
                                           placeholder="<?php echo $openai_ok ? 'Yeni anahtar…' : 'sk-...'; ?>" value="">
                                    <button type="submit" name="save_openai_key" value="1" class="btn btn-primary">Kaydet</button>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form method="post" class="d-flex flex-wrap gap-2 align-items-center">
                                <label class="form-label mb-0 small fw-semibold me-2">Model</label>
                                <select name="openai_model" class="form-select form-select-sm" style="max-width:220px;">
                                    <?php foreach (['gpt-4o-mini' => 'gpt-4o-mini', 'gpt-4o' => 'gpt-4o', 'gpt-4-turbo' => 'gpt-4-turbo', 'gpt-3.5-turbo' => 'gpt-3.5-turbo'] as $v => $lab): ?>
                                        <option value="<?php echo htmlspecialchars($v); ?>" <?php echo $openai_model === $v ? 'selected' : ''; ?>><?php echo htmlspecialchars($lab); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="save_openai_model_bulk" value="1" class="btn btn-sm btn-outline-primary">Kaydet</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var stepUrl = 'ajax/blog_bulk_refresh_step.php';
    var btn = document.getElementById('bulkQuickRunBtn');
    var box = document.getElementById('bulkQuickProgress');
    var bar = document.getElementById('bulkQuickProgressBar');
    var txt = document.getElementById('bulkQuickProgressText');
    var logEl = document.getElementById('bulkQuickLog');

    function appendLog(lines) {
        if (!logEl || !lines || !lines.length) return;
        logEl.textContent += (logEl.textContent ? '\n' : '') + lines.join('\n');
        logEl.scrollTop = logEl.scrollHeight;
    }

    function runSteps(total, opts, onDone) {
        var done = 0;
        var ok = 0;
        var fail = 0;
        if (box) box.classList.remove('d-none');
        if (logEl) logEl.textContent = '';

        function step() {
            if (done >= total) {
                if (txt) txt.textContent = 'Tamamlandı: ' + ok + ' başarılı, ' + fail + ' atlandı/hata.';
                if (bar) bar.style.width = '100%';
                if (btn) btn.disabled = false;
                if (onDone) onDone({ ok: ok, fail: fail });
                return;
            }
            if (txt) txt.textContent = 'İşleniyor ' + (done + 1) + ' / ' + total + '… (yazı başına ~30 sn)';
            if (bar) bar.style.width = Math.round((done / total) * 100) + '%';

            var fd = new FormData();
            if (opts.ignore_quota) fd.append('ignore_quota', '1');
            if (opts.use_date_cutoff) fd.append('use_date_cutoff', '1');
            if (opts.before) fd.append('before', opts.before);
            if (opts.post_id) fd.append('post_id', String(opts.post_id));

            fetch(stepUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    appendLog(data.logs || []);
                    if (data.processed > 0) ok++;
                    else fail++;
                    if (data.fatal) appendLog(['[FATAL] ' + data.fatal]);
                    done++;
                    if (data.done && data.processed === 0 && done < total) {
                        fail += (total - done);
                        done = total;
                    }
                    step();
                })
                .catch(function (err) {
                    appendLog(['[HATA] ' + err.message]);
                    fail++;
                    done++;
                    step();
                });
        }
        step();
    }

    if (btn) {
        btn.addEventListener('click', function () {
            var total = parseInt(btn.getAttribute('data-limit') || '1', 10);
            if (!total || total < 1) total = 1;
            if (!confirm('En fazla ' + total + ' yazı, 1\'er 1\'er yenilenecek (OpenAI maliyeti). Devam?')) return;
            btn.disabled = true;
            runSteps(total, {}, function () {
                setTimeout(function () { location.reload(); }, 1200);
            });
        });
    }

    window.mynakBulkAdvancedSubmit = function (form) {
        if (form.querySelector('[name="dry_run"]') && form.querySelector('[name="dry_run"]').checked) {
            return confirm('Önizleme modu — API yok. Devam?');
        }
        var limit = parseInt(form.querySelector('[name="limit"]').value || '1', 10);
        if (limit <= 1) {
            return confirm('Yapay zekâ üretimi maliyet ve süre oluşturur. Devam?');
        }
        if (!confirm(limit + ' yazı 1\'er 1\'er işlenecek (timeout önlenir). Devam?')) return false;
        var postId = parseInt(form.querySelector('[name="post_id"]').value || '0', 10);
        if (postId > 0) {
            limit = 1;
        }
        var opts = {
            ignore_quota: !!(form.querySelector('[name="ignore_quota"]') && form.querySelector('[name="ignore_quota"]').checked),
            use_date_cutoff: !!(form.querySelector('[name="use_date_cutoff"]') && form.querySelector('[name="use_date_cutoff"]').checked),
            before: form.querySelector('[name="before"]').value || '2025-01-01',
            post_id: postId > 0 ? postId : 0
        };
        if (box) box.classList.remove('d-none');
        if (btn) btn.disabled = true;
        runSteps(limit, opts, function () {
            setTimeout(function () { location.reload(); }, 1200);
        });
        return false;
    };
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
