<?php
/**
 * SEO trace log aggregation — yalnızca logs/seo_pipeline_trace.log okur; canlı SEO çıktısına dokunmaz.
 *
 * @see seo_runtime_trace_aggregate_report()
 */
declare(strict_types=1);

/**
 * Log dosyasından (son N bayt) JSON satırlarını okur.
 *
 * @return list<array<string, mixed>>
 */
function seo_runtime_trace_aggregate_load_entries(int $maxScanBytes = 5242880): array
{
    $path = seo_runtime_trace_log_path();
    if (!is_readable($path)) {
        return [];
    }
    $size = filesize($path);
    if ($size === false || $size === 0) {
        return [];
    }
    $start = $size > $maxScanBytes ? $size - $maxScanBytes : 0;
    $fh = fopen($path, 'rb');
    if ($fh === false) {
        return [];
    }
    if ($start > 0) {
        fseek($fh, $start);
        fgets($fh);
    }
    $buf = stream_get_contents($fh);
    fclose($fh);
    if ($buf === false || $buf === '') {
        return [];
    }
    $lines = preg_split("/\r\n|\n|\r/", $buf) ?: [];
    $entries = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $row = json_decode($line, true);
        if (is_array($row)) {
            $entries[] = $row;
        }
    }
    return $entries;
}

/**
 * @return array<string, array{pillar: ?string, related: list<string>, nav_title: string, path?: string}>
 */
function seo_runtime_trace_aggregate_graph_definitions(): array
{
    if (function_exists('seo_rt_pillar_cluster_definitions')) {
        return seo_rt_pillar_cluster_definitions();
    }
    require_once __DIR__ . '/seo_runtime.php';
    if (function_exists('seo_rt_pillar_cluster_definitions')) {
        return seo_rt_pillar_cluster_definitions();
    }
    return [];
}

/**
 * Slug → cluster anahtarı (pillar slug); pillar sayfalar kendi cluster’larıdır.
 */
function seo_runtime_trace_aggregate_cluster_key_for_slug(string $slug, array $defs): string
{
    if (!isset($defs[$slug])) {
        return '_external';
    }
    $pillar = $defs[$slug]['pillar'] ?? null;
    if ($pillar === null || $pillar === '') {
        return $slug;
    }
    return (string) $pillar;
}

/**
 * @param array<string, int> $weights
 * @return list<array{key: string, weight: int}>
 */
function seo_runtime_trace_aggregate_top_entries(array $weights, int $topN, bool $desc = true): array
{
    if ($weights === []) {
        return [];
    }
    if ($desc) {
        arsort($weights, SORT_NUMERIC);
    } else {
        asort($weights, SORT_NUMERIC);
    }
    $out = [];
    $i = 0;
    foreach ($weights as $k => $w) {
        $out[] = ['key' => (string) $k, 'weight' => (int) $w];
        if (++$i >= $topN) {
            break;
        }
    }
    return $out;
}

/**
 * Trace loglarından SEO özet raporu (JSON-serialize edilebilir dizi).
 *
 * Seçenekler:
 * - max_scan_bytes (int): log sonundan okunacak maks. bayt (varsayılan 5242880)
 * - top_n (int): sıralama başlık sayısı (varsayılan 20)
 * - orphan_max_inbound (int): bu ve altı inbound ağırlığı “orphan adayı” (varsayılan 0)
 * - log_path (string): isteğe bağlı alternatif dosya yolu
 *
 * @param array<string, mixed> $opts
 * @return array<string, mixed>
 */
