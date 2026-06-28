<?php
/**
 * "My Nakliyat" -> "MY Nakliyat" yazim tutarlilik scripti.
 * Settings tablosundaki site adi alanlari + about + page/blog metinlerini guncelleler.
 *
 * Kapsam:
 *   - settings tablosu (site_title, site_name_suffix, copyright_text, short_description, vs.)
 *   - about tablosu (varsa)
 *   - pages.content / pages.title / pages.meta_description
 *   - blog_posts.content / .baslik / .meta_description
 *   - services.aciklama / .icerik / .ana_baslik / .meta_description
 *
 * Idempotent. Tek "My Nakliyat" -> "MY Nakliyat".
 *
 * Usage:
 *   php scripts/normalize_brand_capitalization.php           # dry-run (preview)
 *   php scripts/normalize_brand_capitalization.php --apply   # uygula
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit("CLI only.\n");

require __DIR__ . '/../config/db.php';
/** @var mysqli $conn */

$apply = in_array('--apply', $argv ?? [], true);

echo str_repeat('=', 70) . "\n";
echo "Marka yazim tutarliligi: 'My Nakliyat' -> 'MY Nakliyat' " . ($apply ? '(UYGULA)' : '(dry-run)') . "\n";
echo str_repeat('=', 70) . "\n\n";

/**
 * Hedef tablolar/kolonlar. Her biri bagimsiz, idempotent UPDATE.
 *
 * @var array<int, array{table:string, cols:list<string>, where_extra?:string}>
 */
$targets = [
    ['table' => 'settings',    'cols' => ['value']],
    ['table' => 'about',       'cols' => ['baslik', 'aciklama']],
    ['table' => 'pages',       'cols' => ['title', 'content', 'meta_description', 'meta_keywords']],
    ['table' => 'blog_posts',  'cols' => ['baslik', 'icerik', 'meta_description', 'meta_keywords', 'ozet']],
    ['table' => 'services',    'cols' => ['ana_baslik', 'aciklama', 'icerik', 'meta_description', 'meta_keywords']],
    ['table' => 'sections',    'cols' => ['main_heading', 'sub_heading', 'description']],
];

$totalChanged = 0;

foreach ($targets as $t) {
    $tbl = $t['table'];

    // Tablo var mi?
    $r = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($tbl) . "'");
    if (!$r || $r->num_rows === 0) {
        echo "  [SKIP] tablo yok: $tbl\n";
        continue;
    }

    foreach ($t['cols'] as $col) {
        // Kolon var mi?
        $rc = $conn->query("SHOW COLUMNS FROM `$tbl` LIKE '" . $conn->real_escape_string($col) . "'");
        if (!$rc || $rc->num_rows === 0) {
            // sessizce gec — semantik farkli kurulumlar olabilir
            continue;
        }

        // Etkilenecek satir sayisini onizle.
        // BINARY operator ile case-sensitive arar; "MY Nakliyat" gecen yerler tetiklemez.
        $sqlCount = "SELECT COUNT(*) AS c FROM `$tbl`
                     WHERE BINARY `$col` LIKE '%My Nakliyat%'
                       AND BINARY `$col` NOT LIKE '%MY Nakliyat%'";
        $rs = $conn->query($sqlCount);
        if (!$rs) {
            echo "  [WARN] $tbl.$col preview hata: " . $conn->error . "\n";
            continue;
        }
        $cnt = (int) ($rs->fetch_assoc()['c'] ?? 0);

        if ($cnt === 0) {
            // tamamen sessiz; net rapor kalabaligini engelle
            continue;
        }

        echo "  $tbl.$col -> $cnt satir etkilenir\n";

        if ($apply) {
            // REPLACE'in BINARY versiyonu yok; ama LIKE BINARY ile filtreledigimiz icin
            // mysql REPLACE varsayilan utf8mb4_general_ci ile dahi sadece "My Nakliyat" -> "MY Nakliyat" replaces.
            // Onemli: REPLACE case-sensitive (8.0+) — eski "MY Nakliyat" tekrar dokunulmaz, idempotent.
            $sqlUpd = "UPDATE `$tbl`
                       SET `$col` = REPLACE(`$col`, 'My Nakliyat', 'MY Nakliyat')
                       WHERE BINARY `$col` LIKE '%My Nakliyat%'
                         AND BINARY `$col` NOT LIKE '%MY Nakliyat%'";
            $ok = $conn->query($sqlUpd);
            if (!$ok) {
                echo "    [ERR] $tbl.$col: " . $conn->error . "\n";
                continue;
            }
            $aff = $conn->affected_rows;
            echo "    [APPLIED] $aff satir guncellendi.\n";
            $totalChanged += $aff;
        }
    }
}

echo "\n" . str_repeat('-', 70) . "\n";
echo ($apply ? "Toplam guncellenen satir: $totalChanged" : "Etkilenecek satir sayisi yukarida.") . "\n";
if (!$apply) {
    echo "Uygulamak icin: php scripts/normalize_brand_capitalization.php --apply\n";
}
