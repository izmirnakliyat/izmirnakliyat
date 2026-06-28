<?php
/**
 * EDİTÖR KUYRUĞU — admin/blog_review.php
 *
 * AI tarafında üretilen veya manuel olarak editör kuyruğuna alınmış
 * yazıların (durum=1) editör tarafından gözden geçirildiği, yazarının
 * değiştirildiği ve onaylanıp yayına alındığı tek sayfa.
 *
 * Modlar:
 *   - Liste (default): tüm durum=1 (editör kuyruğu) yazılar
 *   - Detay (?id=N):   tek yazı incelemesi
 *
 * Aksiyonlar (POST):
 *   - approve → durum=3 (yayında) + reviewed_by/at set
 *   - reject  → durum=2 (revize / düzeltme)
 *   - reassign   → author_id değiştir (durum aynı kalır)
 *   - save_notes → editor_notes güncelle
 *   - save_fingerprint → CE metadata (intro/cta/faq/profile)
 *   - bulk_*     → toplu işlem
 */

require_once 'includes/header.php';
require_once 'includes/auto_blog_functions.php';
require_once dirname(__DIR__) . '/includes/mynak_local_seo_content_engine.php';
require_once dirname(__DIR__) . '/includes/mynak_ce_production.php';
require_once dirname(__DIR__) . '/includes/blog_bulk_content_refresh_lib.php';

mynak_ce_ensure_settings($conn);

if (!hasPermission('blog_view')) {
    echo '<div class="alert alert-danger">Bu sayfaya erişim yetkiniz yok.</div>';
    require_once 'includes/footer.php';
    exit;
}

$page_title = "Editör Kuyruğu";
$msg = '';
$err = '';
$focus_id = isset($_GET['focus']) ? (int) $_GET['focus'] : 0;
$detail_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$current_admin_id = (int) ($_SESSION['admin_id'] ?? 0);

