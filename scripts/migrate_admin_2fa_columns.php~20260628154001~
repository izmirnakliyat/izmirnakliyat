<?php
/**
 * Madde 4: admin_users tablosuna 2FA (TOTP) sutunlari ekler.
 * Idempotent — tekrar calistirilirsa "kolon zaten var" diye atlar.
 *
 * Eklenen sutunlar:
 *   - totp_secret      VARCHAR(64)  NULL  — Base32 secret (16-char min)
 *   - totp_enabled     TINYINT(1)   NOT NULL DEFAULT 0
 *   - totp_verified_at DATETIME     NULL  — ilk dogrulama tarihi
 *   - totp_backup_codes MEDIUMTEXT  NULL  — JSON: ["xxxx-xxxx", ...] hash'lenmis
 */

declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit("CLI only.\n"); }
require __DIR__ . '/../config/db.php';

$columns = [
    'totp_secret'       => "VARCHAR(64) NULL DEFAULT NULL",
    'totp_enabled'      => "TINYINT(1) NOT NULL DEFAULT 0",
    'totp_verified_at'  => "DATETIME NULL DEFAULT NULL",
    'totp_backup_codes' => "MEDIUMTEXT NULL DEFAULT NULL",
];

foreach ($columns as $name => $ddl) {
    $check = $conn->query("SHOW COLUMNS FROM admin_users LIKE '" . $conn->real_escape_string($name) . "'");
    if ($check && $check->num_rows > 0) {
        echo "skip: $name (zaten var)\n";
        continue;
    }
    $sql = "ALTER TABLE admin_users ADD COLUMN $name $ddl";
    if ($conn->query($sql)) {
        echo "OK: $name eklendi\n";
    } else {
        echo "HATA: $name -> " . $conn->error . "\n";
    }
}
echo "\nMevcut admin_users sutunlari:\n";
$r = $conn->query("SHOW COLUMNS FROM admin_users");
while ($x = $r->fetch_assoc()) {
    echo "  " . $x['Field'] . " | " . $x['Type'] . "\n";
}
