<?php
declare(strict_types=1);
/**
 * CLI: hizmet, blog, ilçe açılış haritalı sayfalarda <250 kelime var mı?
 *   c:\xampp\php\php.exe scripts/word_count_report.php
 */
if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/config/config.php';
require_once $root . '/config/db.php';
require_once $root . '/includes/pipeline/ilce_unique_opening.php';

if (!isset($conn) || !$conn instanceof mysqli) {
    fwrite(STDERR, "DB yok\n");
    exit(1);
}

$minWords = 250;

$wordCount = static function (string $html): int {
    $t = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $t = preg_replace('/\s+/u', ' ', trim($t)) ?? '';
    if ($t === '') {
        return 0;
    }
    $parts = preg_split('/\s+/u', $t) ?: [];
    $n = 0;
    foreach ($parts as $p) {
        if ($p !== '') {
            $n++;
        }
    }
    return $n;
};

$ilceMap = mynak_ilce_unique_opening_map();
$ilceSlugs = array_keys($ilceMap);

// --- Hizmetler ---
$lowS = [];
$q = $conn->query("SELECT id, slug, icerik, aciklama FROM services WHERE status = 1");
if ($q) {
    while ($row = $q->fetch_assoc()) {
        $body = (isset($row['icerik']) && is_string($row['icerik']) && trim($row['icerik']) !== '')
            ? $row['icerik']
            : (string) ($row['aciklama'] ?? '');
        $w = $wordCount($body);
        if ($w < $minWords) {
            $lowS[] = ['slug' => (string) $row['slug'], 'words' => $w, 'id' => (int) $row['id']];
        }
    }
    $q->free();
}

// --- Blog ---
$lowB = [];
$q2 = $conn->query("SELECT id, slug, baslik, icerik FROM blog_posts WHERE durum = 3");
if ($q2) {
    while ($row = $q2->fetch_assoc()) {
        $w = $wordCount((string) ($row['icerik'] ?? ''));
        if ($w < $minWords) {
            $lowB[] = [
                'id' => (int) $row['id'],
                'slug' => (string) $row['slug'],
                'baslik' => (string) ($row['baslik'] ?? ''),
                'words' => $w,
            ];
        }
    }
    $q2->free();
}

// --- İlçe: DB içerik + (varsa) açılış bloğu metni ---
$lowI = [];
$place = implode(',', array_map(static fn (string $s): string => "'" . $conn->real_escape_string($s) . "'", $ilceSlugs));
$inPages = [];
if ($place !== '') {
    $q3 = $conn->query("SELECT slug, content FROM pages WHERE status = 1 AND slug IN ($place)");
    if ($q3) {
        while ($row = $q3->fetch_assoc()) {
            $inPages[(string) $row['slug']] = (string) ($row['content'] ?? '');
        }
        $q3->free();
    }
}
foreach ($ilceMap as $slug => $ledeText) {
    $dbHtml = $inPages[$slug] ?? '';
    $wDb = $wordCount($dbHtml);
    $wLede = $wordCount($ledeText);
    $wTotal = $wDb + $wLede;
    if ($wTotal < $minWords) {
        $lowI[] = [
            'slug' => $slug,
            'db_words' => $wDb,
            'lede_words' => $wLede,
            'total' => $wTotal,
        ];
    }
}

// Çıktı
echo "Eşik: < {$minWords} kelime (boşlukla ayrılmış token, HTML etiketleri hariç)\n\n";

echo "=== HİZMET (services, status=1) — icerik yoksa aciklama ===\n";
echo '250 altı: ' . count($lowS) . " sayfa\n";
foreach ($lowS as $r) {
    echo "  - {$r['words']} kelime  id={$r['id']}  /{$r['slug']}\n";
}

echo "\n=== BLOG (blog_posts, durum=3 yayında) ===\n";
echo '250 altı: ' . count($lowB) . " yazı\n";
foreach ($lowB as $r) {
    $t = $r['baslik'] !== '' ? $r['baslik'] : $r['slug'];
    echo "  - {$r['words']} kelime  id={$r['id']}  " . $t . "  ({$r['slug']})\n";
}

echo "\n=== İLÇE (ilce_unique_opening map + pages.content) ===\n";
echo "Toplam haritalı ilçe slug: " . count($ilceMap) . " — DB’de sayfa var: " . count($inPages) . "\n";
echo '250 altı (DB + açılış toplamı): ' . count($lowI) . " sayfa\n";
foreach ($lowI as $r) {
    echo "  - toplam={$r['total']} (db={$r['db_words']} + açılış={$r['lede_words']})  /{$r['slug']}\n";
}

$conn->close();
exit(0);
