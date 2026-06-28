<?php
/**
 * Search Intent Discovery — Content Roadmap (Faz A).
 * Discovery → insan onayı → Auto Blog / Bulk Refresh → CE (ayrı modül).
 */
declare(strict_types=1);

$page_title = 'İçerik Roadmap (Intent Discovery)';

require_once __DIR__ . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/mynak_search_intent_discovery.php';

if (!hasPermission('blog_view')) {
    echo '<div class="alert alert-danger">Bu sayfaya erişim yetkiniz yok.</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

sid_ensure_roadmap_table($conn);
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'run_discovery') {
        try {
            @set_time_limit(180);
            $result = sid_run_discovery($conn, true);
            $msg = 'Discovery tamamlandı: batch ' . htmlspecialchars($result['batch'])
                . ', ' . (int) $result['count'] . ' cluster kaydedildi. Kaynaklar: '
                . json_encode($result['sources'], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            $err = 'Discovery hatası: ' . $e->getMessage();
            if (function_exists('sid_log')) {
                sid_log('run_discovery: ' . $e->getMessage());
            }
        }
    }

    if ($action === 'save_gsc') {
        $raw = (string) ($_POST['gsc_import'] ?? '');
        $imp = sid_import_gsc_text($raw);
        if ($imp['ok'] && !empty($imp['items'])) {
            br_setting_set($conn, SID_GSC_CACHE_SETTING, json_encode($imp['items'], JSON_UNESCAPED_UNICODE));
            $msg = $imp['message'];
        } else {
            $err = $imp['message'] ?? 'GSC import başarısız';
        }
    }

    if ($action === 'save_seeds') {
        $seeds = trim((string) ($_POST['manual_seeds'] ?? ''));
        br_setting_set($conn, SID_MANUAL_SEEDS_SETTING, $seeds);
        $msg = 'Manuel seed listesi kaydedildi.';
    }

    if ($action === 'approve' && ($id = (int) ($_POST['cluster_row_id'] ?? 0))) {
        sid_update_cluster_status($conn, $id, 'approved');
        $msg = 'Cluster onaylandı.';
    }

    if ($action === 'reject' && ($id = (int) ($_POST['cluster_row_id'] ?? 0))) {
        sid_update_cluster_status($conn, $id, 'rejected');
        $msg = 'Cluster reddedildi.';
    }

    if ($action === 'send_auto_blog' && ($id = (int) ($_POST['cluster_row_id'] ?? 0))) {
        $r = sid_send_to_auto_blog($conn, $id);
        $msg = $r['message'] ?? '';
        if (!($r['ok'] ?? false)) {
            $err = $msg;
            $msg = '';
        }
    }

    if ($action === 'bulk_priority' && ($id = (int) ($_POST['cluster_row_id'] ?? 0))) {
        $r = sid_set_bulk_priority($conn, $id);
        $msg = $r['message'] ?? '';
        if (!($r['ok'] ?? false)) {
            $err = $msg;
            $msg = '';
        }
    }

    if ($action === 'export_json') {
        $json = br_setting_get($conn, SID_ROADMAP_JSON_SETTING, '');
        if ($json === '') {
            $err = 'Önce discovery çalıştırın.';
        } else {
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="roadmap_' . date('Y-m-d') . '.json"');
            echo $json;
            exit;
        }
    }
}

$filterStatus = isset($_GET['status']) ? (string) $_GET['status'] : '';
$clusters = sid_list_clusters($conn, $filterStatus !== '' ? $filterStatus : null, 300);
$manualSeeds = br_setting_get($conn, SID_MANUAL_SEEDS_SETTING, '');
$gscCount = 0;
$gscRaw = br_setting_get($conn, SID_GSC_CACHE_SETTING, '');
if ($gscRaw !== '') {
    $gscArr = json_decode($gscRaw, true);
    $gscCount = is_array($gscArr) ? count($gscArr) : 0;
}
$services = sid_service_clusters();
$pendingCount = 0;
$mergeCount = 0;
if ($pq = $conn->query("SELECT COUNT(*) AS c FROM content_roadmap_clusters WHERE status='pending'")) {
    $pendingCount = (int) ($pq->fetch_assoc()['c'] ?? 0);
}
if ($mq = $conn->query("SELECT COUNT(*) AS c FROM content_roadmap_clusters WHERE merge_recommendation='merge'")) {
    $mergeCount = (int) ($mq->fetch_assoc()['c'] ?? 0);
}
?>

