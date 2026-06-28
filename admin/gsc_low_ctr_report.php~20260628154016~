<?php
require_once 'includes/header.php';
require_once __DIR__ . '/../includes/seo_runtime.php';

$page_title = 'GSC Düşük CTR Raporu';

// Yüklenebilen CSV formatı: Search Console → Performans → Dışa Aktar → CSV/Excel.
// "Queries" ve "Pages" yaprakları desteklenir. Üst başlık isimleri TR/EN değişebilir;
// anahtar sütunları ismine göre değil, içeriğe göre tanır (impressions, ctr %, clicks).

function mynak_gsc_parse_csv(string $path): array
{
    $out = ['rows' => [], 'format' => 'unknown', 'errors' => []];
    if (!is_readable($path)) {
        $out['errors'][] = 'Dosya okunamadı.';
        return $out;
    }
    $fh = fopen($path, 'r');
    if (!$fh) {
        $out['errors'][] = 'Dosya açılamadı.';
        return $out;
    }
    $headerLine = fgets($fh);
    if ($headerLine === false) {
        fclose($fh);
        $out['errors'][] = 'Boş CSV.';
        return $out;
    }
    // BOM temizle
    $headerLine = preg_replace('/^\xEF\xBB\xBF/', '', $headerLine);
    $delim = substr_count($headerLine, "\t") > substr_count($headerLine, ',') ? "\t" : ',';
    $headers = str_getcsv(trim($headerLine), $delim);
    $headers = array_map(static fn($h) => mb_strtolower(trim((string) $h), 'UTF-8'), $headers);

    $mapIdx = [
        'query' => null, 'page' => null, 'clicks' => null, 'impressions' => null, 'ctr' => null, 'position' => null,
    ];
    foreach ($headers as $i => $h) {
        if ($mapIdx['query'] === null && (strpos($h, 'query') !== false || strpos($h, 'sorgu') !== false || strpos($h, 'arama') !== false)) {
            $mapIdx['query'] = $i;
        } elseif ($mapIdx['page'] === null && (strpos($h, 'page') !== false || strpos($h, 'sayfa') !== false || strpos($h, 'url') !== false)) {
            $mapIdx['page'] = $i;
        } elseif ($mapIdx['clicks'] === null && (strpos($h, 'click') !== false || strpos($h, 'tıklama') !== false || strpos($h, 'tiklama') !== false)) {
            $mapIdx['clicks'] = $i;
        } elseif ($mapIdx['impressions'] === null && (strpos($h, 'impression') !== false || strpos($h, 'gösterim') !== false || strpos($h, 'gosterim') !== false)) {
            $mapIdx['impressions'] = $i;
        } elseif ($mapIdx['ctr'] === null && strpos($h, 'ctr') !== false) {
            $mapIdx['ctr'] = $i;
        } elseif ($mapIdx['position'] === null && (strpos($h, 'position') !== false || strpos($h, 'konum') !== false || strpos($h, 'sıra') !== false)) {
            $mapIdx['position'] = $i;
        }
    }
    if ($mapIdx['query'] !== null) {
        $out['format'] = 'queries';
    } elseif ($mapIdx['page'] !== null) {
        $out['format'] = 'pages';
    }

    while (($line = fgets($fh)) !== false) {
        $cols = str_getcsv(rtrim($line, "\r\n"), $delim);
        if ($cols === []) {
            continue;
        }
        $row = [
            'query' => $mapIdx['query'] !== null ? trim((string) ($cols[$mapIdx['query']] ?? '')) : '',
            'page' => $mapIdx['page'] !== null ? trim((string) ($cols[$mapIdx['page']] ?? '')) : '',
            'clicks' => $mapIdx['clicks'] !== null ? (int) str_replace([',', ' '], ['', ''], (string) ($cols[$mapIdx['clicks']] ?? '0')) : 0,
            'impressions' => $mapIdx['impressions'] !== null ? (int) str_replace([',', ' '], ['', ''], (string) ($cols[$mapIdx['impressions']] ?? '0')) : 0,
            'ctr' => 0.0,
            'position' => 0.0,
        ];
        if ($mapIdx['ctr'] !== null) {
            $ctrRaw = trim((string) ($cols[$mapIdx['ctr']] ?? ''));
            $ctrRaw = str_replace(['%', ','], ['', '.'], $ctrRaw);
            $row['ctr'] = (float) $ctrRaw;
        } elseif ($row['impressions'] > 0) {
            $row['ctr'] = $row['clicks'] * 100.0 / $row['impressions'];
        }
        if ($mapIdx['position'] !== null) {
            $row['position'] = (float) str_replace(',', '.', (string) ($cols[$mapIdx['position']] ?? '0'));
        }
        if ($row['query'] === '' && $row['page'] === '') {
            continue;
        }
        $out['rows'][] = $row;
    }
    fclose($fh);

    return $out;
}

/**
 * Slug'a göre mevcut sayfa/hizmet başlığı ve içerik uzunluğu çek.
 */
