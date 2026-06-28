<?php
/**
 * CE Production Stabilization — 30 gün operasyon paneli (salt okunur rapor).
 * Yeni prompt/engine yok.
 */
declare(strict_types=1);

$page_title = 'CE Stabilizasyon (30 gün)';

require_once __DIR__ . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/mynak_ce_production.php';
require_once dirname(__DIR__) . '/includes/auto_blog_ce_adapter.php';
require_once dirname(__DIR__) . '/includes/mynak_ce_stabilization.php';
require_once dirname(__DIR__) . '/includes/mynak_search_intent_discovery.php';

if (!hasPermission('blog_view')) {
    echo '<div class="alert alert-danger">Bu sayfaya erişim yetkiniz yok.</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

mynak_ce_ensure_settings($conn);
$root = dirname(__DIR__);
$snap = mynak_ce_stab_operations_snapshot($conn, $root);
$qc = $snap['qc'];
$editor = $snap['editor'];
$deploy = $snap['deploy'];
$failReasons = $qc['fail_reasons'] ?? [];
$metaFails = $qc['meta_fail_reasons'] ?? [];
$totalAi = max(1, (int) ($qc['total_ai'] ?? 0));
$failRate = round(100 * ((int) ($qc['qc_fail'] ?? 0)) / $totalAi, 1);
?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-1"><i class="bx bx-shield-quarter"></i> CE Production Stabilization</h4>
            <p class="text-muted small mb-0">
                Pipeline <code><?php echo htmlspecialchars((string) $snap['pipeline_version']); ?></code> —
                Hedef: hacim değil; QC öğrenmesi + editör operasyonu. Yeni prompt/engine eklenmez.
            </p>
        </div>
        <div class="text-end small">
            <a href="blog_bulk_refresh.php" class="btn btn-sm btn-outline-secondary">Toplu yenileme</a>
            <a href="auto_blog_settings.php" class="btn btn-sm btn-outline-primary">Otomatik Blog CE</a>
            <a href="content_roadmap.php" class="btn btn-sm btn-outline-success">Roadmap</a>
        </div>
    </div>

    <?php if ($snap['cron_enabled']): ?>
    <div class="alert alert-danger">
        <strong>Cron AÇIK.</strong> 30 gün planı: <code>auto_blog_cron_enabled=0</code> olmalı.
        <a href="blog_bulk_refresh.php">Production panel</a> → Cron kapat → Kaydet.
    </div>
    <?php else: ?>
    <div class="alert alert-success py-2 mb-3">Cron kapalı — tam otomatik üretim yok. Günlük 3–5 manuel CE üretim.</div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="text-muted small">Bugün CE kotası</div>
                    <div class="fs-4 fw-bold"><?php echo (int) $snap['ab_quota_used_today']; ?> / <?php echo (int) $snap['ab_daily_max']; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="text-muted small">Bugün toplu yenileme</div>
                    <div class="fs-4 fw-bold"><?php echo (int) $snap['br_quota_used_today']; ?> / <?php echo (int) $snap['br_daily_max']; ?></div>
                    <div class="small text-warning">466 toplu — 30 gün ertelendi</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="text-muted small">30 gün QC FAIL oranı</div>
                    <div class="fs-4 fw-bold"><?php echo $failRate; ?>%</div>
                    <div class="small text-muted"><?php echo (int) $qc['qc_fail']; ?> fail / <?php echo (int) $qc['total_ai']; ?> AI yazı</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="text-muted small">Roadmap pending</div>
                    <div class="fs-4 fw-bold"><?php echo (int) $snap['roadmap_pending']; ?></div>
                    <div class="small text-muted">Üretime max 20–30 cluster</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header fw-semibold">Deploy dosya kontrolü (sunucu)</div>
                <div class="card-body">
                    <?php if ($deploy['ok']): ?>
                        <p class="text-success mb-2"><i class="bx bx-check-circle"></i> Zorunlu CE dosyaları mevcut.</p>
                    <?php else: ?>
                        <p class="text-danger mb-2"><i class="bx bx-error"></i> Eksik dosyalar (beyaz ekran riski):</p>
                        <ul class="small mb-0">
                            <?php foreach ($deploy['missing'] as $m): ?>
                            <li><code><?php echo htmlspecialchars($m); ?></code></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <p class="text-muted small mt-3 mb-0">
                        Detay: <code>docs/CE_DEPLOY_CHECKLIST.md</code>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header fw-semibold">30 gün QC dağılımı</div>
                <div class="card-body">
                    <span class="badge bg-success me-1">PASS <?php echo (int) $qc['qc_pass']; ?></span>
                    <span class="badge bg-warning text-dark me-1">WARN <?php echo (int) $qc['qc_warn']; ?></span>
                    <span class="badge bg-danger me-1">FAIL <?php echo (int) $qc['qc_fail']; ?></span>
                    <span class="badge bg-secondary">durum=2 <?php echo (int) $qc['durum_revision']; ?></span>
                    <?php if ($failReasons === []): ?>
                    <p class="text-muted small mt-3 mb-0">Henüz yeterli FAIL kaydı yok — CE üretim sonrası dolacak.</p>
                    <?php else: ?>
                    <table class="table table-sm mt-3 mb-0">
                        <thead><tr><th>FAIL nedeni</th><th>Adet</th></tr></thead>
                        <tbody>
                        <?php foreach ($failReasons as $reason => $cnt): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars((string) $reason); ?></code></td>
                            <td><?php echo (int) $cnt; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header fw-semibold">Profil / flow mix (son 30 gün)</div>
                <div class="card-body small">
                    <strong>Profil:</strong>
                    <?php echo $qc['profile_mix'] === [] ? '—' : ''; ?>
                    <?php foreach ($qc['profile_mix'] as $k => $v): ?>
                        <span class="badge bg-light text-dark border me-1"><?php echo htmlspecialchars((string) $k); ?> (<?php echo (int) $v; ?>)</span>
                    <?php endforeach; ?>
                    <br class="mb-2">
                    <strong>Flow:</strong>
                    <?php foreach ($qc['flow_mix'] as $k => $v): ?>
                        <span class="badge bg-light text-dark border me-1"><?php echo htmlspecialchars((string) $k); ?> (<?php echo (int) $v; ?>)</span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header fw-semibold">Editör dokunuşu (fingerprint)</div>
                <div class="card-body">
                    <p class="small text-muted">Kayıt: <?php echo (int) $editor['touch_count']; ?> yazıda [EDITOR_FP]</p>
                    <?php if (($editor['fields'] ?? []) === []): ?>
                    <p class="text-muted small mb-0">Henüz veri yok — Editör Kuyruğu → Fingerprint kaydedin.</p>
                    <?php else: ?>
                    <ul class="small mb-0">
                        <?php foreach ($editor['fields'] as $field => $cnt): ?>
                        <li><strong><?php echo htmlspecialchars((string) $field); ?></strong>: <?php echo (int) $cnt; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($qc['recent_fails'])): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header fw-semibold">Son QC FAIL yazıları</div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr><th>ID</th><th>Başlık</th><th>Nedenler</th><th>Tarih</th></tr>
                </thead>
                <tbody>
                <?php foreach ($qc['recent_fails'] as $f): ?>
                <tr>
                    <td><a href="blog_review.php?id=<?php echo (int) $f['id']; ?>"><?php echo (int) $f['id']; ?></a></td>
                    <td><?php echo htmlspecialchars((string) $f['baslik']); ?></td>
                    <td class="small"><code><?php echo htmlspecialchars(implode(', ', $f['reasons'] ?? [])); ?></code></td>
                    <td class="small text-muted"><?php echo htmlspecialchars((string) $f['at']); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header fw-semibold">Roadmap — önerilen 30 cluster (üretime)</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Öncelik</th><th>Query</th><th>Intent</th><th>Conv</th><th>Local</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($snap['roadmap_picks'])): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">Uygun pending cluster yok.</td></tr>
                    <?php else: ?>
                    <?php foreach ($snap['roadmap_picks'] as $c): ?>
                    <tr>
                        <td><?php echo number_format((float) $c['priority_score'], 1); ?></td>
                        <td><?php echo htmlspecialchars((string) $c['query_text']); ?></td>
                        <td><?php echo htmlspecialchars((string) $c['intent_type']); ?></td>
                        <td><?php echo (int) $c['conversion_score']; ?></td>
                        <td><?php echo !empty($c['local_intent']) ? 'evet' : '—'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer small text-muted">
            GSC gözlem: index/CTR/snippet — şimdilik sadece not; optimize etme.
            Plan: <code>docs/CE_30DAY_OPERATIONS.md</code>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
