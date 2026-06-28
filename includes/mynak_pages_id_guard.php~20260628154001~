<?php
declare(strict_types=1);

/**
 * pages.id <= 0 satırlarını onarır (blog_posts guard ile aynı mantık).
 */
function mynak_pages_id_repair_non_positive(mysqli $conn): int
{
    $tblEsc = $conn->real_escape_string('pages');
    $chk = @$conn->query("SHOW TABLES LIKE '{$tblEsc}'");
    if (!$chk || $chk->num_rows === 0) {
        return 0;
    }

    $repaired = 0;
    for ($i = 0; $i < 64; $i++) {
        $res = $conn->query('SELECT id FROM pages WHERE id <= 0 LIMIT 1');
        if (!$res || $res->num_rows === 0) {
            break;
        }

        $mx = $conn->query('SELECT COALESCE(MAX(id), 0) AS m FROM pages WHERE id > 0');
        if (!$mx) {
            break;
        }
        $maxRow = $mx->fetch_assoc();
        $newId = (int) ($maxRow['m'] ?? 0) + 1;
        if ($newId < 1) {
            $newId = 1;
        }

        $upd = $conn->prepare('UPDATE pages SET id = ? WHERE id <= 0 LIMIT 1');
        if (!$upd) {
            break;
        }
        $upd->bind_param('i', $newId);
        if (!$upd->execute()) {
            $upd->close();
            break;
        }
        $upd->close();
        $repaired++;
    }

    if ($repaired > 0) {
        $mx2 = $conn->query('SELECT COALESCE(MAX(id), 0) + 1 AS n FROM pages');
        if ($mx2 && ($r2 = $mx2->fetch_assoc())) {
            $next = max(1, (int) $r2['n']);
            @$conn->query('ALTER TABLE pages AUTO_INCREMENT = ' . (int) $next);
        }
    }

    return $repaired;
}

function mynak_pages_resolve_insert_id(mysqli $conn, int $insertId, string $slug): int
{
    if ($insertId > 0) {
        return $insertId;
    }
    mynak_pages_id_repair_non_positive($conn);

    $stmt = $conn->prepare('SELECT id FROM pages WHERE slug = ? ORDER BY id DESC LIMIT 1');
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    return $row ? (int) $row['id'] : 0;
}
