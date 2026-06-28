<?php
/**
 * blog_posts.durum anlam taşıması (tek seferlik, idempotent bayraklı):
 *
 * Eski → Yeni
 *   0 → 0  (taslak)
 *   1 → 3  (yayında)
 *   2 → 1  (editör kuyruğu)
 *   3 → 2  (reddedildi → revize)
 *
 * Ayrıca settings.auto_blog_default_status eski değerleri yeni koleksiyona uyarlanır:
 *   eski 1 (doğrudan yayın) → 3
 *   eski 2 (kuyruk) → 1
 *   0 → 0
 *
 * settings.blog_post_status_v2_migrated = 1 ise tekrar uygulanmaz.
 *
 * KULLANIM:
 *   php scripts/blog_post_status_semantic_migrate.php
 *   php scripts/blog_post_status_semantic_migrate.php --dry-run
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Bu script yalnızca CLI.\n");
    exit(1);
}

require __DIR__ . '/../config/db.php';

$dryRun = in_array('--dry-run', $argv, true);

/**
 * @return non-empty-string|null
 */
function blog_status_migrate_setting_get(mysqli $conn, string $name): ?string
{
    $stmt = $conn->prepare('SELECT value FROM settings WHERE name = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return $row && $row['value'] !== '' && $row['value'] !== null ? (string) $row['value'] : null;
}

function blog_status_migrate_setting_upsert(mysqli $conn, string $name, string $value): void
{
    $stmt = $conn->prepare('SELECT id FROM settings WHERE name = ? LIMIT 1');
    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $exists = (bool) $stmt->get_result()->fetch_assoc();

    if ($exists) {
        $u = $conn->prepare('UPDATE settings SET value = ? WHERE name = ?');
        if (!$u) {
            throw new RuntimeException($conn->error);
        }
        $u->bind_param('ss', $value, $name);
        $u->execute();
    } else {
        $i = $conn->prepare('INSERT INTO settings (name, value) VALUES (?, ?)');
        if (!$i) {
            throw new RuntimeException($conn->error);
        }
        $i->bind_param('ss', $name, $value);
        $i->execute();
    }
}

$flag = blog_status_migrate_setting_get($conn, 'blog_post_status_v2_migrated');
if ($flag === '1') {
    fwrite(STDOUT, "[OK] Zaten taşınmış (blog_post_status_v2_migrated=1).\n");
    exit(0);
}

fwrite(STDOUT, "blog_posts.durum CASE güncellemesi...\n");

if (!$dryRun) {
    $sql = 'UPDATE blog_posts SET durum = CASE durum
        WHEN 0 THEN 0
        WHEN 1 THEN 3
        WHEN 2 THEN 1
        WHEN 3 THEN 2
        ELSE durum END';
    if (!$conn->query($sql)) {
        fwrite(STDERR, 'SQL hata: ' . $conn->error . "\n");
        exit(1);
    }
    fwrite(STDOUT, '  Etkilenen satır: ' . (string) $conn->affected_rows . "\n");
} else {
    fwrite(STDOUT, "  [DRY-RUN] UPDATE atlandı.\n");
}

$abs = blog_status_migrate_setting_get($conn, 'auto_blog_default_status');
if ($abs !== null && $abs !== '') {
    $ai = (int) $abs;
    $new = null;
    if ($ai === 0) {
        $new = '0';
    } elseif ($ai === 1) {
        $new = '3';
    } elseif ($ai === 2) {
        $new = '1';
    } elseif ($ai === 3) {
        $new = '3';
    }
    if ($new !== null) {
        if (!$dryRun) {
            blog_status_migrate_setting_upsert($conn, 'auto_blog_default_status', $new);
        }
        fwrite(STDOUT, "auto_blog_default_status: {$abs} → {$new}\n");
    }
} else {
    if (!$dryRun) {
        blog_status_migrate_setting_upsert($conn, 'auto_blog_default_status', '1');
    }
    fwrite(STDOUT, "auto_blog_default_status yoktu; varsayılan 1 (editör kuyruğu) yazıldı.\n");
}

if (!$dryRun) {
    blog_status_migrate_setting_upsert($conn, 'blog_post_status_v2_migrated', '1');
    fwrite(STDOUT, "blog_post_status_v2_migrated=1 kaydedildi.\n");
} else {
    fwrite(STDOUT, "[DRY-RUN] Bayrak yazılmadı.\n");
}

exit(0);
