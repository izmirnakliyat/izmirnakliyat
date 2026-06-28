<?php
declare(strict_types=1);

/**
 * Phase B1+B2 — production SEO metadata (micro-batch).
 *
 *   php scripts/apply_phase_b_seo.php --production --dry-run
 *   php scripts/apply_phase_b_seo.php --production --batch=1 --apply --confirm
 *   php scripts/apply_phase_b_seo.php --production --batch=2 --apply --confirm --verify-curl
 *
 * Batches 3–5 disabled. fiyat.php deploy ayrı.
 */

$phaseBWebRun = defined('MYNAK_PHASE_B_SEO_WEB') && MYNAK_PHASE_B_SEO_WEB === true;
if (PHP_SAPI !== 'cli' && !$phaseBWebRun) {
    http_response_code(403);
    exit("CLI only.\n");
}

$root = dirname(__DIR__);
$argv = $argv ?? [];

$apply = $phaseBWebRun
    ? (defined('MYNAK_PHASE_B_SEO_APPLY') && MYNAK_PHASE_B_SEO_APPLY === true)
    : in_array('--apply', $argv, true);
$confirm = $phaseBWebRun
    ? (defined('MYNAK_PHASE_B_SEO_CONFIRM') && MYNAK_PHASE_B_SEO_CONFIRM === true)
    : in_array('--confirm', $argv, true);
$dryRunAll = in_array('--dry-run', $argv, true);
$verifyCurl = in_array('--verify-curl', $argv, true);
$useProduction = $phaseBWebRun || in_array('--production', $argv, true);

$batchNum = 0;
$webBatch = $phaseBWebRun && defined('MYNAK_PHASE_B_BATCH') ? (int) MYNAK_PHASE_B_BATCH : 0;
if ($webBatch > 0) {
    $batchNum = $webBatch;
}
foreach ($argv as $arg) {
    if (preg_match('/^--batch=(\d+)$/', (string) $arg, $m)) {
        $batchNum = (int) $m[1];
    }
}

if (!$phaseBWebRun && !$useProduction) {
    fwrite(STDERR, "STOP: CLI requires --production (local DB disabled for Phase B).\n");
    exit(1);
}

if (!$phaseBWebRun) {
    define('MYNAK_FORCE_PRODUCTION_DB', true);
}

require_once $root . '/config/config.php';
require_once $root . '/config/db.php';
require_once $root . '/includes/mynak_canonical_slug_redirects.php';
require_once $root . '/includes/mynak_meta_description.php';
require_once $root . '/includes/seo_runtime/constants.php';

/** @return array<int, array{type: string, slugs: list<string>, enabled: bool, note?: string}> */
function phase_b_micro_batches(): array
{
    return [
        1 => [
            'type' => 'pages',
            'enabled' => true,
            'note' => 'pages only (+ fiyat.php static)',
            'slugs' => ['izmir-evden-eve-nakliyat', 'sehirici-nakliyat'],
        ],
        2 => [
            'type' => 'blog_posts',
            'enabled' => true,
            'slugs' => [
                'izmir-evden-eve-nakliyat-fiyatlari-2026',
                'izmir-evden-eve-nakliyat-firmalar',
                'mobilya-beyaz-esya-tasimaciligi-rehberi',
                'izmir-evden-eve-nakliyat-guncel-tasinma-trendleri',
            ],
        ],
        3 => ['type' => 'blog_posts', 'enabled' => false, 'slugs' => []],
        4 => ['type' => 'pages', 'enabled' => false, 'slugs' => []],
        5 => ['type' => 'blog_posts', 'enabled' => false, 'slugs' => []],
    ];
}

