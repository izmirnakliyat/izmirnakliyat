<?php
declare(strict_types=1);

/**
 * BlogPosting.author için Person/Organization JSON-LD düğümü çözücüsü.
 *
 * Akış:
 *   1) blog.author_id varsa authors tablosundan o satırı al.
 *   2) Yoksa is_default=1 olan satırı al (varsa).
 *   3) Hâlâ yoksa settings.blog_default_author_* anahtarlarından üret.
 *   4) Yine yoksa Organization (publisher) düğümünü döndür.
 *
 * Idempotent + per-request cache.
 */

if (defined('MYNAK_SEO_AUTHOR_RESOLVER_LOADED')) {
    return;
}
define('MYNAK_SEO_AUTHOR_RESOLVER_LOADED', true);

/**
 * Authors tablosundan ham satır okur. Tablo yoksa veya sorgu başarısızsa null.
 *
 * @return array<string,mixed>|null
 */
function seo_runtime_author_row(?int $id = null): ?array
{
    if (!isset($GLOBALS['conn']) || !($GLOBALS['conn'] instanceof mysqli)) {
        return null;
    }
    static $cache = [];
    $key = $id === null ? '__default__' : 'id:' . $id;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $conn = $GLOBALS['conn'];

    $check = @$conn->query("SHOW TABLES LIKE 'authors'");
    if (!$check || $check->num_rows === 0) {
        return $cache[$key] = null;
    }

    if ($id !== null && $id > 0) {
        $stmt = $conn->prepare('SELECT * FROM authors WHERE id = ? AND status = 1 LIMIT 1');
        if ($stmt instanceof mysqli_stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res instanceof mysqli_result ? $res->fetch_assoc() : null;
            $stmt->close();
            if (is_array($row)) {
                return $cache[$key] = $row;
            }
        }
    }

    // Default yazar
    $res = @$conn->query('SELECT * FROM authors WHERE is_default = 1 AND status = 1 ORDER BY id ASC LIMIT 1');
    if ($res instanceof mysqli_result) {
        $row = $res->fetch_assoc();
        if (is_array($row)) {
            return $cache['__default__'] = $cache[$key] = $row;
        }
    }

    return $cache[$key] = null;
}

/**
 * Authors satırını Schema.org Person düğümüne çevirir.
 *
 * @param array<string,mixed> $row
 * @return array<string,mixed>
 */
function seo_runtime_author_row_to_person(array $row, string $orgId, string $orgName, string $origin): array
{
    $person = ['@type' => 'Person'];

    $name = trim((string) ($row['name'] ?? ''));
    if ($name !== '') {
        $person['name'] = $name;
    }

    $title = trim((string) ($row['title'] ?? ''));
    if ($title !== '') {
        $person['jobTitle'] = $title;
    }

    $bio = trim((string) ($row['bio'] ?? ''));
    if ($bio !== '') {
        $person['description'] = $bio;
    }

    $url = trim((string) ($row['url'] ?? ''));
    if ($url !== '') {
        if ($url[0] === '/') {
            $url = rtrim($origin, '/') . $url;
        }
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $person['url'] = $url;
        }
    }

    $email = trim((string) ($row['email'] ?? ''));
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $person['email'] = 'mailto:' . $email;
    }

    $photo = trim((string) ($row['photo_url'] ?? ''));
    if ($photo !== '') {
        if ($photo[0] === '/') {
            $photo = rtrim($origin, '/') . $photo;
        }
        if (filter_var($photo, FILTER_VALIDATE_URL)) {
            $person['image'] = $photo;
        }
    }

    $sameAs = [];
    foreach (['linkedin', 'twitter'] as $f) {
        $v = trim((string) ($row[$f] ?? ''));
        if ($v !== '' && filter_var($v, FILTER_VALIDATE_URL)) {
            $sameAs[] = $v;
        }
    }
    if ($sameAs !== []) {
        $person['sameAs'] = array_values(array_unique($sameAs));
    }

    $knowsAboutRaw = trim((string) ($row['knows_about'] ?? ''));
    if ($knowsAboutRaw !== '') {
        // Hem satır sonu (\n / \r) hem virgül / noktalı virgül ayraçlarını destekle.
        // Yeni admin/authors.php formu satır bazlı saklıyor; eski settings virgüllü.
        $items = preg_split('/[\r\n,;]+/u', $knowsAboutRaw) ?: [];
        $items = array_filter(array_map('trim', $items), static fn($x) => $x !== '');
        if ($items !== []) {
            $person['knowsAbout'] = array_values(array_unique($items));
        }
    }

    $person['worksFor'] = $orgId !== ''
        ? ['@id' => $orgId]
        : ['@type' => 'Organization', 'name' => $orgName, 'url' => $origin];

    return $person;
}