// ─────────────────────────────────────────────────────────────────────
// POST aksiyonları
// ─────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'approve' || $action === 'reject') {
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_map('intval', $_POST['ids']) : [];
        if ($id_single = (int) ($_POST['id'] ?? 0)) {
            $ids[] = $id_single;
        }
        $ids = array_unique(array_filter($ids));

        if (!$ids) {
            $err = 'Hiçbir yazı seçilmedi.';
        } elseif ($action === 'approve') {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types = 'ii' . str_repeat('i', count($ids)) . 'i';
            $params = array_merge([3, $current_admin_id], $ids, [1]);
            $stmt = $conn->prepare(
                "UPDATE blog_posts
                 SET durum = ?, reviewed_by = ?, reviewed_at = NOW()
                 WHERE id IN ($placeholders) AND durum = ?
                   AND (editor_notes IS NULL OR (
                        editor_notes NOT LIKE '%[QC_FAIL]%'
                        AND editor_notes NOT LIKE '%[QC_GATE] FAIL%'
                   ))"
            );
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $msg = $affected > 0
                ? "$affected yazı yayına alındı."
                : 'Yayınlanamadı — önce otomatik kontrolü geçmeli veya metni düzeltmelisiniz.';
            if ($affected === 0) {
                $err = $msg;
                $msg = '';
            }
        } else {
            // Revize: QC FAIL dahil durum 1 veya 2 → düzeltme kuyruğu (2)
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types = 'ii' . str_repeat('i', count($ids));
            $params = array_merge([2, $current_admin_id], $ids);
            $stmt = $conn->prepare(
                "UPDATE blog_posts
                 SET durum = ?, reviewed_by = ?, reviewed_at = NOW()
                 WHERE id IN ($placeholders) AND durum IN (1, 2)"
            );
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            if ($affected > 0) {
                $msg = "$affected yazı düzeltme kuyruğuna alındı (durum=2).";
            } else {
                $err = 'Revizeye gönderilemedi (yazı bulunamadı veya durum uygun değil).';
            }
        }
    }

    if ($action === 'reassign') {
        $id = (int) ($_POST['id'] ?? 0);
        $author_id = (int) ($_POST['author_id'] ?? 0);
        if ($id && $author_id) {
            $stmt = $conn->prepare("UPDATE blog_posts SET author_id = ? WHERE id = ?");
            $stmt->bind_param('ii', $author_id, $id);
            $stmt->execute();
            $msg = "Yazar değiştirildi.";
        }
    }

    if ($action === 'save_notes') {
        $id = (int) ($_POST['id'] ?? 0);
        $notes = trim((string) ($_POST['editor_notes'] ?? ''));
        if ($id) {
            $stmt = $conn->prepare("UPDATE blog_posts SET editor_notes = ? WHERE id = ?");
            $stmt->bind_param('si', $notes, $id);
            $stmt->execute();
            $msg = "Editör notu kaydedildi.";
        }
    }

    if ($action === 'save_fingerprint') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $fp = [
                'intro_type' => trim((string) ($_POST['fp_intro_type'] ?? '')),
                'profile_type' => trim((string) ($_POST['fp_profile_type'] ?? '')),
                'flow_type' => trim((string) ($_POST['fp_flow_type'] ?? '')),
                'faq_count' => (int) ($_POST['fp_faq_count'] ?? 0),
                'cta_style' => trim((string) ($_POST['fp_cta_style'] ?? '')),
                'local_entity' => trim((string) ($_POST['fp_local_entity'] ?? '')),
            ];
            $saved = mynak_ce_save_editor_fingerprint($conn, $id, $fp);
            $msg = ($saved['ok'] ?? false) ? 'Editör fingerprint metadata kaydedildi.' : ('Kayıt hatası: ' . ($saved['error'] ?? ''));
        }
    }

    // POST-redirect-GET
    $redirect = $_SERVER['PHP_SELF'];
    if ($action === 'reject') {
        $redirect .= '?status=2';
    } elseif ($detail_id && $action !== 'approve') {
        $redirect .= '?id=' . $detail_id;
    } elseif ($detail_id && $action === 'approve' && $msg !== '') {
        $redirect .= '?id=' . $detail_id;
    }
    $qm = $msg ?: $err;
    if ($qm !== '') {
        $redirect .= (str_contains($redirect, '?') ? '&' : '?') . 'm=' . urlencode($qm);
    }
    header('Location: ' . $redirect);
    exit;
}

if (isset($_GET['m'])) {
    $msg = (string) $_GET['m'];
}

// ─────────────────────────────────────────────────────────────────────
// Veri yükleme
// ─────────────────────────────────────────────────────────────────────

// Authors
$authors = [];
$r = $conn->query("SELECT id, name, slug, title FROM authors WHERE status = 1 ORDER BY is_default DESC, name ASC");
while ($row = $r->fetch_assoc()) {
    $authors[(int)$row['id']] = $row;
}

// Kategoriler
$categories = [];
$r = $conn->query("SELECT id, ad FROM blog_categories ORDER BY ad ASC");
while ($row = $r->fetch_assoc()) {
    $categories[(int)$row['id']] = $row['ad'];
}

// Filtre paramları
$filter_category = isset($_GET['cat']) ? (int) $_GET['cat'] : 0;
$filter_author   = isset($_GET['author']) ? (int) $_GET['author'] : 0;
$filter_ai       = isset($_GET['ai']) ? (string) $_GET['ai'] : '';
$filter_status   = isset($_GET['status']) ? (int) $_GET['status'] : 1;
if (!in_array($filter_status, [1, 2], true)) {
    $filter_status = 1;
}

