<?php
declare(strict_types=1);

/**
 * Site geneli title + meta_description uzunluk denetim raporu.
 *
 * Kullanım:
 *   php scripts/seo_meta_length_audit.php            # insan için metin
 *   php scripts/seo_meta_length_audit.php --csv      # CSV çıktı
 *   php scripts/seo_meta_length_audit.php --only=services
 *
 * Kurallar (Google SERP için yaygın piksel ≈ karakter yaklaşımı):
 *   - Title: 30–60 karakter (ideal 50–60)
 *   - Meta description: 70–160 karakter (ideal 120–155)
 */

$root = dirname(__DIR__);
require_once $root . '/config/db.php';

$argvList = isset($argv) && is_array($argv) ? $argv : [];
$asCsv = in_array('--csv', $argvList, true);
$onlyFilter = '';
foreach ($argvList as $a) {
    if (is_string($a) && strncmp($a, '--only=', 7) === 0) {
        $onlyFilter = substr($a, 7);
    }
}

const T_MIN = 30;
const T_IDEAL_MIN = 50;
const T_IDEAL_MAX = 60;
const T_MAX = 65;

const M_MIN = 70;
const M_IDEAL_MIN = 120;
const M_IDEAL_MAX = 155;
const M_MAX = 160;

/**
 * @param array<int,array{table:string,slug:string,type:string,url:string,title:string,tlen:int,tflag:string,meta:string,mlen:int,mflag:string}> $rows
 */
function seoAuditClassifyLen(int $len, int $min, int $idealMin, int $idealMax, int $max): string
{
    if ($len === 0)                 return 'EMPTY';
    if ($len < $min)                return 'VERY_SHORT';
    if ($len < $idealMin)           return 'SHORT';
    if ($len >= $idealMin && $len <= $idealMax) return 'OK';
    if ($len <= $max)               return 'LONG_SOFT';
    return 'TOO_LONG';
}

function seoAuditIsBadFlag(string $flag): bool
{
    return in_array($flag, ['EMPTY', 'VERY_SHORT', 'SHORT', 'TOO_LONG'], true);
}

/** @return list<array<string,mixed>> */
function seoAuditFetch(mysqli $conn, string $sql): array
{
    $res = $conn->query($sql);
    if (!$res instanceof mysqli_result) {
        return [];
    }
    $out = [];
    while ($r = $res->fetch_assoc()) {
        $out[] = $r;
    }
    $res->free();
    return $out;
}

$all = [];

// services
if ($onlyFilter === '' || $onlyFilter === 'services') {
    foreach (seoAuditFetch($conn, "SELECT id, slug, ana_baslik, seo_title, meta_description FROM services WHERE status = 1") as $r) {
        $title = trim((string) ($r['seo_title'] ?? '')) !== '' ? (string) $r['seo_title'] : (string) ($r['ana_baslik'] ?? '');
        $meta = (string) ($r['meta_description'] ?? '');
        $tlen = mb_strlen($title);
        $mlen = mb_strlen($meta);
        $all[] = [
            'table' => 'services',
            'id' => (int) $r['id'],
            'slug' => (string) ($r['slug'] ?? ''),
            'url' => '/' . ltrim((string) ($r['slug'] ?? ''), '/'),
            'title' => $title,
            'tlen' => $tlen,
            'tflag' => seoAuditClassifyLen($tlen, T_MIN, T_IDEAL_MIN, T_IDEAL_MAX, T_MAX),
            'meta' => $meta,
            'mlen' => $mlen,
            'mflag' => seoAuditClassifyLen($mlen, M_MIN, M_IDEAL_MIN, M_IDEAL_MAX, M_MAX),
        ];
    }
}

