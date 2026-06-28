<?php
/**
 * CLI migration: services ↔ pages slug çakışmalarında uzun içeriği services'e taşı.
 *
 * Neden: front_controller_slug.php önce services tablosuna bakıyor, bulunca exit ediyor.
 * Aynı slug'lı pages kaydındaki UZUN content canlıda HİÇ görünmüyor (kısa services.aciklama render ediliyor).
 *
 * Bu script:
 *   1) services_backup tablosunu oluşturur (yoksa) ve etkilenen services satırlarının tam yedeğini alır.
 *   2) services.aciklama ← pages.content (mediumtext → mediumtext, kayıpsız).
 *   3) services.meta_description boş veya pages'teki ile karşılaştırma: pages'tekini tercih et (genelde daha net).
 *   4) services.seo_title boş ise pages.seo_title'dan al.
 *   5) services.updated_at = NOW() (article:modified_time + cache bust).
 *   6) pages.status = 0 (arşivle, silmiyor — rollback için saklanıyor).
 *
 * Kullanım:
 *   php scripts\migrate_service_content_from_pages.php                # dry-run (varsayılan, hiçbir şey değişmez)
 *   php scripts\migrate_service_content_from_pages.php --apply        # gerçek uygulama
 *   php scripts\migrate_service_content_from_pages.php --rollback     # services_backup'tan geri yükle + pages.status=1
 *
 * Rollback güvenli: backup tablosu var olduğu sürece istediğin zaman geri dönebilirsin.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

// ---------------------------------------------------------------------------
// DEPRECATED — 2026-04-24
//
// Bu script ESKİ migration stratejisini uyguluyor:
//   services.aciklama ← pages.content  (UZUN HTML -> kısa özet alanına yazıyor)
//
// Ana sayfa hizmet kartları `services.aciklama`'yı kullanıyor (kısa, düz metin
// beklentisi). Bu script tekrar çalıştırılırsa ana sayfa kartları BOZULUR
// (daha önce yaşanmış hata: kartlar 15.000 karakterlik HTML basıyor).
//
// Doğru script:
//   scripts/migrate_service_icerik_from_pages.php
// (uzun HTML'i yeni `services.icerik` kolonuna yazar, `aciklama`'ya dokunmaz).
//
// Bu dosyayı, geçmiş rollback yedeği (services_backup tablosu) için referans
// olarak tutuyoruz. Yanlışlıkla çalıştırılmasını engellemek için aşağıda
// HARD-STOP vardır. Gerçekten çalıştırmak isteyen, şu ortam değişkenini
// bilinçli olarak set etmelidir: MYNAK_ALLOW_LEGACY_MIGRATE=1
// ---------------------------------------------------------------------------
if (getenv('MYNAK_ALLOW_LEGACY_MIGRATE') !== '1') {
    fwrite(STDERR, "\n" .
        "========================================================================\n" .
        "  DEPRECATED SCRIPT — ÇALIŞTIRMA.\n" .
        "========================================================================\n" .
        "  Bu script `services.aciklama`'yı uzun HTML ile değiştirir.\n" .
        "  Ana sayfa hizmet kartları bozulur.\n" .
        "\n" .
        "  Doğru script:\n" .
        "    php scripts\\migrate_service_icerik_from_pages.php\n" .
        "\n" .
        "  Yine de bu eski akışı ÇALIŞTIRMAK ZORUNDA iseniz:\n" .
        "    set MYNAK_ALLOW_LEGACY_MIGRATE=1  (Windows)\n" .
        "    export MYNAK_ALLOW_LEGACY_MIGRATE=1  (Linux/macOS)\n" .
        "========================================================================\n\n");
    exit(2);
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

function m_line(string $ch = '-', int $n = 78): void { echo str_repeat($ch, $n) . "\n"; }
function m_h(string $t): void { m_line('='); echo "  " . $t . "\n"; m_line('='); }

m_h("services ↔ pages migrate  [MODE=" . strtoupper($mode) . "]");

/* 1) services_backup tablosu: aynı şema (yedek deposu). */
$ensureBackupSql = "CREATE TABLE IF NOT EXISTS services_backup LIKE services";
if (!$conn->query($ensureBackupSql)) {
    fwrite(STDERR, "HATA: services_backup tablosu oluşturulamadı: " . $conn->error . "\n");
    exit(2);
}

