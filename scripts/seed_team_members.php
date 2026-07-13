<?php
/**
 * Eski örnek ekip profillerini yayından kaldırır.
 * Ekip üyeleri yalnızca gerçek ve doğrulanabilir bilgilerle admin panelinden eklenmelidir.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}
require __DIR__ . '/../config/db.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    throw new RuntimeException('Veritabanı bağlantısı kurulamadı.');
}

$names = [
    'Murat Yildiz',
    'Mehmet Celik',
    'Ayse Demir',
    'Huseyin Aksoy',
    'Emre Sahin',
    'Selin Kaya',
];
$stmt = $conn->prepare('UPDATE team_members SET durum = 0 WHERE ad IN (?, ?, ?, ?, ?, ?)');
if (!($stmt instanceof mysqli_stmt)) {
    fwrite(STDERR, "team_members güncelleme sorgusu hazırlanamadı.\n");
    exit(1);
}
[$name1, $name2, $name3, $name4, $name5, $name6] = $names;
$stmt->bind_param('ssssss', $name1, $name2, $name3, $name4, $name5, $name6);
$stmt->execute();
echo 'Yayından kaldırılan eski örnek ekip profili: ' . $stmt->affected_rows . "\n";
$stmt->close();
