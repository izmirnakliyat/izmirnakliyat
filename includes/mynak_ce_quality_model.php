<?php
/**
 * MY Nakliyat CE — Production quality model (v1).
 * Structure score ≠ editorial quality ≠ QC gate.
 * Yayın kararı yalnızca QC Gate (PASS/WARN/FAIL) üzerinden.
 */
declare(strict_types=1);

const MYNAK_CE_SCORES_VERSION = 1;

/**
 * Topic-depth: hizmet bazlı coverage beklentisi (kelime tek başına değil).
 *
 * @return array<string, array{label: string, depth: string, word_soft: array{0:int,1:int}, coverage_min: int}>
 */
function mynak_ce_topic_depth_profiles(): array
{
    return [
        'vinc_kiralama' => [
            'label' => 'Vinç kiralama',
            'depth' => 'shallow',
            'word_soft' => [450, 950],
            'coverage_min' => 45,
        ],
        'parca_esya' => [
            'label' => 'Parça eşya',
            'depth' => 'shallow',
            'word_soft' => [500, 1100],
            'coverage_min' => 48,
        ],
        'asansorlu' => [
            'label' => 'Asansörlü',
            'depth' => 'medium',
            'word_soft' => [700, 1400],
            'coverage_min' => 52,
        ],
        'depolama' => [
            'label' => 'Depolama',
            'depth' => 'medium',
            'word_soft' => [800, 1500],
            'coverage_min' => 52,
        ],
        'evden_eve' => [
            'label' => 'Evden eve',
            'depth' => 'medium',
            'word_soft' => [900, 1600],
            'coverage_min' => 55,
        ],
        'izmir_evden_eve' => [
            'label' => 'İzmir evden eve',
            'depth' => 'medium',
            'word_soft' => [950, 1700],
            'coverage_min' => 58,
        ],
        'sehirlerarasi' => [
            'label' => 'Şehirlerarası',
            'depth' => 'deep',
            'word_soft' => [1100, 2100],
            'coverage_min' => 60,
        ],
        'kurumsal' => [
            'label' => 'Kurumsal',
            'depth' => 'deep',
            'word_soft' => [1000, 1900],
            'coverage_min' => 58,
        ],
        'default' => [
            'label' => 'Genel',
            'depth' => 'medium',
            'word_soft' => [800, 1600],
            'coverage_min' => 50,
        ],
    ];
}

function mynak_ce_resolve_depth_profile(array $pack, array $post = []): array
{
    $key = (string) ($pack['depth_tier'] ?? $pack['service_group'] ?? '');
    if ($key === '') {
        $hay = mb_strtolower(
            (string) ($post['baslik'] ?? '') . ' ' . (string) ($post['etiketler'] ?? '') . ' ' . (string) ($pack['service_intent'] ?? ''),
            'UTF-8'
        );
        if (str_contains($hay, 'vinç') || str_contains($hay, 'vinc')) {
            $key = 'vinc_kiralama';
        } elseif (str_contains($hay, 'şehirlerarası') || str_contains($hay, 'sehirlerarasi')) {
            $key = 'sehirlerarasi';
        } elseif (str_contains($hay, 'parça') || str_contains($hay, 'parca')) {
            $key = 'parca_esya';
        } elseif (str_contains($hay, 'asansör') || str_contains($hay, 'asansor')) {
            $key = 'asansorlu';
        } elseif (str_contains($hay, 'depo')) {
            $key = 'depolama';
        } elseif (str_contains($hay, 'izmir') && str_contains($hay, 'evden')) {
            $key = 'izmir_evden_eve';
        } else {
            $key = 'evden_eve';
        }
    }
    $profiles = mynak_ce_topic_depth_profiles();

    return $profiles[$key] ?? $profiles['default'];
}

/**
 * Yapı skoru (0–100): H2/H3/liste yoğunluğu — eski ai_quality_score ile aynı mantık.
 */
function mynak_ce_structure_score(string $html): int
{
    $plain = strip_tags($html);
    $words = preg_match_all('/[\w\p{L}]+/u', $plain);
    $h2 = preg_match_all('/<h2[^>]*>/i', $html);
    $h3 = preg_match_all('/<h3[^>]*>/i', $html);
    $lists = preg_match_all('/<(ul|ol)[^>]*>/i', $html);
    $strong = preg_match_all('/<strong[^>]*>/i', $html);

    $score = 0;
    $score += min(40, (int) ($words / 25));
    $score += min(30, $h2 * 6);
    $score += min(15, $h3 * 3);
    $score += min(15, ($lists * 4) + ($strong * 1));

    return max(0, min(100, (int) $score));
}