/* Migrate edilecek slug'lar listesi (snapshot) */
$listSql = "SELECT s.id AS svc_id, s.slug,
                   CHAR_LENGTH(s.aciklama) AS svc_len,
                   CHAR_LENGTH(p.content) AS page_len,
                   p.id AS page_id,
                   p.title AS page_title,
                   p.seo_title AS page_seo_title,
                   p.meta_description AS page_meta,
                   p.content AS page_content,
                   s.seo_title AS svc_seo_title,
                   s.meta_description AS svc_meta
            FROM services s
            JOIN pages p ON p.slug = s.slug
            WHERE s.status = 1 AND p.status = 1
            ORDER BY (CHAR_LENGTH(p.content) - CHAR_LENGTH(s.aciklama)) DESC";
$res = $conn->query($listSql);
if (!$res) {
    fwrite(STDERR, "HATA: list query: " . $conn->error . "\n");
    exit(3);
}
$rows = [];
while ($r = $res->fetch_assoc()) { $rows[] = $r; }
$res->free();

if ($rows === []) {
    echo "Çakışma bulunamadı. Yapılacak bir şey yok.\n";
    exit(0);
}

/* ---------------- ROLLBACK ---------------- */
if ($mode === 'rollback') {
    $bkRes = $conn->query("SELECT id FROM services_backup");
    if (!$bkRes || $bkRes->num_rows === 0) {
        echo "Backup tablosu boş — rollback yok.\n";
        exit(0);
    }
    $ids = [];
    while ($r = $bkRes->fetch_assoc()) { $ids[] = (int) $r['id']; }
    $bkRes->free();

    echo "services_backup'tan " . count($ids) . " satır geri yüklenecek.\n";
    $conn->begin_transaction();
    try {
        $ok = $conn->query(
            "UPDATE services s
             JOIN services_backup b ON b.id = s.id
             SET s.aciklama = b.aciklama,
                 s.meta_description = b.meta_description,
                 s.seo_title = b.seo_title,
                 s.updated_at = b.updated_at"
        );
        if (!$ok) { throw new RuntimeException('services restore: ' . $conn->error); }

        $slugs = array_column($rows, 'slug');
        $in = implode(',', array_fill(0, count($slugs), '?'));
        $types = str_repeat('s', count($slugs));
        $stmt = $conn->prepare("UPDATE pages SET status=1 WHERE slug IN ($in)");
        $stmt->bind_param($types, ...$slugs);
        if (!$stmt->execute()) { throw new RuntimeException('pages restore: ' . $stmt->error); }
        $stmt->close();

        $conn->commit();
        echo "ROLLBACK TAMAM. Backup tablosu KORUNDU (manuel silebilirsin: DROP TABLE services_backup).\n";
        exit(0);
    } catch (Throwable $e) {
        $conn->rollback();
        fwrite(STDERR, "ROLLBACK HATA: " . $e->getMessage() . "\n");
        exit(4);
    }
}

/* ---------------- DRY / APPLY ---------------- */

/* Dry-run raporu */
echo str_pad('SLUG', 35) . str_pad('SVC→', 7) . str_pad('PAGE', 7) . str_pad('META?', 7) . str_pad('TITLE?', 7) . "AKSIYON\n";
m_line();
$stats = ['update' => 0, 'archive' => 0, 'meta_copy' => 0, 'seo_title_copy' => 0];

