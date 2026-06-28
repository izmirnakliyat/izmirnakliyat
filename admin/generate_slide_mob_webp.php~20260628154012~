<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
checkLogin();

$page_title = 'Slide Mobil WebP Üretimi';
$report = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    $force = !empty($_POST['force']);
    $report = mynak_slide_batch_generate_mob_webp($conn, $force);
    if ($report['error'] !== '') {
        $error = $report['error'];
    }
} else {
    $report = mynak_slide_batch_generate_mob_webp($conn, false);
}

require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Hero LCP — <code>{basename}-mob.webp</code> toplu üretim</h5>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">
                404 alıyorsanız mobil varyant henüz oluşturulmamıştır. Bu işlem mevcut slide görsellerinden
                <strong>768px</strong> genişlikte <code>uploads/slides/{basename}-mob.webp</code> dosyalarını üretir.
            </p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if ($report && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <div class="alert alert-success">
                    Oluşturulan: <strong><?php echo (int) $report['stats']['created']; ?></strong> —
                    Zaten vardı: <strong><?php echo (int) $report['stats']['skipped']; ?></strong> —
                    Başarısız: <strong><?php echo (int) $report['stats']['failed']; ?></strong>
                </div>
            <?php endif; ?>

            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Başlık</th>
                            <th>Orijinal</th>
                            <th>Mobil dosya</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['results'] ?? [] as $row): ?>
                            <tr>
                                <td><?php echo (int) $row['slide_id']; ?></td>
                                <td><?php echo htmlspecialchars((string) $row['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><code><?php echo htmlspecialchars((string) $row['image'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                <td><code><?php echo htmlspecialchars((string) $row['mob_name'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                <td>
                                    <?php
                                    if (!empty($row['skipped'])) {
                                        echo '<span class="badge bg-secondary">Var</span>';
                                    } elseif ($row['ok']) {
                                        echo '<span class="badge bg-success">OK</span> ';
                                        echo round(((int) ($row['bytes'] ?? 0)) / 1024, 1) . ' KB';
                                    } else {
                                        echo '<span class="badge bg-danger">Hata</span> ';
                                        echo htmlspecialchars((string) $row['message'], ENT_QUOTES, 'UTF-8');
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <form method="post" class="d-flex gap-3 align-items-center">
                <input type="hidden" name="apply" value="1">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="force" id="force" value="1">
                    <label class="form-check-label" for="force">Mevcut -mob.webp dosyalarını yeniden üret</label>
                </div>
                <button type="submit" class="btn btn-primary">Mobil WebP Oluştur</button>
                <a href="slides.php" class="btn btn-outline-secondary">Slayt listesi</a>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