/**
 * QC Gate durumu: FAIL | WARN | PASS (yayın kararı buradan).
 */
function mynak_ce_qc_gate_status(array $qc): string
{
    if (!($qc['pass'] ?? false)) {
        return 'FAIL';
    }
    $warn = $qc['warning_keys'] ?? [];
    if (is_array($warn) && $warn !== []) {
        return 'WARN';
    }

    return 'PASS';
}

/**
 * @param array<string, mixed> $pack
 * @param array<string, mixed> $qc
 */
function mynak_ce_coverage_score(string $html, array $pack, array $qc): int
{
    $profile = mynak_ce_resolve_depth_profile($pack);
    $wc = (int) ($qc['checks']['word_count'] ?? 0);
    if ($wc <= 0 && function_exists('br_word_count_html')) {
        $wc = br_word_count_html($html);
    }
    [$softMin, $softMax] = $profile['word_soft'];
    $depth = (string) ($profile['depth'] ?? 'medium');

    if ($wc >= $softMin && $wc <= $softMax) {
        $wordPts = 35;
    } elseif ($wc >= (int) ($softMin * 0.75) && $wc <= (int) ($softMax * 1.2)) {
        $wordPts = 22;
    } elseif ($depth === 'shallow' && $wc >= (int) ($softMin * 0.6)) {
        $wordPts = 28;
    } else {
        $wordPts = max(0, 18 - (int) (abs($wc - (($softMin + $softMax) / 2)) / 40));
    }

    $intentPts = 0;
    $plain = mb_strtolower(strip_tags($html), 'UTF-8');
    $intent = (string) ($pack['intent_type'] ?? $pack['search_intent'] ?? '');
    if ($intent === 'transactional' && (str_contains($plain, 'teklif') || str_contains($plain, 'fiyat'))) {
        $intentPts += 12;
    }
    if ($intent === 'local' && preg_match('/\bizmir\b|karşıyaka|bornova|buca|konak/iu', $plain)) {
        $intentPts += 12;
    }
    if ($intent === 'faq' || (int) ($pack['faq_count'] ?? 0) > 0) {
        $faqOk = (bool) ($qc['checks']['faq_count_ok'] ?? false);
        $intentPts += $faqOk ? 10 : 4;
    }
    if ($intent === 'informational' && preg_match('/<h2[^>]*>/i', $html) >= 3) {
        $intentPts += 8;
    }

    $h2 = preg_match_all('/<h2[^>]*>/i', $html);
    $structurePts = min(18, $h2 * 3);
    if ($depth === 'deep') {
        $structurePts = min(22, $h2 * 4);
    }

    $score = min(100, $wordPts + min(25, $intentPts) + $structurePts);

    return max(0, (int) $score);
}

/**
 * CE-aware editorial score (0–100) — QC sinyalleri + içerik heuristikleri.
 *
 * @param array<string, mixed> $post
 * @param array<string, mixed> $pack
 * @param array<string, mixed> $qc
 */
