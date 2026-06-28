<?php
/** team_members.id AUTO_INCREMENT repair + re-seed. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
$c = new mysqli('localhost', 'root', '', 'mynakliyat', 3306);
if ($c->connect_error) { fwrite(STDERR, $c->connect_error . "\n"); exit(1); }
$c->set_charset('utf8mb4');
$c->query("DELETE FROM team_members");
$c->query("ALTER TABLE team_members MODIFY id int(11) NOT NULL AUTO_INCREMENT");
echo "OK: team_members.id is now AUTO_INCREMENT, table cleared.\n";
