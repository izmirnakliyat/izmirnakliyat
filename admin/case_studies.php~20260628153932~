<?php
/**
 * Madde 5 — Admin: Musteri Hikayeleri (Case Studies) Listesi
 */
declare(strict_types=1);

$page_title = 'Musteri Hikayeleri';
require_once __DIR__ . '/includes/header.php';

$flashSuccess = '';
$flashError = '';

if (isset($_GET['delete'])) {
    $delId = (int) $_GET['delete'];
    if ($delId > 0) {
        $stmt = $conn->prepare("DELETE FROM case_studies WHERE id = ?");
        $stmt->bind_param('i', $delId);
        if ($stmt->execute()) {
            $flashSuccess = 'Musteri hikayesi silindi.';
        } else {
            $flashError = 'Silme hatasi: ' . $conn->error;
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $tId = (int) $_POST['cs_id'];
    if ($tId > 0) {
        $stmt = $conn->prepare("UPDATE case_studies SET status = NOT status WHERE id = ?");
        $stmt->bind_param('i', $tId);
        if ($stmt->execute()) {
            $flashSuccess = 'Durum guncellendi.';
        } else {
            $flashError = 'Durum guncellenirken hata: ' . $conn->error;
        }
        $stmt->close();
    }
}

$rows = [];
$res = $conn->query("SELECT id, slug, baslik, musteri_ad, kalkis_il, varis_il, ev_tipi, puan, status, created_at FROM case_studies ORDER BY created_at DESC, id DESC");
if ($res) {
    while ($x = $res->fetch_assoc()) { $rows[] = $x; }
}
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bx bx-bookmark-heart"></i> Musteri Hikayeleri (<?php echo count($rows); ?>)</h5>
        <div class="d-flex gap-2">
            <a href="../musteri-hikayeleri" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bx bx-link-external"></i> Public Sayfa</a>
            <a href="case_study_edit.php" class="btn btn-primary"><i class="bx bx-plus"></i> Yeni Hikaye</a>
        </div>
    </div>
    <div class="card-body">
        <?php if ($flashSuccess !== ''): ?><div class="alert alert-success"><?php echo htmlspecialchars($flashSuccess); ?></div><?php endif; ?>
        <?php if ($flashError !== ''): ?><div class="alert alert-danger"><?php echo htmlspecialchars($flashError); ?></div><?php endif; ?>

        <?php if (empty($rows)): ?>
            <div class="alert alert-info mb-0">
                <i class="bx bx-info-circle"></i> Henuz musteri hikayesi yok. <a href="case_study_edit.php">Yeni hikaye ekleyin</a>.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Baslik / Slug</th>
                            <th>Rota</th>
                            <th>Tip</th>
                            <th>Musteri</th>
                            <th>Puan</th>
                            <th>Durum</th>
                            <th class="text-end">Islemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><?php echo (int) $r['id']; ?></td>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars((string) $r['baslik']); ?></div>
                                    <small class="text-muted">/musteri-hikayeleri/<?php echo htmlspecialchars((string) $r['slug']); ?></small>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars((string) ($r['kalkis_il'] ?? '-')); ?> &rarr; <?php echo htmlspecialchars((string) ($r['varis_il'] ?? '-')); ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars((string) ($r['ev_tipi'] ?? '-')); ?></span></td>
                                <td><small><?php echo htmlspecialchars((string) ($r['musteri_ad'] ?? '-')); ?></small></td>
                                <td><span class="badge bg-warning text-dark"><?php echo number_format((float) $r['puan'], 1); ?> &#9733;</span></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="cs_id" value="<?php echo (int) $r['id']; ?>">
                                        <button type="submit" name="toggle_status" class="btn btn-sm <?php echo $r['status'] ? 'btn-success' : 'btn-secondary'; ?>">
                                            <i class="bx <?php echo $r['status'] ? 'bx-check' : 'bx-x'; ?>"></i>
                                            <?php echo $r['status'] ? 'Aktif' : 'Pasif'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <a href="../musteri-hikayeleri/<?php echo htmlspecialchars((string) $r['slug']); ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Onizle"><i class="bx bx-show"></i></a>
                                    <a href="case_study_edit.php?id=<?php echo (int) $r['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bx bx-edit"></i> Duzenle</a>
                                    <a href="?delete=<?php echo (int) $r['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bu hikayeyi silmek istediginize emin misiniz?');"><i class="bx bx-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
