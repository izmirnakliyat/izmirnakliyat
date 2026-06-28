<?php
/**
 * Madde 5 — Admin: Musteri Hikayesi Olustur / Duzenle
 */
declare(strict_types=1);

$page_title = 'Musteri Hikayesi';
require_once __DIR__ . '/includes/header.php';

$cs = [
    'id' => 0,
    'slug' => '',
    'baslik' => '',
    'ozet' => '',
    'icerik' => '',
    'musteri_ad' => '',
    'musteri_yorumu' => '',
    'puan' => 5.0,
    'kalkis_il' => '',
    'varis_il' => '',
    'ev_tipi' => '',
    'tasima_tarihi' => '',
    'fiyat_araligi' => '',
    'gorsel' => '',
    'meta_title' => '',
    'meta_description' => '',
    'status' => 1,
];
$flashSuccess = '';
$flashError = '';

$editId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($editId > 0) {
    $stmt = $conn->prepare("SELECT * FROM case_studies WHERE id = ?");
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if ($row) {
        $cs = array_merge($cs, $row);
    } else {
        $flashError = 'Kayit bulunamadi.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cs['slug']             = trim((string) ($_POST['slug'] ?? ''));
    $cs['baslik']           = trim((string) ($_POST['baslik'] ?? ''));
    $cs['ozet']             = trim((string) ($_POST['ozet'] ?? ''));
    $cs['icerik']           = (string) ($_POST['icerik'] ?? '');
    $cs['musteri_ad']       = trim((string) ($_POST['musteri_ad'] ?? ''));
    $cs['musteri_yorumu']   = trim((string) ($_POST['musteri_yorumu'] ?? ''));
    $cs['puan']             = (float) ($_POST['puan'] ?? 5.0);
    $cs['kalkis_il']        = trim((string) ($_POST['kalkis_il'] ?? ''));
    $cs['varis_il']         = trim((string) ($_POST['varis_il'] ?? ''));
    $cs['ev_tipi']          = trim((string) ($_POST['ev_tipi'] ?? ''));
    $cs['tasima_tarihi']    = trim((string) ($_POST['tasima_tarihi'] ?? ''));
    $cs['fiyat_araligi']    = trim((string) ($_POST['fiyat_araligi'] ?? ''));
    $cs['gorsel']           = trim((string) ($_POST['gorsel'] ?? ''));
    $cs['meta_title']       = trim((string) ($_POST['meta_title'] ?? ''));
    $cs['meta_description'] = trim((string) ($_POST['meta_description'] ?? ''));
    $cs['status']           = isset($_POST['status']) ? (int) $_POST['status'] : 1;

    if ($cs['slug'] === '') {
        $cs['slug'] = mb_strtolower($cs['baslik'], 'UTF-8');
        $cs['slug'] = strtr($cs['slug'], ['ı'=>'i','ğ'=>'g','ü'=>'u','ş'=>'s','ö'=>'o','ç'=>'c','İ'=>'i','Ğ'=>'g','Ü'=>'u','Ş'=>'s','Ö'=>'o','Ç'=>'c']);
        $cs['slug'] = preg_replace('/[^a-z0-9]+/', '-', $cs['slug']) ?? '';
        $cs['slug'] = trim($cs['slug'], '-');
    } else {
        $cs['slug'] = preg_replace('/[^a-z0-9-]/', '-', strtolower($cs['slug'])) ?? '';
        $cs['slug'] = trim((string) $cs['slug'], '-');
    }

    if ($cs['baslik'] === '' || $cs['slug'] === '') {
        $flashError = 'Baslik ve slug zorunludur.';
    } else {
        if ($cs['puan'] < 1) { $cs['puan'] = 1.0; }
        if ($cs['puan'] > 5) { $cs['puan'] = 5.0; }
        $tasimaTarihi = $cs['tasima_tarihi'] !== '' ? $cs['tasima_tarihi'] : null;

        if ($editId > 0) {
            $sql = "UPDATE case_studies SET slug=?, baslik=?, ozet=?, icerik=?, musteri_ad=?, musteri_yorumu=?, puan=?, kalkis_il=?, varis_il=?, ev_tipi=?, tasima_tarihi=?, fiyat_araligi=?, gorsel=?, meta_title=?, meta_description=?, status=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssssdsssssssii', $cs['slug'], $cs['baslik'], $cs['ozet'], $cs['icerik'], $cs['musteri_ad'], $cs['musteri_yorumu'], $cs['puan'], $cs['kalkis_il'], $cs['varis_il'], $cs['ev_tipi'], $tasimaTarihi, $cs['fiyat_araligi'], $cs['gorsel'], $cs['meta_title'], $cs['meta_description'], $cs['status'], $editId);

            if ($stmt->execute()) {
                $flashSuccess = 'Hikaye guncellendi.';
            } else {
                $flashError = 'Guncelleme hatasi: ' . $conn->error;
            }
            $stmt->close();
        } else {
            $sql = "INSERT INTO case_studies (slug, baslik, ozet, icerik, musteri_ad, musteri_yorumu, puan, kalkis_il, varis_il, ev_tipi, tasima_tarihi, fiyat_araligi, gorsel, meta_title, meta_description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssssdsssssssii', $cs['slug'], $cs['baslik'], $cs['ozet'], $cs['icerik'], $cs['musteri_ad'], $cs['musteri_yorumu'], $cs['puan'], $cs['kalkis_il'], $cs['varis_il'], $cs['ev_tipi'], $tasimaTarihi, $cs['fiyat_araligi'], $cs['gorsel'], $cs['meta_title'], $cs['meta_description'], $cs['status']);

            if ($stmt->execute()) {
                $newId = $stmt->insert_id;
                $stmt->close();
                header('Location: case_study_edit.php?id=' . $newId . '&saved=1');
                exit;
            } else {
                $flashError = 'Olusturma hatasi: ' . $conn->error;
                $stmt->close();
            }
        }
    }
}
if (isset($_GET['saved'])) { $flashSuccess = 'Hikaye olusturuldu.'; }
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0"><i class="bx bx-bookmark-heart"></i> <?php echo $editId > 0 ? 'Hikayeyi Duzenle' : 'Yeni Musteri Hikayesi'; ?></h2>
    <div class="d-flex gap-2">
        <a href="case_studies.php" class="btn btn-outline-secondary"><i class="bx bx-arrow-back"></i> Liste</a>
        <?php if ($editId > 0): ?>
            <a href="../musteri-hikayeleri/<?php echo htmlspecialchars($cs['slug']); ?>" target="_blank" class="btn btn-outline-info"><i class="bx bx-show"></i> Onizle</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($flashSuccess !== ''): ?><div class="alert alert-success"><?php echo htmlspecialchars($flashSuccess); ?></div><?php endif; ?>
<?php if ($flashError !== ''): ?><div class="alert alert-danger"><?php echo htmlspecialchars($flashError); ?></div><?php endif; ?>

<form method="POST" class="row g-3">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Baslik <span class="text-danger">*</span></label>
                    <input type="text" name="baslik" class="form-control" required maxlength="200" value="<?php echo htmlspecialchars((string) $cs['baslik']); ?>" placeholder="Or: Bornova → Kadikoy 3+1 Tasima">
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <div class="input-group">
                        <span class="input-group-text">/musteri-hikayeleri/</span>
                        <input type="text" name="slug" class="form-control" maxlength="190" value="<?php echo htmlspecialchars((string) $cs['slug']); ?>" placeholder="bos birakilirsa basliktan turetilir">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Ozet</label>
                    <textarea name="ozet" class="form-control" rows="2" maxlength="300" placeholder="2-3 cumlelik ozet (kart + meta description fallback)"><?php echo htmlspecialchars((string) $cs['ozet']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Detay Icerik (HTML)</label>
                    <textarea name="icerik" id="icerik" class="form-control" rows="14"><?php echo htmlspecialchars((string) $cs['icerik']); ?></textarea>
                    <small class="text-muted">H2/H3 basliklari ile yapilandirin. Mevcut hizmet sayfalari ile ayni TinyMCE editor kullanilir.</small>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="bx bx-message-square-detail"></i> Musteri Yorumu (Review Schema)</div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Musteri Adi</label>
                        <input type="text" name="musteri_ad" class="form-control" maxlength="120" value="<?php echo htmlspecialchars((string) $cs['musteri_ad']); ?>" placeholder="Or: Mehmet K.">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Puan (1.0 - 5.0)</label>
                        <input type="number" step="0.5" min="1" max="5" name="puan" class="form-control" value="<?php echo htmlspecialchars((string) $cs['puan']); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Yorum (Review Schema'da reviewBody)</label>
                        <textarea name="musteri_yorumu" class="form-control" rows="4" placeholder="Musterinin gercek goruslerinden kesit"><?php echo htmlspecialchars((string) $cs['musteri_yorumu']); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header"><i class="bx bx-cog"></i> Yayin</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Durum</label>
                    <select name="status" class="form-select">
                        <option value="1" <?php echo $cs['status'] ? 'selected' : ''; ?>>Aktif</option>
                        <option value="0" <?php echo !$cs['status'] ? 'selected' : ''; ?>>Pasif</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-success w-100"><i class="bx bx-save"></i> Kaydet</button>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="bx bx-map"></i> Tasima Detaylari</div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label">Kalkis Il</label>
                        <input type="text" name="kalkis_il" class="form-control" maxlength="60" value="<?php echo htmlspecialchars((string) $cs['kalkis_il']); ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Varis Il</label>
                        <input type="text" name="varis_il" class="form-control" maxlength="60" value="<?php echo htmlspecialchars((string) $cs['varis_il']); ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Ev Tipi</label>
                        <input type="text" name="ev_tipi" class="form-control" maxlength="40" placeholder="2+1 / 3+1 / Ofis" value="<?php echo htmlspecialchars((string) $cs['ev_tipi']); ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Tasima Tarihi</label>
                        <input type="date" name="tasima_tarihi" class="form-control" value="<?php echo htmlspecialchars((string) $cs['tasima_tarihi']); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Fiyat Araligi</label>
                        <input type="text" name="fiyat_araligi" class="form-control" maxlength="60" placeholder="8.000 - 12.000 TL" value="<?php echo htmlspecialchars((string) $cs['fiyat_araligi']); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Gorsel URL (opsiyonel)</label>
                        <input type="text" name="gorsel" class="form-control" maxlength="255" placeholder="uploads/case-studies/foo.jpg" value="<?php echo htmlspecialchars((string) $cs['gorsel']); ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bx bx-search-alt"></i> SEO Meta</div>
            <div class="card-body">
                <div class="mb-2">
                    <label class="form-label">Meta Title</label>
                    <input type="text" name="meta_title" class="form-control" maxlength="180" value="<?php echo htmlspecialchars((string) $cs['meta_title']); ?>">
                </div>
                <div>
                    <label class="form-label">Meta Description</label>
                    <textarea name="meta_description" class="form-control" rows="3" maxlength="255"><?php echo htmlspecialchars((string) $cs['meta_description']); ?></textarea>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
