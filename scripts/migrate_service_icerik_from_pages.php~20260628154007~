<?php
/**
 * CLI v2 migration: services tablosuna `icerik` MEDIUMTEXT kolonu ekler.
 * Çakışan slug'larda pages.content → services.icerik kopyalar.
 * pages.status=0 (arşiv).
 *
 * MANTIK:
 *  - services.aciklama → ana sayfa kartlarında KISA özet (eski hâli, dokunma).
 *  - services.icerik   → detay sayfada UZUN HTML içerik (yeni, pages'ten geldi).
 *
 * Kullanım:
 *   php scripts\migrate_service_icerik_from_pages.php                # dry-run
 *   php scripts\migrate_service_icerik_from_pages.php --apply        # uygula
 *   php scripts\migrate_service_icerik_from_pages.php --rollback     # icerik=NULL + pages.status=1
 *
 * Güvenli: services.aciklama'ya HİÇ dokunmaz. Kolon zaten varsa ekleme atlar.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

$root = dirname(__DIR__);
require_once $root . '/config/db.php';

/** @var mysqli $conn */
if (!isset($conn) || !($conn instanceof mysqli)) {
    fwrite(STDERR, "HATA: \$conn hazır değil.\n");
    exit(1);
}
$conn->set_charset('utf8mb4');

$mode = 'dry';
foreach ($argv as $arg) {
    if ($arg === '--apply') { $mode = 'apply'; }
    elseif ($arg === '--rollback') { $mode = 'rollback'; }
}

function h(string $t): void { echo "\n" . str_repeat('=', 76) . "\n  $t\n" . str_repeat('=', 76) . "\n"; }
function line(): void { echo str_repeat('-', 76) . "\n"; }

h("services.icerik migration  [MODE=" . strtoupper($mode) . "]");

/* 1) Kolon var mı? */
$colCheck = $conn->query("SHOW COLUMNS FROM services LIKE 'icerik'");
$hasIcerik = $colCheck && $colCheck->num_rows > 0;
if ($colCheck) { $colCheck->free(); }

echo "services.icerik kolonu: " . ($hasIcerik ? 'VAR' : 'YOK') . "\n";

if ($mode === 'rollback') {
    if (!$hasIcerik) {
        echo "Kolon zaten yok — rollback yapılacak bir şey yok.\n";
        exit(0);
    }
    $conn->begin_transaction();
    try {
        $rollbackSlugs = [];
        $r = $conn->query("SELECT slug FROM services WHERE icerik IS NOT NULL AND icerik <> ''");
        if ($r) {
            while ($row = $r->fetch_assoc()) { $rollbackSlugs[] = $row['slug']; }
            $r->free();
        }
        if ($rollbackSlugs !== []) {
            $in = implode(',', array_fill(0, count($rollbackSlugs), '?'));
            $types = str_repeat('s', count($rollbackSlugs));
            $stmt = $conn->prepare("UPDATE pages SET status=1 WHERE slug IN ($in)");
            $stmt->bind_param($types, ...$rollbackSlugs);
            $stmt->execute();
            $pagesRestored = $stmt->affected_rows;
            $stmt->close();
        } else {
            $pagesRestored = 0;
        }
        $conn->query("UPDATE services SET icerik = NULL WHERE icerik IS NOT NULL");
        $svcCleared = $conn->affected_rows;
        $conn->commit();
        echo "ROLLBACK: services.icerik = NULL yapıldı ($svcCleared kayıt).\n";
        echo "          pages.status=1 restore (" . $pagesRestored . " kayıt).\n";
        echo "NOT: services.icerik kolonu tabloda kalır (DROP için manuel: ALTER TABLE services DROP COLUMN icerik).\n";
        exit(0);
    } catch (Throwable $e) {
        $conn->rollback();
        fwrite(STDERR, "ROLLBACK HATA: " . $e->getMessage() . "\n");
        exit(4);
    }
}

