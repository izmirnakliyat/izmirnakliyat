<?php
/**
 * GBP rating sync helper.
 * Settings tablosuna google_place_rating + google_total_reviews yazar.
 * Idempotent — INSERT...ON DUPLICATE KEY UPDATE.
 *
 * Usage: php scripts/seed_gbp_settings.php [--rating=5.0 --reviews=270]
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit("CLI only.\n");

require __DIR__ . '/../config/db.php';
/** @var mysqli $conn */

$rating = '5.0';
$reviews = '270';

foreach ($argv ?? [] as $a) {
    if (preg_match('/^--rating=(.+)$/', $a, $m)) $rating = trim($m[1]);
    if (preg_match('/^--reviews=(\d+)$/', $a, $m)) $reviews = trim($m[1]);
}

echo "Hedef: rating=$rating  reviews=$reviews\n\n";

$pairs = [
    ['google_place_rating',   $rating,  'GBP yildiz ortalamasi (manuel/otomatik sync)'],
    ['google_total_reviews',  $reviews, 'GBP toplam yorum sayisi (manuel/otomatik sync)'],
];

foreach ($pairs as [$n, $v, $d]) {
    $cur = $conn->query("SELECT value FROM settings WHERE name = '" . $conn->real_escape_string($n) . "' LIMIT 1");
    $oldVal = $cur && $cur->num_rows ? $cur->fetch_assoc()['value'] : '(yok)';
    echo "  $n: $oldVal -> $v\n";

    $stmt = $conn->prepare(
        'INSERT INTO settings (name, value, description) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE value = VALUES(value)'
    );
    $stmt->bind_param('sss', $n, $v, $d);
    $stmt->execute();
    $stmt->close();
}

echo "\nSonra GBP cache'ini de senkronize ediyorum (schema'nin hizli okumasi icin)...\n";
$cacheDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'cache';
if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0775, true); }
$cachePath = $cacheDir . DIRECTORY_SEPARATOR . 'gbp_data.json';

$existing = is_readable($cachePath) ? (json_decode((string) file_get_contents($cachePath), true) ?: []) : [];

$payload = array_merge([
    'opening_hours_spec' => [],
    'weekday_text' => [],
    'place_id' => $existing['place_id'] ?? '',
    'source' => 'manual_seed',
    'http_status' => 0,
    'error' => null,
], $existing, [
    'synced_at' => time(),
    'rating' => (float) $rating,
    'user_ratings_total' => (int) $reviews,
]);
file_put_contents($cachePath, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "  cache/gbp_data.json yazildi.\n";

echo "\nOK.\n";
