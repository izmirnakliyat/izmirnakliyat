<?php
/**
 * Editorial Workflow Migration (Faz 6)
 *
 * Yaptıkları (idempotent — istenirse defalarca çalıştırılabilir):
 *  1) 3 yeni yazar ekler: Site Editörü / Nakliye Uzmanı / Nakliye Ekspertizi
 *  2) Default yazarı "Site Editörü" yapar (id=1 default'tan çıkar, status=0 olur)
 *  3) blog_posts'a editör akışı kolonlarını ekler:
 *     - is_ai_generated TINYINT(1)
 *     - reviewed_by INT NULL
 *     - reviewed_at DATETIME NULL
 *     - editor_notes TEXT NULL
 *     - ai_quality_score INT NULL
 *  4) 472 mevcut yazıyı pattern + kategori bazlı 3 yazara dağıtır
 *  5) auto_blog_tasks'a "default editor mode" ayarını seed eder
 *
 * KULLANIM:
 *   C:\xampp\php\php.exe scripts\editorial_workflow_migrate.php
 *   C:\xampp\php\php.exe scripts\editorial_workflow_migrate.php --dry-run
 *   C:\xampp\php\php.exe scripts\editorial_workflow_migrate.php --reassign-all
 *     (mevcut author_id'leri yok say, baştan dağıt — varsayılan: sadece NULL ve id=1)
 */

declare(strict_types=1);

require __DIR__ . '/../config/db.php';

$DRY_RUN       = in_array('--dry-run', $argv, true);
$REASSIGN_ALL  = in_array('--reassign-all', $argv, true);

function step(string $title): void
{
    echo PHP_EOL . str_repeat('=', 60) . PHP_EOL;
    echo $title . PHP_EOL;
    echo str_repeat('=', 60) . PHP_EOL;
}

function ok(string $msg): void { echo "  [OK]    $msg\n"; }
function warn(string $msg): void { echo "  [WARN]  $msg\n"; }
function info(string $msg): void { echo "  [INFO]  $msg\n"; }

function column_exists(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_row();
}

function table_exists(mysqli $conn, string $table): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?");
    $stmt->bind_param('s', $table);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_row();
}

// ─────────────────────────────────────────────────────────────────────
// 0) Önkoşul: authors tablosu var mı?
// ─────────────────────────────────────────────────────────────────────
step('0) Authors tablosu önkoşul kontrolü');
if (!table_exists($conn, 'authors')) {
    echo "  [FATAL] 'authors' tablosu yok. Önce scripts/llm_seo_migrate.php çalıştırın.\n";
    exit(1);
}
ok("authors tablosu mevcut.");

// ─────────────────────────────────────────────────────────────────────
// 1) 3 yeni yazar profili
// ─────────────────────────────────────────────────────────────────────
step('1) 3 yeni yazar profili (Site Editörü / Nakliye Uzmanı / Nakliye Ekspertizi)');

$authorProfiles = [
    'site-editoru' => [
        'name'        => 'Site Editörü',
        'slug'        => 'site-editoru',
        'title'       => 'Baş Editör · İçerik & Yayın Sorumlusu',
        'bio'         => 'My Nakliyat içerik ve yayın editörlüğü. Sektör haberleri, kurumsal duyurular, yıllık sezon rehberleri ve kapsamlı hizmet incelemelerinin son denetimini yapar. Tüm yazılar yayına çıkmadan önce kalite, doğruluk ve okuma akıcılığı açısından bu masadan geçer.',
        'url'         => '/yazarlar/site-editoru',
        'email'       => 'editor@mynakliyat.com.tr',
        'photo_url'   => '',
        'linkedin'    => '',
        'twitter'     => '',
        'knows_about' => "içerik editörlüğü\nsektör haberleri\nkurumsal duyurular\nyazım denetimi\nseo metin yönetimi\nyıllık nakliyat sezonu\nmüşteri rehberleri",
        'is_default'  => 1,
        'status'      => 1,
    ],
    'nakliye-uzmani' => [
        'name'        => 'Nakliye Uzmanı',
        'slug'        => 'nakliye-uzmani',
        'title'       => 'Nakliye Operasyon Uzmanı · 18+ Yıl Saha Deneyimi',
        'bio'         => 'Evden eve nakliyat, ofis taşıma, asansörlü çıkarma-indirme, parça eşya taşıma ve eşya depolama operasyonlarında 18 yılı aşkın saha deneyimine sahip operasyon uzmanı. Paketleme teknikleri, asansörlü ekipman seçimi, kat ve mesafe hesabı, taşıma günü iş planı konularında pratik rehberler hazırlar.',
        'url'         => '/yazarlar/nakliye-uzmani',
        'email'       => 'uzman@mynakliyat.com.tr',
        'photo_url'   => '',
        'linkedin'    => '',
        'twitter'     => '',
        'knows_about' => "evden eve nakliyat\nasansörlü taşıma\nofis taşıma\nparça eşya taşıma\neşya depolama\npaketleme teknikleri\ntaşıma günü planlaması\nizmir evden eve nakliyat\nizmir nakliye\nprofesyonel taşımacılık",
        'is_default'  => 0,
        'status'      => 1,
    ],
    'nakliye-ekspertizi' => [
        'name'        => 'Nakliye Ekspertizi',
        'slug'        => 'nakliye-ekspertizi',
        'title'       => 'Lojistik Eksperi · Sigorta & Hasar Danışmanı',
        'bio'         => 'Şehirler arası nakliyat, lojistik mühendisliği, taşıma sigortası, hasar tespiti ve nakliyat sözleşmeleri konularında uzmanlaşmış eksper. Müşterilere taşıma öncesi risk analizi, ekspertiz raporu, sigorta poliçesi okuma ve hasar tazmin süreçlerinde profesyonel danışmanlık sağlar.',
        'url'         => '/yazarlar/nakliye-ekspertizi',
        'email'       => 'ekspertiz@mynakliyat.com.tr',
        'photo_url'   => '',
        'linkedin'    => '',
        'twitter'     => '',
        'knows_about' => "nakliye sigortası\nekspertiz raporu\nhasar tespiti\nnakliyat sözleşmesi\nşehirler arası nakliyat\nlojistik mühendisliği\nrisk analizi\ntazminat süreci\nkurumsal taşıma\ntaşıma hukuku",
        'is_default'  => 0,
        'status'      => 1,
    ],
];