/** @return array<string, array{seo_title?: string, meta_description?: string}> */
function phase_b_seo_map(): array
{
    return [
        'izmir-evden-eve-nakliyat-fiyatlari-2026' => [
            'seo_title' => '2026 İzmir Nakliyat Fiyat Rehberi | MY Nakliyat',
            'meta_description' => '2026 İzmir nakliyat fiyatları: daire tipi, kat ve mesafeye göre şeffaf tablo. Ücretsiz keşif ile net teklif alın.',
        ],
        'izmir-evden-eve-nakliyat-firmalar' => [
            'seo_title' => 'İzmir Nakliyat Firmaları Rehberi | MY Nakliyat',
        ],
        'mobilya-beyaz-esya-tasimaciligi-rehberi' => [
            'seo_title' => 'Mobilya Taşıma Rehberi | MY Nakliyat',
        ],
        'izmir-evden-eve-nakliyat-guncel-tasinma-trendleri' => [
            'seo_title' => '2026 Taşınma Trendleri | MY Nakliyat',
        ],
        'izmir-evden-eve-nakliyat' => [
            'seo_title' => 'İzmir Evden Eve Nakliyat | MY Nakliyat',
            'meta_description' => 'İzmir evden eve nakliyat: sigortalı taşıma, ücretsiz keşif ve yazılı sözleşme. 30 ilçe ve 81 il hizmeti.',
        ],
        'sehirici-nakliyat' => [
            'seo_title' => 'Şehiriçi Nakliyat İzmir | MY Nakliyat',
            'meta_description' => 'İzmir şehiriçi nakliyat: aynı gün planlama, sigortalı ekip ve asansörlü taşıma. Ücretsiz ekspertiz için arayın.',
        ],
    ];
}

function phase_b_resolve_slug(mysqli $conn, string $slug, string $expectedTable): ?array
{
    $specs = [
        'services' => ['services', 'id', 'slug', 'ana_baslik', 'seo_title', 'meta_description', 'status', 1],
        'blog_posts' => ['blog_posts', 'id', 'slug', 'baslik', 'seo_title', 'meta_description', 'durum', 3],
        'pages' => ['pages', 'id', 'slug', 'title', 'seo_title', 'meta_description', 'status', 1],
    ];

    foreach (['services', 'blog_posts', 'pages'] as $probe) {
        if (!isset($specs[$probe])) {
            continue;
        }
        [$table, $idCol, $slugCol, $titleCol, $seoCol, $metaCol, $statusCol, $statusVal] = $specs[$probe];
        $sql = "SELECT {$idCol} AS id, {$slugCol} AS slug, {$titleCol} AS title, {$seoCol} AS seo_title, {$metaCol} AS meta_description, {$statusCol} AS status_val FROM `{$table}` WHERE {$slugCol} = ? AND {$statusCol} = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            continue;
        }
        $stmt->bind_param('si', $slug, $statusVal);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if (!$row) {
            continue;
        }
        $row['table'] = $table;
        $row['status_field'] = $statusCol;
        $row['status_val'] = $statusVal;
        if ($table === 'services') {
            $row['read_only'] = true;
        }
        if ($table !== $expectedTable) {
            $row['table_mismatch'] = $expectedTable;
        }
        return $row;
    }

    return null;
}

function phase_b_run_verify(mysqli $conn, bool $quiet = true): int
{
    $root = dirname(__DIR__);
    $verifyScript = $root . '/scripts/_phase_b_production_verify.php';
    if (!is_file($verifyScript)) {
        fwrite(STDERR, "STOP: verify script missing.\n");
        return 2;
    }
    echo "=== ADIM 1: _phase_b_production_verify.php ===\n";
    if ($quiet) {
        ob_start();
    }
    passthru(PHP_BINARY . ' ' . escapeshellarg($verifyScript) . ' --production', $code);
    if ($quiet) {
        $out = (string) ob_get_clean();
        $data = json_decode($out, true);
        if (is_array($data)) {
            if (empty($data['db_connected'])) {
                fwrite(STDERR, "STOP: verify — DB not connected.\n");
                return 2;
            }
            echo 'db=' . ($data['db_name'] ?? '?');
            echo ' title_dup=' . count($data['title_duplicates'] ?? []);
            echo ' meta_dup=' . count($data['meta_duplicates'] ?? []);
            echo ' eligible=' . ($data['all_eligible_count'] ?? 0) . "\n";
        }
    }
    if ($code !== 0) {
        fwrite(STDERR, "STOP: verify exited {$code}.\n");
    }
    echo "\n";
    return $code;
}

/**
 * @return array{plan: list<array>, rollback_lines: list<string>, stmt_count: int, errors: list<string>}
 */