function mynak_gsc_resolve_page_meta(mysqli $conn, string $urlOrSlug): array
{
    $slug = trim($urlOrSlug);
    if (preg_match('#^https?://[^/]+(/.*)?$#i', $slug, $m)) {
        $slug = $m[1] ?? '/';
    }
    $slug = ltrim($slug, '/');
    $slug = preg_replace('#[?#].*$#', '', $slug);
    $slug = rtrim($slug, '/');
    if ($slug === '') {
        return ['kind' => 'home', 'title' => 'Ana Sayfa', 'len' => 0, 'slug' => '', 'id' => 0];
    }
    // Uzun detay içeriği services.icerik'te; yoksa services.aciklama'ya düş (2026-04 migration).
    $stmt = $conn->prepare("SELECT id, ana_baslik AS title, LENGTH(COALESCE(icerik, aciklama)) AS len FROM services WHERE slug = ? AND status = 1 LIMIT 1");
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($r) {
        return ['kind' => 'service', 'title' => (string) $r['title'], 'len' => (int) $r['len'], 'slug' => $slug, 'id' => (int) $r['id']];
    }
    $stmt = $conn->prepare("SELECT id, title, LENGTH(content) AS len FROM pages WHERE slug = ? AND status = 1 LIMIT 1");
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($r) {
        return ['kind' => 'page', 'title' => (string) $r['title'], 'len' => (int) $r['len'], 'slug' => $slug, 'id' => (int) $r['id']];
    }
    $stmt = $conn->prepare("SELECT id, baslik AS title, LENGTH(icerik) AS len FROM blog_posts WHERE slug = ? AND durum = 3 LIMIT 1");
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($r) {
        return ['kind' => 'blog', 'title' => (string) $r['title'], 'len' => (int) $r['len'], 'slug' => $slug, 'id' => (int) $r['id']];
    }
    return ['kind' => 'unknown', 'title' => $urlOrSlug, 'len' => 0, 'slug' => $slug, 'id' => 0];
}

// Parametreler
$minImpressions = max(0, (int) ($_GET['min_imp'] ?? 50));
$maxCtr = max(0.0, (float) ($_GET['max_ctr'] ?? 2.0));
$limit = max(10, min(500, (int) ($_GET['limit'] ?? 100)));

$rows = [];
$format = null;
$errors = [];
$uploadedName = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['gsc_csv']) && is_uploaded_file($_FILES['gsc_csv']['tmp_name'])) {
    $uploadedName = (string) $_FILES['gsc_csv']['name'];
    $parse = mynak_gsc_parse_csv($_FILES['gsc_csv']['tmp_name']);
    $rows = $parse['rows'];
    $format = $parse['format'];
    $errors = array_merge($errors, $parse['errors']);
}

