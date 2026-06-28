<?php
/**
 * Admin Dashboard — SEO skor paneli için salt-okuma metrik toplayıcı.
 *
 * Veri kaynağı:
 *  - services / pages / blog_posts tablolarındaki seo_title & meta_description alanları
 *  - internal_link_anchors (varsa)
 *  - sitemap-index.xml (filemtime)
 *  - local_cluster_coverage özeti (district × service leaf matrix)
 *
 * Performans:
 *  - Tüm sayımlar DB aggregate; N+1 yok.
 *  - Sonuç cache/dashboard_seo_metrics.json içine yazılır, TTL 15 dk.
 *  - Yenileme GET parametresi ?refresh=1 ile (dashboard.php'de) tetiklenebilir.
 */

if (!defined('MYNAK_DASHBOARD_SEO_METRICS_LOADED')) {
    define('MYNAK_DASHBOARD_SEO_METRICS_LOADED', true);
}

if (!function_exists('mynak_dashboard_seo_metrics_cache_path')) {
    function mynak_dashboard_seo_metrics_cache_path(): string
    {
        $root = defined('PROJECT_ROOT') ? PROJECT_ROOT : realpath(__DIR__ . '/../..');
        if (!is_string($root) || $root === '') {
            $root = dirname(__DIR__, 2);
        }
        return rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'dashboard_seo_metrics.json';
    }
}

if (!function_exists('mynak_dashboard_seo_metrics_classify')) {
    /**
     * Uzunluk sınıflandırma (title: 30–60, meta: 120–160; boş = missing).
     */
    function mynak_dashboard_seo_metrics_classify(int $len, int $idealMin, int $idealMax): string
    {
        if ($len === 0) {
            return 'missing';
        }
        if ($len < $idealMin) {
            return 'short';
        }
        if ($len > $idealMax) {
            return 'long';
        }
        return 'ok';
    }
}

if (!function_exists('mynak_dashboard_seo_metrics_scan_table')) {
    /**
     * @param \mysqli $conn
     * @param array{table:string,title_col:string,meta_col:string,status_col:string,status_val:int} $cfg
     * @return array{total:int,title:array<string,int>,meta:array<string,int>,score_avg:float|null}
     */
    function mynak_dashboard_seo_metrics_scan_table(\mysqli $conn, array $cfg): array
    {
        $bucket = [
            'total' => 0,
            'title' => ['ok' => 0, 'short' => 0, 'long' => 0, 'missing' => 0],
            'meta' => ['ok' => 0, 'short' => 0, 'long' => 0, 'missing' => 0],
            'score_avg' => null,
        ];
        $table = preg_replace('/[^a-z0-9_]/i', '', $cfg['table']);
        if ($table === '') {
            return $bucket;
        }
        $titleCol = preg_replace('/[^a-z0-9_]/i', '', $cfg['title_col']);
        $metaCol = preg_replace('/[^a-z0-9_]/i', '', $cfg['meta_col']);
        $statusCol = preg_replace('/[^a-z0-9_]/i', '', $cfg['status_col']);
        $statusVal = (int) $cfg['status_val'];

        $sql = "SELECT `{$titleCol}` AS t, `{$metaCol}` AS m, seo_score AS s FROM `{$table}` WHERE `{$statusCol}` = {$statusVal}";
        $res = @$conn->query($sql);
        if (!$res) {
            return $bucket;
        }
        $scoreSum = 0;
        $scoreCount = 0;
        while ($row = $res->fetch_assoc()) {
            $bucket['total']++;
            $t = trim((string) ($row['t'] ?? ''));
            $m = trim((string) ($row['m'] ?? ''));
            $bucket['title'][mynak_dashboard_seo_metrics_classify(mb_strlen($t), 30, 60)]++;
            $bucket['meta'][mynak_dashboard_seo_metrics_classify(mb_strlen($m), 120, 160)]++;
            if (isset($row['s']) && $row['s'] !== null && $row['s'] !== '') {
                $scoreSum += (int) $row['s'];
                $scoreCount++;
            }
        }
        $res->free();
        if ($scoreCount > 0) {
            $bucket['score_avg'] = round($scoreSum / $scoreCount, 1);
        }
        return $bucket;
    }
}

