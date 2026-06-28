<?php
/**
 * Toplu blog içerik yenileme (AI) — yayında yazıları yeniler, editör kuyruğuna alır.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once __DIR__ . '/includes/auto_blog_functions.php';
require_once dirname(__DIR__) . '/includes/blog_bulk_content_refresh_lib.php';

if (!hasPermission('blog_view')) {
    echo '<div class="alert alert-danger">Bu sayfaya erişim yetkiniz yok.</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$page_title = 'Toplu blog içerik yenileme';

$key_saved_msg = '';
$model_saved_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
}

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

    $result = blog_bulk_content_refresh_run($conn, [
        'dry_run' => $dry,
        'ignore_quota' => $ignore,
        'limit' => $limit,
        'before' => $before,
        'post_id' => $postId > 0 ? $postId : null,
        'date_cutoff' => $dateCutoff,
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_run'])) {
    @set_time_limit(600);
    @ini_set('max_execution_time', '600');

    $quotaDay = br_setting_get($conn, 'blog_bulk_refresh_quota_day', '');
    $quotaUsed = (int) br_setting_get($conn, 'blog_bulk_refresh_quota_used', '0');
    if ($quotaDay !== $today) {
        $quotaUsed = 0;
    }
    $quotaRemaining = max(0, $dailyMax - $quotaUsed);

    if ($q = $conn->query('SELECT COUNT(*) AS c FROM blog_posts WHERE durum = 3 AND seo_content_upgrade_2026_at IS NULL')) {
        $cPendingUpgrade = (int) (($q->fetch_assoc()['c'] ?? 0));
    }

    $lim = br_bulk_smart_limit($dailyMax, $quotaRemaining, $cPendingUpgrade);

    $result = blog_bulk_content_refresh_run($conn, [
        'dry_run' => false,
        'ignore_quota' => false,
        'limit' => $lim,
        'before' => '2025-01-01',
        'post_id' => null,
        'date_cutoff' => false,
    ]);

    $quotaDay = br_setting_get($conn, 'blog_bulk_refresh_quota_day', '');
    $quotaUsed = (int) br_setting_get($conn, 'blog_bulk_refresh_quota_used', '0');
    if ($quotaDay !== $today) {
        $quotaUsed = 0;
    }
    $quotaRemaining = max(0, $dailyMax - $quotaUsed);
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
                <strong>Başlık ve slug değişmez.</strong> Sonuç <a href="blog_review.php">editör kuyruğuna</a> (<code>durum = 1</code>) düşer.
                Sıra: <strong>öncelik skoru</strong> (İzmir / evden eve / ilçe / tavsiye-fiyat).
                İç link: <strong>hub-first</strong> (pillar + teklif + küme + konuya yakın blog).
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
                        Tek tuş: aşağıdaki koşuda en fazla <strong><?php echo (int) $smartLimit; ?></strong> yazı işlenir
                        (kota ve bekleyen sayısına göre otomatik). Tarih filtresi kapalıdır.
                    </p>
                </div>
                <div class="col-lg-5 text-lg-end">
                    <form method="post" class="d-inline-block me-2 mb-2">
                        <button type="submit" name="quick_preview" value="1"
                                class="btn btn-outline-secondary btn-lg"
                                title="API kullanılmaz; kaç yazının seçileceğini gösterir">
                            <i class="bx bx-show"></i> Önizleme
                        </button>
                    </form>
                    <form method="post" class="d-inline-block mb-2">
                        <button type="submit" name="quick_run" value="1"
                                class="btn btn-success btn-lg px-4"
                            <?php echo $canQuickRun ? '' : ' disabled'; ?>>
                            <i class="bx bx-zap"></i> Şimdi yenile (<?php echo (int) $smartLimit; ?> yazı)
                        </button>
                    </form>
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
                Başarılı: <?php echo (int) $result['processed']; ?> /
                aday: <?php echo (int) $result['candidates']; ?>.
                <?php if ((int) $result['candidates'] === 0): ?>
                    Günlükte <strong>[TEŞHİS]</strong> satırları nedenini özetler.
                <?php else: ?>
                    <a href="blog_review.php">Editör kuyruğunda</a> kontrol edin.
                <?php endif; ?>
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
                    <form method="post" class="row g-3"
                          onsubmit="return confirm('Yapay zekâ üretimi maliyet ve süre oluşturur. Devam?');">
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