function phase_b_build_plan(mysqli $conn, array $batch, int $batchNum): array
{
    $seoMap = phase_b_seo_map();
    $redirectMap = mynak_seo_cannibalization_redirect_map();
    $batchType = $batch['type'];
    $batchSlugs = $batch['slugs'];
    $errors = [];
    $resolved = [];

    if (count($batchSlugs) > 4) {
        return ['plan' => [], 'rollback_lines' => [], 'stmt_count' => 0, 'errors' => ["Batch {$batchNum}: >4 slugs"]];
    }

    foreach ($batchSlugs as $slug) {
        if (isset($redirectMap[strtolower($slug)])) {
            $errors[] = "{$slug}: 301 redirect slug.";
            continue;
        }
        if (!isset($seoMap[$slug])) {
            $errors[] = "{$slug}: not in SEO map.";
            continue;
        }

        $probe = phase_b_resolve_slug($conn, $slug, $batchType);
        if (!$probe) {
            $errors[] = "{$slug}: not found via services→blog_posts→pages.";
            continue;
        }
        if (!empty($probe['read_only'])) {
            $errors[] = "{$slug}: in services (read-only) — STOP.";
            continue;
        }
        if ($probe['table'] !== $batchType) {
            $errors[] = "{$slug}: table mismatch got {$probe['table']}, expected {$batchType}.";
            continue;
        }
        $resolved[$slug] = $probe;
    }

    if ($errors !== []) {
        return ['plan' => [], 'rollback_lines' => [], 'stmt_count' => 0, 'errors' => $errors];
    }

    $tablesFound = array_unique(array_column($resolved, 'table'));
    if (count($tablesFound) !== 1 || $tablesFound[0] !== $batchType) {
        return ['plan' => [], 'rollback_lines' => [], 'stmt_count' => 0, 'errors' => ['Mixed tables in batch.']];
    }

    $plan = [];
    $rollbackLines = [
        '-- Phase B batch ' . $batchNum . ' (' . $batchType . ') ' . date('c'),
        'START TRANSACTION;',
        '',
    ];

    foreach ($batchSlugs as $slug) {
        $target = $seoMap[$slug];
        $row = $resolved[$slug];
        $table = (string) $row['table'];
        $id = (int) $row['id'];
        $statusField = (string) $row['status_field'];
        $statusVal = (int) $row['status_val'];

        $entry = [
            'slug' => $slug,
            'table' => $table,
            'id' => $id,
            'status_field' => $statusField,
            'status_val' => $statusVal,
            'before' => [
                'seo_title' => (string) ($row['seo_title'] ?? ''),
                'meta_description' => (string) ($row['meta_description'] ?? ''),
            ],
            'after' => [],
            'sql' => [],
        ];

        if (isset($target['seo_title'])) {
            $newTitle = $target['seo_title'];
            $oldTitle = $entry['before']['seo_title'];
            if ($newTitle !== $oldTitle) {
                $entry['after']['seo_title'] = $newTitle;
                $entry['sql'][] = [
                    'field' => 'seo_title',
                    'sql' => "UPDATE `{$table}` SET seo_title = ? WHERE id = ? AND {$statusField} = ?",
                    'params' => [$newTitle, $id, $statusVal],
                ];
                $rollbackLines[] = "UPDATE `{$table}` SET seo_title = " . phase_b_sql_quote($conn, $oldTitle) . " WHERE id = {$id} AND {$statusField} = {$statusVal};";
            }
        }

        if (isset($target['meta_description'])) {
            $newMeta = mynak_meta_description_clamp($target['meta_description'], 160);
            $oldMeta = $entry['before']['meta_description'];
            if ($newMeta !== $oldMeta) {
                $entry['after']['meta_description'] = $newMeta;
                $entry['sql'][] = [
                    'field' => 'meta_description',
                    'sql' => "UPDATE `{$table}` SET meta_description = ? WHERE id = ? AND {$statusField} = ?",
                    'params' => [$newMeta, $id, $statusVal],
                ];
                $rollbackLines[] = "UPDATE `{$table}` SET meta_description = " . phase_b_sql_quote($conn, $oldMeta) . " WHERE id = {$id} AND {$statusField} = {$statusVal};";
            }
        }

        if ($entry['sql'] !== []) {
            $plan[] = $entry;
        }
    }

    $rollbackLines[] = 'COMMIT;';
    $stmtCount = array_sum(array_map(static fn (array $p): int => count($p['sql']), $plan));

    return ['plan' => $plan, 'rollback_lines' => $rollbackLines, 'stmt_count' => $stmtCount, 'errors' => []];
}