if (!function_exists('mynak_dashboard_seo_metrics_freshness')) {
    /**
     * Son 7 ve 28 gün içinde güncellenen içerik sayımı.
     * @return array{7d:int,28d:int}
     */
    function mynak_dashboard_seo_metrics_freshness(\mysqli $conn, string $table, string $statusCol, int $statusVal): array
    {
        $out = ['7d' => 0, '28d' => 0];
        $table = preg_replace('/[^a-z0-9_]/i', '', $table);
        $statusCol = preg_replace('/[^a-z0-9_]/i', '', $statusCol);
        $statusVal = (int) $statusVal;
        $sql = "SELECT
            SUM(CASE WHEN updated_at >= NOW() - INTERVAL 7 DAY THEN 1 ELSE 0 END) AS d7,
            SUM(CASE WHEN updated_at >= NOW() - INTERVAL 28 DAY THEN 1 ELSE 0 END) AS d28
            FROM `{$table}` WHERE `{$statusCol}` = {$statusVal} AND updated_at IS NOT NULL";
        $res = @$conn->query($sql);
        if ($res && ($row = $res->fetch_assoc())) {
            $out['7d'] = (int) ($row['d7'] ?? 0);
            $out['28d'] = (int) ($row['d28'] ?? 0);
        }
        if ($res) {
            $res->free();
        }
        return $out;
    }
}

if (!function_exists('mynak_dashboard_seo_metrics_sitemap')) {
    function mynak_dashboard_seo_metrics_sitemap(): array
    {
        $out = ['index' => null, 'main' => null, 'image' => null, 'video' => null];
        $root = defined('PROJECT_ROOT') ? PROJECT_ROOT : realpath(__DIR__ . '/../..');
        if (!is_string($root) || $root === '') {
            return $out;
        }
        $map = [
            'index' => 'sitemap-index.xml',
            'main' => 'sitemap.xml',
            'image' => 'image-sitemap.xml',
            'video' => 'video-sitemap.xml',
        ];
        foreach ($map as $k => $file) {
            $p = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
            if (is_readable($p)) {
                $out[$k] = [
                    'mtime' => (int) filemtime($p),
                    'size' => (int) filesize($p),
                ];
            }
        }
        return $out;
    }
}

if (!function_exists('mynak_dashboard_seo_metrics_anchor_pool')) {
    function mynak_dashboard_seo_metrics_anchor_pool(\mysqli $conn): array
    {
        $out = ['total_anchors' => 0, 'unique_targets' => 0, 'active' => 0];
        $tableCheck = @$conn->query("SHOW TABLES LIKE 'internal_link_anchors'");
        if (!$tableCheck || $tableCheck->num_rows === 0) {
            if ($tableCheck) {
                $tableCheck->free();
            }
            return $out;
        }
        $tableCheck->free();
        $res = @$conn->query("SELECT COUNT(*) c, COUNT(DISTINCT target_slug) u, SUM(active=1) a FROM internal_link_anchors");
        if ($res && ($r = $res->fetch_assoc())) {
            $out['total_anchors'] = (int) ($r['c'] ?? 0);
            $out['unique_targets'] = (int) ($r['u'] ?? 0);
            $out['active'] = (int) ($r['a'] ?? 0);
        }
        if ($res) {
            $res->free();
        }
        return $out;
    }
}

if (!function_exists('mynak_dashboard_seo_metrics_local_cluster')) {
    /**
     * İzmir ilçeleri × hizmet leaf matrix özeti (local_cluster_coverage ile aynı kaynak).
     * @return array{covered:int,weak:int,missing:int,total:int}
     */
    function mynak_dashboard_seo_metrics_local_cluster(\mysqli $conn): array
    {
        $summary = ['covered' => 0, 'weak' => 0, 'missing' => 0, 'total' => 0];

        $districts = [];
        if (function_exists('seo_ei_izmir_district_names_local_pack_order')) {
            $districts = seo_ei_izmir_district_names_local_pack_order();
        }
        if (!is_array($districts) || $districts === []) {
            return $summary;
        }

        $services = [
            'evden-eve-nakliyat',
            'ofis-tasima',
            'parca-esya-tasima',
            'esya-depolama',
            'sehirlerarasi-nakliyat',
        ];

        $slugToDistrict = static function (string $name): string {
            $n = mb_strtolower(trim($name), 'UTF-8');
            $tr = ['ç' => 'c','ğ' => 'g','ı' => 'i','ö' => 'o','ş' => 's','ü' => 'u'];
            $n = strtr($n, $tr);
            $n = preg_replace('/[^a-z0-9]+/', '-', $n);
            return trim((string) $n, '-');
        };

        $existing = [];
        $res = @$conn->query("SELECT slug FROM pages WHERE status = 1 UNION ALL SELECT slug FROM services WHERE status = 1");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $existing[(string) $row['slug']] = true;
            }
            $res->free();
        }

        foreach ($districts as $d) {
            $base = $slugToDistrict((string) $d);
            foreach ($services as $svc) {
                $summary['total']++;
                $candidate = $base . '-' . $svc;
                if (!isset($existing[$candidate])) {
                    $summary['missing']++;
                    continue;
                }
                // Uzun içerik services.icerik'te tutulur (2026-04 migration); yoksa aciklama'ya düş.
                $lenRes = @$conn->query("SELECT CHAR_LENGTH(COALESCE(content, '')) + CHAR_LENGTH(COALESCE(meta_description, '')) AS l FROM pages WHERE slug = '" . $conn->real_escape_string($candidate) . "' AND status = 1 UNION SELECT CHAR_LENGTH(COALESCE(icerik, aciklama, '')) + CHAR_LENGTH(COALESCE(meta_description, '')) AS l FROM services WHERE slug = '" . $conn->real_escape_string($candidate) . "' AND status = 1 LIMIT 1");
                $len = 0;
                if ($lenRes && ($lr = $lenRes->fetch_assoc())) {
                    $len = (int) ($lr['l'] ?? 0);
                }
                if ($lenRes) {
                    $lenRes->free();
                }
                if ($len < 6000) {
                    $summary['weak']++;
                } else {
                    $summary['covered']++;
                }
            }
        }
        return $summary;
    }
}

