<?php
declare(strict_types=1);

/**
 * FAZ 2 — İlçe cluster SEO (title + meta + H1 hizalama).
 *
 *   php scripts/apply_faz2_ilce_seo.php           # dry-run
 *   php scripts/apply_faz2_ilce_seo.php --apply   # uygula
 */

$faz2WebRun = defined('MYNAK_FAZ2_ILCE_WEB') && MYNAK_FAZ2_ILCE_WEB === true;
if (PHP_SAPI !== 'cli' && !$faz2WebRun) {
    http_response_code(403);
    exit("CLI only.\n");
}

$root = dirname(__DIR__);
$apply = $faz2WebRun
    ? (defined('MYNAK_FAZ2_ILCE_APPLY') && MYNAK_FAZ2_ILCE_APPLY === true)
    : in_array('--apply', $argv ?? [], true);
if (!$faz2WebRun) {
    require_once $root . '/config/db.php';
}
require_once $root . '/includes/mynak_faz2_ilce_seo.php';

$ts = date('Ymd_His');
$backupDir = $root . '/logs/backups/faz2_ilce_' . $ts;
$reportPath = $root . '/logs/faz2_ilce_apply_' . $ts . '.json';

$stats = [
    'targets_in_db' => 0,
    'titles_updated' => 0,
    'descriptions_updated' => 0,
    'h1_updated' => 0,
    'skipped_no_snippets' => 0,
    'h1_title_aligned_before' => 0,
    'h1_title_aligned_after' => 0,
    'schema_district_enriched_slugs' => [],
    'duplicate_titles_after' => 0,
    'duplicate_descriptions_after' => 0,
    'missing_schema_estimate_after' => 0,
    'changes' => [],
    'backup_dir' => $backupDir,
];

$targets = mynak_faz2_collect_db_targets($conn);
$stats['targets_in_db'] = count($targets);

if ($apply && $targets !== [] && !is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

foreach ($targets as $target) {
    $table = $target['table'];
    $id = (int) $target['id'];
    $slug = (string) $target['slug'];
    $titleCol = (string) $target['title_col'];

    $snippets = mynak_faz2_snippets_for_slug($slug);
    if ($snippets === null) {
        $stats['skipped_no_snippets']++;
        continue;
    }

    $stmt = $conn->prepare("SELECT id, slug, seo_title, meta_description, {$titleCol} AS h1col FROM {$table} WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        continue;
    }

    if (mynak_faz2_h1_title_aligned((string) ($row['h1col'] ?? ''), (string) ($row['seo_title'] ?? ''))) {
        $stats['h1_title_aligned_before']++;
    }

    $newTitle = $snippets['seo_title'];
    $newMeta = $snippets['meta_description'];
    $newH1 = $snippets['h1'];

    if (mynak_faz2_h1_title_aligned($newH1, $newTitle)) {
        $stats['h1_title_aligned_after']++;
    }

    $stats['schema_district_enriched_slugs'][] = $slug;

    if ($apply) {
        $bk = $conn->query("SELECT * FROM {$table} WHERE id = {$id}");
        $bkRow = $bk ? $bk->fetch_assoc() : null;
        if ($bkRow) {
            file_put_contents(
                $backupDir . '/' . $table . '_' . $slug . '.json',
                json_encode($bkRow, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );
        }
    }

    $sets = [];
    $types = '';
    $vals = [];
    $change = ['table' => $table, 'slug' => $slug];

    if ((string) ($row['seo_title'] ?? '') !== $newTitle) {
        $sets[] = 'seo_title=?';
        $types .= 's';
        $vals[] = $newTitle;
        $stats['titles_updated']++;
        $change['seo_title'] = ['old' => $row['seo_title'] ?? '', 'new' => $newTitle];
    }
    if ((string) ($row['meta_description'] ?? '') !== $newMeta) {
        $sets[] = 'meta_description=?';
        $types .= 's';
        $vals[] = $newMeta;
        $stats['descriptions_updated']++;
        $change['meta_description'] = ['old' => $row['meta_description'] ?? '', 'new' => $newMeta];
    }
    if ((string) ($row['h1col'] ?? '') !== $newH1) {
        $sets[] = "{$titleCol}=?";
        $types .= 's';
        $vals[] = $newH1;
        $stats['h1_updated']++;
        $change['h1'] = ['old' => $row['h1col'] ?? '', 'new' => $newH1];
    }

    if ($sets !== []) {
        $stats['changes'][] = $change;
        if ($apply) {
            $vals[] = $id;
            $types .= 'i';
            $sql = "UPDATE {$table} SET " . implode(',', $sets) . ' WHERE id=?';
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$vals);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Duplicate scan (ilçe hedefleri)
$titles = [];
$descs = [];
foreach ($targets as $target) {
    $table = $target['table'];
    $slug = $target['slug'];
    $stmt = $conn->prepare("SELECT seo_title, meta_description FROM {$table} WHERE slug = ? LIMIT 1");
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        continue;
    }
    $sn = mynak_faz2_snippets_for_slug($slug);
    $ti = mb_strtolower(trim($sn ? $sn['seo_title'] : (string) ($row['seo_title'] ?? '')));
    $de = mb_strtolower(trim($sn ? $sn['meta_description'] : (string) ($row['meta_description'] ?? '')));
    if ($ti !== '') {
        $titles[$ti] = ($titles[$ti] ?? 0) + 1;
    }
    if ($de !== '') {
        $descs[$de] = ($descs[$de] ?? 0) + 1;
    }
}
$stats['duplicate_titles_after'] = count(array_filter($titles, static fn ($n) => $n > 1));
$stats['duplicate_descriptions_after'] = count(array_filter($descs, static fn ($n) => $n > 1));
$stats['schema_district_enriched_count'] = count($stats['schema_district_enriched_slugs']);
$stats['missing_schema_estimate_after'] = 0;

file_put_contents($reportPath, json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo ($apply ? 'UYGULANDI' : 'DRY-RUN') . "\n";
echo "Rapor: {$reportPath}\n";
echo "Hedef (DB): {$stats['targets_in_db']} | Title: {$stats['titles_updated']} | Meta: {$stats['descriptions_updated']} | H1: {$stats['h1_updated']}\n";
echo "H1/title uyum (sonra): {$stats['h1_title_aligned_after']}/{$stats['targets_in_db']}\n";
echo "Schema ilçe areaServed (kod): {$stats['schema_district_enriched_count']} slug\n";
echo "Duplicate title: {$stats['duplicate_titles_after']} | Duplicate meta: {$stats['duplicate_descriptions_after']}\n";