// Filtrele + sırala
$filtered = [];
foreach ($rows as $r) {
    if ($r['impressions'] < $minImpressions) {
        continue;
    }
    if ($r['ctr'] > $maxCtr) {
        continue;
    }
    $filtered[] = $r;
}
usort($filtered, static function ($a, $b) {
    if ($a['impressions'] !== $b['impressions']) {
        return $b['impressions'] <=> $a['impressions'];
    }
    return $a['ctr'] <=> $b['ctr'];
});
$filtered = array_slice($filtered, 0, $limit);
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">GSC Düşük CTR Sorgu/Sayfa Raporu (CSV modu)</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info small">
            <strong>Nasıl kullanılır?</strong>
            <ol class="mb-0">
                <li>
                    Google Search Console → <em>Performans</em> ekranı → üstte son 28 gün veya 3 ay aralığını seç →
                    <em>Sorgular</em> veya <em>Sayfalar</em> sekmesine tıkla → sağ üstte <em>Dışa aktar</em> → <em>CSV</em>.
                </li>
                <li>Aşağıya indirdiğin CSV'yi yükle.</li>
                <li>
                    Sistem, <strong>gösterim ≥ {minimum}</strong> ve <strong>CTR ≤ {maksimum}</strong> olan satırları
                    listeler. Bu satırlar, başlık/meta/H2 iyileştirme için potansiyel fırsatlardır.
                </li>
            </ol>
        </div>

        <form method="post" enctype="multipart/form-data" class="row g-2 align-items-end mb-3">
            <div class="col-md-5">
                <label class="form-label">GSC CSV Dosyası</label>
                <input type="file" name="gsc_csv" accept=".csv,.tsv,text/csv" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Min. Gösterim</label>
                <input type="number" name="min_imp" value="<?php echo (int) $minImpressions; ?>" min="0" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Max CTR (%)</label>
                <input type="number" name="max_ctr" value="<?php echo htmlspecialchars((string) $maxCtr); ?>" step="0.1" min="0" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Limit</label>
                <input type="number" name="limit" value="<?php echo (int) $limit; ?>" min="10" max="500" class="form-control">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100">
                    <i class='bx bx-upload'></i>
                </button>
            </div>
        </form>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $e): echo '<div>' . htmlspecialchars($e) . '</div>'; endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($uploadedName !== ''): ?>
            <div class="d-flex gap-3 align-items-center mb-3">
                <div><strong>Dosya:</strong> <code><?php echo htmlspecialchars($uploadedName); ?></code></div>
                <div><strong>Format:</strong> <?php echo htmlspecialchars((string) $format); ?></div>
                <div><strong>Toplam satır:</strong> <?php echo count($rows); ?></div>
                <div><strong>Filtre sonrası:</strong> <?php echo count($filtered); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($format === 'queries' && !empty($filtered)): ?>
            <h6 class="mt-3">Sorgu Bazlı — İyileştirme Fırsatları</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Sorgu</th>
                            <th class="text-end">Gösterim</th>
                            <th class="text-end">Tıklama</th>
                            <th class="text-end">CTR</th>
                            <th class="text-end">Ortalama Konum</th>
                            <th>Öneri</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filtered as $r): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($r['query']); ?></td>
                                <td class="text-end"><?php echo number_format($r['impressions']); ?></td>
                                <td class="text-end"><?php echo number_format($r['clicks']); ?></td>
                                <td class="text-end"><?php echo number_format($r['ctr'], 2); ?>%</td>
                                <td class="text-end"><?php echo $r['position'] > 0 ? number_format($r['position'], 1) : '-'; ?></td>
                                <td class="small text-muted">
                                    <?php if ($r['position'] > 10): ?>
                                        Sayfa 2+ — içerikte sorguyu H2/H3 başlığı yap, meta title'ı sorguya yaklaştır.
                                    <?php elseif ($r['position'] > 5): ?>
                                        Sayfa 1 alt sıra — title ve meta description'ı daha çekici yaz (fayda + rakam + CTA).
                                    <?php else: ?>
                                        Üst sıra ama CTR düşük — title/meta çekiciliği yeniden gözden geçir, snippet hedefli giriş cümlesi ekle.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($format === 'pages' && !empty($filtered)): ?>
            <h6 class="mt-3">Sayfa Bazlı — İyileştirme Fırsatları</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Sayfa</th>
                            <th>Tip</th>
                            <th class="text-end">Gösterim</th>
                            <th class="text-end">Tıklama</th>
                            <th class="text-end">CTR</th>
                            <th class="text-end">İçerik (B)</th>
                            <th>Öneri</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filtered as $r):
                            $meta = mynak_gsc_resolve_page_meta($conn, $r['page']);
                            $editUrl = null;
                            if ($meta['kind'] === 'service' && $meta['id'] > 0) {
                                $editUrl = 'service_edit.php?id=' . $meta['id'];
                            } elseif ($meta['kind'] === 'page' && $meta['id'] > 0) {
                                $editUrl = 'page_edit.php?id=' . $meta['id'];
                            } elseif ($meta['kind'] === 'blog' && $meta['id'] > 0) {
                                $editUrl = 'blog_edit.php?id=' . $meta['id'];
                            }
                        ?>
                            <tr>
                                <td>
                                    <div class="small"><?php echo htmlspecialchars($r['page']); ?></div>
                                    <?php if ($editUrl !== null): ?>
                                        <a href="<?php echo $editUrl; ?>" class="btn btn-sm btn-outline-primary mt-1">
                                            <i class='bx bx-edit'></i> <?php echo htmlspecialchars($meta['title']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">eşleşme yok</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($meta['kind']); ?></span></td>
                                <td class="text-end"><?php echo number_format($r['impressions']); ?></td>
                                <td class="text-end"><?php echo number_format($r['clicks']); ?></td>
                                <td class="text-end"><?php echo number_format($r['ctr'], 2); ?>%</td>
                                <td class="text-end"><?php echo number_format($meta['len']); ?></td>
                                <td class="small text-muted">
                                    <?php
                                    $recs = [];
                                    if ($meta['len'] > 0 && $meta['len'] < 6000) {
                                        $recs[] = 'İçerik kısa — H2/H3 soru bölümleri ekle, FAQ genişlet.';
                                    }
                                    if ($r['position'] > 10) {
                                        $recs[] = 'Sayfa 2+; hedef sorgu başlıklarda yoksa ekle.';
                                    } elseif ($r['position'] > 5 && $r['ctr'] < 2) {
                                        $recs[] = 'Title ve meta description tekrar yaz — fayda + yerel ipucu.';
                                    } elseif ($r['ctr'] < 2) {
                                        $recs[] = 'Snippet hedefli giriş cümlesi ekle.';
                                    }
                                    if ($recs === []) {
                                        $recs[] = 'CTR iyileşmeye müsait — title + meta revizyonu önerilir.';
                                    }
                                    echo htmlspecialchars(implode(' · ', $recs));
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($uploadedName !== '' && empty($filtered)): ?>
            <div class="alert alert-warning">Eşik koşullarına uyan satır bulunamadı. Filtreyi gevşet (min_imp düşür, max_ctr yükselt).</div>
        <?php endif; ?>

        <?php if ($uploadedName === ''): ?>
            <div class="alert alert-light small">
                <strong>Neden OAuth değil?</strong> İlk aşamada CSV modu hızlı başlangıç sağlar. Canlıda OAuth
                API entegrasyonu ekleneceğinde bu ekrana "Otomatik çek (son 28 gün)" butonu eklenir.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