// pages
if ($onlyFilter === '' || $onlyFilter === 'pages') {
    foreach (seoAuditFetch($conn, "SELECT id, slug, title, seo_title, meta_description FROM pages WHERE status = 1") as $r) {
        $title = trim((string) ($r['seo_title'] ?? '')) !== '' ? (string) $r['seo_title'] : (string) ($r['title'] ?? '');
        $meta = (string) ($r['meta_description'] ?? '');
        $tlen = mb_strlen($title);
        $mlen = mb_strlen($meta);
        $all[] = [
            'table' => 'pages',
            'id' => (int) $r['id'],
            'slug' => (string) ($r['slug'] ?? ''),
            'url' => '/' . ltrim((string) ($r['slug'] ?? ''), '/'),
            'title' => $title,
            'tlen' => $tlen,
            'tflag' => seoAuditClassifyLen($tlen, T_MIN, T_IDEAL_MIN, T_IDEAL_MAX, T_MAX),
            'meta' => $meta,
            'mlen' => $mlen,
            'mflag' => seoAuditClassifyLen($mlen, M_MIN, M_IDEAL_MIN, M_IDEAL_MAX, M_MAX),
        ];
    }
}

// blog_posts
if ($onlyFilter === '' || $onlyFilter === 'blog_posts') {
    foreach (seoAuditFetch($conn, "SELECT id, slug, baslik, seo_title, meta_description FROM blog_posts WHERE durum = 3") as $r) {
        $title = trim((string) ($r['seo_title'] ?? '')) !== '' ? (string) $r['seo_title'] : (string) ($r['baslik'] ?? '');
        $meta = (string) ($r['meta_description'] ?? '');
        $tlen = mb_strlen($title);
        $mlen = mb_strlen($meta);
        $all[] = [
            'table' => 'blog_posts',
            'id' => (int) $r['id'],
            'slug' => (string) ($r['slug'] ?? ''),
            'url' => '/' . ltrim((string) ($r['slug'] ?? ''), '/'),
            'title' => $title,
            'tlen' => $tlen,
            'tflag' => seoAuditClassifyLen($tlen, T_MIN, T_IDEAL_MIN, T_IDEAL_MAX, T_MAX),
            'meta' => $meta,
            'mlen' => $mlen,
            'mflag' => seoAuditClassifyLen($mlen, M_MIN, M_IDEAL_MIN, M_IDEAL_MAX, M_MAX),
        ];
    }
}

if ($asCsv) {
    $fh = fopen('php://output', 'w');
    fwrite($fh, "\xEF\xBB\xBF");
    fputcsv($fh, ['table', 'id', 'slug', 'url', 'title_length', 'title_flag', 'meta_length', 'meta_flag', 'title', 'meta']);
    foreach ($all as $row) {
        fputcsv($fh, [
            $row['table'], $row['id'], $row['slug'], $row['url'],
            $row['tlen'], $row['tflag'], $row['mlen'], $row['mflag'],
            $row['title'], $row['meta'],
        ]);
    }
    fclose($fh);
    exit(0);
}

$bad = array_values(array_filter($all, function (array $r): bool {
    return seoAuditIsBadFlag($r['tflag']) || seoAuditIsBadFlag($r['mflag']);
}));

$total = count($all);
$badCount = count($bad);
echo "SEO META LENGTH AUDIT\n";
echo "Kayit: $total  |  Sorunlu: $badCount\n";
echo str_repeat('-', 90) . "\n";
echo sprintf("%-11s %-5s %-40s %-6s %-11s %-6s %-11s\n", 'TABLE', 'ID', 'SLUG', 'T_LEN', 'T_FLAG', 'M_LEN', 'M_FLAG');
echo str_repeat('-', 90) . "\n";
foreach ($bad as $r) {
    echo sprintf(
        "%-11s %-5d %-40s %-6d %-11s %-6d %-11s\n",
        $r['table'], $r['id'], mb_substr($r['slug'], 0, 40),
        $r['tlen'], $r['tflag'], $r['mlen'], $r['mflag']
    );
}
echo str_repeat('-', 90) . "\n";
echo "Kurallar: Title OK " . T_IDEAL_MIN . "-" . T_IDEAL_MAX . " (soft " . T_MIN . "-" . T_MAX . ")";
echo " | Meta OK " . M_IDEAL_MIN . "-" . M_IDEAL_MAX . " (soft " . M_MIN . "-" . M_MAX . ")\n";
echo "CSV: php scripts/seo_meta_length_audit.php --csv > audit.csv\n";
