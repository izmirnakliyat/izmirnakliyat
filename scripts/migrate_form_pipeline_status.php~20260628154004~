<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit("CLI only.\n"); }
require __DIR__ . '/../config/db.php';
echo "Once:\n";
$r = $conn->query("SELECT status, COUNT(*) c FROM form_submissions GROUP BY status");
while ($x = $r->fetch_assoc()) { echo "  status=" . $x['status'] . " -> " . $x['c'] . " kayit\n"; }
$conn->query("UPDATE form_submissions SET status = 5 WHERE status = 2");
echo "Etkilenen: " . $conn->affected_rows . "\n";
echo "Sonra:\n";
$r = $conn->query("SELECT status, COUNT(*) c FROM form_submissions GROUP BY status");
while ($x = $r->fetch_assoc()) { echo "  status=" . $x['status'] . " -> " . $x['c'] . " kayit\n"; }
echo "OK\n";