// Sayı özetleri
$counts = [
    'review'   => (int) $conn->query("SELECT COUNT(*) AS c FROM blog_posts WHERE durum = 1")->fetch_assoc()['c'],
    'draft'    => (int) $conn->query("SELECT COUNT(*) AS c FROM blog_posts WHERE durum = 0")->fetch_assoc()['c'],
    'revision' => (int) $conn->query("SELECT COUNT(*) AS c FROM blog_posts WHERE durum = 2")->fetch_assoc()['c'],
    'published'=> (int) $conn->query("SELECT COUNT(*) AS c FROM blog_posts WHERE durum = 3")->fetch_assoc()['c'],
    'ai_today' => (int) $conn->query("SELECT COUNT(*) AS c FROM blog_posts WHERE is_ai_generated = 1 AND DATE(created_at) = CURDATE()")->fetch_assoc()['c'],
];

// Detay modu
if ($detail_id) {
    $stmt = $conn->prepare(
        "SELECT bp.*, bc.ad AS kategori_ad, a.name AS author_name
         FROM blog_posts bp
         LEFT JOIN blog_categories bc ON bc.id = bp.kategori_id
         LEFT JOIN authors a ON a.id = bp.author_id
         WHERE bp.id = ?"
    );
    $stmt->bind_param('i', $detail_id);
    $stmt->execute();
    $detail = $stmt->get_result()->fetch_assoc();
    if (!$detail) {
        echo '<div class="alert alert-danger">Yazı bulunamadı.</div>';
        require_once 'includes/footer.php';
        exit;
    }
    $ceMeta = mynak_ce_parse_post_meta($detail);
    $fp = is_array($ceMeta['editor_fingerprint'] ?? null) ? $ceMeta['editor_fingerprint'] : [];
    $qcFailNote = str_contains((string) ($detail['editor_notes'] ?? ''), '[QC_GATE] FAIL')
        || str_contains((string) ($detail['editor_notes'] ?? ''), '[QC_FAIL]');
    $scoreRow = mynak_ce_scores_from_row($detail);
}
?>