function phase_b_print_plan(int $batchNum, string $batchType, array $plan, int $stmtCount, bool $apply): void
{
    echo "=== Batch {$batchNum} [{$batchType}] — " . ($apply ? 'APPLY' : 'DRY-RUN') . " ===\n";
    echo 'DB: ' . (defined('DB_NAME') ? DB_NAME : '?') . "\n";
    echo 'Rows: ' . count($plan) . " | SQL: {$stmtCount}\n\n";
    foreach ($plan as $p) {
        echo sprintf("[%s id=%d] /%s\n", $p['table'], $p['id'], $p['slug']);
        foreach ($p['sql'] as $s) {
            $val = (string) $s['params'][0];
            echo '  ' . $s['field'] . ': ' . phase_b_preview($val) . ' (' . mb_strlen($val) . " chars)\n";
            echo '  SQL: ' . $s['sql'] . "\n";
        }
        echo "\n";
    }
}

function phase_b_apply_plan(mysqli $conn, array $plan, array $rollbackLines, int $batchNum): array
{
    $ts = date('Ymd_His');
    $root = dirname(__DIR__);
    $backupDir = $root . '/logs/backups/phase_b_micro' . $batchNum . '_' . $ts;
    $rollbackPath = $backupDir . '/rollback.sql';
    $reportPath = $root . '/logs/phase_b_micro' . $batchNum . '_' . $ts . '.json';

    if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
        throw new RuntimeException("Cannot create {$backupDir}");
    }
    file_put_contents($rollbackPath, implode("\n", $rollbackLines) . "\n");

    $conn->begin_transaction();
    $applied = 0;
    try {
        foreach ($plan as $p) {
            foreach ($p['sql'] as $s) {
                $stmt = $conn->prepare($s['sql']);
                if (!$stmt) {
                    throw new RuntimeException('Prepare failed: ' . $conn->error);
                }
                $stmt->bind_param('sii', $s['params'][0], $s['params'][1], $s['params'][2]);
                if (!$stmt->execute()) {
                    throw new RuntimeException($p['slug'] . '/' . $s['field'] . ': ' . $stmt->error);
                }
                $applied += $stmt->affected_rows;
                $stmt->close();
            }
        }
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }

    file_put_contents($reportPath, json_encode([
        'batch' => $batchNum,
        'applied_at' => date('c'),
        'db' => defined('DB_NAME') ? DB_NAME : null,
        'rows' => count($plan),
        'affected_rows' => $applied,
        'rollback_sql' => $rollbackPath,
        'plan' => $plan,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    return ['applied' => $applied, 'rollback' => $rollbackPath, 'report' => $reportPath];
}

$batches = phase_b_micro_batches();

if ($dryRunAll) {
    $v = phase_b_run_verify($conn);
    if ($v !== 0) {
        exit($v);
    }
    echo "=== ADIM 2: DRY-RUN (batches 1–2 only) ===\n";
    $totalSql = 0;
    foreach ([1, 2] as $n) {
        if (empty($batches[$n]['enabled'])) {
            continue;
        }
        $built = phase_b_build_plan($conn, $batches[$n], $n);
        if ($built['errors'] !== []) {
            fwrite(STDERR, "STOP batch {$n}:\n");
            foreach ($built['errors'] as $e) {
                fwrite(STDERR, "  - {$e}\n");
            }
            exit(3);
        }
        phase_b_print_plan($n, $batches[$n]['type'], $built['plan'], $built['stmt_count'], false);
        $totalSql += $built['stmt_count'];
    }
    echo "DRY-RUN complete. Total SQL: {$totalSql}\n";
    echo "fiyat.php (deploy): İzmir Nakliyat Fiyatları 2026 | MY Nakliyat\n";
    echo "Apply: --production --batch=1 --apply --confirm\n";
    exit(0);
}

if ($batchNum < 1) {
    fwrite(STDERR, "Usage:\n");
    fwrite(STDERR, "  php scripts/apply_phase_b_seo.php --production --dry-run\n");
    fwrite(STDERR, "  php scripts/apply_phase_b_seo.php --production --batch=1|2 --apply --confirm\n");
    exit(1);
}

if ($batchNum > 2 || empty($batches[$batchNum]['enabled'])) {
    fwrite(STDERR, "STOP: batch {$batchNum} disabled (only 1–2 allowed).\n");
    exit(1);
}

$v = phase_b_run_verify($conn);
if ($v !== 0) {
    exit($v);
}

$batch = $batches[$batchNum];
$built = phase_b_build_plan($conn, $batch, $batchNum);
if ($built['errors'] !== []) {
    fwrite(STDERR, "STOP batch {$batchNum}:\n");
    foreach ($built['errors'] as $e) {
        fwrite(STDERR, "  - {$e}\n");
    }
    exit(3);
}

$plan = $built['plan'];
phase_b_print_plan($batchNum, $batch['type'], $plan, $built['stmt_count'], $apply);

if ($plan === []) {
    echo "Nothing to update.\n";
    exit(0);
}

if (!$apply) {
    echo "DRY-RUN OK. Apply: --production --batch={$batchNum} --apply --confirm\n";
    exit(0);
}

if (!$confirm) {
    fwrite(STDERR, "STOP: --apply requires --confirm\n");
    exit(4);
}

try {
    $result = phase_b_apply_plan($conn, $plan, $built['rollback_lines'], $batchNum);
} catch (Throwable $e) {
    fwrite(STDERR, "TRANSACTION ROLLED BACK: " . $e->getMessage() . "\n");
    exit(6);
}

echo "APPLIED batch {$batchNum}: {$result['applied']} row(s).\n";
echo "Rollback: {$result['rollback']}\n";
echo "Report: {$result['report']}\n";

if ($verifyCurl || $batchNum === 2) {
    phase_b_curl_verify();
}

exit(0);

function phase_b_sql_quote(mysqli $conn, string $value): string
{
    return "'" . $conn->real_escape_string($value) . "'";
}

function phase_b_preview(string $text, int $max = 72): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    return mb_substr($text, 0, $max - 1) . '…';
}

function phase_b_curl_verify(): void
{
    $paths = [
        '/fiyat',
        '/izmir-evden-eve-nakliyat',
        '/sehirici-nakliyat',
        '/izmir-evden-eve-nakliyat-fiyatlari-2026',
        '/2026-sehirler-arasi-nakliyat-fiyatlari-guncel-rehber',
    ];
    $base = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : 'https://www.mynakliyat.com.tr';

    echo "\n=== ADIM 5: Curl verify ===\n";
    foreach ($paths as $path) {
        $ctx = stream_context_create(['http' => ['timeout' => 25, 'header' => "User-Agent: PhaseB-Prod/1.0\r\n"]]);
        $headers = @get_headers($base . $path, true, $ctx);
        $code = 0;
        if (is_array($headers)) {
            $status = $headers[0] ?? '';
            if (preg_match('/\s(\d{3})\s/', (string) $status, $m)) {
                $code = (int) $m[1];
            }
        }
        $html = @file_get_contents($base . $path, false, $ctx);
        $title = '';
        $meta = '';
        if (is_string($html) && $html !== '') {
            if (preg_match('/<title[^>]*>([^<]+)<\/title>/iu', $html, $m)) {
                $title = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
            if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/iu', $html, $m)
                || preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']description["\']/iu', $html, $m)) {
                $meta = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }
        echo "{$path} HTTP {$code}\n";
        echo '  title: ' . phase_b_preview($title, 80) . ' (' . mb_strlen($title) . ")\n";
        echo '  meta:  ' . phase_b_preview($meta, 80) . ' (' . mb_strlen($meta) . ")\n";
    }
}
