<?php
/**
 * Blog İçerik Sağlığı Raporu (admin, salt-okuma).
 *
 * Her aktif blog yazısı için aşağıdaki sinyalleri ölçer ve sayısal "sorun
 * skoru" üretir (yüksek = daha çok iyileştirme alanı):
 *
 *   - Title uzunluğu (ideal 30-60 karakter)
 *   - Meta description uzunluğu (ideal 120-160 karakter)
 *   - İçerik uzunluğu (ideal 800-3500 düz metin karakteri)
 *   - H2/H3 başlık sayısı (FAQ schema ve okunurluk için ideal ≥ 3)
 *   - Görsel sayısı (<img> minimum 1)
 *   - İç link sayısı (yazıdan başka sayfalara <a href> minimum 1)
 *   - Focus keyword'in title + içerikte geçişi
 *   - Yayın tarihi yaşı (created_at) ve son güncelleme yaşı (updated_at)
 *   - Slug çakışması (services/pages tablolarında aynı slug)
 *
 * Hiçbir DB yazımı yapılmaz; sadece okur ve listeler.
 */

declare(strict_types=1);

require_once 'includes/header.php';

$page_title = 'Blog İçerik Sağlığı';

if (!function_exists('mynak_blog_health_text_metrics')) {
    /**
     * @return array{plain_len:int,h_count:int,img_count:int,internal_link_count:int}
     */
    function mynak_blog_health_text_metrics(string $html): array
    {
        $plain = trim((string) preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
        $hCount = preg_match_all('#<h[2-3](?:\s[^>]*)?>#i', $html) ?: 0;
        $imgCount = preg_match_all('#<img\s[^>]*>#i', $html) ?: 0;
        // İç link: aynı domain ya da göreceli (http(s) hariç olabilir).
        $linkCount = 0;
        if (preg_match_all('#<a\s[^>]*href\s*=\s*([\'"])(.+?)\1#i', $html, $m)) {
            foreach ($m[2] as $href) {
                $h = trim((string) $href);
                if ($h === '' || strpos($h, '#') === 0 || strpos($h, 'mailto:') === 0 || strpos($h, 'tel:') === 0) {
                    continue;
                }
                if (strpos($h, 'http') === 0 && strpos($h, 'mynakliyat.com.tr') === false && strpos($h, 'localhost') === false) {
                    continue; // dış link
                }
                $linkCount++;
            }
        }
        return [
            'plain_len' => mb_strlen($plain, 'UTF-8'),
            'h_count' => (int) $hCount,
            'img_count' => (int) $imgCount,
            'internal_link_count' => $linkCount,
        ];
    }
}

if (!function_exists('mynak_blog_health_score_post')) {
    /**
     * @param array<string,mixed> $row
     * @param array{plain_len:int,h_count:int,img_count:int,internal_link_count:int} $metrics
     * @return array{score:int,issues:list<string>,age_days:int,update_age_days:int}
     */
    function mynak_blog_health_score_post(array $row, array $metrics): array
    {
        $issues = [];
        $score = 0;

        $titleLen = mb_strlen((string) ($row['seo_title'] ?? $row['baslik'] ?? ''), 'UTF-8');
        if ($titleLen === 0) { $issues[] = 'Title yok'; $score += 4; }
        elseif ($titleLen < 30) { $issues[] = 'Title kısa (' . $titleLen . ')'; $score += 2; }
        elseif ($titleLen > 70) { $issues[] = 'Title uzun (' . $titleLen . ')'; $score += 1; }

        $metaLen = mb_strlen((string) ($row['meta_description'] ?? ''), 'UTF-8');
        if ($metaLen === 0) { $issues[] = 'Meta yok'; $score += 4; }
        elseif ($metaLen < 120) { $issues[] = 'Meta kısa (' . $metaLen . ')'; $score += 2; }
        elseif ($metaLen > 165) { $issues[] = 'Meta uzun (' . $metaLen . ')'; $score += 1; }

        $plainLen = (int) $metrics['plain_len'];
        if ($plainLen < 400) { $issues[] = 'İçerik çok kısa (' . $plainLen . ')'; $score += 5; }
        elseif ($plainLen < 800) { $issues[] = 'İçerik kısa (' . $plainLen . ')'; $score += 3; }
        elseif ($plainLen > 8000) { $issues[] = 'İçerik aşırı uzun (' . $plainLen . ')'; $score += 1; }

        if ($metrics['h_count'] === 0) { $issues[] = 'H2/H3 yok'; $score += 3; }
        elseif ($metrics['h_count'] < 3) { $issues[] = 'H2/H3 az (' . $metrics['h_count'] . ')'; $score += 1; }

        if ($metrics['img_count'] === 0) { $issues[] = 'Görsel yok'; $score += 2; }

        if ($metrics['internal_link_count'] === 0) { $issues[] = 'İç link yok'; $score += 3; }
        elseif ($metrics['internal_link_count'] < 2) { $issues[] = 'İç link az'; $score += 1; }

        $focus = trim((string) ($row['focus_keyword'] ?? ''));
        if ($focus === '') {
            $issues[] = 'Focus kw yok';
            $score += 2;
        } else {
            $title = mb_strtolower((string) ($row['seo_title'] ?? $row['baslik'] ?? ''), 'UTF-8');
            $body = mb_strtolower((string) ($row['icerik'] ?? ''), 'UTF-8');
            $fk = mb_strtolower($focus, 'UTF-8');
            if ($title !== '' && strpos($title, $fk) === false) { $issues[] = 'Title focus kw içermiyor'; $score += 2; }
            if ($body !== '' && strpos($body, $fk) === false) { $issues[] = 'İçerik focus kw içermiyor'; $score += 2; }
        }

        $createdAt = isset($row['created_at']) ? strtotime((string) $row['created_at']) : 0;
        $updatedAt = isset($row['updated_at']) ? strtotime((string) $row['updated_at']) : 0;
        $now = time();
        $ageDays = $createdAt > 0 ? max(0, (int) floor(($now - $createdAt) / 86400)) : 0;
        $updAgeDays = $updatedAt > 0 ? max(0, (int) floor(($now - $updatedAt) / 86400)) : $ageDays;
        if ($updAgeDays > 365) { $issues[] = 'Güncelleme 1 yıl+'; $score += 2; }
        elseif ($updAgeDays > 180) { $issues[] = 'Güncelleme 6 ay+'; $score += 1; }

        return [
            'score' => $score,
            'issues' => $issues,
            'age_days' => $ageDays,
            'update_age_days' => $updAgeDays,
        ];
    }
}

// Aktif yazıları çek (durum=3 yayında)
$rows = [];
$sql = "SELECT bp.id, bp.baslik, bp.slug, bp.icerik, bp.seo_title, bp.meta_description, bp.focus_keyword, bp.seo_score, bp.created_at, bp.updated_at, c.ad AS kategori
        FROM blog_posts bp
        LEFT JOIN blog_categories c ON c.id = bp.kategori_id
        WHERE bp.durum = 3
        ORDER BY bp.created_at DESC";
$res = $conn->query($sql);
if ($res) {
    while ($r = $res->fetch_assoc()) { $rows[] = $r; }
}

// Slug çakışması (services & pages)
$conflictSlugs = [];
$slugSet = array_filter(array_map(static fn($r) => (string) $r['slug'], $rows));
if ($slugSet !== []) {
    $in = implode(',', array_map(static fn($s) => "'" . $conn->real_escape_string($s) . "'", $slugSet));
    foreach (['services', 'pages'] as $tbl) {
        $q = $conn->query("SELECT slug FROM `$tbl` WHERE status = 1 AND slug IN ($in)");
        if ($q) {
            while ($x = $q->fetch_assoc()) {
                $conflictSlugs[(string) $x['slug']] = $tbl;
            }
        }
    }
}

// Skorla
$reports = [];
$totalIssues = 0;
$problemBuckets = ['kritik' => 0, 'orta' => 0, 'iyi' => 0];
foreach ($rows as $r) {
    $metrics = mynak_blog_health_text_metrics((string) $r['icerik']);
    $report = mynak_blog_health_score_post($r, $metrics);
    if (isset($conflictSlugs[(string) $r['slug']])) {
        $report['issues'][] = 'Slug çakışması (' . $conflictSlugs[(string) $r['slug']] . ')';
        $report['score'] += 4;
    }
    $totalIssues += count($report['issues']);
    if ($report['score'] >= 10) { $problemBuckets['kritik']++; }
    elseif ($report['score'] >= 5) { $problemBuckets['orta']++; }
    else { $problemBuckets['iyi']++; }
    $reports[] = ['row' => $r, 'metrics' => $metrics, 'report' => $report];
}

// Filtre
$mode = (string) ($_GET['mode'] ?? 'all'); // all | issues | old | conflict
$filtered = $reports;
if ($mode === 'issues') {
    $filtered = array_filter($filtered, static fn($x) => $x['report']['score'] >= 5);
} elseif ($mode === 'old') {
    $filtered = array_filter($filtered, static fn($x) => $x['report']['update_age_days'] >= 180);
} elseif ($mode === 'conflict') {
    $filtered = array_filter($filtered, static function ($x) use ($conflictSlugs) {
        return isset($conflictSlugs[(string) $x['row']['slug']]);
    });
}

// Sırala (score azalan → en sorunlu en üstte)
usort($filtered, static fn($a, $b) => $b['report']['score'] <=> $a['report']['score']);
?>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class='bx bx-clipboard'></i> Blog İçerik Sağlığı Raporu</h5>
        <div class="small text-muted">
            <?php echo count($rows); ?> aktif yazı &middot;
            <span class="text-success"><?php echo $problemBuckets['iyi']; ?> iyi</span> &middot;
            <span class="text-warning"><?php echo $problemBuckets['orta']; ?> orta</span> &middot;
            <span class="text-danger"><?php echo $problemBuckets['kritik']; ?> kritik</span>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-info small mb-3">
            Bu rapor <strong>salt-okuma</strong>dır; içerik veya DB üzerinde değişiklik yapmaz. Skor ne kadar yüksekse,
            o yazıda iyileştirilecek alan o kadar fazla. Tıklayarak doğrudan düzenleyebilirsin.
        </div>

        <div class="btn-group btn-group-sm mb-3" role="group">
            <a href="?mode=all" class="btn btn-outline-secondary <?php echo $mode === 'all' ? 'active' : ''; ?>">Tümü (<?php echo count($reports); ?>)</a>
            <a href="?mode=issues" class="btn btn-outline-warning <?php echo $mode === 'issues' ? 'active' : ''; ?>">Sorunlu (<?php echo $problemBuckets['kritik'] + $problemBuckets['orta']; ?>)</a>
            <a href="?mode=old" class="btn btn-outline-info <?php echo $mode === 'old' ? 'active' : ''; ?>">Eski (180+ gün)</a>
            <a href="?mode=conflict" class="btn btn-outline-danger <?php echo $mode === 'conflict' ? 'active' : ''; ?>">Slug Çakışması (<?php echo count($conflictSlugs); ?>)</a>
        </div>

        <?php if (empty($filtered)): ?>
            <div class="alert alert-success mb-0">Bu filtrede listelenen yazı yok.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:36%">Başlık</th>
                            <th class="text-end">Skor</th>
                            <th class="text-end">İçerik</th>
                            <th class="text-end">H2/H3</th>
                            <th class="text-end">İmg</th>
                            <th class="text-end">İç Link</th>
                            <th class="text-end">Yaş / Güncelleme</th>
                            <th>Sorunlar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filtered as $item):
                            $r = $item['row'];
                            $m = $item['metrics'];
                            $rep = $item['report'];
                            $score = (int) $rep['score'];
                            $rowClass = $score >= 10 ? 'table-danger' : ($score >= 5 ? 'table-warning' : '');
                        ?>
                            <tr class="<?php echo $rowClass; ?>">
                                <td>
                                    <div class="small fw-bold"><?php echo htmlspecialchars((string) $r['baslik']); ?></div>
                                    <div class="small text-muted">
                                        <code><?php echo htmlspecialchars((string) $r['slug']); ?></code>
                                        <?php if (!empty($r['kategori'])): ?>
                                            &middot; <?php echo htmlspecialchars((string) $r['kategori']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <a href="blog_edit.php?id=<?php echo (int) $r['id']; ?>" class="btn btn-sm btn-outline-primary mt-1">
                                        <i class='bx bx-edit'></i> Düzenle
                                    </a>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-<?php echo $score >= 10 ? 'danger' : ($score >= 5 ? 'warning' : 'success'); ?>">
                                        <?php echo $score; ?>
                                    </span>
                                </td>
                                <td class="text-end small"><?php echo number_format((int) $m['plain_len']); ?></td>
                                <td class="text-end small"><?php echo (int) $m['h_count']; ?></td>
                                <td class="text-end small"><?php echo (int) $m['img_count']; ?></td>
                                <td class="text-end small"><?php echo (int) $m['internal_link_count']; ?></td>
                                <td class="text-end small">
                                    <span class="text-muted"><?php echo (int) $rep['age_days']; ?>g</span> /
                                    <span class="<?php echo $rep['update_age_days'] >= 365 ? 'text-danger' : ($rep['update_age_days'] >= 180 ? 'text-warning' : 'text-success'); ?>">
                                        <?php echo (int) $rep['update_age_days']; ?>g
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    <?php
                                    $issues = $rep['issues'];
                                    if ($issues === []) {
                                        echo '<span class="text-success">Sorun yok</span>';
                                    } else {
                                        echo htmlspecialchars(implode(' · ', $issues));
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