if (!function_exists('mynak_dashboard_seo_metrics_collect')) {
    /**
     * @return array<string,mixed>
     */
    function mynak_dashboard_seo_metrics_collect(\mysqli $conn): array
    {
        $out = [
            'generated_at' => time(),
            'services' => mynak_dashboard_seo_metrics_scan_table($conn, [
                'table' => 'services',
                'title_col' => 'seo_title',
                'meta_col' => 'meta_description',
                'status_col' => 'status',
                'status_val' => 1,
            ]),
            'pages' => mynak_dashboard_seo_metrics_scan_table($conn, [
                'table' => 'pages',
                'title_col' => 'seo_title',
                'meta_col' => 'meta_description',
                'status_col' => 'status',
                'status_val' => 1,
            ]),
            'blog_posts' => mynak_dashboard_seo_metrics_scan_table($conn, [
                'table' => 'blog_posts',
                'title_col' => 'seo_title',
                'meta_col' => 'meta_description',
                'status_col' => 'durum',
                'status_val' => 3,
            ]),
            'freshness' => [
                'services' => mynak_dashboard_seo_metrics_freshness($conn, 'services', 'status', 1),
                'pages' => mynak_dashboard_seo_metrics_freshness($conn, 'pages', 'status', 1),
                'blog_posts' => mynak_dashboard_seo_metrics_freshness($conn, 'blog_posts', 'durum', 3),
            ],
            'sitemap' => mynak_dashboard_seo_metrics_sitemap(),
            'anchor_pool' => mynak_dashboard_seo_metrics_anchor_pool($conn),
            'local_cluster' => mynak_dashboard_seo_metrics_local_cluster($conn),
        ];
        return $out;
    }
}

if (!function_exists('mynak_dashboard_seo_metrics_fallback_shell')) {
    /**
     * Dashboard şablonu için güvenli boş metrik (Throwable sonrası veya hata anı).
     * @return array<string,mixed>
     */
    function mynak_dashboard_seo_metrics_fallback_shell(): array
    {
        $z = ['ok' => 0, 'short' => 0, 'long' => 0, 'missing' => 0];
        $b = ['total' => 0, 'title' => $z, 'meta' => $z, 'score_avg' => null];
        $fr = ['7d' => 0, '28d' => 0];

        return [
            'generated_at' => time(),
            'services' => $b,
            'pages' => $b,
            'blog_posts' => $b,
            'freshness' => [
                'services' => $fr,
                'pages' => $fr,
                'blog_posts' => $fr,
            ],
            'sitemap' => ['index' => null, 'main' => null, 'image' => null, 'video' => null],
            'anchor_pool' => ['total_anchors' => 0, 'unique_targets' => 0, 'active' => 0],
            'local_cluster' => ['covered' => 0, 'weak' => 0, 'missing' => 0, 'total' => 0],
            '_source' => 'error',
            '_age_seconds' => 0,
        ];
    }
}

if (!function_exists('mynak_dashboard_seo_metrics_get')) {
    /**
     * Cache-first çağrı. $force=true ise cache atlanır.
     */
    function mynak_dashboard_seo_metrics_get(\mysqli $conn, bool $force = false, int $ttlSeconds = 900): array
    {
        $cachePath = mynak_dashboard_seo_metrics_cache_path();
        if (!$force && is_readable($cachePath)) {
            $age = time() - (int) filemtime($cachePath);
            if ($age < $ttlSeconds) {
                $raw = @file_get_contents($cachePath);
                if (is_string($raw) && $raw !== '') {
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded) && isset($decoded['generated_at'])) {
                        $decoded['_source'] = 'cache';
                        $decoded['_age_seconds'] = $age;
                        return $decoded;
                    }
                }
            }
        }

        $metrics = mynak_dashboard_seo_metrics_collect($conn);
        $dir = dirname($cachePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($cachePath, json_encode($metrics, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $metrics['_source'] = 'fresh';
        $metrics['_age_seconds'] = 0;
        return $metrics;
    }
}
