<?php
declare(strict_types=1);
/**
 * Migration: internal_link_anchors tablosu.
 *
 * Kullanım (CLI):
 *   php scripts/install_internal_link_anchors.php
 * Web'den (admin'de giriş yapılmış oturumda):
 *   /mynakliyat/scripts/install_internal_link_anchors.php?run=1
 *
 * Tablo amacı: iç linklerde hedef slug'a göre ELLE yazılmış anchor metin
 * havuzunu tutar. `weight` rotasyon ağırlığı (yüksek = daha sık), `active=0`
 * pasifleştirir. Pool boşsa runtime mevcut nav_title/context algoritmasına düşer.
 */

$root = dirname(__DIR__);
require_once $root . '/config/config.php';
require_once $root . '/config/db.php';

$isCli = PHP_SAPI === 'cli';
if (!$isCli) {
    session_start();
    $adminOk = !empty($_SESSION['admin_id']) || !empty($_SESSION['user_id']) || !empty($_SESSION['admin']);
    if (!$adminOk) {
        http_response_code(403);
        echo 'Bu scripti çalıştırmak için admin oturumu gerekli.';
        exit;
    }
    if (($_GET['run'] ?? '') !== '1') {
        header('Content-Type: text/plain; charset=UTF-8');
        echo "Migration hazır. Çalıştırmak için ?run=1 ekleyin.\n";
        exit;
    }
    header('Content-Type: text/plain; charset=UTF-8');
}

if (!($conn instanceof mysqli)) {
    echo "HATA: \$conn (mysqli) bulunamadı.\n";
    exit(1);
}

$sql = <<<SQL
CREATE TABLE IF NOT EXISTS `internal_link_anchors` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `target_slug` VARCHAR(191) NOT NULL,
  `anchor_text` VARCHAR(255) NOT NULL,
  `weight` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `note` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_slug_active` (`target_slug`, `active`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

if (!$conn->query($sql)) {
    echo "HATA: " . $conn->error . "\n";
    exit(1);
}

echo "OK: internal_link_anchors tablosu hazır.\n";

$res = $conn->query("SELECT COUNT(*) AS c FROM internal_link_anchors");
$row = $res ? $res->fetch_assoc() : ['c' => 0];
echo "Kayıt: " . (int) ($row['c'] ?? 0) . "\n";