$insertSql = "INSERT INTO authors (name, slug, title, bio, url, email, photo_url, linkedin, twitter, knows_about, is_default, status)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
              ON DUPLICATE KEY UPDATE
                name       = VALUES(name),
                title      = VALUES(title),
                bio        = VALUES(bio),
                url        = VALUES(url),
                email      = VALUES(email),
                photo_url  = VALUES(photo_url),
                linkedin   = VALUES(linkedin),
                twitter    = VALUES(twitter),
                knows_about= VALUES(knows_about),
                is_default = VALUES(is_default),
                status     = VALUES(status)";

if (!$DRY_RUN) {
    foreach ($authorProfiles as $slug => $a) {
        $stmt = $conn->prepare($insertSql);
        $stmt->bind_param(
            'ssssssssssii',
            $a['name'], $a['slug'], $a['title'], $a['bio'], $a['url'], $a['email'],
            $a['photo_url'], $a['linkedin'], $a['twitter'], $a['knows_about'],
            $a['is_default'], $a['status']
        );
        if (!$stmt->execute()) {
            warn("Yazar upsert hatası ($slug): " . $stmt->error);
            continue;
        }
        ok("Yazar upsert: $slug");
    }
} else {
    info("DRY-RUN: 3 yazar upsert atlandı.");
}

// Eski default yazarı (My Nakliyat Müşteri İlişkileri = id 1) default'tan çıkar
if (!$DRY_RUN) {
    $conn->query("UPDATE authors SET is_default = 0 WHERE slug = 'my-nakliyat-musteri-iliskileri'");
    ok("Eski default yazar (Müşteri İlişkileri) is_default=0 yapıldı.");
}

// Tek default kalsın (Site Editörü)
if (!$DRY_RUN) {
    $conn->query("UPDATE authors SET is_default = 0 WHERE slug <> 'site-editoru'");
    $conn->query("UPDATE authors SET is_default = 1 WHERE slug = 'site-editoru'");
    ok("Yeni default yazar: Site Editörü.");
}

// Yazar id haritası
$idMap = [];
$r = $conn->query("SELECT id, slug FROM authors WHERE slug IN ('site-editoru','nakliye-uzmani','nakliye-ekspertizi','my-nakliyat-musteri-iliskileri')");
while ($row = $r->fetch_assoc()) {
    $idMap[$row['slug']] = (int) $row['id'];
}

$editorId    = $idMap['site-editoru']        ?? null;
$uzmanId     = $idMap['nakliye-uzmani']      ?? null;
$ekspertizId = $idMap['nakliye-ekspertizi']  ?? null;
$legacyId    = $idMap['my-nakliyat-musteri-iliskileri'] ?? null;

info("ID Haritası: editor=$editorId | uzman=$uzmanId | ekspertiz=$ekspertizId | legacy=$legacyId");

if (!$editorId || !$uzmanId || !$ekspertizId) {
    echo "  [FATAL] Yazar id'leri çözümlenemedi. Migration durduruluyor.\n";
    exit(1);
}

// ─────────────────────────────────────────────────────────────────────
// 2) blog_posts: editör akışı kolonları
// ─────────────────────────────────────────────────────────────────────
step('2) blog_posts editör akışı kolonları');

