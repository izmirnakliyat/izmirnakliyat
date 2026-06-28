<?php
/**
 * CE Production Stabilization — raporlama (yeni engine/prompt yok).
 * QC fail analizi, editör fingerprint özeti, deploy dosya kontrolü.
 */
declare(strict_types=1);

const MYNAK_CE_PIPELINE_VERSION = '2026.05-stabilization-v1';

/** Canlıda zorunlu CE dosyaları (göreli: site kökü). */
function mynak_ce_required_deploy_files(): array
{
    return [
        'includes/mynak_local_seo_content_engine.php',
        'includes/mynak_ce_production.php',
        'includes/mynak_ce_quality_model.php',
        'includes/mynak_ce_stabilization.php',
        'includes/auto_blog_ce_adapter.php',
        'includes/blog_bulk_content_refresh_lib.php',
        'includes/mynak_search_intent_discovery.php',
        'admin/includes/auto_blog_functions.php',
        'admin/auto_blog_settings.php',
        'admin/blog_review.php',
        'admin/blog_bulk_refresh.php',
        'admin/content_roadmap.php',
        'admin/ce_stabilization.php',
        'admin/ajax/auto_blog_generate.php',
        'admin/ajax/auto_blog_publish.php',
        'admin/ajax/blog_ce_regenerate.php',
    ];
}

/**
 * @return array{ok: bool, missing: list<string>, version: string}
 */
