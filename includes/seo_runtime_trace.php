<?php
/**
 * SEO pipeline observability — yalnızca MYNAK_SEO_PIPELINE_TRACE=true iken çalışır; HTML çıktısını etkilemez.
 *
 * Log analizi (sadece dosya okuma): seo_runtime_trace_aggregate_report(), seo_runtime_trace_aggregate_report_html(),
 * seo_runtime_trace_optimization_suggestions() (salt okunur öneriler).
 *
 * @see seo_runtime_pipeline_trace()
 */
declare(strict_types=1);

function seo_runtime_pipeline_trace_enabled(): bool
{
    return defined('MYNAK_SEO_PIPELINE_TRACE') && MYNAK_SEO_PIPELINE_TRACE === true;
}

/**
 * @return array<string, mixed>
 */
function seo_runtime_trace_empty_shell(): array
{
    return [
        'trace_version' => 1,
        'request_started_at' => microtime(true),
        'request_url' => (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') ? seo_runtime_trace_request_url() : '',
        'redirect' => null,
        'head' => null,
        'page_type_decision' => null,
        'internal_links' => null,
        'graph_path_resolutions' => [],
    ];
}

function seo_runtime_trace_request_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    return $host !== '' ? ($scheme . '://' . $host . $uri) : $uri;
}

function seo_runtime_trace_register_shutdown_once(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    register_shutdown_function(static function (): void {
        seo_runtime_trace_shutdown_write();
    });
}

function seo_runtime_trace_bootstrap_request(): void
{
    if (!seo_runtime_pipeline_trace_enabled()) {
        return;
    }
    seo_runtime_trace_register_shutdown_once();
    $GLOBALS['MYNAK_SEO_PIPELINE_TRACE_DATA'] = seo_runtime_trace_empty_shell();
}

function seo_runtime_trace_ensure_init(): void
{
    if (!seo_runtime_pipeline_trace_enabled()) {
        return;
    }
    seo_runtime_trace_register_shutdown_once();
    if (!isset($GLOBALS['MYNAK_SEO_PIPELINE_TRACE_DATA']) || !is_array($GLOBALS['MYNAK_SEO_PIPELINE_TRACE_DATA'])) {
        $GLOBALS['MYNAK_SEO_PIPELINE_TRACE_DATA'] = seo_runtime_trace_empty_shell();
    }
}

function seo_runtime_trace_log_path(): string
{
    return dirname(__DIR__) . '/logs/seo_pipeline_trace.log';
}

function seo_runtime_trace_shutdown_write(): void
{
    if (!seo_runtime_pipeline_trace_enabled()) {
        return;
    }
    $data = $GLOBALS['MYNAK_SEO_PIPELINE_TRACE_DATA'] ?? null;
    if (!is_array($data)) {
        return;
    }
    $path = seo_runtime_trace_log_path();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $data['logged_at_iso'] = date('c');
    $line = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Toplanan pipeline izi (trace kapalıyken boş dizi).
 *
 * @return array<string, mixed>
 */
function seo_runtime_pipeline_trace(): array
{
    if (!seo_runtime_pipeline_trace_enabled()) {
        return [];
    }
    $d = $GLOBALS['MYNAK_SEO_PIPELINE_TRACE_DATA'] ?? null;
    return is_array($d) ? $d : [];
}

function seo_runtime_trace_record_redirect(string $from, string $to, int $code): void
{
    if (!seo_runtime_pipeline_trace_enabled()) {
        return;
    }
    seo_runtime_trace_ensure_init();
    $GLOBALS['MYNAK_SEO_PIPELINE_TRACE_DATA']['redirect'] = [
        'from' => $from,
        'to' => $to,
        'code' => $code,
    ];
}

/**
 * Sayfa türü karar izi — canonical_page_type_decide() çıktısı (MYNAK_SEO_PIPELINE_TRACE=1).
 *
 * @param array<string, mixed> $decision
 */
function seo_runtime_trace_record_page_type_decision(array $decision): void
{
    if (!seo_runtime_pipeline_trace_enabled()) {
        return;
    }
    seo_runtime_trace_ensure_init();
    if (($GLOBALS['MYNAK_SEO_PIPELINE_TRACE_DATA']['page_type_decision'] ?? null) !== null) {
        return;
    }
    $GLOBALS['MYNAK_SEO_PIPELINE_TRACE_DATA']['page_type_decision'] = $decision;
}

function seo_runtime_trace_record_head(
    string $canonical,
    ?string $metaRobots,
    bool $fallbackContentFlag,
    bool $metaRobotsTagEmitted,
    ?string $resolvedPageType = null,
    ?string $pageTypeFallbackReason = null
): void {
    if (!seo_runtime_pipeline_trace_enabled()) {
        return;
    }
    seo_runtime_trace_ensure_init();
    $robotsBucket = 'indexable';
    if ($metaRobots !== null && $metaRobots !== '') {
        $robotsBucket = (stripos($metaRobots, 'noindex') !== false) ? 'noindex' : $metaRobots;
    }
    $GLOBALS['MYNAK_SEO_PIPELINE_TRACE_DATA']['head'] = [
        'canonical' => $canonical,
        'robots' => $robotsBucket,
        'robots_directive_raw' => $metaRobots,
        'meta_robots_tag_emitted' => $metaRobotsTagEmitted,
        'fallback_content' => $fallbackContentFlag,
        'resolved_page_type' => $resolvedPageType,
        'page_type_fallback_reason' => $pageTypeFallbackReason,
    ];
}

function seo_runtime_trace_record_graph_path(string $slug, string $path): void
{
    if (!seo_runtime_pipeline_trace_enabled()) {
        return;
    }
    seo_runtime_trace_ensure_init();
    $GLOBALS['MYNAK_SEO_PIPELINE_TRACE_DATA']['graph_path_resolutions'][] = [
        'slug' => $slug,
        'path' => $path,
    ];
}

/**
 * @param list<string> $orderedTargetSlugs
 * @param list<string> $renderedTargetSlugs
 */
function seo_runtime_trace_record_internal_links(
    string $sourceSlug,
    array $orderedTargetSlugs,
    int $emittedCount,
    bool $graphMiss,
    array $renderedTargetSlugs,
    array $meta = []
): void {
    if (!seo_runtime_pipeline_trace_enabled()) {
        return;
    }
    seo_runtime_trace_ensure_init();
    $counts = [];
    foreach ($renderedTargetSlugs as $t) {
        $counts[$t] = ($counts[$t] ?? 0) + 1;
    }
    $GLOBALS['MYNAK_SEO_PIPELINE_TRACE_DATA']['internal_links'] = array_merge([
        'source_slug' => $sourceSlug,
        'ordered_target_slugs' => $orderedTargetSlugs,
        'emitted_count' => $emittedCount,
        'graph_miss' => $graphMiss,
        'outbound_to_slug_counts' => $counts,
    ], $meta);
}

/**
 * Log dosyasında fallback_content=true olan son kayıtlar (en yeniler sonda).
 *
 * @return list<array<string, mixed>>
 */
function seo_runtime_trace_fallback_reports_from_log(int $maxLines = 500, int $maxScanBytes = 1048576): array
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
    $hits = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $row = json_decode($line, true);
        if (!is_array($row)) {
            continue;
        }
        $fb = $row['head']['fallback_content'] ?? false;
        if ($fb === true) {
            $hits[] = $row;
        }
    }
    if (count($hits) > $maxLines) {
        $hits = array_slice($hits, -$maxLines);
    }
    return $hits;
}

require_once __DIR__ . '/seo_runtime_trace_aggregate.php';
