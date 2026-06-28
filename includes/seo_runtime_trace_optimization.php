<?php
/**
 * SEO trace tabanlı salt okunur iyileştirme önerileri — canlı çıktı veya pipeline’ı değiştirmez.
 *
 * @see seo_runtime_trace_optimization_suggestions()
 */
declare(strict_types=1);

/**
 * @param list<int|float> $values
 */
function seo_runtime_trace_opt_median_positive(array $values): float
{
    $values = array_values(array_filter($values, static function ($v): bool {
        return (float) $v > 0;
    }));
    if ($values === []) {
        return 0.0;
    }
    sort($values);
    $n = count($values);
    $m = intdiv($n, 2);
    return $n % 2 ? (float) $values[$m] : ((float) $values[$m - 1] + (float) $values[$m]) / 2.0;
}

/**
 * @return list<string>
 */
function seo_runtime_trace_opt_cluster_member_slugs(string $clusterKey, array $defs): array
{
    $members = [];
    foreach (array_keys($defs) as $slug) {
        if (seo_runtime_trace_aggregate_cluster_key_for_slug((string) $slug, $defs) === $clusterKey) {
            $members[] = (string) $slug;
        }
    }
    sort($members);
    return $members;
}

/**
 * Tanımda $target related listesinde geçen kaynak slug’lar.
 *
 * @return list<string>
 */
function seo_runtime_trace_opt_definition_sources_listing_target(string $target, array $defs): array
{
    $out = [];
    foreach ($defs as $src => $meta) {
        $rel = $meta['related'] ?? [];
        if (!is_array($rel)) {
            continue;
        }
        foreach ($rel as $r) {
            if ((string) $r === $target) {
                $out[] = (string) $src;
                break;
            }
        }
    }
    sort($out);
    return $out;
}

/**
 * Analytics raporundan salt okunur SEO önerileri (uygulama yok).
 *
 * Seçenekler: seo_runtime_trace_aggregate_report ile aynıları + ek olarak:
 * - orphan_suggest_sources_max (int, varsayılan 8)
 * - cluster_weak_bottom_fraction (0–1, varsayılan 0.33) — en düşük iç çapraz ağırlıklı cluster payı
 * - cluster_suggested_edges_max (int, varsayılan 24) — zayıf cluster başına max kenar önerisi
 * - outbound_high_ratio_vs_median (float, varsayılan 2.0)
 * - outbound_high_absolute_min (int, varsayılan 15) — medyan 0 ise eşik
 *
 * @param array<string, mixed> $opts
 * @return array<string, mixed>
 */
