<?php
/**
 * case_studies tablosunu oluşturur ve geçmiş örnek kayıtları yayından kaldırır.
 * Müşteri hikâyeleri yalnızca gerçek taşıma kaydı ve doğrulanabilir müşteri
 * beyanı admin üzerinden sağlandığında yayınlanmalıdır.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}
require __DIR__ . '/../config/db.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    throw new RuntimeException('Veritabanı bağlantısı kurulamadı.');
}

$exists = $conn->query("SHOW TABLES LIKE 'case_studies'");
if (!$exists || $exists->num_rows === 0) {
    $sql = "CREATE TABLE case_studies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(190) NOT NULL,
        baslik VARCHAR(200) NOT NULL,
        ozet VARCHAR(300) NULL,
        icerik MEDIUMTEXT NULL,
        musteri_ad VARCHAR(120) NULL,
        musteri_yorumu TEXT NULL,
        puan DECIMAL(2,1) NULL DEFAULT NULL,
        kalkis_il VARCHAR(60) NULL,
        varis_il VARCHAR(60) NULL,
        ev_tipi VARCHAR(40) NULL,
        tasima_tarihi DATE NULL,
        fiyat_araligi VARCHAR(60) NULL,
        gorsel VARCHAR(255) NULL,
        meta_title VARCHAR(180) NULL,
        meta_description VARCHAR(255) NULL,
        status TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_slug (slug),
        KEY idx_status_created (status, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (!$conn->query($sql)) {
        fwrite(STDERR, "HATA: " . $conn->error . "\n");
        exit(1);
    }
    echo "OK: case_studies tablosu oluşturuldu.\n";
} else {
    $conn->query('ALTER TABLE case_studies MODIFY puan DECIMAL(2,1) NULL DEFAULT NULL');
}

$legacySampleSlugs = [
    'izmir-bornova-istanbul-kadikoy-3-1-tasima',
    'karsiyaka-buca-2-1-asansorlu-tasima',
    'konak-ofis-tasima-25-personel',
];
[$slug1, $slug2, $slug3] = $legacySampleSlugs;
$stmt = $conn->prepare('UPDATE case_studies SET status = 0 WHERE slug IN (?, ?, ?)');
if ($stmt instanceof mysqli_stmt) {
    $stmt->bind_param('sss', $slug1, $slug2, $slug3);
    $stmt->execute();
    echo 'Yayından kaldırılan eski örnek kayıt: ' . $stmt->affected_rows . "\n";
    $stmt->close();
}

echo "Tamamlandı. Gerçek ve doğrulanabilir müşteri hikâyeleri admin panelinden taslak olarak eklenmelidir.\n";