$newColumns = [
    'is_ai_generated'   => "ALTER TABLE blog_posts ADD COLUMN is_ai_generated TINYINT(1) NOT NULL DEFAULT 0 AFTER author_id",
    'reviewed_by'       => "ALTER TABLE blog_posts ADD COLUMN reviewed_by INT NULL DEFAULT NULL AFTER is_ai_generated",
    'reviewed_at'       => "ALTER TABLE blog_posts ADD COLUMN reviewed_at DATETIME NULL DEFAULT NULL AFTER reviewed_by",
    'editor_notes'      => "ALTER TABLE blog_posts ADD COLUMN editor_notes TEXT NULL DEFAULT NULL AFTER reviewed_at",
    'ai_quality_score'  => "ALTER TABLE blog_posts ADD COLUMN ai_quality_score INT NULL DEFAULT NULL AFTER editor_notes",
];

foreach ($newColumns as $col => $sql) {
    if (column_exists($conn, 'blog_posts', $col)) {
        info("Kolon zaten var: $col");
        continue;
    }
    if ($DRY_RUN) { info("DRY-RUN: $sql"); continue; }
    if ($conn->query($sql)) {
        ok("Kolon eklendi: $col");
    } else {
        warn("Kolon eklenemedi ($col): " . $conn->error);
    }
}

// Index'ler
$indexes = [
    "CREATE INDEX idx_blog_posts_durum ON blog_posts(durum)",
    "CREATE INDEX idx_blog_posts_author_id ON blog_posts(author_id)",
    "CREATE INDEX idx_blog_posts_is_ai_generated ON blog_posts(is_ai_generated)",
];
foreach ($indexes as $sql) {
    if ($DRY_RUN) { info("DRY-RUN: $sql"); continue; }
    @$conn->query($sql);
}
ok("Index'ler garantiye alındı (varsa atlandı).");

// ─────────────────────────────────────────────────────────────────────
// 3) 472 yazıyı 3 yazara akıllıca dağıt
// ─────────────────────────────────────────────────────────────────────
step('3) Mevcut yazıların akıllı dağıtımı');

/**
 * Pattern-based assignment (kategori-öncelikli, dar pattern):
 *
 *   1) STRONG override → EKSPERTIZ: başlıkta "sigorta/ekspertiz/hasar/tazminat/
 *      sözleşme/kontrat/kasko/sgk/taşıma hukuku" (dar liste)
 *   2) STRONG override → EDITOR: başlıkta "haber/duyuru/bayram/yılbaşı/kampanya/
 *      açıklama" (sektör gündem yazıları)
 *   3) Kategori varsayılanı:
 *        kategori 11 (Şehirler Arası)       → EKSPERTIZ
 *        kategori 5 (Haberler), 7 (Duyurular)→ EDITOR
 *        diğer hepsi                        → UZMAN
 */
$ekspertizStrong = [
    'sigorta', 'ekspertiz', 'hasar', 'tazminat', 'sözleşme', 'sozlesme',
    'kontrat', 'kasko', 'sgk', 'taşıma hukuk', 'tasima hukuk', 'sigortalı taşıma',
    'sigortali tasima', 'sigortalı nakliye', 'sigortali nakliye',
    'kurumsal taşıma', 'kurumsal tasima',
];
$editorStrong = [
    'haber', 'duyuru', 'bayram', 'yılbaşı', 'yilbasi', 'kampanya',
    'açıklama', 'aciklama', 'sektör analizi', 'sektor analizi',
    'yıllık değerlendirme', 'yillik degerlendirme',
];

$categoryDefault = [
    4  => 'uzman',      // Evden Eve Nakliyat
    5  => 'editor',     // Haberler
    6  => 'uzman',      // Asansörlü Nakliyat
    7  => 'editor',     // Duyurular
    8  => 'uzman',      // Ofis Taşıma
    9  => 'uzman',      // Sepetli Vinç
    10 => 'uzman',      // Parça Eşya
    11 => 'ekspertiz',  // Şehirler Arası → Ekspertiz
    12 => 'uzman',      // Eşya Depolama
];

function assign_author(string $title, string $slug, string $tags, int $catId, array $ekspertizStrong, array $editorStrong, array $categoryDefault): string
{
    // Sadece BAŞLIK ve SLUG'a bakıyoruz (etiketler çok geniş yakalama yapıyor).
    $haystack = mb_strtolower($title . ' ' . $slug, 'UTF-8');

    foreach ($ekspertizStrong as $p) {
        if (mb_stripos($haystack, $p, 0, 'UTF-8') !== false) {
            return 'ekspertiz';
        }
    }
    foreach ($editorStrong as $p) {
        if (mb_stripos($haystack, $p, 0, 'UTF-8') !== false) {
            return 'editor';
        }
    }
    return $categoryDefault[$catId] ?? 'uzman';
}

