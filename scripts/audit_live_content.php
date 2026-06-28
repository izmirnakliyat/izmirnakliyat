<?php
/**
 * Canlı render sağlığı raporu.
 * DB'deki aktif services/pages/blog_posts kayıtları için HTTP GET atar,
 * HTML gövdesindeki ana içerik bloğunun uzunluğunu ölçer, DB ile kıyaslar.
 *
 * KULLANIM:
 *   php scripts\audit_live_content.php
 *   php scripts\audit_live_content.php --base=https://www.mynakliyat.com.tr/
 *   php scripts\audit_live_content.php --base=http://localhost/mynakliyat/
 *   php scripts\audit_live_content.php --blog-sample
 *   php scripts\audit_live_content.php --blog-limit=150
 *   php scripts\audit_live_content.php --csv > audit.csv
 *   php scripts\audit_live_content.php --skip-blog
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}

$root = dirname(__DIR__);
require_once $root . '/config/db.php';

/** @var mysqli $conn */
$conn->set_charset('utf8mb4');

$base = 'http://localhost/mynakliyat/';
$csv = in_array('--csv', $argv, true);
$skipBlog = in_array('--skip-blog', $argv, true);
$blogSampleOnly = in_array('--blog-sample', $argv, true);
$blogLimit = null;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--base=')) {
        $b = trim(substr($arg, 7));
        if ($b !== '') {
            $base = $b;
            if (!str_ends_with($base, '/')) {
                $base .= '/';
            }
        }
    }
    if (preg_match('/^--blog-limit=(\d+)$/', $arg, $m)) {
        $blogLimit = max(1, (int) $m[1]);
    }
}

echo 'Base URL: ' . $base . "\n\n";

function fetchSize(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_ENCODING => '',
        CURLOPT_USERAGENT => 'mynak-audit/1.1',
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false) {
        return ['code' => 0, 'total' => 0, 'body_len' => 0];
    }
    $total = strlen((string) $body);
    $len = 0;
    if (preg_match('~<section[^>]*class="[^"]*page-content[^"]*"[^>]*>(.*?)</section>~is', (string) $body, $m)) {
        $len = strlen($m[1]);
    } elseif (preg_match('~<article[^>]*>(.*?)</article>~is', (string) $body, $m)) {
        $len = strlen($m[1]);
    } elseif (preg_match('~<main[^>]*>(.*?)</main>~is', (string) $body, $m)) {
        $len = strlen($m[1]);
    }

    return ['code' => (int) $code, 'total' => $total, 'body_len' => $len];
}

$rows = [];

$r = $conn->query("SELECT id, slug, ana_baslik AS title, CHAR_LENGTH(aciklama) AS aciklama_len, CHAR_LENGTH(COALESCE(icerik,'')) AS icerik_len FROM services WHERE status=1");
if ($r) {
    while ($s = $r->fetch_assoc()) {
        $db_len = max((int) $s['icerik_len'], 0);
        $fallback_len = (int) $s['aciklama_len'];
        $expected = $db_len > 0 ? $db_len : $fallback_len;
        $rows[] = ['tip' => 'service', 'slug' => $s['slug'], 'title' => $s['title'], 'db_len' => $expected, 'fallback' => $db_len === 0];
    }
}

$r = $conn->query("SELECT id, slug, title, CHAR_LENGTH(COALESCE(content,'')) AS content_len FROM pages WHERE status=1");
if ($r) {
    while ($p = $r->fetch_assoc()) {
        $rows[] = ['tip' => 'page', 'slug' => $p['slug'], 'title' => $p['title'], 'db_len' => (int) $p['content_len'], 'fallback' => false];
    }
}