function mynak_ce_editorial_score(string $html, array $post, array $pack, array $qc): int
{
    $score = 72;
    $checks = is_array($qc['checks'] ?? null) ? $qc['checks'] : [];
    $critical = $qc['critical_fail_keys'] ?? [];
    $warn = $qc['warning_keys'] ?? [];

    $positives = [
        'natural_local' => 8,
        'semantic_faq' => 6,
        'link_variety' => 8,
        'low_repetition' => 10,
        'cta_natural' => 8,
        'no_banned' => 10,
        'behavior_variation' => 6,
    ];

    $plain = strip_tags($html);
    $plainLow = mb_strtolower($plain, 'UTF-8');

    if (!empty($pack['local_entity']) || preg_match('/\bizmir\b|karşıyaka|bornova|buca|konak|gaziemir/iu', $plainLow)) {
        $score += $positives['natural_local'];
    }
    if (!empty($checks['faq_count_ok'])) {
        $score += $positives['semantic_faq'];
    }
    $linkCount = (int) ($checks['internal_link_count'] ?? 0);
    if ($linkCount >= 2 && $linkCount <= 6 && empty($checks['off_whitelist_links'])) {
        $score += $positives['link_variety'];
    }
    if (empty($checks['paragraph_repetition_reasons'])) {
        $score += $positives['low_repetition'];
    }
    if (empty($checks['cta_salesy_hits']) && empty($checks['banned_phrase_hits'])) {
        $score += $positives['cta_natural'] + $positives['no_banned'];
    }
    if (!empty($pack['fatigue_applied']) || !empty($pack['profile_type'])) {
        $score += $positives['behavior_variation'];
    }

    $negMap = [
        'keyword_stuffing' => 22,
        'paragraph_repetition' => 18,
        'spam_cta' => 16,
        'banned' => 20,
        'micro_listy' => 14,
        'title_spam' => 12,
        'internal_links' => 10,
        'off_whitelist_link' => 12,
        'word_range' => 8,
        'h1' => 6,
    ];
    foreach ($critical as $key) {
        $score -= $negMap[$key] ?? 8;
    }
    foreach ($warn as $key) {
        $score -= ($key === 'faq' ? 4 : 3);
    }

    preg_match_all('/<h2[^>]*>/i', $html, $h2m);
    if (count($h2m[0] ?? []) > 9) {
        $score -= 10;
    }
    preg_match_all('/<a\s[^>]*href=/i', $html, $am);
    $anchors = $am[0] ?? [];
    if (count($anchors) > 0) {
        $exact = 0;
        foreach ($anchors as $_) {
            if (preg_match('/>([^<]{3,40})</', $html, $tm)) {
                $t = mb_strtolower(trim($tm[1] ?? ''), 'UTF-8');
                if ($t !== '' && str_contains($plainLow, $t)) {
                    ++$exact;
                }
            }
        }
        if ($exact >= 4) {
            $score -= 8;
        }
    }

    return max(0, min(100, (int) $score));
}

/**
 * @param array<string, mixed> $post
 * @param array<string, mixed> $pack
 * @param array<string, mixed> $qc
 * @return array<string, mixed>
 */
function mynak_ce_compute_score_bundle(string $html, array $post, array $pack, array $qc): array
{
    $structure = mynak_ce_structure_score($html);
    $editorial = mynak_ce_editorial_score($html, $post, $pack, $qc);
    $coverage = mynak_ce_coverage_score($html, $pack, $qc);
    $qcStatus = mynak_ce_qc_gate_status($qc);
    $profile = mynak_ce_resolve_depth_profile($pack, $post);

    return [
        'v' => MYNAK_CE_SCORES_VERSION,
        'structure_score' => $structure,
        'editorial_score' => $editorial,
        'coverage_score' => $coverage,
        'qc_status' => $qcStatus,
        'qc_pass' => (bool) ($qc['pass'] ?? false),
        'qc_warn_keys' => $qc['warning_keys'] ?? [],
        'qc_critical_keys' => $qc['critical_fail_keys'] ?? [],
        'depth_tier' => $profile['depth'] ?? 'medium',
        'depth_profile' => $profile['label'] ?? 'Genel',
        'publish_allowed' => $qcStatus !== 'FAIL',
    ];
}

/**
 * Meta JSON içine skor + GSC alanlarını birleştir.
 */
