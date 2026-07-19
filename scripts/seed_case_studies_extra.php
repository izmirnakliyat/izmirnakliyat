<?php
/**
 * Eski örnek müşteri hikâyelerini yayından kaldırır.
 * Yeni müşteri hikâyeleri yalnızca doğrulanabilir kayıtlarla admin panelinden eklenmelidir.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}
require __DIR__ . '/../config/db.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    throw new RuntimeException('Veritabanı bağlantısı kurulamadı.');
}

$slugs = [
    'karsiyaka-ankara-cankaya-3-1-sehirler-arasi',
    'gaziemir-istanbul-avcilar-2-1-asansorlu',
    'bornova-izmir-esya-depolama-3-ay-yeni-eve',
];
[$slug1, $slug2, $slug3] = $slugs;
$stmt = $conn->prepare('UPDATE case_studies SET status = 0 WHERE slug IN (?, ?, ?)');
if (!($stmt instanceof mysqli_stmt)) {
    fwrite(STDERR, "case_studies güncelleme sorgusu hazırlanamadı.\n");
    exit(1);
}
$stmt->bind_param('sss', $slug1, $slug2, $slug3);
$stmt->execute();
echo 'Yayından kaldırılan eski örnek kayıt: ' . $stmt->affected_rows . "\n";
$stmt->close();
