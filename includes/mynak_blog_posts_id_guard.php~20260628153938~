<?php
declare(strict_types=1);

/**
 * blog_posts.id <= 0 satırları PHP'de empty($id) === true üretir; detay sayfası ve önceki/sonraki sorguları kırılır.
 * İçe aktarma / SQL hatası sonrası oluşabilir. Bu modül id'yi MAX+1 atayıp AUTO_INCREMENT hizalar.
 *
 * Ön yüz ve CLI'da db bağlantısından sonra otomatik çalışır; /admin/ isteklerinde çalışmaz (blog_edit.php?id=0
 * gibi adreslerin aynı istekte bozulmaması için). Tamamen kapatmak için config/db.php öncesi:
 * define('MYNAK_SKIP_BLOG_POSTS_ID_GUARD', true);
 */
function mynak_blog_posts_id_guard_should_run(): bool
{
    if (defined('MYNAK_SKIP_BLOG_POSTS_ID_GUARD') && MYNAK_SKIP_BLOG_POSTS_ID_GUARD) {
        return false;
    }
    if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
        return true;
    }
    $sn = str_replace('\\', '/', strtolower((string) ($_SERVER['SCRIPT_NAME'] ?? '')));

    return !str_contains($sn, '/admin/');
}

/**
 * Yayınlanabilir blog yazısı satırında id alanı geçerli mi? (0 geçerli bir PK olabilir; empty() kullanmayın.)
 *
 * @param array<string, mixed>|null $row
 */
function mynak_blog_post_row_has_usable_id(?array $row): bool
{
    if (!is_array($row) || !array_key_exists('id', $row)) {
        return false;
    }
    if ($row['id'] === null || $row['id'] === '') {
        return false;
    }

    return is_numeric($row['id']) && (int) $row['id'] > 0;
}

/**
 * id <= 0 satırlarını onarır ve AUTO_INCREMENT hizalar (admin INSERT sonrası da çağrılabilir).
 */
function mynak_blog_posts_id_repair_non_positive(mysqli $conn): void
{
    $tblEsc = $conn->real_escape_string('blog_posts');
    $chk = @$conn->query("SHOW TABLES LIKE '{$tblEsc}'");
    if (!$chk || $chk->num_rows === 0) {
        return;
    }

    $changed = false;
    for ($i = 0; $i < 64; $i++) {
        $res = $conn->query('SELECT id FROM blog_posts WHERE id <= 0 LIMIT 1');
        if (!$res || $res->num_rows === 0) {
            break;
        }

        $mx = $conn->query('SELECT COALESCE(MAX(id), 0) AS m FROM blog_posts');
        if (!$mx) {
            error_log('mynak_blog_posts_id_guard: MAX(id) sorgusu başarısız');
            break;
        }
        $maxRow = $mx->fetch_assoc();
        $newId = (int) ($maxRow['m'] ?? 0) + 1;
        if ($newId < 1) {
            $newId = 1;
        }

        $upd = $conn->prepare('UPDATE blog_posts SET id = ? WHERE id <= 0 LIMIT 1');
        if (!$upd) {
            error_log('mynak_blog_posts_id_guard: prepare başarısız: ' . $conn->error);
            break;
        }
        $upd->bind_param('i', $newId);
        if (!$upd->execute()) {
            error_log('mynak_blog_posts_id_guard: UPDATE başarısız: ' . $upd->error);
            $upd->close();
            break;
        }
        $upd->close();
        $changed = true;
    }

    if ($changed) {
        $mx2 = $conn->query('SELECT COALESCE(MAX(id), 0) + 1 AS n FROM blog_posts');
        if ($mx2 && ($r2 = $mx2->fetch_assoc())) {
            $next = max(1, (int) $r2['n']);
            @$conn->query('ALTER TABLE blog_posts AUTO_INCREMENT = ' . (int) $next);
        }
    }
}

function mynak_blog_posts_id_guard_run(mysqli $conn): void
{
    static $ran = false;
    if ($ran) {
        return;
    }
    $ran = true;

    if (!mynak_blog_posts_id_guard_should_run()) {
        return;
    }

    mynak_blog_posts_id_repair_non_positive($conn);
}