function mynak_ce_merge_scores_into_meta(string $ceMetaJson, array $scores, array $pack, array $qc): string
{
    $meta = json_decode($ceMetaJson, true);
    if (!is_array($meta)) {
        $meta = [];
    }
    $meta['scores'] = $scores;
    $meta['qc_status'] = $scores['qc_status'] ?? mynak_ce_qc_gate_status($qc);
    $meta['structure_score'] = (int) ($scores['structure_score'] ?? 0);
    $meta['editorial_score'] = (int) ($scores['editorial_score'] ?? 0);
    $meta['coverage_score'] = (int) ($scores['coverage_score'] ?? 0);
    $meta['gsc_feedback'] = [
        'profile_type' => (string) ($pack['profile_type'] ?? ''),
        'flow_type' => (string) ($pack['flow_type'] ?? ''),
        'intro_type' => (string) ($pack['intro_type'] ?? ''),
        'faq_count' => (int) ($pack['faq_count'] ?? 0),
        'cta_style' => (string) ($pack['cta_style'] ?? ''),
        'local_density' => (string) ($pack['local_density'] ?? (!empty($pack['use_local']) ? 'izmir' : 'none')),
        'micro_detail_ids' => $pack['micro_detail_ids'] ?? [],
        'internal_link_pattern' => (string) ($pack['link_entropy'] ?? $pack['internal_link_pattern'] ?? ''),
        'title_style' => (string) ($pack['title_style'] ?? 'yeni_baslik_line'),
        'word_range' => [(int) ($pack['word_min'] ?? 0), (int) ($pack['word_max'] ?? 0)],
        'depth_tier' => (string) ($scores['depth_tier'] ?? ''),
        'qc_status' => (string) ($scores['qc_status'] ?? ''),
        'structure_score' => (int) ($scores['structure_score'] ?? 0),
        'editorial_score' => (int) ($scores['editorial_score'] ?? 0),
        'coverage_score' => (int) ($scores['coverage_score'] ?? 0),
    ];

    return json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Satırdan skor + QC özeti (liste/detay UI).
 *
 * @param array<string, mixed> $row
 * @return array{structure: int, editorial: int, coverage: int, qc_status: string}
 */
function mynak_ce_scores_from_row(array $row): array
{
    $structure = (int) ($row['ai_quality_score'] ?? 0);
    $editorial = 0;
    $coverage = 0;
    $qcStatus = '—';
    $meta = [];
    if (function_exists('mynak_ce_extract_meta_from_row')) {
        $meta = mynak_ce_extract_meta_from_row($row);
    }
    if ($meta !== []) {
        $structure = (int) ($meta['structure_score'] ?? $meta['scores']['structure_score'] ?? $structure);
        $editorial = (int) ($meta['editorial_score'] ?? $meta['scores']['editorial_score'] ?? 0);
        $coverage = (int) ($meta['coverage_score'] ?? $meta['scores']['coverage_score'] ?? 0);
        $qcStatus = (string) ($meta['qc_status'] ?? $meta['scores']['qc_status'] ?? '');
    }
    if ($qcStatus === '' || $qcStatus === '—') {
        $notes = (string) ($row['editor_notes'] ?? '');
        if (str_contains($notes, '[QC_GATE] FAIL') || str_contains($notes, '[QC_FAIL]')) {
            $qcStatus = 'FAIL';
        } elseif (str_contains($notes, 'Uyarılar:') && str_contains($notes, '[QC_GATE] PASS')) {
            $qcStatus = 'WARN';
        } elseif (str_contains($notes, '[QC_GATE] PASS')) {
            $qcStatus = 'PASS';
        } elseif ((int) ($row['durum'] ?? 0) === 2) {
            $qcStatus = 'FAIL';
        } elseif ((int) ($row['durum'] ?? 0) === 1) {
            $qcStatus = 'PASS';
        }
    }

    return [
        'structure' => $structure,
        'editorial' => $editorial,
        'coverage' => $coverage,
        'qc_status' => $qcStatus,
    ];
}

/**
 * @param array{structure: int, editorial: int, coverage: int, qc_status: string} $s
 */
function mynak_ce_render_score_badges_html(array $s, bool $compact = false): string
{
    $struct = (int) $s['structure'];
    $qc = (string) $s['qc_status'];
    $sClass = $struct >= 70 ? 'quality-high' : ($struct >= 50 ? 'quality-mid' : 'quality-low');
    $qcClass = match ($qc) {
        'PASS' => 'bg-success',
        'WARN' => 'bg-warning text-dark',
        'FAIL' => 'bg-danger',
        default => 'bg-secondary',
    };
    $html = '<span class="quality-pill ' . $sClass . '" title="HTML yapı yoğunluğu (H2/H3/kelime)">'
        . '<i class="bx bx-layout"></i> Yapı ' . $struct . '</span> ';
    $html .= '<span class="badge ' . $qcClass . ' ms-1" title="Yayın kararı — QC Gate">QC ' . htmlspecialchars($qc) . '</span>';
    if (!$compact && (int) $s['editorial'] > 0) {
        $html .= ' <span class="badge bg-light text-dark border ms-1" title="CE editorial heuristik">Ed.'
            . (int) $s['editorial'] . '</span>';
    }
    if (!$compact && (int) $s['coverage'] > 0) {
        $html .= ' <span class="badge bg-light text-dark border ms-1" title="Konu derinliği / intent">Cov.'
            . (int) $s['coverage'] . '</span>';
    }

    return $html;
}