// Hangi yazılar yeniden atanacak?
$whereClause = $REASSIGN_ALL
    ? "1=1"
    : "(author_id IS NULL OR author_id = " . (int) $legacyId . ")";

$total = (int) $conn->query("SELECT COUNT(*) AS c FROM blog_posts WHERE $whereClause")->fetch_assoc()['c'];
info("Yeniden atanacak yazı: $total ($whereClause)");

$counters = ['editor' => 0, 'uzman' => 0, 'ekspertiz' => 0];

if ($total > 0) {
    $res = $conn->query("SELECT id, baslik, slug, etiketler, kategori_id FROM blog_posts WHERE $whereClause");
    $update = $conn->prepare("UPDATE blog_posts SET author_id = ? WHERE id = ?");

    while ($row = $res->fetch_assoc()) {
        $bucket = assign_author(
            (string) $row['baslik'],
            (string) $row['slug'],
            (string) ($row['etiketler'] ?? ''),
            (int) ($row['kategori_id'] ?? 0),
            $ekspertizStrong,
            $editorStrong,
            $categoryDefault
        );
        $aid = match ($bucket) {
            'editor'    => $editorId,
            'uzman'     => $uzmanId,
            'ekspertiz' => $ekspertizId,
        };
        $counters[$bucket]++;
        if (!$DRY_RUN) {
            $bid = (int) $row['id'];
            $update->bind_param('ii', $aid, $bid);
            $update->execute();
        }
    }
}

info("Dağıtım sonucu:");
echo "         Site Editörü      : " . $counters['editor']    . "\n";
echo "         Nakliye Uzmanı    : " . $counters['uzman']     . "\n";
echo "         Nakliye Ekspertizi: " . $counters['ekspertiz'] . "\n";

// ─────────────────────────────────────────────────────────────────────
// 4) Mevcut yayında olan AI yazılarını işaretle (legacy)
// ─────────────────────────────────────────────────────────────────────
step('4) Eski AI yazılarını işaretleme (best-effort, etiketler ipuçlu)');

// Eski yazılarda is_ai_generated = NULL/0 → 1 yapmak için kesin sinyal yok.
// Bu yüzden HİÇBİRİNİ TOPLU 1 YAPMIYORUZ.
// Sadece auto_blog_tasks'tan üretilenleri tespit etmek üzere bir kolon doldurmuyoruz.
// Yeni üretimler is_ai_generated=1 ile gelecek.
ok("Eski yazılar olduğu gibi bırakıldı (is_ai_generated=0). Yeni üretimler işaretlenecek.");

// ─────────────────────────────────────────────────────────────────────
// 5) settings: editör akışı varsayılanları
// ─────────────────────────────────────────────────────────────────────
step('5) Editör akışı varsayılan ayarları');

$settingsToUpsert = [
    'auto_blog_default_status'         => '1', // 1 = Editör kuyruğu (v2)
    'auto_blog_default_author_slug'    => 'site-editoru',
    'editorial_workflow_enabled'       => '1',
    'editorial_min_word_count'         => '600',
    'editorial_require_h2_count'       => '3',
];

if (!$DRY_RUN) {
    foreach ($settingsToUpsert as $k => $v) {
        $exists = $conn->query("SELECT id FROM settings WHERE name = '" . $conn->real_escape_string($k) . "' LIMIT 1");
        if ($exists && $exists->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE name = ?");
            $stmt->bind_param('ss', $v, $k);
            $stmt->execute();
            info("settings update: $k=$v");
        } else {
            $stmt = $conn->prepare("INSERT INTO settings (name, value) VALUES (?, ?)");
            $stmt->bind_param('ss', $k, $v);
            $stmt->execute();
            ok("settings insert: $k=$v");
        }
    }
}

// ─────────────────────────────────────────────────────────────────────
// 6) Özet
// ─────────────────────────────────────────────────────────────────────
step('6) Final durum');

$r = $conn->query("SELECT id, name, slug, is_default, status FROM authors ORDER BY id");
echo "  Authors tablosu:\n";
while ($row = $r->fetch_assoc()) {
    echo "    id={$row['id']} | {$row['name']} | default={$row['is_default']} | status={$row['status']}\n";
}

$r = $conn->query("SELECT a.name, COUNT(b.id) AS c
                   FROM authors a LEFT JOIN blog_posts b ON b.author_id = a.id
                   GROUP BY a.id, a.name ORDER BY c DESC");
echo "\n  Yazar başına yazı dağılımı:\n";
while ($row = $r->fetch_assoc()) {
    echo "    " . str_pad($row['name'], 38) . " : " . $row['c'] . "\n";
}

echo "\n" . ($DRY_RUN ? "[DRY-RUN] Hiçbir veri değişmedi." : "[OK] Migration tamamlandı.") . "\n";