foreach ($rows as $r) {
    $svcSeo = trim((string) ($r['svc_seo_title'] ?? ''));
    $pageSeo = trim((string) ($r['page_seo_title'] ?? ''));
    $svcMeta = trim((string) ($r['svc_meta'] ?? ''));
    $pageMeta = trim((string) ($r['page_meta'] ?? ''));

    $copyMeta = ($pageMeta !== '' && (mb_strlen($pageMeta) > mb_strlen($svcMeta) || $svcMeta === ''));
    $copyTitle = ($svcSeo === '' && $pageSeo !== '');
    if ($copyMeta) { $stats['meta_copy']++; }
    if ($copyTitle) { $stats['seo_title_copy']++; }
    $stats['update']++;
    $stats['archive']++;

    echo str_pad((string) $r['slug'], 35)
       . str_pad((string) $r['svc_len'], 7)
       . str_pad((string) $r['page_len'], 7)
       . str_pad($copyMeta ? 'copy' : '—', 7)
       . str_pad($copyTitle ? 'copy' : '—', 7)
       . "services.aciklama ← pages.content + pages.status=0\n";
}

m_line();
echo "TOPLAM: " . count($rows) . " hizmet sayfası etkilenecek\n";
echo "  - services.aciklama güncelleme : {$stats['update']}\n";
echo "  - meta_description kopyalama   : {$stats['meta_copy']}\n";
echo "  - seo_title kopyalama           : {$stats['seo_title_copy']}\n";
echo "  - pages.status=0 (arşiv)        : {$stats['archive']}\n";
echo "\n";

if ($mode === 'dry') {
    echo "[DRY-RUN] Hiçbir şey değiştirilmedi.\n";
    echo "Uygulamak için: php scripts\\migrate_service_content_from_pages.php --apply\n";
    exit(0);
}

/* APPLY: tek transaction */
$conn->begin_transaction();
try {
    foreach ($rows as $r) {
        $slug = (string) $r['slug'];
        $svcId = (int) $r['svc_id'];
        $pageId = (int) $r['page_id'];
        $pageContent = (string) $r['page_content'];
        $pageMeta = (string) ($r['page_meta'] ?? '');
        $pageSeo = (string) ($r['page_seo_title'] ?? '');
        $svcSeo = trim((string) ($r['svc_seo_title'] ?? ''));
        $svcMeta = trim((string) ($r['svc_meta'] ?? ''));

        /* a) Backup: aynı id varsa üzerine yaz (idempotent) */
        $stmt = $conn->prepare("REPLACE INTO services_backup SELECT * FROM services WHERE id = ?");
        $stmt->bind_param('i', $svcId);
        if (!$stmt->execute()) { throw new RuntimeException("backup failed for id=$svcId: " . $stmt->error); }
        $stmt->close();

        /* b) UPDATE services */
        $setAciklama = $pageContent;
        $setMeta = ($pageMeta !== '' && (mb_strlen(trim($pageMeta)) > mb_strlen($svcMeta) || $svcMeta === '')) ? $pageMeta : $svcMeta;
        $setSeoTitle = ($svcSeo === '' && $pageSeo !== '') ? $pageSeo : $svcSeo;

        $stmt = $conn->prepare("UPDATE services SET aciklama = ?, meta_description = ?, seo_title = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('sssi', $setAciklama, $setMeta, $setSeoTitle, $svcId);
        if (!$stmt->execute()) { throw new RuntimeException("services update failed for id=$svcId: " . $stmt->error); }
        $stmt->close();

        /* c) pages.status = 0 */
        $stmt = $conn->prepare("UPDATE pages SET status = 0 WHERE id = ?");
        $stmt->bind_param('i', $pageId);
        if (!$stmt->execute()) { throw new RuntimeException("pages archive failed for id=$pageId: " . $stmt->error); }
        $stmt->close();

        echo "  ✓ $slug\n";
    }
    $conn->commit();
    echo "\nAPPLY TAMAM. Backup tablosu: services_backup (rollback için saklı).\n";
    echo "Rollback: php scripts\\migrate_service_content_from_pages.php --rollback\n";
} catch (Throwable $e) {
    $conn->rollback();
    fwrite(STDERR, "APPLY HATA (rollback edildi): " . $e->getMessage() . "\n");
    exit(5);
}