function mynak_ce_deploy_file_check(string $root): array
{
    $missing = [];
    foreach (mynak_ce_required_deploy_files() as $rel) {
        $path = rtrim($root, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        if (!is_file($path)) {
            $missing[] = $rel;
        }
    }

    return [
        'ok' => $missing === [],
        'missing' => $missing,
        'version' => MYNAK_CE_PIPELINE_VERSION,
    ];
}

/**
 * @return array<string, int>
 */
function mynak_ce_stab_parse_critical_line(string $notes): array
{
    $counts = [];
    if (!preg_match('/Kritik hatalar:\s*(.+)$/mi', $notes, $m)) {
        return $counts;
    }
    $part = trim($m[1]);
    if ($part === '' || $part === 'yok') {
        return $counts;
    }
    foreach (preg_split('/\s*,\s*/', $part) as $key) {
        $key = trim($key);
        if ($key !== '') {
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
    }

    return $counts;
}

/**
 * Son N gün CE/QC aktivitesi.
 *
 * @return array{
 *   days: int,
 *   total_ai: int,
 *   qc_pass: int,
 *   qc_warn: int,
 *   qc_fail: int,
 *   durum_revision: int,
 *   fail_reasons: array<string, int>,
 *   meta_fail_reasons: array<string, int>,
 *   profile_mix: array<string, int>,
 *   flow_mix: array<string, int>,
 *   recent_fails: list<array<string, mixed>>
 * }
 */
function mynak_ce_stab_qc_report(mysqli $conn, int $days = 30): array
{
    br_ensure_content_engine_meta_column($conn);
    $since = date('Y-m-d H:i:s', strtotime('-' . max(1, $days) . ' days'));
    $sql = "SELECT id, baslik, slug, durum, editor_notes, content_engine_meta, created_at, updated_at, is_ai_generated
            FROM blog_posts
            WHERE is_ai_generated = 1
              AND COALESCE(updated_at, created_at) >= ?
            ORDER BY COALESCE(updated_at, created_at) DESC
            LIMIT 500";
    $stmt = $conn->prepare($sql);
    $report = [
        'days' => $days,
        'total_ai' => 0,
        'qc_pass' => 0,
        'qc_warn' => 0,
        'qc_fail' => 0,
        'durum_revision' => 0,
        'fail_reasons' => [],
        'meta_fail_reasons' => [],
        'profile_mix' => [],
        'flow_mix' => [],
        'recent_fails' => [],
    ];
    if (!$stmt) {
        return $report;
    }
    $stmt->bind_param('s', $since);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        ++$report['total_ai'];
        $notes = (string) ($row['editor_notes'] ?? '');
        $meta = mynak_ce_parse_post_meta($row);
        $status = (string) ($meta['qc_status'] ?? '');
        if ($status === '') {
            if (str_contains($notes, '[QC_GATE] FAIL') || str_contains($notes, '[QC_FAIL]')) {
                $status = 'FAIL';
            } elseif (str_contains($notes, '[QC_GATE] WARN')) {
                $status = 'WARN';
            } elseif (str_contains($notes, '[QC_GATE] PASS')) {
                $status = 'PASS';
            }
        }
        if ($status === 'PASS') {
            ++$report['qc_pass'];
        } elseif ($status === 'WARN') {
            ++$report['qc_warn'];
        } elseif ($status === 'FAIL' || (int) ($row['durum'] ?? 0) === 2) {
            ++$report['qc_fail'];
        }
        if ((int) ($row['durum'] ?? 0) === 2) {
            ++$report['durum_revision'];
        }
        $prof = (string) ($meta['profile_type'] ?? $meta['gsc_feedback']['profile_type'] ?? '');
        $flow = (string) ($meta['flow_type'] ?? $meta['gsc_feedback']['flow_type'] ?? '');
        if ($prof !== '') {
            $report['profile_mix'][$prof] = ($report['profile_mix'][$prof] ?? 0) + 1;
        }
        if ($flow !== '') {
            $report['flow_mix'][$flow] = ($report['flow_mix'][$flow] ?? 0) + 1;
        }
        foreach (mynak_ce_stab_parse_critical_line($notes) as $k => $c) {
            $report['fail_reasons'][$k] = ($report['fail_reasons'][$k] ?? 0) + $c;
        }
        $crit = $meta['qc_critical_keys'] ?? $meta['scores']['qc_critical_keys'] ?? [];
        if (is_array($crit)) {
            foreach ($crit as $k) {
                $k = (string) $k;
                if ($k !== '') {
                    $report['meta_fail_reasons'][$k] = ($report['meta_fail_reasons'][$k] ?? 0) + 1;
                }
            }
        }
        if ($status === 'FAIL' && count($report['recent_fails']) < 15) {
            $report['recent_fails'][] = [
                'id' => (int) $row['id'],
                'baslik' => (string) $row['baslik'],
                'slug' => (string) $row['slug'],
                'at' => (string) ($row['updated_at'] ?? $row['created_at']),
                'reasons' => array_keys(mynak_ce_stab_parse_critical_line($notes)),
            ];
        }
    }
    arsort($report['fail_reasons']);
    arsort($report['meta_fail_reasons']);

    return $report;
}

/**
 * Editör fingerprint / dokunuş özeti.
 *
 * @return array{touch_count: int, fields: array<string, int>, samples: list<array<string, mixed>>}
 */
function mynak_ce_stab_editor_report(mysqli $conn, int $days = 30): array
{
    $since = date('Y-m-d H:i:s', strtotime('-' . max(1, $days) . ' days'));
    $sql = "SELECT id, baslik, content_engine_meta, editor_notes, reviewed_at, updated_at, created_at
            FROM blog_posts
            WHERE (editor_notes LIKE '%[EDITOR_FP]%' OR content_engine_meta LIKE '%editor_fingerprint%')
              AND COALESCE(reviewed_at, updated_at, created_at) >= ?
            ORDER BY COALESCE(reviewed_at, updated_at) DESC
            LIMIT 200";
    $stmt = $conn->prepare($sql);
    $out = ['touch_count' => 0, 'fields' => [], 'samples' => []];
    if (!$stmt) {
        return $out;
    }
    $stmt->bind_param('s', $since);
    $stmt->execute();
    $res = $stmt->get_result();
    $fieldKeys = ['intro_type', 'profile_type', 'faq_count', 'cta_style', 'flow_type', 'local_entity', 'title', 'internal_links'];
    while ($row = $res->fetch_assoc()) {
        ++$out['touch_count'];
        $meta = mynak_ce_parse_post_meta($row);
        $fp = is_array($meta['editor_fingerprint'] ?? null) ? $meta['editor_fingerprint'] : [];
        foreach ($fieldKeys as $fk) {
            if (!empty($fp[$fk]) || !empty($meta[$fk])) {
                $out['fields'][$fk] = ($out['fields'][$fk] ?? 0) + 1;
            }
        }
        if (count($out['samples']) < 10) {
            $out['samples'][] = [
                'id' => (int) $row['id'],
                'baslik' => (string) $row['baslik'],
                'fp' => $fp,
            ];
        }
    }
    arsort($out['fields']);

    return $out;
}

/**
 * Roadmap: yüksek öncelikli cluster önerisi (max 30).
 *
 * @return list<array<string, mixed>>
 */
function mynak_ce_stab_roadmap_picks(mysqli $conn, int $limit = 30): array
{
    sid_ensure_roadmap_table($conn);
    $sql = "SELECT * FROM content_roadmap_clusters
            WHERE status = 'pending'
              AND merge_recommendation != 'merge'
              AND (local_intent = 1 OR conversion_score >= 55 OR intent_type IN ('transactional','local'))
            ORDER BY priority_score DESC, conversion_score DESC, topical_authority_score DESC
            LIMIT " . max(1, min(50, $limit));
    $q = $conn->query($sql);
    $rows = [];
    if ($q) {
        while ($r = $q->fetch_assoc()) {
            $rows[] = $r;
        }
    }

    return $rows;
}

/**
 * Operasyon özeti (tek ekran).
 *
 * @return array<string, mixed>
 */
function mynak_ce_stab_operations_snapshot(mysqli $conn, string $siteRoot): array
{
    mynak_ce_ensure_settings($conn);
    $today = date('Y-m-d');
    $abUsed = (int) br_setting_get($conn, AB_CE_QUOTA_USED_SETTING, '0');
    $abDay = br_setting_get($conn, AB_CE_QUOTA_DAY_SETTING, '');
    if ($abDay !== $today) {
        $abUsed = 0;
    }
    $brUsed = (int) br_setting_get($conn, 'blog_bulk_refresh_quota_used', '0');
    $brDay = br_setting_get($conn, 'blog_bulk_refresh_quota_day', '');
    if ($brDay !== $today) {
        $brUsed = 0;
    }

    return [
        'pipeline_version' => MYNAK_CE_PIPELINE_VERSION,
        'cron_enabled' => mynak_ce_cron_enabled($conn),
        'ab_daily_max' => ab_ce_daily_max($conn),
        'ab_quota_used_today' => $abUsed,
        'br_daily_max' => br_daily_max($conn),
        'br_quota_used_today' => $brUsed,
        'deploy' => mynak_ce_deploy_file_check($siteRoot),
        'qc' => mynak_ce_stab_qc_report($conn, 30),
        'editor' => mynak_ce_stab_editor_report($conn, 30),
        'roadmap_pending' => (int) ($conn->query("SELECT COUNT(*) AS c FROM content_roadmap_clusters WHERE status='pending'")->fetch_assoc()['c'] ?? 0),
        'roadmap_picks' => mynak_ce_stab_roadmap_picks($conn, 30),
    ];
}
