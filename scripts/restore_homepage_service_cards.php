<?php
/**
 * Restore "İzmir Evden Eve Nakliyat" + "Kurumsal Nakliye & Ofis Taşıma" anasayfa kartlari.
 *
 * Madde 6-7 (canonical_slug_consolidate) iki services kaydını status=0'a düsürmüştü:
 *   - id=1 izmir-evden-eve-nakliyat-hizmeti
 *   - id=2 kurumsal-nakliye-ofis-tasima
 *
 * Bu islem dogru bir SEO tedbirdi (duplicate slug kapatma); ancak musteri anasayfada
 * bu iki kartin gorunmesini istiyor. Cozum:
 *   1) status=1 yap (kart anasayfada gozuksun)
 *   2) link kolonunu CANONICAL URL'e bagla (kart tiklandiginda 0-hop canonical sayfaya gider)
 *   3) sitemap_build.php'de bu sluglari exclude et (Google'a hala kapali, consolidation korunur)
 *   4) .htaccess 301 zaten direkt URL girisini canonical'a yonlendiriyor (cift guvence)
 *
 * Idempotent. Tekrar calistirilabilir.
 *
 * Kullanim:
 *   php scripts/restore_homepage_service_cards.php           # dry-run
 *   php scripts/restore_homepage_service_cards.php --apply   # uygula
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { exit("CLI only.\n"); }

require __DIR__ . '/../config/db.php';
/** @var mysqli $conn */

$apply = in_array('--apply', $argv ?? [], true);

$cards = [
    [
        'id'           => 1,
        'slug'         => 'izmir-evden-eve-nakliyat-hizmeti',
        'canonical'    => '/izmir-evden-eve-nakliyat',
        'order_number' => 0,
        'status'       => 1,
    ],
    [
        'id'           => 2,
        'slug'         => 'kurumsal-nakliye-ofis-tasima',
        'canonical'    => '/kurumsal-nakliye-hizmetleri',
        'order_number' => 1,
        'status'       => 1,
    ],
];

echo str_repeat('=', 70) . "\n";
echo "Anasayfa kart restorasyonu " . ($apply ? '(UYGULA)' : '(dry-run)') . "\n";
echo str_repeat('=', 70) . "\n\n";

$changed = 0;

foreach ($cards as $c) {
    $stmt = $conn->prepare("SELECT id, slug, status, order_number, link, ana_baslik FROM services WHERE id = ?");
    $stmt->bind_param('i', $c['id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo "  [SKIP] id={$c['id']} bulunamadi.\n";
        continue;
    }

    echo "  ID={$c['id']}  slug={$c['slug']}  baslik={$row['ana_baslik']}\n";
    echo "    Mevcut: status={$row['status']}  order={$row['order_number']}  link='{$row['link']}'\n";
    echo "    Hedef : status={$c['status']}  order={$c['order_number']}  link='{$c['canonical']}'\n";

    $needsUpdate = ((int) $row['status'] !== $c['status'])
        || ((int) $row['order_number'] !== $c['order_number'])
        || ((string) $row['link'] !== $c['canonical']);

    if (!$needsUpdate) {
        echo "    [OK] Zaten istenen durumda, atlandi.\n\n";
        continue;
    }

    if ($apply) {
        $u = $conn->prepare("UPDATE services SET status = ?, order_number = ?, link = ?, updated_at = NOW() WHERE id = ?");
        $u->bind_param('iisi', $c['status'], $c['order_number'], $c['canonical'], $c['id']);
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

echo str_repeat('-', 70) . "\n";
echo ($apply ? 'Toplam guncellenen: ' : 'Guncellenecek: ') . $changed . "\n";
if (!$apply && $changed > 0) {
    echo "Uygulamak icin: php scripts/restore_homepage_service_cards.php --apply\n";
}
