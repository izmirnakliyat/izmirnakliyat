<?php
/**
 * MY Nakliyat — Ekip uyeleri seed scripti.
 * Idempotent: ayni isimde kayit varsa atlar; durum=1, order_number'a gore siralanir.
 *
 * Foto yolu placeholder olarak /uploads/team/<slug>.jpg uretir; dosya yoksa
 * sayfa.php tarafindaki kart bilesi initials fallback gosterir.
 *
 * Usage:
 *   php scripts/seed_team_members.php           # dry-run
 *   php scripts/seed_team_members.php --apply   # uygula
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit("CLI only.\n");

$apply = in_array('--apply', $argv ?? [], true);

$conn = new mysqli('localhost', 'root', '', 'mynakliyat', 3306);
if ($conn->connect_error) { fwrite(STDERR, $conn->connect_error . "\n"); exit(1); }
$conn->set_charset('utf8mb4');

// Bazı kurulumlarda `id` AUTO_INCREMENT degil; tum INSERT'ler 0 cakisir. Bir kez duzelt.
$col = $conn->query("SHOW COLUMNS FROM team_members WHERE Field = 'id' LIMIT 1");
$colRow = $col ? $col->fetch_assoc() : null;
$hasAuto = $colRow && stripos((string) ($colRow['Extra'] ?? ''), 'auto_increment') !== false;
if (!$hasAuto) {
    echo "  [UYARI] team_members.id AUTO_INCREMENT DEGIL — once calistirin:\n          php scripts/fix_team_members_autoinc.php\n          (Canli: mysqldan id sutununu AUTO_INCREMENT yapin.)\n\n";
    if ($apply) {
        echo "  [ABORT] --apply bu durumda guvenli degil. Yukaridaki fix'i uygulayin.\n";
        exit(1);
    }
}

$members = [
    [
        'ad'         => 'Murat Yildiz',
        'unvan'      => 'Kurucu & Genel Mudur',
        'aciklama'   => '20 yili askin sektor deneyimi ile MY Nakliyat in kurucusudur. Sirketin operasyonel ve stratejik liderligini ustlenir; ISO 9001 ve "Guvenilir Marka" odul surecini bizzat yonetmistir.',
        'foto'       => 'uploads/team/murat-yildiz.jpg',
        'email'      => 'info@mynakliyat.com.tr',
        'order_number' => 1,
    ],
    [
        'ad'         => 'Mehmet Celik',
        'unvan'      => 'Operasyon Muduru',
        'aciklama'   => 'Sehir ici ve sehirler arasi nakliyat operasyonlarini yonetir. Gunluk planlama, sigorta sureci ve musteri memnuniyeti uzerinde dogrudan calisir. 12 yildir MY Nakliyat ekibinde.',
        'foto'       => 'uploads/team/mehmet-celik.jpg',
        'email'      => null,
        'order_number' => 2,
    ],
    [
        'ad'         => 'Ayse Demir',
        'unvan'      => 'Musteri Iliskileri Sefi',
        'aciklama'   => 'Telefon ve WhatsApp uzerinden gelen tum tekliflerin ilk temas noktasi. Yazili teklif, sigorta acikalama ve ekspertiz randevulari konusunda uzmanlasmistir.',
        'foto'       => 'uploads/team/ayse-demir.jpg',
        'email'      => null,
        'order_number' => 3,
    ],
    [
        'ad'         => 'Huseyin Aksoy',
        'unvan'      => 'Saha Sefi (Izmir)',
        'aciklama'   => 'Izmir ici tum nakliyelerde sahada bulunan ekip lideri. 30 ilcenin tamaminda paketleme, montaj ve asansorlu tasima koordinasyonunu yurutur. 8 yillik MY ekibi uyesi.',
        'foto'       => 'uploads/team/huseyin-aksoy.jpg',
        'email'      => null,
        'order_number' => 4,
    ],
    [
        'ad'         => 'Emre Sahin',
        'unvan'      => 'Sehirler Arasi Operasyon Sorumlusu',
        'aciklama'   => 'Izmir-Istanbul, Izmir-Ankara basta olmak uzere 81 il sehirler arasi nakliyat operasyonlarini koordine eder. Arac filo planlamasi ve guzergah optimizasyonu konusunda uzmandir.',
        'foto'       => 'uploads/team/emre-sahin.jpg',
        'email'      => null,
        'order_number' => 5,
    ],
    [
        'ad'         => 'Selin Kaya',
        'unvan'      => 'Kurumsal Musteri Temsilcisi',
        'aciklama'   => 'Banka, hastane, ofis ve magaza tasinmalarinda kurumsal musterilerin tek temas noktasi. Sozlesme, fatura ve raporlama sureclerini yonetir.',
        'foto'       => 'uploads/team/selin-kaya.jpg',
        'email'      => null,
        'order_number' => 6,
    ],
];

echo str_repeat('=', 70) . "\n";
echo "MY Nakliyat ekip uyesi seed " . ($apply ? '(UYGULA)' : '(dry-run)') . "\n";
echo str_repeat('=', 70) . "\n\n";

$inserted = 0;
$skipped  = 0;

foreach ($members as $m) {
    $stmt = $conn->prepare("SELECT id FROM team_members WHERE ad = ? LIMIT 1");
    $stmt->bind_param('s', $m['ad']);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        echo "  [SKIP] {$m['ad']} (id={$existing['id']}) zaten var.\n";
        $skipped++;
        continue;
    }

    if ($apply) {
        $stmt = $conn->prepare(
            "INSERT INTO team_members (ad, unvan, aciklama, foto, email, durum, order_number) VALUES (?, ?, ?, ?, ?, 1, ?)"
        );
        $em = $m['email'];
        $ad = $m['ad'];
        $un = $m['unvan'];
        $ac = $m['aciklama'];
        $fo = $m['foto'];
        $ord = (int) $m['order_number'];
        $stmt->bind_param('sssssi', $ad, $un, $ac, $fo, $em, $ord);
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();
        echo "  [INSERTED] id={$newId} -> {$m['ad']} | {$m['unvan']}\n";
        $inserted++;
    } else {
        echo "  [DRY-RUN] would insert -> {$m['ad']} | {$m['unvan']}\n";
        $inserted++;
    }
}

echo "\n" . str_repeat('-', 70) . "\n";
echo ($apply ? "Yeni eklenen: $inserted | Atlanan: $skipped\n" : "Eklenecek: $inserted | Atlanan: $skipped\n");
if (!$apply && $inserted > 0) {
    echo "Uygulamak icin: php scripts/seed_team_members.php --apply\n";
}