/**
 * settings.blog_default_author_* anahtarlarından Person düğümü üretir.
 * authors tablosu yoksa veya boşsa fallback olarak kullanılır.
 *
 * @param array<string,mixed> $site_settings
 * @return array<string,mixed>|null
 */
function seo_runtime_author_person_from_settings(array $site_settings, string $orgId, string $orgName, string $origin): ?array
{
    $name = trim((string) ($site_settings['blog_default_author_name'] ?? ''));
    if ($name === '') {
        return null;
    }
    $row = [
        'name' => $name,
        'title' => (string) ($site_settings['blog_default_author_title'] ?? ''),
        'bio' => (string) ($site_settings['blog_default_author_bio'] ?? ''),
        'url' => (string) ($site_settings['blog_default_author_url'] ?? ''),
        'email' => (string) ($site_settings['blog_default_author_email'] ?? ''),
        'photo_url' => (string) ($site_settings['blog_default_author_photo'] ?? ''),
        'linkedin' => (string) ($site_settings['blog_default_author_linkedin'] ?? ''),
        'twitter' => (string) ($site_settings['blog_default_author_twitter'] ?? ''),
        'knows_about' => (string) ($site_settings['blog_default_author_knows_about'] ?? ''),
    ];

    return seo_runtime_author_row_to_person($row, $orgId, $orgName, $origin);
}

/**
 * Ana çözücü. blog kaydı + site_settings → BlogPosting.author düğümü.
 *
 * @param array<string,mixed>|null $blog
 * @param array<string,mixed> $site_settings
 * @return array<string,mixed>
 */
function seo_runtime_resolve_blog_author(?array $blog, array $site_settings, string $orgId, string $orgName, string $origin): array
{
    // 1) blog.author_id → authors satırı
    $authorId = null;
    if (is_array($blog) && isset($blog['author_id'])) {
        $aid = (int) $blog['author_id'];
        if ($aid > 0) {
            $authorId = $aid;
        }
    }
    $row = seo_runtime_author_row($authorId);
    if (is_array($row)) {
        return seo_runtime_author_row_to_person($row, $orgId, $orgName, $origin);
    }

    // 2) Eski blog.yazar_adi (geriye uyum)
    if (is_array($blog) && !empty($blog['yazar_adi'])) {
        $legacyRow = [
            'name' => (string) $blog['yazar_adi'],
            'title' => (string) ($site_settings['blog_default_author_title'] ?? ''),
            'bio' => (string) ($site_settings['blog_default_author_bio'] ?? ''),
            'url' => (string) ($site_settings['blog_default_author_url'] ?? ''),
            'email' => (string) ($site_settings['blog_default_author_email'] ?? ''),
            'knows_about' => (string) ($site_settings['blog_default_author_knows_about'] ?? ''),
        ];
        return seo_runtime_author_row_to_person($legacyRow, $orgId, $orgName, $origin);
    }

    // 3) settings fallback
    $fromSettings = seo_runtime_author_person_from_settings($site_settings, $orgId, $orgName, $origin);
    if (is_array($fromSettings)) {
        return $fromSettings;
    }

    // 4) En son: Organization (publisher)
    $org = [
        '@type' => 'Organization',
        'name' => $orgName,
        'url' => $origin,
    ];
    if ($orgId !== '') {
        $org['@id'] = $orgId;
    }

    return $org;
}