<style>
.roadmap-table { font-size: 0.82rem; }
.roadmap-table th { white-space: nowrap; }
.score-pill { display: inline-block; min-width: 2rem; text-align: center; padding: 2px 6px; border-radius: 6px; font-size: 11px; font-weight: 600; }
.score-high { background: #d3f9d8; color: #2b8a3e; }
.score-mid { background: #fff3bf; color: #b58900; }
.score-low { background: #ffe0e0; color: #c92a2a; }
.merge-row { background: rgba(255, 193, 7, 0.08); }
</style>

<?php if ($msg): ?><div class="alert alert-success"><?php echo $msg; ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

<div class="alert alert-info py-2 small mb-3">
    <strong>30 gün stabilizasyon:</strong> Aynı anda yüzlerce cluster üretime sokmayın.
    En fazla <strong>20–30 yüksek öncelikli</strong> cluster (local + dönüşüm + düşük merge).
    <a href="ce_stabilization.php">CE Stabilizasyon</a> panelinde önerilen liste.
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1"><i class="bx bx-map"></i> Search Intent Discovery — Content Roadmap</h4>
        <p class="text-muted small mb-0">
            CE üretmez; <strong>neyi yazacağımızı</strong> planlar. Onay →
            <a href="auto_blog_settings.php">Auto Blog</a> veya
            <a href="blog_bulk_refresh.php">Bulk Refresh</a> → CE + QC.
        </p>
    </div>
    <div class="d-flex gap-2">
        <form method="post" class="d-inline">
            <input type="hidden" name="action" value="export_json">
            <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="bx bx-download"></i> roadmap.json</button>
        </form>
        <form method="post" class="d-inline" onsubmit="return confirm('Pending cluster\'lar silinip yeniden üretilecek. Devam?');">
            <input type="hidden" name="action" value="run_discovery">
            <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-radar"></i> Discovery çalıştır</button>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Bekleyen cluster</div>
                <div class="fs-4 fw-bold"><?php echo $pendingCount; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Merge önerisi</div>
                <div class="fs-4 fw-bold text-warning"><?php echo $mergeCount; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="text-muted small">GSC cache</div>
                <div class="fs-4 fw-bold"><?php echo $gscCount; ?> sorgu</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Hizmet kümeleri</div>
                <div class="fs-4 fw-bold"><?php echo count($services); ?></div>
            </div>
        </div>
    </div>
</div>

<div class="accordion mb-4" id="roadmapInputs">
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeeds">
                Girdi kaynakları (GSC + manuel seed)
            </button>
        </h2>
        <div id="collapseSeeds" class="accordion-collapse collapse" data-bs-parent="#roadmapInputs">
            <div class="accordion-body">
                <form method="post" class="mb-3">
                    <input type="hidden" name="action" value="save_seeds">
                    <label class="form-label small">Manuel seed (satır veya virgülle)</label>
                    <textarea name="manual_seeds" class="form-control form-control-sm" rows="3"><?php echo htmlspecialchars($manualSeeds); ?></textarea>
                    <button type="submit" class="btn btn-sm btn-outline-primary mt-2">Seed kaydet</button>
                </form>
                <form method="post">
                    <input type="hidden" name="action" value="save_gsc">
                    <label class="form-label small">GSC export (TSV/CSV: query, clicks, impressions)</label>
                    <textarea name="gsc_import" class="form-control form-control-sm font-monospace" rows="5" placeholder="query	clicks	impressions"></textarea>
                    <button type="submit" class="btn btn-sm btn-outline-success mt-2">GSC içe aktar</button>
                </form>
                <p class="small text-muted mt-2 mb-0">Ayrıca otomatik: blog başlıkları, slug’lar, auto_blog_settings keywords.</p>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <span class="fw-semibold">Önceliklendirilmiş cluster listesi</span>
        <div class="btn-group btn-group-sm">
            <a href="content_roadmap.php" class="btn btn-outline-secondary <?php echo $filterStatus === '' ? 'active' : ''; ?>">Tümü</a>
            <a href="?status=pending" class="btn btn-outline-secondary <?php echo $filterStatus === 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="?status=approved" class="btn btn-outline-secondary <?php echo $filterStatus === 'approved' ? 'active' : ''; ?>">Onaylı</a>
            <a href="?status=sent_auto_blog" class="btn btn-outline-secondary <?php echo $filterStatus === 'sent_auto_blog' ? 'active' : ''; ?>">Auto Blog</a>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if ($clusters === []): ?>
        <div class="p-4 text-center text-muted">
            Henüz roadmap yok. <strong>Discovery çalıştır</strong> ile başlayın.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover roadmap-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Öncelik</th>
                        <th>Query</th>
                        <th>Intent</th>
                        <th>Hizmet</th>
                        <th>AI</th>
                        <th>Conv</th>
                        <th>Local</th>
                        <th>Topical</th>
                        <th>Merge</th>
                        <th>CE öneri</th>
                        <th>Durum</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($clusters as $c):
                    $prio = (float) $c['priority_score'];
                    $merge = (string) ($c['merge_recommendation'] ?? 'none');
                    $rowClass = $merge === 'merge' ? 'merge-row' : '';
                    $scoreClass = static function (int $v): string {
                        return $v >= 65 ? 'score-high' : ($v >= 45 ? 'score-mid' : 'score-low');
                    };
                ?>
                    <tr class="<?php echo $rowClass; ?>">
                        <td><strong><?php echo number_format($prio, 1); ?></strong></td>
                        <td style="max-width:220px"><?php echo htmlspecialchars((string) $c['query_text']); ?></td>
                        <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars((string) $c['intent_type']); ?></span></td>
                        <td class="small"><?php echo htmlspecialchars((string) ($services[$c['service_group']]['label'] ?? $c['service_group'])); ?></td>
                        <td><span class="score-pill <?php echo $scoreClass((int) $c['ai_retrieval_score']); ?>"><?php echo (int) $c['ai_retrieval_score']; ?></span></td>
                        <td><span class="score-pill <?php echo $scoreClass((int) $c['conversion_score']); ?>"><?php echo (int) $c['conversion_score']; ?></span></td>
                        <td><span class="score-pill <?php echo $scoreClass((int) $c['local_score']); ?>"><?php echo (int) $c['local_score']; ?></span></td>
                        <td><span class="score-pill <?php echo $scoreClass((int) $c['topical_authority_score']); ?>"><?php echo (int) $c['topical_authority_score']; ?></span></td>
                        <td class="small">
                            <?php if ($merge === 'merge'): ?>
                                <span class="text-warning">merge →</span><br>
                                <code class="small"><?php echo htmlspecialchars((string) ($c['merge_target'] ?? '')); ?></code>
                            <?php elseif ($merge === 'canonical'): ?>
                                <span class="text-success">canonical</span>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td class="small" style="max-width:140px">
                            <?php echo htmlspecialchars((string) $c['recommended_profile']); ?> /
                            <?php echo htmlspecialchars((string) $c['recommended_flow']); ?><br>
                            <span class="text-muted"><?php echo htmlspecialchars((string) $c['suggested_word_range']); ?></span>
                        </td>
                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars((string) $c['status']); ?></span></td>
                        <td class="text-nowrap">
                            <?php if (($c['status'] ?? '') === 'pending' || ($c['status'] ?? '') === 'approved'): ?>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="cluster_row_id" value="<?php echo (int) $c['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-success" title="Onayla">✓</button>
                            </form>
                            <?php endif; ?>
                            <?php if ($merge !== 'merge'): ?>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="send_auto_blog">
                                <input type="hidden" name="cluster_row_id" value="<?php echo (int) $c['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Auto Blog'a gönder">AB</button>
                            </form>
                            <?php endif; ?>
                            <?php if ((int) ($c['linked_post_id'] ?? 0) > 0): ?>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="bulk_priority">
                                <input type="hidden" name="cluster_row_id" value="<?php echo (int) $c['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Bulk öncelik">BR</button>
                            </form>
                            <?php endif; ?>
                            <form method="post" class="d-inline" onsubmit="return confirm('Reddet?');">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="cluster_row_id" value="<?php echo (int) $c['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">×</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm mt-3">
    <div class="card-body small text-muted">
        <strong>Operasyon:</strong> Discovery → İnsan onayı → Auto Blog (yeni) / Bulk Refresh (mevcut yazı) → CE Production → QC → Editor Queue → Publish → GSC feedback (Faz C).
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