<style>
.review-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 1.5rem; }
.review-stat { background: var(--bg-secondary, #f8f9fa); border-radius: 12px; padding: 16px; display: flex; flex-direction: column; align-items: flex-start; }
.review-stat .num { font-size: 1.8rem; font-weight: 700; line-height: 1; }
.review-stat .lbl { font-size: 0.85rem; color: var(--text-secondary, #6c757d); margin-top: 4px; }
.review-stat.review .num { color: #f59f00; }
.review-stat.published .num { color: #2f9e44; }
.review-stat.rejected .num { color: #e03131; }
.review-stat.ai .num { color: #6741d9; }
.review-stat.draft .num { color: #868e96; }

.quality-pill { display: inline-flex; align-items: center; gap: 4px; padding: 2px 10px; border-radius: 50px; font-size: 11px; font-weight: 600; }
.quality-high { background: #d3f9d8; color: #2b8a3e; }
.quality-mid  { background: #fff3bf; color: #b58900; }
.quality-low  { background: #ffe0e0; color: #c92a2a; }

.ai-badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 50px; font-size: 10px; font-weight: 700; background: #e7f5ff; color: #1971c2; }

.review-row { transition: background 0.2s; }
.review-row:hover { background: rgba(0,0,0,0.02); }
.review-title-link { color: var(--text-primary, #212529); text-decoration: none; font-weight: 600; }
.review-title-link:hover { color: #1971c2; text-decoration: underline; }

.checklist-item { display: flex; gap: 8px; align-items: flex-start; padding: 6px 0; border-bottom: 1px dashed rgba(0,0,0,0.05); }
.checklist-item input[type="checkbox"] { margin-top: 4px; }
.checklist-item label { flex: 1; cursor: pointer; }

.detail-preview { background: white; border: 1px solid #e9ecef; border-radius: 12px; padding: 24px; max-height: 700px; overflow-y: auto; }
.detail-preview h2 { font-size: 1.4rem; margin-top: 1.5rem; }
.detail-preview h3 { font-size: 1.15rem; margin-top: 1rem; }
.detail-preview p  { line-height: 1.7; }

.bulk-bar { position: sticky; top: 0; z-index: 50; background: #fff3bf; border-radius: 8px; padding: 12px 16px; display: none; align-items: center; justify-content: space-between; margin-bottom: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.bulk-bar.active { display: flex; }
</style>

<?php if ($msg): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div>
<?php endif; ?>

<!-- İstatistikler -->
<div class="review-stats">
    <a href="blog_review.php?status=1" class="review-stat review text-decoration-none" style="color:inherit;">
        <div class="num"><?php echo $counts['review']; ?></div>
        <div class="lbl"><i class="bx bx-time-five"></i> İnceleme bekliyor</div>
    </a>
    <div class="review-stat published">
        <div class="num"><?php echo $counts['published']; ?></div>
        <div class="lbl"><i class="bx bx-check-circle"></i> Yayında</div>
    </div>
    <div class="review-stat draft">
        <div class="num"><?php echo $counts['draft']; ?></div>
        <div class="lbl"><i class="bx bx-file-blank"></i> Taslak</div>
    </div>
    <a href="blog_review.php?status=2" class="review-stat rejected text-decoration-none" style="color:inherit;">
        <div class="num"><?php echo $counts['revision']; ?></div>
        <div class="lbl"><i class="bx bx-shield-x"></i> Düzeltme gerekli</div>
    </a>
    <div class="review-stat ai">
        <div class="num"><?php echo $counts['ai_today']; ?></div>
        <div class="lbl"><i class="bx bx-bot"></i> Bugün AI</div>
    </div>
</div>

<?php if ($detail_id && isset($detail)): ?>
    <!-- ═══════════════════════════════════════════════════ -->
    <!-- DETAY MODU                                            -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <a href="blog_review.php" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="bx bx-arrow-back"></i> Kuyruk
                        </a>
                        <span class="fw-semibold"><?php echo htmlspecialchars($detail['baslik']); ?></span>
                    </div>
                    <div>
                        <?php echo mynak_ce_render_score_badges_html($scoreRow, false); ?>
                        <?php if ((int)$detail['is_ai_generated'] === 1): ?>
                            <span class="ai-badge ms-1"><i class="bx bx-bot"></i> AI Üretim</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($ceMeta['profile_type']) || !empty($ceMeta['intro_type'])): ?>
                    <div class="alert alert-light border small py-2 mb-3">
                        <strong>CE:</strong>
                        profil=<?php echo htmlspecialchars((string)($ceMeta['profile_type'] ?? '—')); ?> ·
                        intro=<?php echo htmlspecialchars((string)($ceMeta['intro_type'] ?? '—')); ?> ·
                        flow=<?php echo htmlspecialchars((string)($ceMeta['flow_type'] ?? '—')); ?> ·
                        kelime=<?php echo (int)(($ceMeta['word_range'][0] ?? 0)); ?>–<?php echo (int)(($ceMeta['word_range'][1] ?? 0)); ?>
                    </div>
                    <?php endif; ?>
                    <div class="mb-2 text-muted small">
                        <i class="bx bx-folder"></i> <?php echo htmlspecialchars($categories[(int)$detail['kategori_id']] ?? '—'); ?> ·
                        <i class="bx bx-link"></i> /blog/<?php echo htmlspecialchars($detail['slug']); ?> ·
                        <i class="bx bx-calendar"></i> <?php echo date('d.m.Y H:i', strtotime($detail['created_at'])); ?>
                    </div>
                    <div class="detail-preview">
                        <?php echo $detail['icerik']; /* zaten HTML */ ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <!-- Yayın Kararı Kartı -->
            <div class="card mb-3">
                <div class="card-header"><i class="bx bx-clipboard-check"></i> Yayın Kontrol Listesi</div>
                <div class="card-body">
                    <p class="text-muted small">Yayınlamadan önce kontrol et:</p>
                    <div class="checklist-item">
                        <input type="checkbox" id="ck1" class="form-check-input">
                        <label for="ck1">Başlık doğal ve Türkçe doğru</label>
                    </div>
                    <div class="checklist-item">
                        <input type="checkbox" id="ck2" class="form-check-input">
                        <label for="ck2">AI imzası/jenerik kapanış cümlesi temizlendi</label>
                    </div>
                    <div class="checklist-item">
                        <input type="checkbox" id="ck3" class="form-check-input">
                        <label for="ck3">En az 1 kişisel/saha deneyimi cümlesi var</label>
                    </div>
                    <div class="checklist-item">
                        <input type="checkbox" id="ck4" class="form-check-input">
                        <label for="ck4">Yazar konuya uygun atandı</label>
                    </div>
                    <div class="checklist-item">
                        <input type="checkbox" id="ck5" class="form-check-input">
                        <label for="ck5">Kapak fotoğrafı var</label>
                    </div>
                    <div class="checklist-item">
                        <input type="checkbox" id="ck6" class="form-check-input">
                        <label for="ck6">İçindeki link/iletişim bilgileri kontrol edildi</label>
                    </div>
                </div>
            </div>

            <!-- Yazar Atama -->
            <div class="card mb-3">
                <div class="card-header"><i class="bx bx-user-pin"></i> Yazar Atama</div>
                <div class="card-body">
                    <form method="post" class="d-flex gap-2">
                        <input type="hidden" name="action" value="reassign">
                        <input type="hidden" name="id" value="<?php echo $detail_id; ?>">
                        <select name="author_id" class="form-select form-select-sm">
                            <?php foreach ($authors as $aid => $a): ?>
                                <option value="<?php echo $aid; ?>" <?php echo ((int)$detail['author_id'] === $aid) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($a['name']); ?>
                                    <?php if (!empty($a['title'])): ?>
                                        — <?php echo htmlspecialchars($a['title']); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-primary">Ata</button>
                    </form>
                    <div class="text-muted small mt-2">Mevcut: <strong><?php echo htmlspecialchars($detail['author_name'] ?? '—'); ?></strong></div>
                </div>
            </div>

            <!-- Editör Notu -->
            <div class="card mb-3">
                <div class="card-header"><i class="bx bx-note"></i> Editör Notu</div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="save_notes">
                        <input type="hidden" name="id" value="<?php echo $detail_id; ?>">
                        <textarea name="editor_notes" class="form-control mb-2" rows="3" placeholder="Yazar için not, düzeltme talebi vs."><?php echo htmlspecialchars((string)($detail['editor_notes'] ?? '')); ?></textarea>
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Notu Kaydet</button>
                    </form>
                </div>
            </div>

            <!-- Editör Fingerprint (CE metadata) -->
            <div class="card mb-3">
                <div class="card-header"><i class="bx bx-fingerprint"></i> Editör Fingerprint</div>
                <div class="card-body">
                    <p class="text-muted small mb-2">Sonraki CE üretiminde kullanılacak varyant tercihleri (GSC metadata).</p>
                    <form method="post" class="row g-2">
                        <input type="hidden" name="action" value="save_fingerprint">
                        <input type="hidden" name="id" value="<?php echo $detail_id; ?>">
                        <div class="col-12">
                            <label class="form-label small">Profil</label>
                            <select name="fp_profile_type" class="form-select form-select-sm">
                                <?php foreach (array_keys(mynak_ce_behavior_profiles()) as $pk): ?>
                                <option value="<?php echo htmlspecialchars($pk); ?>" <?php echo (($fp['profile_type'] ?? $ceMeta['profile_type'] ?? '') === $pk) ? 'selected' : ''; ?>><?php echo htmlspecialchars($pk); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Intro tipi</label>
                            <select name="fp_intro_type" class="form-select form-select-sm">
                                <?php
                                $intros = ['direct_answer','user_question','mini_scenario','field_observation','myth_bust','checklist','price_worry','planning_stress'];
                                foreach ($intros as $it):
                                ?>
                                <option value="<?php echo $it; ?>" <?php echo (($fp['intro_type'] ?? $ceMeta['intro_type'] ?? '') === $it) ? 'selected' : ''; ?>><?php echo $it; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">FAQ sayısı</label>
                            <input type="number" name="fp_faq_count" class="form-control form-control-sm" min="0" max="8" value="<?php echo (int)($fp['faq_count'] ?? $ceMeta['faq_count'] ?? 4); ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Flow</label>
                            <input type="text" name="fp_flow_type" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)($fp['flow_type'] ?? $ceMeta['flow_type'] ?? 'A-linear')); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small">CTA stili</label>
                            <input type="text" name="fp_cta_style" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)($fp['cta_style'] ?? $ceMeta['cta_style'] ?? '')); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Lokal entity (ilçe/yer)</label>
                            <input type="text" name="fp_local_entity" class="form-control form-control-sm" placeholder="örn. Bornova" value="<?php echo htmlspecialchars((string)($fp['local_entity'] ?? $ceMeta['local_entity'] ?? '')); ?>">
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-primary">Fingerprint Kaydet</button>
                        </div>
                    </form>
                    <div id="ce-regen-status" class="small text-muted mt-2"></div>
                </div>
            </div>

            <!-- Karar — basit akış -->
            <div class="card border-primary">
                <div class="card-header bg-primary text-white"><i class="bx bx-magic-wand"></i> Ne yapalım?</div>
                <div class="card-body d-flex flex-column gap-2">
                    <p class="small text-muted mb-1">
                        <?php if (!empty($qcFailNote)): ?>
                        <strong class="text-danger">Otomatik kontrol geçmedi</strong> — çoğunlukle metin kısa veya tekrarlı kelime. Aşağıdan <em>Tekrar yaz</em> deneyin.
                        <?php elseif (($scoreRow['qc_status'] ?? '') === 'PASS'): ?>
                        <strong class="text-success">Hazır görünüyor</strong> — okuyup yayınlayabilirsiniz.
                        <?php else: ?>
                        İnceleyin; gerekirse tekrar yazdırın.
                        <?php endif; ?>
                    </p>
                    <button type="button" class="btn btn-primary btn-lg w-100" id="btn-rewrite-send" data-id="<?php echo $detail_id; ?>">
                        <i class="bx bx-refresh"></i> Tekrar yaz ve editöre al
                    </button>
                    <div id="rewrite-send-status" class="small text-muted"></div>
                    <hr class="my-1">
                    <a href="blog_edit.php?id=<?php echo $detail_id; ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bx bx-edit"></i> Elle düzenle
                    </a>
                    <?php if (empty($qcFailNote)): ?>
                    <form method="post" onsubmit="return confirm('Bu yazıyı yayına almak istediğinizden emin misiniz?');">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="id" value="<?php echo $detail_id; ?>">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bx bx-check"></i> Yayınla
                        </button>
                    </form>
                    <?php endif; ?>
                    <form method="post" onsubmit="return confirm('Düzeltme listesinde kalsın mı?');">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="id" value="<?php echo $detail_id; ?>">
                        <button type="submit" class="btn btn-link btn-sm text-danger p-0">
                            Düzeltme listesinde tut
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ═══════════════════════════════════════════════════ -->
    <!-- LİSTE MODU                                            -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bx bx-time-five text-warning"></i> Editör Kuyruğu</h5>
            <div>
                <a href="auto_blog_settings.php" class="btn btn-outline-info btn-sm">
                    <i class="bx bx-bot"></i> Otomatik Blog (CE)
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Filtreler -->
            <form method="get" class="row g-2 mb-3">
                <div class="col-md-3">
                    <select name="cat" class="form-select form-select-sm">
                        <option value="0">Tüm Kategoriler</option>
                        <?php foreach ($categories as $cid => $cname): ?>
                            <option value="<?php echo $cid; ?>" <?php echo $filter_category === $cid ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cname); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="author" class="form-select form-select-sm">
                        <option value="0">Tüm Yazarlar</option>
                        <?php foreach ($authors as $aid => $a): ?>
                            <option value="<?php echo $aid; ?>" <?php echo $filter_author === $aid ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($a['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="ai" class="form-select form-select-sm">
                        <option value="">AI/İnsan: hepsi</option>
                        <option value="1" <?php echo $filter_ai === '1' ? 'selected' : ''; ?>>Sadece AI üretim</option>
                        <option value="0" <?php echo $filter_ai === '0' ? 'selected' : ''; ?>>Sadece insan üretim</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="bx bx-filter"></i> Filtrele
                    </button>
                </div>
            </form>

            <!-- Bulk Bar -->
            <form method="post" id="bulkForm">
                <div class="bulk-bar" id="bulkBar">
                    <span><strong id="bulkCount">0</strong> yazı seçildi</span>
                    <div>
                        <?php if ($filter_status === 1): ?>
                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-success" onclick="return confirm('Seçili yazıları yayına almak istediğinizden emin misiniz?');">
                            <i class="bx bx-check"></i> Toplu Onayla & Yayınla
                        </button>
                        <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline-danger" onclick="return confirm('Seçili yazıları revizeye göndermek istediğinizden emin misiniz?');">
                            <i class="bx bx-undo"></i> Toplu revize
                        </button>
                        <?php else: ?>
                        <span class="small text-muted">Düzeltme listesi — yazıyı açıp <strong>Tekrar yaz ve editöre al</strong> kullanın.</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php
                // Liste sorgusu
                $where = ['bp.durum = ?'];
                $bindTypes = 'i';
                $bindVals = [$filter_status];
                if ($filter_category > 0) { $where[] = 'bp.kategori_id = ?'; $bindTypes .= 'i'; $bindVals[] = $filter_category; }
                if ($filter_author > 0) { $where[] = 'bp.author_id = ?'; $bindTypes .= 'i'; $bindVals[] = $filter_author; }
                if ($filter_ai !== '') { $where[] = 'bp.is_ai_generated = ?'; $bindTypes .= 'i'; $bindVals[] = (int) $filter_ai; }

                $sql = "SELECT bp.id, bp.baslik, bp.slug, bp.created_at, bp.author_id, bp.kategori_id,
                               bp.is_ai_generated, bp.ai_quality_score, bp.kapak_foto,
                               a.name AS author_name, bc.ad AS kategori_ad,
                               CHAR_LENGTH(bp.icerik) AS icerik_len
                        FROM blog_posts bp
                        LEFT JOIN authors a ON a.id = bp.author_id
                        LEFT JOIN blog_categories bc ON bc.id = bp.kategori_id
                        WHERE " . implode(' AND ', $where) . "
                        ORDER BY bp.created_at DESC
                        LIMIT 200";

                $stmt = $conn->prepare($sql);
                if ($bindVals) $stmt->bind_param($bindTypes, ...$bindVals);
                $stmt->execute();
                $list = $stmt->get_result();
                ?>

                <?php if ($list->num_rows === 0): ?>
                    <div class="alert alert-success text-center mb-0">
                        <i class="bx bx-check-circle" style="font-size: 2rem;"></i>
                        <h5 class="mt-2"><?php echo $filter_status === 2 ? 'Düzeltme kuyruğu boş.' : 'Editör kuyruğu boş.'; ?></h5>
                        <p class="mb-0 text-muted">Yeni AI üretimleri burada görünecek.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th width="40"><input type="checkbox" id="selectAll"></th>
                                    <th>Başlık</th>
                                    <th width="160">Kategori</th>
                                    <th width="160">Yazar</th>
                                    <th width="180">Yapı / QC</th>
                                    <th width="80">Tip</th>
                                    <th width="120">Tarih</th>
                                    <th width="200">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $list->fetch_assoc()):
                                    $scoreRow = mynak_ce_scores_from_row($row);
                                    $isFocus = ($focus_id === (int) $row['id']);
                                ?>
                                <tr class="review-row <?php echo $isFocus ? 'table-warning' : ''; ?>">
                                    <td>
                                        <input type="checkbox" name="ids[]" value="<?php echo (int)$row['id']; ?>" class="row-check">
                                    </td>
                                    <td>
                                        <a href="blog_review.php?id=<?php echo (int)$row['id']; ?>" class="review-title-link">
                                            <?php echo htmlspecialchars($row['baslik']); ?>
                                        </a>
                                        <div class="text-muted small">/blog/<?php echo htmlspecialchars($row['slug']); ?> · <?php echo number_format(strlen(strip_tags($row['baslik'])) + (int)$row['icerik_len']); ?> karakter</div>
                                    </td>
                                    <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($row['kategori_ad'] ?? '—'); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['author_name'] ?? '—'); ?></td>
                                    <td class="small"><?php echo mynak_ce_render_score_badges_html($scoreRow, true); ?></td>
                                    <td>
                                        <?php if ((int)$row['is_ai_generated'] === 1): ?>
                                            <span class="ai-badge"><i class="bx bx-bot"></i> AI</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">İnsan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small"><?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <a href="blog_review.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-outline-primary" title="İncele">
                                            <i class="bx bx-show"></i>
                                        </a>
                                        <a href="blog_edit.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Düzenle">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script>
    (function() {
        const checks = document.querySelectorAll('.row-check');
        const all = document.getElementById('selectAll');
        const bar = document.getElementById('bulkBar');
        const cnt = document.getElementById('bulkCount');
        function update() {
            const selected = Array.from(checks).filter(c => c.checked).length;
            cnt.textContent = selected;
            bar.classList.toggle('active', selected > 0);
        }
        all?.addEventListener('change', () => {
            checks.forEach(c => c.checked = all.checked);
            update();
        });
        checks.forEach(c => c.addEventListener('change', update));
    })();
    </script>

<?php endif; ?>

<?php if ($detail_id && isset($detail)): ?>
<script>
function fetchRewriteSend(fd, ms) {
    var ctrl = new AbortController();
    var t = setTimeout(function() { ctrl.abort(); }, ms);
    return fetch('ajax/blog_ce_rewrite_send.php', { method: 'POST', body: fd, signal: ctrl.signal })
        .finally(function() { clearTimeout(t); });
}
function runRewriteSend(btn, statusEl) {
    var id = btn.getAttribute('data-id');
    if (!confirm('AI metni yeniden yazacak (3–8 dk sürebilir). Sayfayı kapatmayın. Devam?')) return;
    statusEl.textContent = 'Yazılıyor… 3–8 dk sürebilir, lütfen bekleyin';
    btn.disabled = true;
    var fd = new FormData();
    fd.append('post_id', id);
    var tick = 0;
    var pulse = setInterval(function() {
        tick += 30;
        statusEl.textContent = 'Yazılıyor… ' + tick + ' sn (normal: 3–8 dk)';
    }, 30000);
    fetchRewriteSend(fd, 600000)
        .then(function(r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function(d) {
            statusEl.textContent = d.message || (d.success ? 'Tamam' : 'Hata');
            if (d.success && d.review_url) {
                setTimeout(function() { location.href = d.review_url; }, 1500);
            }
        })
        .catch(function(e) {
            statusEl.textContent = (e && e.name === 'AbortError')
                ? 'Zaman aşımı (10 dk). Hosting Proxy Timeout artırılmalı veya tekrar deneyin.'
                : 'Bağlantı hatası: ' + (e.message || 'bilinmiyor');
        })
        .finally(function() { clearInterval(pulse); btn.disabled = false; });
}
document.getElementById('btn-rewrite-send')?.addEventListener('click', function() {
    runRewriteSend(this, document.getElementById('rewrite-send-status'));
});
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
