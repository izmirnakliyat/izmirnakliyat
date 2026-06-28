<?php
declare(strict_types=1);

/**
 * Phase B SEO — web-safe batch apply (no passthru / no CLI).
 */

/** @return array<int, array{type: string, slugs: list<string>}> */
function mynak_phase_b_batches(): array
{
    return [
        1 => [
            'type' => 'pages',
            'slugs' => ['izmir-evden-eve-nakliyat', 'sehirici-nakliyat'],
        ],
        2 => [
            'type' => 'blog_posts',
            'slugs' => [
                'izmir-evden-eve-nakliyat-fiyatlari-2026',
                'izmir-evden-eve-nakliyat-firmalar',
                'mobilya-beyaz-esya-tasimaciligi-rehberi',
                'izmir-evden-eve-nakliyat-guncel-tasinma-trendleri',
            ],
        ],
    ];
}

/** @return array<string, array{seo_title?: string, meta_description?: string}> */
function mynak_phase_b_seo_map(): array
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

function mynak_phase_b_resolve(mysqli $conn, string $slug, string $expectedTable): ?array
{
    if ($expectedTable === 'pages') {
        $sql = "SELECT id, slug, seo_title, meta_description, status AS status_val FROM pages WHERE slug = ? AND status = 1 LIMIT 1";
    } elseif ($expectedTable === 'blog_posts') {
        $sql = "SELECT id, slug, seo_title, meta_description, durum AS status_val FROM blog_posts WHERE slug = ? AND durum = 3 LIMIT 1";
    } else {
        return null;
    }
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!$row) {
        return null;
    }
    $row['table'] = $expectedTable;
    $row['status_field'] = $expectedTable === 'pages' ? 'status' : 'durum';
    return $row;
}

/**
 * @return array{ok: bool, output: string, rollback_path?: string}
 */
function mynak_phase_b_run_batch(mysqli $conn, int $batchNum, bool $apply): array
{
    require_once dirname(__DIR__) . '/includes/mynak_meta_description.php';

    $batches = mynak_phase_b_batches();
    $seoMap = mynak_phase_b_seo_map();
    $lines = [];
    $lines[] = '=== Phase B Batch ' . $batchNum . ' ===';
    $lines[] = 'Mode: ' . ($apply ? 'APPLY' : 'DRY-RUN');
    $lines[] = 'DB: ' . (defined('DB_NAME') ? DB_NAME : '?');
    $lines[] = '';

    if (!isset($batches[$batchNum])) {
        return ['ok' => false, 'output' => implode("\n", $lines) . "\nHATA: Geçersiz batch."];
    }

    $batch = $batches[$batchNum];
    $plan = [];
    $rollback = ['-- Phase B batch ' . $batchNum . ' rollback ' . date('c'), 'START TRANSACTION;', ''];

    foreach ($batch['slugs'] as $slug) {
        $row = mynak_phase_b_resolve($conn, $slug, $batch['type']);
        if (!$row) {
            return ['ok' => false, 'output' => implode("\n", $lines) . "\nSTOP: {$slug} bulunamadı ({$batch['type']})."];
        }
        $target = $seoMap[$slug] ?? null;
        if (!$target) {
            return ['ok' => false, 'output' => implode("\n", $lines) . "\nSTOP: {$slug} SEO map yok."];
        }

        $table = $batch['type'];
        $id = (int) $row['id'];
        $statusField = (string) $row['status_field'];
        $statusVal = (int) $row['status_val'];
        $entry = ['slug' => $slug, 'table' => $table, 'id' => $id, 'ops' => []];

        if (isset($target['seo_title'])) {
            $new = $target['seo_title'];
            $old = (string) ($row['seo_title'] ?? '');
            if ($new !== $old) {
                $entry['ops'][] = ['field' => 'seo_title', 'new' => $new, 'old' => $old];
                $rollback[] = "UPDATE `{$table}` SET seo_title = '" . $conn->real_escape_string($old) . "' WHERE id = {$id};";
            }
        }
        if (isset($target['meta_description'])) {
            $new = mynak_meta_description_clamp($target['meta_description'], 160);
            $old = (string) ($row['meta_description'] ?? '');
            if ($new !== $old) {
                $entry['ops'][] = ['field' => 'meta_description', 'new' => $new, 'old' => $old];
                $rollback[] = "UPDATE `{$table}` SET meta_description = '" . $conn->real_escape_string($old) . "' WHERE id = {$id};";
            }
        }
        if ($entry['ops'] !== []) {
            $plan[] = $entry;
        }
    }

    foreach ($plan as $p) {
        $lines[] = "[{$p['table']} id={$p['id']}] /{$p['slug']}";
        foreach ($p['ops'] as $op) {
            $lines[] = '  ' . $op['field'] . ': ' . $op['new'];
        }
        $lines[] = '';
    }

    if ($plan === []) {
        $lines[] = 'Güncellenecek alan yok (zaten güncel).';
        return ['ok' => true, 'output' => implode("\n", $lines)];
    }

    if (!$apply) {
        $lines[] = 'DRY-RUN tamam. Uygula butonuna basın.';
        return ['ok' => true, 'output' => implode("\n", $lines)];
    }

    $root = dirname(__DIR__);
    $ts = date('Ymd_His');
    $backupDir = $root . '/logs/backups/phase_b_web' . $batchNum . '_' . $ts;
    if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
        return ['ok' => false, 'output' => implode("\n", $lines) . "\nHATA: backup klasörü oluşturulamadı."];
    }
    $rollback[] = 'COMMIT;';
    $rollbackPath = $backupDir . '/rollback.sql';
    file_put_contents($rollbackPath, implode("\n", $rollback) . "\n");

    $conn->begin_transaction();
    $affected = 0;
    try {
        foreach ($plan as $p) {
            $table = $p['table'];
            $id = $p['id'];
            $statusField = $table === 'pages' ? 'status' : 'durum';
            $statusVal = $table === 'pages' ? 1 : 3;
            foreach ($p['ops'] as $op) {
                $field = $op['field'];
                $sql = "UPDATE `{$table}` SET {$field} = ? WHERE id = ? AND {$statusField} = ?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new RuntimeException($conn->error);
                }
                $stmt->bind_param('sii', $op['new'], $id, $statusVal);
                if (!$stmt->execute()) {
                    throw new RuntimeException($stmt->error);
                }
                $affected += $stmt->affected_rows;
                $stmt->close();
            }
        }
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        $lines[] = 'HATA — transaction geri alındı: ' . $e->getMessage();
        return ['ok' => false, 'output' => implode("\n", $lines)];
    }

    $lines[] = "BAŞARILI: {$affected} satır güncellendi.";
    $lines[] = 'Rollback: ' . $rollbackPath;

    return ['ok' => true, 'output' => implode("\n", $lines), 'rollback_path' => $rollbackPath];
}