function seo_runtime_trace_optimization_suggestions(array $opts = []): array
{
    $report = seo_runtime_trace_aggregate_report($opts);
    $defs = seo_runtime_trace_aggregate_graph_definitions();

    $orphanSourceMax = (int) ($opts['orphan_suggest_sources_max'] ?? 8);
    if ($orphanSourceMax < 1) {
        $orphanSourceMax = 8;
    }
    $weakFraction = (float) ($opts['cluster_weak_bottom_fraction'] ?? 0.33);
    if ($weakFraction < 0.05) {
        $weakFraction = 0.05;
    }
    if ($weakFraction > 0.95) {
        $weakFraction = 0.95;
    }
    $edgesMax = (int) ($opts['cluster_suggested_edges_max'] ?? 24);
    if ($edgesMax < 1) {
        $edgesMax = 24;
    }
    $outRatio = (float) ($opts['outbound_high_ratio_vs_median'] ?? 2.0);
    if ($outRatio < 1.1) {
        $outRatio = 1.1;
    }
    $outAbsMin = (int) ($opts['outbound_high_absolute_min'] ?? 15);
    if ($outAbsMin < 1) {
        $outAbsMin = 15;
    }

    $inbound = $report['link_graph']['inbound_weight_by_slug'] ?? [];
    $outbound = $report['link_graph']['outbound_weight_by_slug'] ?? [];
    if (!is_array($inbound)) {
        $inbound = [];
    }
    if (!is_array($outbound)) {
        $outbound = [];
    }

    $orphanCandidates = $report['rankings']['orphan_candidates_definition_slugs'] ?? [];
    if (!is_array($orphanCandidates)) {
        $orphanCandidates = [];
    }

    $orphanFixes = [];
    foreach ($orphanCandidates as $row) {
        if (!is_array($row) || empty($row['slug'])) {
            continue;
        }
        $target = (string) $row['slug'];
        $inW = (int) ($row['inbound_weight'] ?? ($inbound[$target] ?? 0));
        $ck = seo_runtime_trace_aggregate_cluster_key_for_slug($target, $defs);
        $members = $ck !== '_external' ? seo_runtime_trace_opt_cluster_member_slugs($ck, $defs) : [];
        $defSources = seo_runtime_trace_opt_definition_sources_listing_target($target, $defs);

        $candidates = [];
        foreach ($members as $mem) {
            if ((string) $mem !== $target) {
                $candidates[] = (string) $mem;
            }
        }
        usort($candidates, static function (string $a, string $b) use ($outbound): int {
            $wa = isset($outbound[$a]) ? (int) $outbound[$a] : 0;
            $wb = isset($outbound[$b]) ? (int) $outbound[$b] : 0;
            return $wa <=> $wb;
        });

        $suggestedSources = [];
        $seen = [];
        if ($ck !== '_external' && $target !== $ck) {
            $suggestedSources[] = [
                'from_slug' => $ck,
                'to_slug' => $target,
                'reason' => 'Cluster pillar sayfası; zayıf inbound için üst bağlantı önerilir.',
                'priority' => 'high',
                'current_outbound_weight_in_sample' => (int) ($outbound[$ck] ?? 0),
            ];
            $seen[$ck] = true;
        }
        foreach ($defSources as $ds) {
            if ($ds === $target || isset($seen[$ds])) {
                continue;
            }
            $seen[$ds] = true;
            $suggestedSources[] = [
                'from_slug' => $ds,
                'to_slug' => $target,
                'reason' => 'seo_rt_pillar_cluster_definitions: related içinde bu hedef zaten tanımlı; içerikte bağlantıyı güçlendirin.',
                'priority' => 'high',
                'current_outbound_weight_in_sample' => (int) ($outbound[$ds] ?? 0),
            ];
        }
        foreach ($candidates as $cand) {
            if (count($suggestedSources) >= $orphanSourceMax) {
                break;
            }
            if (isset($seen[$cand])) {
                continue;
            }
            $seen[$cand] = true;
            $suggestedSources[] = [
                'from_slug' => $cand,
                'to_slug' => $target,
                'reason' => 'Aynı cluster üyesi; trace örneklemine göre düşük outbound veren sayfalar önceliklendirildi (dağılım dengesi).',
                'priority' => 'normal',
                'current_outbound_weight_in_sample' => (int) ($outbound[$cand] ?? 0),
            ];
        }

        $orphanFixes[] = [
            'target_slug' => $target,
            'inbound_weight_in_sample' => $inW,
            'cluster_key' => $ck,
            'definition_sources_listing_target' => $defSources,
            'suggested_internal_links' => $suggestedSources,
        ];
    }

    $clusterRows = $report['clusters']['by_cluster_key'] ?? [];
    if (!is_array($clusterRows)) {
        $clusterRows = [];
    }
    $sortedByCross = $clusterRows;
    usort($sortedByCross, static function (array $a, array $b): int {
        return ($a['internal_crosslink_weight'] ?? 0) <=> ($b['internal_crosslink_weight'] ?? 0);
    });
    $nCl = count($sortedByCross);
    $weakCount = max(1, (int) ceil($nCl * $weakFraction));
    $weakSet = [];
    foreach (array_slice($sortedByCross, 0, $weakCount) as $wc) {
        if (is_array($wc) && isset($wc['cluster_key'])) {
            $weakSet[(string) $wc['cluster_key']] = true;
        }
    }

    $clusterPlans = [];
    foreach ($clusterRows as $crow) {
        if (!is_array($crow) || empty($crow['cluster_key'])) {
            continue;
        }
        $ck = (string) $crow['cluster_key'];
        $cross = (int) ($crow['internal_crosslink_weight'] ?? 0);
        $isWeak = isset($weakSet[$ck]);
        if (!$isWeak) {
            continue;
        }
        $edgeSeen = [];
        $suggestedEdges = [];
        foreach ($defs as $from => $meta) {
            if (seo_runtime_trace_aggregate_cluster_key_for_slug((string) $from, $defs) !== $ck) {
                continue;
            }
            $rel = $meta['related'] ?? [];
            if (!is_array($rel)) {
                continue;
            }
            foreach ($rel as $to) {
                $to = (string) $to;
                if ($to === '' || $to === $from) {
                    continue;
                }
                if (seo_runtime_trace_aggregate_cluster_key_for_slug($to, $defs) !== $ck) {
                    continue;
                }
                $k = $from . '→' . $to;
                if (isset($edgeSeen[$k])) {
                    continue;
                }
                $edgeSeen[$k] = true;
                $suggestedEdges[] = [
                    'from_slug' => (string) $from,
                    'to_slug' => $to,
                    'reason' => 'Aynı cluster + tanım related; iç çapraz bağ önerisi (salt okunur).',
                ];
                if (count($suggestedEdges) >= $edgesMax) {
                    break 2;
                }
            }
        }
        $clusterPlans[] = [
            'cluster_key' => $ck,
            'internal_crosslink_weight_in_sample' => $cross,
            'cluster_inbound_total' => (int) ($crow['cluster_inbound_total'] ?? 0),
            'member_slugs' => $crow['member_slugs'] ?? [],
            'weak_by_bottom_fraction' => true,
            'suggested_intracluster_edges' => $suggestedEdges,
        ];
    }

    $outVals = [];
    foreach (array_values($outbound) as $v) {
        $outVals[] = (int) $v;
    }
    $medianOut = seo_runtime_trace_opt_median_positive($outVals);
    if ($medianOut <= 0) {
        $highThreshold = $outAbsMin;
    } else {
        $highThreshold = (int) ceil($medianOut * $outRatio);
    }

    $balancing = [];
    foreach ($outbound as $slug => $w) {
        $w = (int) $w;
        if ($w < $highThreshold) {
            continue;
        }
        if (!isset($defs[$slug])) {
            continue;
        }
        $ck = seo_runtime_trace_aggregate_cluster_key_for_slug((string) $slug, $defs);
        if ($ck === '_external') {
            continue;
        }
        $peers = seo_runtime_trace_opt_cluster_member_slugs($ck, $defs);
        $peersLow = [];
        foreach ($peers as $p) {
            if ($p === $slug) {
                continue;
            }
            $pw = (int) ($outbound[$p] ?? 0);
            if ($medianOut > 0 && $pw < $medianOut) {
                $peersLow[] = ['slug' => $p, 'outbound_weight_in_sample' => $pw];
            }
        }
        usort($peersLow, static function (array $a, array $b): int {
            return ($a['outbound_weight_in_sample'] <=> $b['outbound_weight_in_sample']);
        });
        $peersLow = array_slice($peersLow, 0, $orphanSourceMax);
        $balancing[] = [
            'slug' => (string) $slug,
            'outbound_weight_in_sample' => $w,
            'median_outbound_reference' => $medianOut,
            'high_threshold_used' => $highThreshold,
            'peer_slugs_with_lower_outbound' => $peersLow,
            'note' => 'Yüksek outbound; konu bağlantılarını eş cluster’da daha düşük yükü olan sayfalara dağıtmayı değerlendirin (manuel).',
        ];
    }
    usort($balancing, static function (array $a, array $b): int {
        return ($b['outbound_weight_in_sample'] <=> $a['outbound_weight_in_sample']);
    });

    $fallbackPages = $report['fallback_content_pages'] ?? [];
    if (!is_array($fallbackPages)) {
        $fallbackPages = [];
    }
    $fallbackActions = [];
    foreach ($fallbackPages as $fb) {
        if (!is_array($fb)) {
            continue;
        }
        $fallbackActions[] = [
            'request_url' => (string) ($fb['request_url'] ?? ''),
            'canonical' => (string) ($fb['canonical'] ?? ''),
            'logged_at_iso' => (string) ($fb['logged_at_iso'] ?? ''),
            'suggest_meta_noindex' => true,
            'suggest_content_improvement' => true,
            'note' => 'Trace’te fallback_content işaretli; şablonda mynak_seo_fallback_content veya içerik kalitesi gözden geçirilmeli (otomatik uygulama yok).',
        ];
    }

    return [
        'generated_at_iso' => date('c'),
        'mode' => 'suggestions_read_only',
        'aggregate' => [
            'generated_at_iso' => $report['generated_at_iso'] ?? '',
            'source' => $report['source'] ?? [],
        ],
        'orphan_fixes' => $orphanFixes,
        'cluster_strengthening' => $clusterPlans,
        'link_distribution' => [
            'parameters' => [
                'outbound_median_positive_in_sample' => $medianOut,
                'outbound_high_threshold' => $highThreshold,
                'outbound_high_ratio_vs_median' => $outRatio,
            ],
            'high_outbound_pages' => $balancing,
        ],
        'fallback_actions' => $fallbackActions,
        'disclaimer' => [
            'Otomatik değişiklik yok; öneriler trace örneklemine ve seo_rt_pillar_cluster_definitions() yapısına dayanır.',
            'Uygulama öncesi editoryal ve teknik doğrulama gereklidir.',
        ],
    ];
}
