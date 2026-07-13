<?php
/**
 * Firma aciklamasi kanonik guncelleme — settings tablosu.
 *
 * Kurumsal açıklamaları doğrulanabilir hizmet kapsamıyla tek kanonik metinde birleştirir.
 * Ödül, puan, kuruluş yılı veya üstünlük iddiası üretmez.
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

require __DIR__ . '/../config/db.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    throw new RuntimeException('Veritabanı bağlantısı kurulamadı.');
}

$apply = in_array('--apply', $argv ?? [], true);

$canonical = 'MY Nakliyat; evden eve nakliyat, ofis taşıma, eşya depolama, parça eşya taşıma ve şehirler arası nakliyat hizmetleri sunan İzmir merkezli taşıma firmasıdır.';

// Meta description (155 karakter siniri kategorisinde olabilir; biraz kisa varyant)
$canonicalMeta = 'MY Nakliyat; İzmir evden eve nakliyat, ofis taşıma, eşya depolama, parça eşya ve şehirler arası nakliyat hizmetleri için yazılı teklif sunar.';

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
