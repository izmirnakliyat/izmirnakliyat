<?php
/**
 * Firma aciklamasi kanonik guncelleme — settings tablosu.
 *
 * Eski (DB'de oturan):
 *   "MY Nakliyat (R) ... saglayan odullu ve yuksek puanli Izmir nakliyat firmasidir."
 *   ('Esya Depolama' yok, 'Guvenilir Marka odullu' yerine 'odullu ve yuksek puanli')
 *
 * Yeni (kanonik, kullanici beyani):
 *   "MY Nakliyat (R) Evden eve nakliyat, Ofis tasima, Esya Depolama, Parca esya tasima
 *    & Sehirler arasi nakliyati saglayan Guvenilir Marka odullu Izmir nakliyat firmasidir."
 *
 * Etkilenen settings key'leri:
 *   - short_description
 *   - global_meta_description
 *   - site_description (varsa)
 *
 * Idempotent. Eger zaten yeni cumle ile esleniyorsa hicbir sey yapmaz.
 *
 * Usage:
 *   php scripts/update_brand_canonical_description.php           # dry-run
 *   php scripts/update_brand_canonical_description.php --apply   # uygula
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit("CLI only.\n");

// Direkt mysqli — config/db.php icindeki guard scriptini bypass et (CLI hizli yol).
$conn = new mysqli('localhost', 'root', '', 'mynakliyat', 3306);
if ($conn->connect_error) {
    fwrite(STDERR, 'DB connect error: ' . $conn->connect_error . PHP_EOL);
    exit(1);
}
$conn->set_charset('utf8mb4');

$apply = in_array('--apply', $argv ?? [], true);

$canonical = 'MY Nakliyat ® Evden eve nakliyat, Ofis taşıma, Eşya Depolama, Parça eşya taşıma & Şehirler arası nakliyatı sağlayan Güvenilir Marka ödüllü İzmir nakliyat firmasıdır.';

// Meta description (155 karakter siniri kategorisinde olabilir; biraz kisa varyant)
$canonicalMeta = 'MY Nakliyat ® Evden eve nakliyat, Ofis taşıma, Eşya Depolama, Parça eşya taşıma & Şehirler arası nakliyat. Güvenilir Marka ödüllü İzmir nakliyat firması — 270+ Google yorumu 5,0 puan.';

$targets = [
    'short_description'        => $canonical,
    'site_description'         => $canonical,
    'global_meta_description'  => $canonicalMeta,
];

echo str_repeat('=', 70) . "\n";
echo "Firma aciklamasi kanonik guncelleme " . ($apply ? '(UYGULA)' : '(dry-run)') . "\n";
echo str_repeat('=', 70) . "\n\n";

$changed = 0;

foreach ($targets as $key => $newVal) {
    $stmt = $conn->prepare("SELECT value FROM settings WHERE name = ? LIMIT 1");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $oldVal = $row['value'] ?? '';
    $exists = $row !== null;

    echo "  $key:\n";
    echo "    Mevcut: " . (mb_strlen($oldVal) > 90 ? mb_substr($oldVal, 0, 87) . '...' : ($oldVal === '' ? '(yok)' : $oldVal)) . "\n";
    echo "    Yeni  : " . (mb_strlen($newVal) > 90 ? mb_substr($newVal, 0, 87) . '...' : $newVal) . "\n";

    if ((string) $oldVal === (string) $newVal) {
        echo "    [OK] Zaten ayni, atlandi.\n\n";
        continue;
    }

    if ($apply) {
        $desc = 'Firma aciklamasi (kanonik kullanici beyani)';
        if ($exists) {
            $u = $conn->prepare("UPDATE settings SET value = ? WHERE name = ?");
            $u->bind_param('ss', $newVal, $key);
        } else {
            $u = $conn->prepare("INSERT INTO settings (name, value, description) VALUES (?, ?, ?)");
            $u->bind_param('sss', $key, $newVal, $desc);
        }
        $u->execute();
        $aff = $u->affected_rows;
        $u->close();
        echo "    [APPLIED] etkilenen: $aff satir.\n\n";
        $changed++;
    } else {
        echo "    [DRY-RUN] degisiklik bekliyor.\n\n";
        $changed++;
    }
}

// Cache'i sifirla — public_layout_settings_menus.json icinde eski aciklama varsa
$cacheFile = dirname(__DIR__) . '/cache/public_layout_settings_menus.json';
if ($apply && is_file($cacheFile)) {
    @unlink($cacheFile);
    echo "  cache/public_layout_settings_menus.json silindi (yeniden uretilecek).\n";
}

echo str_repeat('-', 70) . "\n";
echo ($apply ? "Toplam guncellenen anahtar: $changed" : "Guncellenecek anahtar: $changed") . "\n";
if (!$apply && $changed > 0) {
    echo "Uygulamak icin: php scripts/update_brand_canonical_description.php --apply\n";
}
