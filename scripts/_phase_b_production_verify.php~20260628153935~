<?php
declare(strict_types=1);
/** Phase B production verification — READ ONLY. CLI only. */
if (PHP_SAPI !== 'cli') {
    exit(1);
}

$verifyArgv = $argv ?? [];
if (in_array('--production', $verifyArgv, true)) {
    define('MYNAK_FORCE_PRODUCTION_DB', true);
}

$root = dirname(__DIR__);
require_once $root . '/config/config.php';
require_once $root . '/config/db.php';
require_once $root . '/includes/mynak_canonical_slug_redirects.php';
require_once $root . '/includes/mynak_meta_description.php';
require_once $root . '/includes/seo_runtime/constants.php';

$redirectMap = mynak_seo_cannibalization_redirect_map();
$fiyatAliases = [
    'izmir-nakliyat-fiyatlari', 'nakliyat-fiyatlari',
    'evden-eve-nakliyat-fiyat-listesi', 'izmir-evden-eve-nakliyat-fiyatlari',
];

$targetSlugs = [
    'fiyat',
    'izmir-evden-eve-nakliyat-fiyatlari-2026',
    'izmir-evden-eve-nakliyat-firmalar',
    'mobilya-beyaz-esya-tasimaciligi-rehberi',
    'izmir-evden-eve-nakliyat-guncel-tasinma-trendleri',
    'izmir-evden-eve-nakliyat-my-nakliyat-sorunsuz-tasinma',
    'izmir-evden-eve-nakliyat-ile-tasinmanin-avantajlari',
    'izmir-evden-eve-nakliyat-icin-en-iyi-hizmet',
    'izmir-evden-eve-nakliyat',
    'sehirici-nakliyat',
    'evden-eve-nakliyat-profesyonel-tasimacilik-avantajlari',
    'evden-eve-nakliyatta-profesyonel-kadronun-onemi',
    'evden-eve-nakliyat',
    '2026-sehirler-arasi-nakliyat-fiyatlari-guncel-rehber',
    'izmir-evden-eve-nakliyat-yorumlari',
    'urla-evden-eve-nakliyat',
    'urla-evdeneve-nakliyat',
    'profesyonel-tibbi-cihaz-tasima',
    'tibbi-cihaz-tasima',
    'galeri',
    'iletisim',
];

$resolve = static function (mysqli $conn, string $slug): ?array {
  foreach ([
    ['services', 'id', 'slug', 'ana_baslik', 'seo_title', 'meta_description', 'status', 1],
    ['blog_posts', 'id', 'slug', 'baslik', 'seo_title', 'meta_description', 'durum', 3],
    ['pages', 'id', 'slug', 'title', 'seo_title', 'meta_description', 'status', 1],
  ] as [$table, $idCol, $slugCol, $titleCol, $seoCol, $metaCol, $statusCol, $statusVal]) {
    $sql = "SELECT {$idCol} AS id, {$slugCol} AS slug, {$titleCol} AS title, {$seoCol} AS seo_title, {$metaCol} AS meta_description, {$statusCol} AS status_val FROM `{$table}` WHERE {$slugCol} = ? AND {$statusCol} = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
      continue;
    }
    $stmt->bind_param('si', $slug, $statusVal);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if ($row) {
      $row['table'] = $table;
      $row['status_field'] = $statusCol;
      return $row;
    }
  }
  return null;
};

$effectiveTitle = static function (array $row): string {
  $seo = trim((string) ($row['seo_title'] ?? ''));
  if ($seo !== '') {
    return function_exists('mynak_normalize_public_page_title')
      ? mynak_normalize_public_page_title($seo)
      : $seo;
  }
  $stem = trim((string) ($row['title'] ?? ''));
  return function_exists('mynak_build_page_title') ? mynak_build_page_title($stem) : $stem;
};

$effectiveMeta = static function (array $row, string $slug): string {
  $md = trim((string) ($row['meta_description'] ?? ''));
  if ($md !== '') {
    return mynak_meta_description_clamp($md, 160);
  }
  return mynak_default_meta_description_for_page(trim((string) ($row['title'] ?? '')), $slug);
};

$rows = [];
$dbOk = true;
try {
  $conn->query('SELECT 1');
} catch (Throwable $e) {
  $dbOk = false;
}

foreach ($targetSlugs as $slug) {
  $entry = [
    'slug' => $slug,
    'url' => 'https://www.mynakliyat.com.tr/' . rawurlencode($slug),
    'redirect_301_map' => $redirectMap[strtolower($slug)] ?? null,
    'fiyat_alias_redirect' => in_array(strtolower($slug), $fiyatAliases, true),
    'db' => null,
    'static_php' => in_array($slug, ['fiyat', 'galeri', 'iletisim'], true) ? $slug . '.php' : null,
  ];

  if ($dbOk) {
    $entry['db'] = $resolve($conn, $slug);
    if ($entry['db']) {
      $entry['db']['effective_title'] = $effectiveTitle($entry['db']);
      $entry['db']['effective_meta'] = $effectiveMeta($entry['db'], $slug);
      $entry['db']['title_len'] = mb_strlen($entry['db']['effective_title']);
      $entry['db']['meta_len'] = mb_strlen($entry['db']['effective_meta']);
    }
  }

  $rows[] = $entry;
}

// all indexable slugs from DB for duplicate scan
$all = [];
if ($dbOk) {
  $q = [
    "SELECT id, slug, title, seo_title, meta_description, status AS status_val, 'pages' AS src FROM pages WHERE status=1",
    "SELECT id, slug, baslik AS title, seo_title, meta_description, durum AS status_val, 'blog_posts' AS src FROM blog_posts WHERE durum=3",
    "SELECT id, slug, ana_baslik AS title, seo_title, meta_description, status AS status_val, 'services' AS src FROM services WHERE status=1",
  ];
  foreach ($q as $sql) {
    $res = $conn->query($sql);
    if (!$res) {
      continue;
    }
    while ($r = $res->fetch_assoc()) {
      $slug = strtolower(trim((string) ($r['slug'] ?? '')));
      if ($slug === '' || isset($redirectMap[$slug]) || in_array($slug, $fiyatAliases, true)) {
        continue;
      }
      if (in_array($slug, ['galeri', 'iletisim'], true)) {
        continue; // Phase A fixed
      }
      $r['effective_title'] = $effectiveTitle($r);
      $r['effective_meta'] = $effectiveMeta($r, $slug);
      $all[] = $r;
    }
  }
}

$group = static function (array $items, string $key): array {
  $m = [];
  foreach ($items as $it) {
    $k = trim((string) ($it[$key] ?? ''));
    if ($k === '') {
      continue;
    }
    $m[$k][] = $it;
  }
  $out = [];
  foreach ($m as $k => $list) {
    if (count($list) > 1) {
      $out[] = ['value' => $k, 'count' => count($list), 'items' => $list];
    }
  }
  usort($out, static fn ($a, $b) => $b['count'] <=> $a['count']);
  return $out;
};

echo json_encode([
  'db_connected' => $dbOk,
  'db_name' => defined('DB_NAME') ? DB_NAME : null,
  'targets' => $rows,
  'title_duplicates' => $group($all, 'effective_title'),
  'meta_duplicates' => $group($all, 'effective_meta'),
  'all_eligible_count' => count($all),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
