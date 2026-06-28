<?php
require_once 'includes/header.php';
require_once __DIR__ . '/../includes/seo_runtime.php';

$page_title = 'İç Link Anchor Havuzu';
$success = '';
$error = '';

// Tablo garantisi
@$conn->query("CREATE TABLE IF NOT EXISTS `internal_link_anchors` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `target_slug` VARCHAR(191) NOT NULL,
  `anchor_text` VARCHAR(255) NOT NULL,
  `weight` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `note` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_slug_active` (`target_slug`, `active`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Silme
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($id > 0 && $conn->query("DELETE FROM internal_link_anchors WHERE id = $id")) {
        $success = 'Anchor silindi.';
    } else {
        $error = 'Silme başarısız: ' . $conn->error;
    }
}

// Aktif/pasif toggle
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    if ($id > 0 && $conn->query("UPDATE internal_link_anchors SET active = 1 - active WHERE id = $id")) {
        $success = 'Durum değiştirildi.';
    } else {
        $error = 'Değiştirilemedi.';
    }
}

// Ekle / güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_anchor'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $slug = trim((string) ($_POST['target_slug'] ?? ''), " \t\n\r\0\x0B/");
    $anchor = trim((string) ($_POST['anchor_text'] ?? ''));
    $weight = max(1, min(10, (int) ($_POST['weight'] ?? 1)));
    $active = isset($_POST['active']) ? 1 : 0;
    $note = trim((string) ($_POST['note'] ?? ''));
    if ($slug === '' || $anchor === '') {
        $error = 'Hedef slug ve anchor metni zorunludur.';
    } elseif (mb_strlen($anchor) < 4 || mb_strlen($anchor) > 255) {
        $error = 'Anchor metni 4–255 karakter olmalı.';
    } else {
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE internal_link_anchors SET target_slug=?, anchor_text=?, weight=?, active=?, note=? WHERE id=?");
            $stmt->bind_param('ssiisi', $slug, $anchor, $weight, $active, $note, $id);
            if ($stmt->execute()) {
                $success = 'Anchor güncellendi.';
            } else {
                $error = 'Güncelleme hatası: ' . $stmt->error;
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO internal_link_anchors (target_slug, anchor_text, weight, active, note) VALUES (?,?,?,?,?)");
            $stmt->bind_param('ssiis', $slug, $anchor, $weight, $active, $note);
            if ($stmt->execute()) {
                $success = 'Anchor eklendi.';
            } else {
                $error = 'Ekleme hatası: ' . $stmt->error;
            }
        }
    }
}

// Slug önerileri (pillar tanımları + services)
$slugOptions = [];
if (function_exists('seo_rt_pillar_cluster_definitions')) {
    foreach (seo_rt_pillar_cluster_definitions() as $slug => $def) {
        $slugOptions[$slug] = (string) ($def['nav_title'] ?? $slug);
    }
}
$svcRes = @$conn->query("SELECT slug, ana_baslik FROM services WHERE status = 1 ORDER BY slug");
if ($svcRes) {
    while ($r = $svcRes->fetch_assoc()) {
        $s = trim((string) ($r['slug'] ?? ''));
        if ($s !== '' && !isset($slugOptions[$s])) {
            $slugOptions[$s] = (string) ($r['ana_baslik'] ?? $s);
        }
    }
}
ksort($slugOptions);

// Liste
$filterSlug = trim((string) ($_GET['q'] ?? ''), " \t\n\r\0\x0B/");
$where = '';
$params = [];
$types = '';
if ($filterSlug !== '') {
    $where = "WHERE target_slug = ?";
    $params[] = $filterSlug;
    $types = 's';
}
$rows = [];
$sql = "SELECT * FROM internal_link_anchors $where ORDER BY target_slug ASC, active DESC, weight DESC, id DESC";
if ($params !== []) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
} else {
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
    }
}

// Slug başına sayım
$countsBySlug = [];
foreach ($rows as $r) {
    $s = (string) $r['target_slug'];
    $countsBySlug[$s] = ($countsBySlug[$s] ?? 0) + 1;
}
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">İç Link Anchor Havuzu</h5>
        <div class="d-flex gap-2">
            <form method="get" class="d-flex gap-2">
                <select name="q" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">— Tüm slugs —</option>
                    <?php foreach ($slugOptions as $s => $label): ?>
                        <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $filterSlug === $s ? 'selected' : ''; ?>>
                            /<?php echo htmlspecialchars($s); ?> — <?php echo htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#anchorModal">
                <i class='bx bx-plus'></i> Yeni Anchor
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="alert alert-info small mb-3">
            <strong>Nasıl çalışır?</strong>
            Aynı sayfaya her yerden aynı anchor ile link verilmesini önler. Hedef slug için
            2–5 farklı doğal cümle ekleyin. Sistem, <em>kaynak sayfa + hedef sayfa</em> çifti için
            havuzdan deterministik olarak rotasyon yapar (<code>weight</code> yüksek olan daha sık). Havuz
            boşsa mevcut <em>nav_title</em> + bağlam algoritması devreye girer — yani bu ekran
            <strong>opsiyonel zenginleştirme</strong>dir, bozulma riski yoktur.
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Hedef Slug</th>
                        <th>Anchor Metni</th>
                        <th style="width:80px;">Ağırlık</th>
                        <th style="width:80px;">Aktif</th>
                        <th>Not</th>
                        <th style="width:130px;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">Henüz anchor eklenmemiş.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <tr class="<?php echo ((int) $r['active']) === 0 ? 'table-secondary' : ''; ?>">
                                <td><?php echo (int) $r['id']; ?></td>
                                <td>
                                    <code>/<?php echo htmlspecialchars((string) $r['target_slug']); ?></code>
                                    <div class="text-muted small">
                                        <?php echo htmlspecialchars((string) ($slugOptions[$r['target_slug']] ?? '')); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars((string) $r['anchor_text']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo (int) $r['weight']; ?></span></td>
                                <td>
                                    <a href="?toggle=<?php echo (int) $r['id']; ?><?php echo $filterSlug !== '' ? '&q=' . urlencode($filterSlug) : ''; ?>"
                                       class="btn btn-sm btn-outline-<?php echo ((int) $r['active']) === 1 ? 'success' : 'secondary'; ?>">
                                        <?php echo ((int) $r['active']) === 1 ? 'Evet' : 'Hayır'; ?>
                                    </a>
                                </td>
                                <td class="small text-muted"><?php echo htmlspecialchars((string) ($r['note'] ?? '')); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary edit-anchor"
                                            data-id="<?php echo (int) $r['id']; ?>"
                                            data-slug="<?php echo htmlspecialchars((string) $r['target_slug']); ?>"
                                            data-anchor="<?php echo htmlspecialchars((string) $r['anchor_text']); ?>"
                                            data-weight="<?php echo (int) $r['weight']; ?>"
                                            data-active="<?php echo (int) $r['active']; ?>"
                                            data-note="<?php echo htmlspecialchars((string) ($r['note'] ?? '')); ?>"
                                            data-bs-toggle="modal" data-bs-target="#anchorModal">
                                        <i class='bx bx-edit'></i>
                                    </button>
                                    <a href="?delete=<?php echo (int) $r['id']; ?><?php echo $filterSlug !== '' ? '&q=' . urlencode($filterSlug) : ''; ?>"
                                       onclick="return confirm('Bu anchor silinecek. Emin misiniz?');"
                                       class="btn btn-sm btn-danger">
                                        <i class='bx bx-trash'></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="anchorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="id" id="am_id" value="">
                <input type="hidden" name="save_anchor" value="1">
                <div class="modal-header">
                    <h5 class="modal-title">Anchor Ekle / Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Hedef Slug <span class="text-danger">*</span></label>
                        <input type="text" list="am_slug_options" class="form-control" id="am_slug" name="target_slug" required placeholder="ör. izmir-evden-eve-nakliyat">
                        <datalist id="am_slug_options">
                            <?php foreach ($slugOptions as $s => $label): ?>
                                <option value="<?php echo htmlspecialchars($s); ?>"><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </datalist>
                        <small class="text-muted">Başında <code>/</code> YAZMAYIN. İzin verilen: pillar tanımları veya aktif hizmet slugs.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Anchor Metni <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="am_anchor" name="anchor_text" rows="2" required maxlength="255" placeholder="ör. İzmir evden eve nakliyat: profesyonel ev taşımacılığı ve yazılı teklif"></textarea>
                        <small class="text-muted">Doğal, kısa, <strong>SPAM stili değil</strong>. Her hedef için 2-5 farklı cümle önerilir.</small>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Ağırlık</label>
                            <input type="number" class="form-control" id="am_weight" name="weight" min="1" max="10" value="1">
                            <small class="text-muted">1–10. Yüksek = daha sık seçilir.</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label d-block">Aktif</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="am_active" name="active" value="1" checked>
                                <label class="form-check-label" for="am_active">Rotasyona dahil</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Not (opsiyonel)</label>
                            <input type="text" class="form-control" id="am_note" name="note" maxlength="255" placeholder="örn. pillar → /izmir-evden-eve-nakliyat">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).on('click', '.edit-anchor', function() {
    $('#am_id').val($(this).data('id'));
    $('#am_slug').val($(this).data('slug'));
    $('#am_anchor').val($(this).data('anchor'));
    $('#am_weight').val($(this).data('weight'));
    $('#am_note').val($(this).data('note'));
    $('#am_active').prop('checked', parseInt($(this).data('active'), 10) === 1);
    $('.modal-title').text('Anchor Düzenle');
});
$('#anchorModal').on('hidden.bs.modal', function() {
    $('#am_id').val('');
    $('#am_slug').val('');
    $('#am_anchor').val('');
    $('#am_weight').val(1);
    $('#am_note').val('');
    $('#am_active').prop('checked', true);
    $('.modal-title').text('Anchor Ekle');
});
</script>

<?php require_once 'includes/footer.php'; ?>