/* 2) Çakışma listesi */
$joinStatus = $hasIcerik ? "AND p.status = 1" : "AND p.status = 1";
$listSql = "SELECT s.id AS svc_id, s.slug,
                   CHAR_LENGTH(s.aciklama) AS svc_aciklama_len,
                   CHAR_LENGTH(p.content)  AS page_len,
                   p.id AS page_id, p.content AS page_content
            FROM services s
            JOIN pages p ON p.slug = s.slug
            WHERE s.status = 1 $joinStatus
            ORDER BY page_len DESC";
$res = $conn->query($listSql);
if (!$res) {
    fwrite(STDERR, "HATA: list query: " . $conn->error . "\n");
    exit(3);
}
$rows = [];
while ($r = $res->fetch_assoc()) { $rows[] = $r; }
$res->free();

echo sprintf("%-35s %10s %10s\n", 'SLUG', 'ACIKLAMA', 'PAGE_LEN');
line();
foreach ($rows as $r) {
    echo sprintf("%-35s %10d %10d\n", $r['slug'], $r['svc_aciklama_len'], $r['page_len']);
}
line();
echo "TOPLAM: " . count($rows) . " slug çakışması\n";

if ($mode === 'dry') {
    echo "\n[DRY-RUN] Hiçbir şey değiştirilmedi.\n";
    if (!$hasIcerik) {
        echo "APPLY modunda yapılacak: ALTER TABLE services ADD COLUMN icerik MEDIUMTEXT NULL AFTER aciklama\n";
    }
    echo "APPLY modunda:\n";
    echo "  - services.icerik ← pages.content (" . count($rows) . " satır)\n";
    echo "  - services.updated_at = NOW()\n";
    echo "  - pages.status = 0\n";
    echo "  - services.aciklama KORUNUR (ana sayfa kartları değişmez)\n";
    echo "\nUygulamak için: php scripts\\migrate_service_icerik_from_pages.php --apply\n";
    exit(0);
}

/* APPLY */
if ($rows === []) {
    echo "Çakışma yok — ALTER haricinde yapılacak bir şey yok.\n";
}

$conn->begin_transaction();
try {
    if (!$hasIcerik) {
        if (!$conn->query("ALTER TABLE services ADD COLUMN icerik MEDIUMTEXT NULL AFTER aciklama")) {
            throw new RuntimeException("ALTER failed: " . $conn->error);
        }
        echo "  ✓ ALTER TABLE services ADD COLUMN icerik MEDIUMTEXT\n";
    }

    foreach ($rows as $r) {
        $slug = (string) $r['slug'];
        $svcId = (int) $r['svc_id'];
        $pageId = (int) $r['page_id'];
        $pageContent = (string) $r['page_content'];

        $stmt = $conn->prepare("UPDATE services SET icerik = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('si', $pageContent, $svcId);
        if (!$stmt->execute()) { throw new RuntimeException("services update id=$svcId: " . $stmt->error); }
        $stmt->close();

        $stmt = $conn->prepare("UPDATE pages SET status = 0 WHERE id = ?");
        $stmt->bind_param('i', $pageId);
        if (!$stmt->execute()) { throw new RuntimeException("pages archive id=$pageId: " . $stmt->error); }
        $stmt->close();

        echo "  ✓ $slug  (icerik: " . mb_strlen($pageContent) . " karakter)\n";
    }
    $conn->commit();
    echo "\nAPPLY TAMAM. Ana sayfa kartları DEĞİŞMEDİ (services.aciklama korundu).\n";
    echo "Detay sayfalarda services.icerik gösterilecek (front_controller güncellemesi ardından).\n";
    echo "Rollback: php scripts\\migrate_service_icerik_from_pages.php --rollback\n";
} catch (Throwable $e) {
    $conn->rollback();
    fwrite(STDERR, "APPLY HATA (rollback edildi): " . $e->getMessage() . "\n");
    exit(5);
}
