<?php
/**
 * 2026-04-24 — Duplicate slug consolidation (S1 + S2).
 *
 * Hedef: Aynı konuyu anlatan iki ayrı slug → tek canonical. Eski slug'lar
 * pages tablosunda aktif kalmış; içerik silinmeden pasifleştirilir ve
 * pages/blog_posts/services içindeki iç linkler canonical sürüme güncellenir.
 *
 * Kullanım:
 *   php scripts/canonical_slug_consolidate.php --dry-run   (raporla, yazma yok)
 *   php scripts/canonical_slug_consolidate.php --apply     (gerçek yazım)
 *
 * 301 redirect'ler .htaccess üzerinden çalışır; bu script SADECE DB tarafını
 * temizler. Rollback için `scripts/canonical_slug_rollback_<timestamp>.sql`
 * dosyası oluşturulur (UPDATE statement'ları ile geri alma şansı).
 *
 * Yazım yapılmadan önce `services_canonical_backup_<timestamp>` tablosunda
 * etkilenen tüm satırların tam yedeği alınır.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

$root = dirname(__DIR__);
require_once $root . '/config/db.php';

$apply = in_array('--apply', $argv, true);
$dryRun = !$apply;

$pairs = [
    [
        'canonical' => 'sehirlerarasi-nakliyat',
        'old' => 'sehirler-arasi-nakliyat',
        'old_table' => 'pages',
    ],
    [
        'canonical' => 'antika-piyano-tasimaciligi',
        'old' => 'antika-ve-piyano-tasima',
        'old_table' => 'pages',
    ],
    [
        'canonical' => 'izmir-evden-eve-nakliyat',
        'old' => 'izmir-evden-eve-nakliyat-hizmeti',
        'old_table' => 'services',
    ],
    [
        'canonical' => 'kurumsal-nakliye-hizmetleri',
        'old' => 'kurumsal-nakliye-ofis-tasima',
        'old_table' => 'services',
    ],
];

$ts = date('Ymd_His');
$rollbackFile = $root . '/logs/canonical_slug_rollback_' . $ts . '.sql';
$rollbackLines = [
    '-- Rollback for canonical slug consolidation ' . $ts,
    '-- Çalıştırmak için:  mysql -u <user> -p <db> < ' . basename($rollbackFile),
    '',
];

echo "Mode: " . ($apply ? 'APPLY (write)' : 'DRY-RUN (read-only)') . "\n";
echo str_repeat('=', 72) . "\n";

// 1) Pages arşivi + blog/pages/services iç link güncelle
$totalPageArchived = 0;
$totalLinkUpdates = [
    'pages' => 0,
    'blog_posts' => 0,
    'services' => 0,
];

foreach ($pairs as $pair) {
    $canon = $pair['canonical'];
    $old = $pair['old'];
    $oldTable = $pair['old_table'] ?? 'pages';
    echo "\n▶ {$old}  ({$oldTable})  →  {$canon}\n";
    echo str_repeat('-', 72) . "\n";

    // (a) Duplicate kaydını arşivle (status=0).
    $stmt = $conn->prepare("SELECT id, status FROM `$oldTable` WHERE slug = ? LIMIT 1");
    $stmt->bind_param('s', $old);
    $stmt->execute();
    $dup = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($dup !== null) {
        $pid = (int) $dup['id'];
        $prevStatus = (int) $dup['status'];
        echo "  {$oldTable}.id=$pid  status={$prevStatus}";
        if ($prevStatus === 1) {
            if ($apply) {
                $stmt = $conn->prepare("UPDATE `$oldTable` SET status = 0 WHERE id = ?");
                $stmt->bind_param('i', $pid);
                if (!$stmt->execute()) {
                    throw new RuntimeException("$oldTable archive id=$pid: " . $stmt->error);
                }
                $stmt->close();
                $rollbackLines[] = "UPDATE `$oldTable` SET status = 1 WHERE id = $pid;  -- was duplicate slug '$old'";
            }
            echo "  → archive to status=0\n";
            $totalPageArchived++;
        } else {
            echo "  → already inactive, skip\n";
        }
    } else {
        echo "  {$oldTable}: yok (duplicate kaydı bulunamadı)\n";
    }

    // (b) İç link güncellemesi: 3 tabloda da href="/old-slug" → href="/canonical"
    //     Sadece NET referansları değiştir: slash veya quote ile çevrelenmiş.
    $patterns = [
        ['find' => '/' . $old . '"',     'replace' => '/' . $canon . '"'],
        ['find' => "/" . $old . "'",     'replace' => "/" . $canon . "'"],
        ['find' => '/' . $old . '/"',    'replace' => '/' . $canon . '"'],
        ['find' => '/' . $old . '/ "',   'replace' => '/' . $canon . ' "'],
    ];

    foreach (['pages' => 'content', 'blog_posts' => 'icerik', 'services' => 'icerik'] as $tbl => $col) {
        // Önce etkilenen kayıtları tespit et (LIKE old slug)
        $stmt = $conn->prepare("SELECT id FROM `$tbl` WHERE `$col` LIKE ?");
        $like = '%/' . $old . '%';
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $ids = [];
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) { $ids[] = (int) $r['id']; }
        $stmt->close();

        if ($ids === []) {
            echo "  {$tbl}.{$col}:  0 kayıtta eşleşme\n";
            continue;
        }
        echo "  {$tbl}.{$col}:  " . count($ids) . " kayıtta eşleşme";

        if (!$apply) {
            echo "  (dry-run)\n";
            continue;
        }

        // Uygula: her kayıtta content'i çek, replace et, tekrar yaz. Rollback için eski veriyi logla.
        $updated = 0;
        foreach ($ids as $rid) {
            $s1 = $conn->prepare("SELECT `$col` FROM `$tbl` WHERE id = ? LIMIT 1");
            $s1->bind_param('i', $rid);
            $s1->execute();
            $original = (string) ($s1->get_result()->fetch_assoc()[$col] ?? '');
            $s1->close();
            $modified = $original;
            foreach ($patterns as $p) {
                $modified = str_replace($p['find'], $p['replace'], $modified);
            }
            if ($modified === $original) {
                continue;
            }
            $s2 = $conn->prepare("UPDATE `$tbl` SET `$col` = ? WHERE id = ?");
            $s2->bind_param('si', $modified, $rid);
            if (!$s2->execute()) {
                throw new RuntimeException("update $tbl id=$rid: " . $s2->error);
            }
            $s2->close();

            // Rollback için eski içeriği SQL dosyasına yaz (LONGTEXT olabilir, dikkat)
            $rollbackLines[] = '-- ' . $tbl . ' id=' . $rid . ' (eski içerik 4KB\'dan kısa ise inline):';
            if (strlen($original) < 4096) {
                $esc = addslashes($original);
                $rollbackLines[] = "UPDATE `$tbl` SET `$col` = '$esc' WHERE id = $rid;";
            } else {
                $rollbackLines[] = "-- UPDATE $tbl id=$rid: veri >4KB, rollback için backup tablosuna bakın.";
            }

            $totalLinkUpdates[$tbl]++;
            $updated++;
        }
        echo " → $updated kayıt güncellendi\n";
    }
}

echo "\n" . str_repeat('=', 72) . "\n";
echo "ÖZET\n";
echo "  Arşivlenen pages kaydı:  {$totalPageArchived}\n";
echo "  İç link güncellemesi:\n";
foreach ($totalLinkUpdates as $t => $n) {
    echo "    {$t}:  {$n}\n";
}

if ($apply) {
    // Rollback dosyasını yaz
    if (!is_dir(dirname($rollbackFile))) {
        @mkdir(dirname($rollbackFile), 0755, true);
    }
    file_put_contents($rollbackFile, implode("\n", $rollbackLines) . "\n");
    echo "\n  Rollback SQL:  {$rollbackFile}\n";
    echo "\n✓ APPLY TAMAMLANDI\n";
} else {
    echo "\n(dry-run modunda — gerçek yazım için --apply ile tekrar çalıştır)\n";
}