if (!$skipBlog) {
    if ($blogSampleOnly) {
        $blogSql = 'SELECT id, slug, baslik AS title, CHAR_LENGTH(icerik) AS icerik_len FROM blog_posts WHERE durum=3 ORDER BY RAND() LIMIT 25';
    } elseif ($blogLimit !== null) {
        $lim = (int) $blogLimit;
        $blogSql = "SELECT id, slug, baslik AS title, CHAR_LENGTH(icerik) AS icerik_len FROM blog_posts WHERE durum=3 ORDER BY id ASC LIMIT {$lim}";
    } else {
        $blogSql = 'SELECT id, slug, baslik AS title, CHAR_LENGTH(icerik) AS icerik_len FROM blog_posts WHERE durum=3';
    }
    $r = $conn->query($blogSql);
    if ($r) {
        while ($b = $r->fetch_assoc()) {
            $rows[] = ['tip' => 'blog', 'slug' => $b['slug'], 'title' => $b['title'], 'db_len' => (int) $b['icerik_len'], 'fallback' => false];
        }
    }
}

if ($csv) {
    echo "tip,slug,http,db_len,rendered_block_len,total_kb,ratio,flag,title\n";
} else {
    echo str_pad('TIP', 8) . str_pad('SLUG', 52) . str_pad('HTTP', 6) . str_pad('DB', 9) . str_pad('RENDER', 9) . str_pad('TOTAL_KB', 10) . "FLAG\n";
    echo str_repeat('-', 110) . "\n";
}

$flags = [];
foreach ($rows as $row) {
    $slug = (string) $row['slug'];
    $seg = $slug === '' ? '' : rawurlencode($slug);
    $url = $base . $seg;
    $res = fetchSize($url);
    $http = $res['code'];
    $rendered = $res['body_len'];
    $total_kb = round($res['total'] / 1024, 1);

    $flag = '';
    if ($http !== 200) {
        $flag = 'HTTP_' . $http;
    } elseif ($row['tip'] === 'blog') {
        if ($rendered < 1000) {
            $flag = 'BLOG_SHORT';
        }
    } elseif ($row['tip'] === 'service' && $row['fallback']) {
        if ($rendered < 500) {
            $flag = 'SVC_EMPTY_FALLBACK';
        } else {
            $flag = 'SVC_FALLBACK_OK';
        }
    } elseif ($row['tip'] === 'service') {
        if ($rendered < 1500) {
            $flag = 'SVC_SHORT';
        }
    } elseif ($row['tip'] === 'page') {
        if ($row['db_len'] > 1000 && $rendered < 800) {
            $flag = 'PAGE_TRUNCATED';
        } elseif ($row['db_len'] < 100) {
            $flag = 'PAGE_DB_EMPTY';
        }
    }

    if ($flag !== '') {
        $flags[] = ['tip' => $row['tip'], 'slug' => $row['slug'], 'flag' => $flag, 'http' => $http, 'db_len' => $row['db_len'], 'rendered' => $rendered, 'title' => $row['title']];
    }

    if ($csv) {
        $ratio = $row['db_len'] > 0 ? round($rendered / $row['db_len'], 2) : 0;
        $t = str_replace(['"', ','], ['""', ' '], (string) $row['title']);
        echo "{$row['tip']},{$row['slug']},{$http},{$row['db_len']},{$rendered},{$total_kb},{$ratio},{$flag},\"{$t}\"\n";
    } else {
        echo str_pad($row['tip'], 8) . str_pad(substr($row['slug'], 0, 50), 52) . str_pad((string) $http, 6) . str_pad((string) $row['db_len'], 9) . str_pad((string) $rendered, 9) . str_pad($total_kb . ' KB', 10) . $flag . "\n";
    }
}

if (!$csv) {
    echo "\n" . str_repeat('=', 110) . "\n";
    echo 'FLAG OZETI (toplam ' . count($flags) . " anomali)\n";
    echo str_repeat('=', 110) . "\n";
    $groups = [];
    foreach ($flags as $f) {
        $groups[$f['flag']][] = $f;
    }
    foreach ($groups as $flag => $list) {
        echo "\n[$flag] — " . count($list) . " kayit\n";
        foreach ($list as $item) {
            echo '  ' . str_pad($item['tip'], 8) . str_pad(substr($item['slug'], 0, 45), 47) .
                'db=' . str_pad((string) $item['db_len'], 6) .
                'rendered=' . str_pad((string) $item['rendered'], 8) . "\n";
        }
    }
}