function seo_runtime_trace_aggregate_report(array $opts = []): array
{
    $maxScanBytes = (int) ($opts['max_scan_bytes'] ?? 5242880);
    if ($maxScanBytes < 1024) {
        $maxScanBytes = 1024;
    }
    $topN = (int) ($opts['top_n'] ?? 20);
    if ($topN < 1) {
        $topN = 20;
    }
    $orphanMaxInbound = (int) ($opts['orphan_max_inbound'] ?? 0);
    if ($orphanMaxInbound < 0) {
        $orphanMaxInbound = 0;
    }

    $customLog = isset($opts['log_path']) && is_string($opts['log_path']) && $opts['log_path'] !== '';
    if ($customLog) {
        $path = $opts['log_path'];
        $entries = seo_runtime_trace_aggregate_load_entries_from_path($path, $maxScanBytes);
    } else {
        $path = seo_runtime_trace_log_path();
        $entries = seo_runtime_trace_aggregate_load_entries($maxScanBytes);
    }

    $inbound = [];
    $outbound = [];
    $sourceEvents = [];
    $allSlugs = [];
    $fallbackPages = [];
    $redirectFromCounts = [];
    $redirectToCounts = [];
    $redirectCodes = [];
    $redirectTotal = 0;
    $graphPathSlugHits = [];

    foreach ($entries as $row) {
        if (isset($row['internal_links']) && is_array($row['internal_links'])) {
            $il = $row['internal_links'];
            $src = isset($il['source_slug']) ? trim((string) $il['source_slug']) : '';
            if ($src !== '') {
                $allSlugs[$src] = true;
                $emitted = (int) ($il['emitted_count'] ?? 0);
                $outbound[$src] = ($outbound[$src] ?? 0) + $emitted;
                $sourceEvents[$src] = ($sourceEvents[$src] ?? 0) + 1;
            }
            $counts = $il['outbound_to_slug_counts'] ?? [];
            if (is_array($counts)) {
                foreach ($counts as $targetSlug => $c) {
                    $t = trim((string) $targetSlug);
                    if ($t === '') {
                        continue;
                    }
                    $w = (int) $c;
                    $inbound[$t] = ($inbound[$t] ?? 0) + $w;
                    $allSlugs[$t] = true;
                }
            }
        }

        $head = $row['head'] ?? null;
        if (is_array($head) && !empty($head['fallback_content'])) {
            $fallbackPages[] = [
                'request_url' => (string) ($row['request_url'] ?? ''),
                'canonical' => (string) ($head['canonical'] ?? ''),
                'logged_at_iso' => (string) ($row['logged_at_iso'] ?? ''),
            ];
        }

        $redir = $row['redirect'] ?? null;
        if (is_array($redir) && isset($redir['to'])) {
            $redirectTotal++;
            $from = (string) ($redir['from'] ?? '');
            $to = (string) ($redir['to'] ?? '');
            $code = (int) ($redir['code'] ?? 301);
            $redirectCodes[$code] = ($redirectCodes[$code] ?? 0) + 1;
            if ($from !== '') {
                $redirectFromCounts[$from] = ($redirectFromCounts[$from] ?? 0) + 1;
            }
            if ($to !== '') {
                $redirectToCounts[$to] = ($redirectToCounts[$to] ?? 0) + 1;
            }
        }

        $gpr = $row['graph_path_resolutions'] ?? [];
        if (is_array($gpr)) {
            foreach ($gpr as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $sg = isset($item['slug']) ? trim((string) $item['slug']) : '';
                if ($sg !== '') {
                    $graphPathSlugHits[$sg] = ($graphPathSlugHits[$sg] ?? 0) + 1;
                }
            }
        }
    }

    $defs = seo_runtime_trace_aggregate_graph_definitions();
    $defSlugs = array_keys($defs);
    $defSet = array_fill_keys($defSlugs, true);

    $observedSlugs = array_keys($allSlugs);
    sort($observedSlugs);

    $neverTargeted = [];
    foreach ($defSlugs as $ds) {
        if (!isset($inbound[$ds]) || (int) $inbound[$ds] === 0) {
            $neverTargeted[] = $ds;
        }
    }
    sort($neverTargeted);

    $traceOnly = [];
    foreach ($observedSlugs as $s) {
        if (!isset($defSet[$s])) {
            $traceOnly[] = $s;
        }
    }

    $orphanCandidates = [];
    foreach ($defSlugs as $ds) {
        $w = (int) ($inbound[$ds] ?? 0);
        if ($w <= $orphanMaxInbound) {
            $orphanCandidates[] = ['slug' => $ds, 'inbound_weight' => $w];
        }
    }
    usort($orphanCandidates, static function (array $a, array $b): int {
        return ($a['inbound_weight'] <=> $b['inbound_weight']) ?: strcmp($a['slug'], $b['slug']);
    });
    $orphanCandidates = array_slice($orphanCandidates, 0, $topN);

    $clusterStats = [];
    foreach ($defSlugs as $slug) {
        $ck = seo_runtime_trace_aggregate_cluster_key_for_slug($slug, $defs);
        if (!isset($clusterStats[$ck])) {
            $clusterStats[$ck] = [
                'cluster_key' => $ck,
                'member_slugs' => [],
                'cluster_inbound_total' => 0,
                'cluster_outbound_total' => 0,
                'internal_crosslink_weight' => 0,
            ];
        }
        $clusterStats[$ck]['member_slugs'][] = $slug;
        $clusterStats[$ck]['cluster_inbound_total'] += (int) ($inbound[$slug] ?? 0);
        $clusterStats[$ck]['cluster_outbound_total'] += (int) ($outbound[$slug] ?? 0);
    }
    foreach ($clusterStats as $k => $st) {
        sort($clusterStats[$k]['member_slugs']);
    }

    foreach ($entries as $row) {
        if (!isset($row['internal_links']) || !is_array($row['internal_links'])) {
            continue;
        }
        $il = $row['internal_links'];
        $src = isset($il['source_slug']) ? trim((string) $il['source_slug']) : '';
        if ($src === '') {
            continue;
        }
        $ckSrc = seo_runtime_trace_aggregate_cluster_key_for_slug($src, $defs);
        $counts = $il['outbound_to_slug_counts'] ?? [];
        if (!is_array($counts)) {
            continue;
        }
        foreach ($counts as $targetSlug => $c) {
            $t = trim((string) $targetSlug);
            if ($t === '') {
                continue;
            }
            $ckTgt = seo_runtime_trace_aggregate_cluster_key_for_slug($t, $defs);
            $w = (int) $c;
            if ($ckSrc !== '_external' && $ckTgt !== '_external' && $ckSrc === $ckTgt) {
                $clusterStats[$ckSrc]['internal_crosslink_weight'] += $w;
            }
        }
    }

    $clusterList = array_values($clusterStats);
    usort($clusterList, static function (array $a, array $b): int {
        return ($b['internal_crosslink_weight'] <=> $a['internal_crosslink_weight'])
            ?: ($b['cluster_inbound_total'] <=> $a['cluster_inbound_total']);
    });

    $topInbound = seo_runtime_trace_aggregate_top_entries($inbound, $topN, true);
    $topOutbound = seo_runtime_trace_aggregate_top_entries($outbound, $topN, true);

    return [
        'generated_at_iso' => date('c'),
        'source' => [
            'log_path' => $path,
            'entries_parsed' => count($entries),
            'max_scan_bytes' => $maxScanBytes,
        ],
        'link_graph' => [
            'inbound_weight_by_slug' => $inbound,
            'outbound_weight_by_slug' => $outbound,
            'unique_sources_emitting_blocks' => count($sourceEvents),
            'source_emit_events_by_slug' => $sourceEvents,
        ],
        'rankings' => [
            'top_inbound_slugs' => array_map(static function (array $e): array {
                return ['slug' => $e['key'], 'weighted_inbound' => $e['weight']];
            }, $topInbound),
            'top_outbound_slugs' => array_map(static function (array $e): array {
                return ['slug' => $e['key'], 'weighted_outbound' => $e['weight']];
            }, $topOutbound),
            'orphan_candidates_definition_slugs' => $orphanCandidates,
            'orphan_rule' => [
                'max_inbound_weight' => $orphanMaxInbound,
                'note' => 'Tanım grafiğindeki slug’lar arasında inbound ağırlığı bu eşiğe eşit veya altında olanlar (trace örneklemine göre).',
            ],
        ],
        'fallback_content_pages' => $fallbackPages,
        'redirects' => [
            'total_events' => $redirectTotal,
            'by_status_code' => $redirectCodes,
            'top_targets' => array_map(static function (array $e): array {
                return ['to' => $e['key'], 'count' => $e['weight']];
            }, seo_runtime_trace_aggregate_top_entries($redirectToCounts, $topN, true)),
            'top_sources' => array_map(static function (array $e): array {
                return ['from' => $e['key'], 'count' => $e['weight']];
            }, seo_runtime_trace_aggregate_top_entries($redirectFromCounts, $topN, true)),
        ],
        'graph_coverage' => [
            'definition_slug_count' => count($defSlugs),
            'observed_slug_count' => count($observedSlugs),
            'definition_slugs_never_inbound_in_sample' => $neverTargeted,
            'observed_slugs_not_in_definition' => $traceOnly,
        ],
        'clusters' => [
            'by_cluster_key' => $clusterList,
            'note' => 'internal_crosslink_weight: aynı cluster_key içinde kalan kaynak→hedef kenar ağırlığı toplamı.',
        ],
        'graph_path_resolution_frequency' => array_map(static function (array $e): array {
            return ['slug' => $e['key'], 'resolutions_logged' => $e['weight']];
        }, seo_runtime_trace_aggregate_top_entries($graphPathSlugHits, $topN, true)),
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function seo_runtime_trace_aggregate_load_entries_from_path(string $path, int $maxScanBytes): array
{
    if (!is_readable($path)) {
        return [];
    }
    $size = filesize($path);
    if ($size === false || $size === 0) {
        return [];
    }
    $start = $size > $maxScanBytes ? $size - $maxScanBytes : 0;
    $fh = fopen($path, 'rb');
    if ($fh === false) {
        return [];
    }
    if ($start > 0) {
        fseek($fh, $start);
        fgets($fh);
    }
    $buf = stream_get_contents($fh);
    fclose($fh);
    if ($buf === false || $buf === '') {
        return [];
    }
    $lines = preg_split("/\r\n|\n|\r/", $buf) ?: [];
    $entries = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $row = json_decode($line, true);
        if (is_array($row)) {
            $entries[] = $row;
        }
    }
    return $entries;
}

/**
 * Raporu HTML olarak gösterim (debug / admin); stil minimal, layout’a müdahale etmez.
 *
 * @param array<string, mixed> $report
 */
function seo_runtime_trace_aggregate_report_html(array $report): string
{
    $json = json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false) {
        $json = '{}';
    }
    $esc = htmlspecialchars($json, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $topIn = $report['rankings']['top_inbound_slugs'] ?? [];
    $topOut = $report['rankings']['top_outbound_slugs'] ?? [];
    $fb = $report['fallback_content_pages'] ?? [];
    $redir = $report['redirects'] ?? [];

    $tbl = static function (string $title, array $rows, array $cols): string {
        if ($rows === []) {
            return '<p><strong>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</strong> — veri yok.</p>';
        }
        $h = '<table class="seo-trace-aggregate-table" style="border-collapse:collapse;width:100%;max-width:900px;margin:1em 0;font-size:13px">';
        $h .= '<caption style="text-align:left;font-weight:bold;margin-bottom:6px">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</caption><thead><tr>';
        foreach ($cols as $c) {
            $h .= '<th style="border:1px solid #ccc;padding:6px;text-align:left">' . htmlspecialchars($c, ENT_QUOTES, 'UTF-8') . '</th>';
        }
        $h .= '</tr></thead><tbody>';
        foreach ($rows as $r) {
            if (!is_array($r)) {
                continue;
            }
            $h .= '<tr>';
            foreach ($cols as $c) {
                $v = $r[$c] ?? '';
                $h .= '<td style="border:1px solid #ccc;padding:6px">' . htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $h .= '</tr>';
        }
        $h .= '</tbody></table>';
        return $h;
    };

    $html = '<div class="seo-trace-aggregate" style="font-family:system-ui,sans-serif">';
    $html .= '<p style="color:#444">SEO trace aggregation — kaynak: <code>' . htmlspecialchars((string) ($report['source']['log_path'] ?? ''), ENT_QUOTES, 'UTF-8') . '</code>, '
        . 'satır: ' . (int) ($report['source']['entries_parsed'] ?? 0) . '</p>';
    $html .= $tbl('En çok inbound (hedef) ağırlığı', is_array($topIn) ? $topIn : [], ['slug', 'weighted_inbound']);
    $html .= $tbl('En çok outbound (kaynak) ağırlığı', is_array($topOut) ? $topOut : [], ['slug', 'weighted_outbound']);
    $html .= $tbl('Fallback içerik (head.fallback_content)', is_array($fb) ? $fb : [], ['request_url', 'canonical', 'logged_at_iso']);
    $html .= '<p><strong>Redirect</strong> — toplam: ' . (int) ($redir['total_events'] ?? 0) . '</p>';
    $html .= '<details style="margin:1em 0"><summary>Tam JSON</summary><pre style="overflow:auto;max-height:420px;background:#f8f8f8;padding:12px;border:1px solid #ddd">'
        . $esc . '</pre></details>';
    $html .= '</div>';

    return $html;
}

require_once __DIR__ . '/seo_runtime_trace_optimization.php';
