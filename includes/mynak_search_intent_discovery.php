<?php
/**
 * MY Nakliyat — Search Intent Discovery Engine (Faz A).
 * CE'den bağımsız: neyi yazacağız? (query cluster, intent, roadmap, CE önerileri).
 * İçerik üretmez, prompt yazmaz.
 */
declare(strict_types=1);

require_once __DIR__ . '/blog_bulk_content_refresh_lib.php';

const SID_ROADMAP_JSON_SETTING = 'content_roadmap_json';
const SID_MANUAL_SEEDS_SETTING = 'content_roadmap_manual_seeds';
const SID_GSC_CACHE_SETTING = 'content_roadmap_gsc_queries';
const SID_BULK_PRIORITY_SETTING = 'content_roadmap_bulk_priority';

/** @return array<string, array{key: string, label: string, slug_hints: list<string>, hub_paths: list<string>}> */
function sid_service_clusters(): array
{
    return [
        'evden_eve' => [
            'key' => 'evden_eve',
            'label' => 'Evden eve nakliyat',
            'slug_hints' => ['evden-eve', 'ev-tasima', 'evden'],
            'hub_paths' => ['/evden-eve-nakliyat', '/blog'],
        ],
        'izmir_evden_eve' => [
            'key' => 'izmir_evden_eve',
            'label' => 'İzmir evden eve nakliyat',
            'slug_hints' => ['izmir-evden', 'izmir.*evden'],
            'hub_paths' => ['/izmir-evden-eve-nakliyat', '/blog'],
        ],
        'sehirlerarasi' => [
            'key' => 'sehirlerarasi',
            'label' => 'Şehirlerarası nakliyat',
            'slug_hints' => ['sehirlerarasi', 'sehirler-arasi'],
            'hub_paths' => ['/sehirlerarasi-nakliyat', '/blog'],
        ],
        'asansorlu' => [
            'key' => 'asansorlu',
            'label' => 'Asansörlü taşımacılık',
            'slug_hints' => ['asansorlu', 'asansor'],
            'hub_paths' => ['/asansorlu-tasima', '/blog'],
        ],
        'depolama' => [
            'key' => 'depolama',
            'label' => 'Eşya depolama',
            'slug_hints' => ['depo', 'depolama'],
            'hub_paths' => ['/esya-depolama', '/blog'],
        ],
        'parca_esya' => [
            'key' => 'parca_esya',
            'label' => 'Parça eşya taşıma',
            'slug_hints' => ['parca-esya', 'parca'],
            'hub_paths' => ['/parca-esya-tasima', '/blog'],
        ],
        'ofis' => [
            'key' => 'ofis',
            'label' => 'Ofis taşıma',
            'slug_hints' => ['ofis-tasima', 'ofis'],
            'hub_paths' => ['/ofis-tasima', '/blog'],
        ],
        'asansor_kiralama' => [
            'key' => 'asansor_kiralama',
            'label' => 'Asansör kiralama',
            'slug_hints' => ['asansor-kiralama', 'asansor.*kiralama'],
            'hub_paths' => ['/asansor-kiralama', '/blog'],
        ],
        'vinc' => [
            'key' => 'vinc',
            'label' => 'Sepetli vinç kiralama',
            'slug_hints' => ['vinc', 'vinç', 'sepetli'],
            'hub_paths' => ['/sepetli-vinc', '/blog'],
        ],
    ];
}

/** @return list<string> */
function sid_intent_types(): array
{
    return [
        'informational',
        'transactional',
        'local',
        'comparison',
        'problem_solving',
        'faq',
        'ai_overview',
    ];
}

function sid_ensure_roadmap_table(mysqli $conn): void
{
    $sql = "CREATE TABLE IF NOT EXISTS content_roadmap_clusters (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        cluster_id VARCHAR(64) NOT NULL,
        query_text VARCHAR(500) NOT NULL,
        intent_type VARCHAR(32) NOT NULL DEFAULT 'informational',
        service_group VARCHAR(64) NOT NULL,
        local_intent TINYINT UNSIGNED NOT NULL DEFAULT 0,
        priority_score DECIMAL(8,2) NOT NULL DEFAULT 0,
        ai_retrieval_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
        local_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
        conversion_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
        topical_authority_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
        semantic_overlap DECIMAL(5,4) NOT NULL DEFAULT 0,
        semantic_group VARCHAR(64) DEFAULT NULL,
        merge_recommendation VARCHAR(32) DEFAULT 'none',
        merge_target VARCHAR(64) DEFAULT NULL,
        recommended_profile VARCHAR(32) DEFAULT NULL,
        recommended_flow VARCHAR(32) DEFAULT NULL,
        recommended_intro VARCHAR(32) DEFAULT NULL,
        recommended_cta VARCHAR(255) DEFAULT NULL,
        recommended_internal_links TEXT DEFAULT NULL,
        local_entity_opportunities TEXT DEFAULT NULL,
        suggested_word_range VARCHAR(24) DEFAULT NULL,
        status VARCHAR(24) NOT NULL DEFAULT 'pending',
        discovery_batch VARCHAR(32) DEFAULT NULL,
        linked_post_id INT UNSIGNED DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_cluster_id (cluster_id),
        KEY idx_status (status),
        KEY idx_priority (priority_score),
        KEY idx_service (service_group),
        KEY idx_semantic (semantic_group)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->query($sql);
}

function sid_log(string $msg): void
{
    $dir = realpath(__DIR__ . '/../logs') ?: (__DIR__ . '/../logs');
    @file_put_contents($dir . '/search_intent_discovery.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
}

/** @return list<string> */
function sid_tokenize(string $text): array
{
    $t = mb_strtolower($text, 'UTF-8');
    $t = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $t) ?? $t;
    $parts = preg_split('/\s+/u', $t, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $stop = ['ve', 'ile', 'için', 'bir', 'mi', 'mı', 'mu', 'mü', 'da', 'de', 'na', 'ne', 'the', 'how'];
    $out = [];
    foreach ($parts as $p) {
        if (mb_strlen($p) < 3 || in_array($p, $stop, true)) {
            continue;
        }
        $out[$p] = true;
    }

    return array_keys($out);
}

function sid_jaccard(array $a, array $b): float
{
    if ($a === [] || $b === []) {
        return 0.0;
    }
    $setA = array_fill_keys($a, true);
    $setB = array_fill_keys($b, true);
    $inter = 0;
    foreach ($setA as $k => $_) {
        if (isset($setB[$k])) {
            ++$inter;
        }
    }
    $union = count($setA) + count($setB) - $inter;

    return $union > 0 ? $inter / $union : 0.0;
}

/**
 * @return array{rows: list<array<string, mixed>>, sources: array<string, int>}
 */
function sid_collect_inputs(mysqli $conn): array
{
    $sources = ['blog_titles' => 0, 'auto_blog' => 0, 'gsc' => 0, 'manual' => 0, 'templates' => 0];
    $rows = [];

    $q = $conn->query("SELECT id, baslik, slug, etiketler FROM blog_posts WHERE baslik != '' ORDER BY id DESC LIMIT 800");
    if ($q) {
        while ($r = $q->fetch_assoc()) {
            $rows[] = [
                'query' => trim((string) $r['baslik']),
                'source' => 'blog_titles',
                'slug' => (string) $r['slug'],
                'post_id' => (int) $r['id'],
            ];
            ++$sources['blog_titles'];
        }
    }

    $ab = $conn->query('SELECT id, keywords FROM auto_blog_settings WHERE keywords IS NOT NULL AND keywords != ""');
    if ($ab) {
        while ($r = $ab->fetch_assoc()) {
            foreach (explode(',', (string) $r['keywords']) as $kw) {
                $kw = trim($kw);
                if ($kw !== '') {
                    $rows[] = ['query' => $kw, 'source' => 'auto_blog', 'setting_id' => (int) $r['id']];
                    ++$sources['auto_blog'];
                }
            }
        }
    }

    $gscRaw = br_setting_get($conn, SID_GSC_CACHE_SETTING, '');
    if ($gscRaw !== '') {
        $gsc = json_decode($gscRaw, true);
        if (is_array($gsc)) {
            foreach ($gsc as $item) {
                $query = trim((string) ($item['query'] ?? ''));
                if ($query === '') {
                    continue;
                }
                $rows[] = [
                    'query' => $query,
                    'source' => 'gsc',
                    'clicks' => (int) ($item['clicks'] ?? 0),
                    'impressions' => (int) ($item['impressions'] ?? 0),
                ];
                ++$sources['gsc'];
            }
        }
    }

    $manualRaw = br_setting_get($conn, SID_MANUAL_SEEDS_SETTING, '');
    foreach (preg_split('/[\r\n,;]+/', $manualRaw) ?: [] as $line) {
        $line = trim($line);
        if ($line !== '') {
            $rows[] = ['query' => $line, 'source' => 'manual'];
            ++$sources['manual'];
        }
    }

    return ['rows' => $rows, 'sources' => $sources];
}

function sid_detect_service_group(string $query, string $slug = ''): string
{
    $hay = mb_strtolower($query . ' ' . $slug, 'UTF-8');
    $clusters = sid_service_clusters();
    $best = 'evden_eve';
    $bestScore = 0;
    foreach ($clusters as $key => $meta) {
        $score = 0;
        foreach ($meta['slug_hints'] as $hint) {
            if (str_contains($hint, '.*')) {
                if (preg_match('/' . $hint . '/u', $hay)) {
                    $score += 3;
                }
            } elseif (str_contains($hay, str_replace('-', ' ', $hint)) || str_contains($hay, $hint)) {
                $score += 2;
            }
        }
        if (str_contains($hay, str_replace('_', ' ', $key))) {
            ++$score;
        }
        if ($score > $bestScore) {
            $bestScore = $score;
            $best = $key;
        }
    }
    if (str_contains($hay, 'izmir') && str_contains($hay, 'evden')) {
        return 'izmir_evden_eve';
    }
    if (str_contains($hay, 'şehirlerarası') || str_contains($hay, 'sehirlerarasi')) {
        return 'sehirlerarasi';
    }

    return $best;
}

function sid_infer_intent(string $query): string
{
    $q = mb_strtolower($query, 'UTF-8');
    if (preg_match('/\b(fiyat|ücret|ucret|teklif|kaç para|maliyet|ne kadar)\b/u', $q)) {
        return 'transactional';
    }
    if (preg_match('/\b(karşıyaka|karsiyaka|bornova|buca|konak|alsancak|gaziemir|çeşme|cesme|izmir)\b/u', $q)) {
        return 'local';
    }
    if (preg_match('/\b(vs|mi yoksa|karşılaştır|fark|hangisi)\b/u', $q)) {
        return 'comparison';
    }
    if (preg_match('/\b(nasıl|neden|ne zaman|sorun|problem|hata|yapılır|yapilir)\b/u', $q)) {
        return 'problem_solving';
    }
    if (preg_match('/\?$/u', trim($query)) || preg_match('/\b(nedir|nelerdir|sıkça|sikca|sss|faq)\b/u', $q)) {
        return 'faq';
    }
    if (preg_match('/\b(nedir|rehber|bilgi|ipucu|tavsiye)\b/u', $q)) {
        return 'informational';
    }
    if (preg_match('/^(what|how|why|is)\b/u', $q)) {
        return 'ai_overview';
    }

    return 'informational';
}

function sid_has_question_shape(string $query): bool
{
    $q = trim($query);

    return str_ends_with($q, '?')
        || (bool) preg_match('/\b(nasıl|nedir|ne kadar|kaç|hangi|mı|mi|mu|mü)\b/ui', $q);
}

function sid_score_ai_retrieval(string $query, string $intent): int
{
    $score = 40;
    if (sid_has_question_shape($query)) {
        $score += 18;
    }
    if (in_array($intent, ['faq', 'ai_overview', 'problem_solving'], true)) {
        $score += 15;
    }
    if (preg_match('/\b(izmir|karşıyaka|bornova|buca|asansör|asansor|depo|vinç|vinc)\b/ui', $query)) {
        $score += 10;
    }
    if (mb_strlen($query) >= 25 && mb_strlen($query) <= 90) {
        $score += 8;
    }
    if (preg_match('/\b(en iyi|lider|#1)\b/ui', $query)) {
        $score -= 25;
    }

    return max(0, min(100, $score));
}

function sid_score_conversion(string $query, string $intent): int
{
    $q = mb_strtolower($query, 'UTF-8');
    $score = 30;
    if ($intent === 'transactional') {
        $score += 45;
    }
    if (preg_match('/\b(fiyat|teklif|ücret|ucret|keşif|kesif|randevu)\b/u', $q)) {
        $score += 25;
    }
    if (preg_match('/\b(nedir|rehber|nasıl yapılır)\b/u', $q) && !preg_match('/\b(fiyat|teklif)\b/u', $q)) {
        $score -= 15;
    }
    if ($intent === 'informational' || $intent === 'faq') {
        $score += 5;
    }

    return max(0, min(100, $score));
}

function sid_score_local(string $query, string $service): int
{
    $q = mb_strtolower($query, 'UTF-8');
    $score = 20;
    $districts = ['izmir', 'karşıyaka', 'karsiyaka', 'bornova', 'buca', 'konak', 'alsancak', 'gaziemir', 'çeşme', 'cesme', 'bayraklı', 'cigli', 'menemen'];
    foreach ($districts as $d) {
        if (str_contains($q, $d)) {
            $score += 22;
        }
    }
    if ($service === 'izmir_evden_eve') {
        $score += 25;
    }
    if (str_contains($q, 'izmir')) {
        $score += 15;
    }

    return max(0, min(100, $score));
}

function sid_score_topical_authority(string $query, string $service, int $existingPostId = 0): int
{
    $score = 55;
    if ($existingPostId > 0) {
        $score -= 35;
    }
    if (in_array($service, ['izmir_evden_eve', 'sehirlerarasi', 'evden_eve'], true)) {
        $score += 12;
    }
    if (mb_strlen($query) > 12 && mb_strlen($query) < 70) {
        $score += 8;
    }

    return max(0, min(100, $score));
}

/**
 * @return array<string, string>
 */
function sid_recommend_ce_params(string $intent, string $service, string $query): array
{
    $map = [
        'informational' => ['profile' => 'calm_expert', 'flow' => 'A-linear', 'intro' => 'direct_answer', 'cta' => 'sakin planlama + teklif'],
        'transactional' => ['profile' => 'practical', 'flow' => 'G-price-late', 'intro' => 'price_worry', 'cta' => 'yazılı teklif vurgusu'],
        'local' => ['profile' => 'observational', 'flow' => 'H-local-mid', 'intro' => 'field_observation', 'cta' => 'yerel keşif + teklif'],
        'comparison' => ['profile' => 'analytical', 'flow' => 'A-linear', 'intro' => 'myth_bust', 'cta' => 'kriter özeti + teklif'],
        'problem_solving' => ['profile' => 'operational', 'flow' => 'C-field-first', 'intro' => 'user_question', 'cta' => '3 madde + teklif'],
        'faq' => ['profile' => 'checklist', 'flow' => 'B-faq-early', 'intro' => 'checklist', 'cta' => 'checklist kapanış + teklif'],
        'ai_overview' => ['profile' => 'calm_expert', 'flow' => 'A-linear', 'intro' => 'direct_answer', 'cta' => 'tek cümle + link'],
    ];
    $p = $map[$intent] ?? $map['informational'];
    $meta = sid_service_clusters()[$service] ?? sid_service_clusters()['evden_eve'];
    $links = array_slice($meta['hub_paths'], 0, 3);
    $links[] = '/teklif-al';
    $word = match ($intent) {
        'transactional', 'local' => '1400-1900',
        'faq', 'ai_overview' => '1500-2000',
        'comparison' => '1600-2100',
        default => '1400-1800',
    };
    $entities = [];
    if (preg_match_all('/\b(karşıyaka|bornova|buca|konak|alsancak|gaziemir|çeşme|izmir)\b/ui', $query, $em)) {
        $entities = array_values(array_unique(array_map('mb_strtolower', $em[0])));
    }

    return [
        'recommended_profile' => $p['profile'],
        'recommended_flow' => $p['flow'],
        'recommended_intro' => $p['intro'],
        'recommended_cta' => $p['cta'],
        'recommended_internal_links' => implode(', ', $links),
        'local_entity_opportunities' => implode(', ', $entities),
        'suggested_word_range' => $word,
    ];
}

/** @return list<array{query: string, intent: string, service: string, source: string, post_id?: int, gsc_boost?: float}> */
function sid_template_candidates(): array
{
    $out = [];
    $locals = ['İzmir', 'Karşıyaka', 'Bornova', 'Buca', 'Konak'];
    $intents = sid_intent_types();
    foreach (sid_service_clusters() as $svcKey => $svc) {
        $label = $svc['label'];
        foreach ($intents as $intent) {
            $base = match ($intent) {
                'informational' => ["{$label} nedir", "{$label} rehberi", "{$label} hakkında bilgi"],
                'transactional' => ["{$label} fiyatları", "{$label} teklif al", "{$label} maliyeti ne kadar"],
                'local' => ["İzmir {$label}", "Karşıyaka {$label} hizmeti"],
                'comparison' => ["{$label} ile parça eşya farkı", "{$label} mı şehirlerarası mı"],
                'problem_solving' => ["{$label} sırasında nelere dikkat edilmeli", "{$label} planlama nasıl yapılır"],
                'faq' => ["{$label} sık sorulan sorular", "{$label} ile ilgili merak edilenler"],
                'ai_overview' => ["{$label} nasıl çalışır", "İzmir'de {$label} güvenli mi"],
                default => ["{$label} bilgisi"],
            };
            foreach ($base as $q) {
                $out[] = ['query' => $q, 'intent' => $intent, 'service' => $svcKey, 'source' => 'templates'];
            }
            if ($intent === 'local' && $svcKey === 'izmir_evden_eve') {
                foreach ($locals as $loc) {
                    $out[] = [
                        'query' => "{$loc} evden eve nakliyat fiyatları",
                        'intent' => 'local',
                        'service' => $svcKey,
                        'source' => 'templates',
                    ];
                    $out[] = [
                        'query' => "{$loc} asansörlü taşıma nasıl planlanır?",
                        'intent' => 'problem_solving',
                        'service' => 'asansorlu',
                        'source' => 'templates',
                    ];
                }
            }
        }
    }

    return $out;
}

function sid_make_cluster_id(string $service, string $intent, string $query): string
{
    return substr(sha1($service . '|' . $intent . '|' . mb_strtolower(trim($query), 'UTF-8')), 0, 16);
}

/**
 * @param list<array<string, mixed>> $clusters
 * @return list<array<string, mixed>>
 */
function sid_apply_semantic_dedup(array $clusters): array
{
    $n = count($clusters);
    $tokens = [];
    foreach ($clusters as $i => $c) {
        $tokens[$i] = sid_tokenize((string) $c['query_text']);
    }
    $groups = [];
    for ($i = 0; $i < $n; $i++) {
        $groups[$i] = $i;
    }
    for ($i = 0; $i < $n; $i++) {
        for ($j = $i + 1; $j < $n; $j++) {
            if ($clusters[$i]['service_group'] !== $clusters[$j]['service_group']) {
                continue;
            }
            $sim = sid_jaccard($tokens[$i], $tokens[$j]);
            if ($sim >= 0.58) {
                $groups[$j] = $groups[$i];
            }
        }
    }
    $byRoot = [];
    foreach ($groups as $idx => $root) {
        $byRoot[$root][] = $idx;
    }
    foreach ($byRoot as $root => $members) {
        if (count($members) < 2) {
            continue;
        }
        usort($members, static function ($a, $b) use ($clusters) {
            return ($clusters[$b]['priority_score'] ?? 0) <=> ($clusters[$a]['priority_score'] ?? 0);
        });
        $canonical = $clusters[$members[0]]['cluster_id'];
        $groupKey = 'sg_' . substr(sha1((string) $canonical), 0, 10);
        foreach ($members as $m) {
            $clusters[$m]['semantic_group'] = $groupKey;
            if ($m === $members[0]) {
                $clusters[$m]['merge_recommendation'] = 'canonical';
                $clusters[$m]['merge_target'] = null;
                $clusters[$m]['semantic_overlap'] = 0;
                continue;
            }
            $clusters[$m]['merge_recommendation'] = 'merge';
            $clusters[$m]['merge_target'] = $canonical;
            $clusters[$m]['semantic_overlap'] = sid_jaccard($tokens[$members[0]], $tokens[$m]);
        }
    }

    return $clusters;
}

/**
 * Ana discovery koşusu.
 *
 * @return array{batch: string, count: int, clusters: list<array<string, mixed>>, sources: array<string, int>}
 */
function sid_run_discovery(mysqli $conn, bool $replacePending = true): array
{
    sid_ensure_roadmap_table($conn);
    $batch = 'disc_' . date('Ymd_His');
    $collected = sid_collect_inputs($conn);
    $candidates = [];

    foreach ($collected['rows'] as $row) {
        $q = trim((string) $row['query']);
        if (mb_strlen($q) < 8 || mb_strlen($q) > 200) {
            continue;
        }
        $svc = sid_detect_service_group($q, (string) ($row['slug'] ?? ''));
        $intent = sid_infer_intent($q);
        $candidates[$svc . '|' . $intent . '|' . mb_strtolower($q, 'UTF-8')] = [
            'query' => $q,
            'intent' => $intent,
            'service' => $svc,
            'source' => $row['source'],
            'post_id' => (int) ($row['post_id'] ?? 0),
            'gsc_boost' => isset($row['clicks']) ? log(2 + (int) $row['clicks']) * 8 : 0,
        ];
    }

    foreach (sid_template_candidates() as $t) {
        $key = $t['service'] . '|' . $t['intent'] . '|' . mb_strtolower($t['query'], 'UTF-8');
        if (!isset($candidates[$key])) {
            $candidates[$key] = [
                'query' => $t['query'],
                'intent' => $t['intent'],
                'service' => $t['service'],
                'source' => 'templates',
                'post_id' => 0,
                'gsc_boost' => 0,
            ];
            ++$collected['sources']['templates'];
        }
    }

    $clusters = [];
    foreach ($candidates as $c) {
        $query = $c['query'];
        $intent = $c['intent'];
        $service = $c['service'];
        $localIntent = ($intent === 'local' || str_contains(mb_strtolower($query, 'UTF-8'), 'izmir')) ? 1 : 0;
        $ai = sid_score_ai_retrieval($query, $intent);
        $conv = sid_score_conversion($query, $intent);
        $local = sid_score_local($query, $service);
        $topical = sid_score_topical_authority($query, $service, (int) $c['post_id']);
        $priority = ($ai * 0.22) + ($conv * 0.28) + ($local * 0.22) + ($topical * 0.28) + (float) $c['gsc_boost'];
        $ce = sid_recommend_ce_params($intent, $service, $query);
        $clusters[] = array_merge([
            'cluster_id' => sid_make_cluster_id($service, $intent, $query),
            'query_text' => $query,
            'intent_type' => $intent,
            'service_group' => $service,
            'local_intent' => $localIntent,
            'priority_score' => round($priority, 2),
            'ai_retrieval_score' => $ai,
            'local_score' => $local,
            'conversion_score' => $conv,
            'topical_authority_score' => $topical,
            'semantic_overlap' => 0,
            'semantic_group' => null,
            'merge_recommendation' => 'none',
            'merge_target' => null,
            'status' => 'pending',
            'discovery_batch' => $batch,
            'linked_post_id' => (int) ($c['post_id'] ?? 0),
        ], $ce);
    }

    usort($clusters, static fn ($a, $b) => ($b['priority_score'] ?? 0) <=> ($a['priority_score'] ?? 0));
    $clusters = sid_apply_semantic_dedup($clusters);

    if ($replacePending) {
        $conn->query("DELETE FROM content_roadmap_clusters WHERE status = 'pending'");
    }

    $saved = sid_save_clusters($conn, $clusters);
    $json = sid_export_roadmap_json($clusters, $batch);
    br_setting_set($conn, SID_ROADMAP_JSON_SETTING, $json);
    sid_log("Discovery batch=$batch saved=$saved candidates=" . count($candidates));

    return [
        'batch' => $batch,
        'count' => $saved,
        'clusters' => $clusters,
        'sources' => $collected['sources'],
    ];
}

/**
 * @param list<array<string, mixed>> $clusters
 */
function sid_save_clusters(mysqli $conn, array $clusters): int
{
    $sql = 'INSERT INTO content_roadmap_clusters (
        cluster_id, query_text, intent_type, service_group, local_intent,
        priority_score, ai_retrieval_score, local_score, conversion_score, topical_authority_score,
        semantic_overlap, semantic_group, merge_recommendation, merge_target,
        recommended_profile, recommended_flow, recommended_intro, recommended_cta,
        recommended_internal_links, local_entity_opportunities, suggested_word_range,
        status, discovery_batch, linked_post_id
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE
        priority_score=VALUES(priority_score),
        ai_retrieval_score=VALUES(ai_retrieval_score),
        local_score=VALUES(local_score),
        conversion_score=VALUES(conversion_score),
        topical_authority_score=VALUES(topical_authority_score),
        semantic_overlap=VALUES(semantic_overlap),
        semantic_group=VALUES(semantic_group),
        merge_recommendation=VALUES(merge_recommendation),
        merge_target=VALUES(merge_target),
        recommended_profile=VALUES(recommended_profile),
        recommended_flow=VALUES(recommended_flow),
        recommended_intro=VALUES(recommended_intro),
        recommended_cta=VALUES(recommended_cta),
        recommended_internal_links=VALUES(recommended_internal_links),
        local_entity_opportunities=VALUES(local_entity_opportunities),
        suggested_word_range=VALUES(suggested_word_range),
        discovery_batch=VALUES(discovery_batch),
        updated_at=NOW()';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }
    $n = 0;
    foreach ($clusters as $c) {
        $semanticGroup = $c['semantic_group'] ?? '';
        $mergeTarget = $c['merge_target'] ?? '';
        $stmt->bind_param(
            'ssssidiiiidssssssssssssi',
            $c['cluster_id'],
            $c['query_text'],
            $c['intent_type'],
            $c['service_group'],
            $c['local_intent'],
            $c['priority_score'],
            $c['ai_retrieval_score'],
            $c['local_score'],
            $c['conversion_score'],
            $c['topical_authority_score'],
            $c['semantic_overlap'],
            $semanticGroup,
            $c['merge_recommendation'],
            $mergeTarget,
            $c['recommended_profile'],
            $c['recommended_flow'],
            $c['recommended_intro'],
            $c['recommended_cta'],
            $c['recommended_internal_links'],
            $c['local_entity_opportunities'],
            $c['suggested_word_range'],
            $c['status'],
            $c['discovery_batch'],
            $c['linked_post_id']
        );
        if ($stmt->execute()) {
            ++$n;
        }
    }

    return $n;
}

/**
 * @param list<array<string, mixed>> $clusters
 */
function sid_export_roadmap_json(array $clusters, string $batch): string
{
    $payload = [
        'engine' => 'mynak_search_intent_discovery',
        'v' => 1,
        'generated_at' => date('c'),
        'discovery_batch' => $batch,
        'cluster_count' => count($clusters),
        'clusters' => array_map(static function (array $c) {
            return [
                'cluster_id' => $c['cluster_id'],
                'query' => $c['query_text'],
                'intent_type' => $c['intent_type'],
                'service_group' => $c['service_group'],
                'local_intent' => (bool) $c['local_intent'],
                'priority_score' => $c['priority_score'],
                'ai_retrieval_score' => $c['ai_retrieval_score'],
                'local_score' => $c['local_score'],
                'conversion_score' => $c['conversion_score'],
                'topical_authority_score' => $c['topical_authority_score'],
                'semantic_overlap' => $c['semantic_overlap'],
                'semantic_group' => $c['semantic_group'],
                'merge_recommendation' => $c['merge_recommendation'],
                'merge_target' => $c['merge_target'],
                'recommended_profile' => $c['recommended_profile'],
                'recommended_flow' => $c['recommended_flow'],
                'recommended_intro' => $c['recommended_intro'],
                'recommended_cta' => $c['recommended_cta'],
                'recommended_internal_links' => $c['recommended_internal_links'],
                'local_entity_opportunities' => $c['local_entity_opportunities'],
                'suggested_word_range' => $c['suggested_word_range'],
            ];
        }, $clusters),
    ];

    return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

/**
 * @return array<string, mixed>|null
 */
function sid_get_cluster_by_id(mysqli $conn, int $id): ?array
{
    sid_ensure_roadmap_table($conn);
    $stmt = $conn->prepare('SELECT * FROM content_roadmap_clusters WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc() ?: null;
}

function sid_update_cluster_status(mysqli $conn, int $id, string $status): bool
{
    $stmt = $conn->prepare('UPDATE content_roadmap_clusters SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $status, $id);

    return $stmt->execute();
}

function sid_resolve_category_id(mysqli $conn, string $serviceGroup): int
{
    $meta = sid_service_clusters()[$serviceGroup] ?? null;
    if (!$meta) {
        return 0;
    }
    $hints = $meta['slug_hints'];
    $res = $conn->query('SELECT id, ad FROM blog_categories ORDER BY id ASC');
    if (!$res) {
        return 0;
    }
    while ($row = $res->fetch_assoc()) {
        $ad = mb_strtolower((string) $row['ad'], 'UTF-8');
        foreach ($hints as $h) {
            $h = str_replace('.*', '', $h);
            if ($h !== '' && str_contains($ad, str_replace('-', ' ', $h))) {
                return (int) $row['id'];
            }
        }
    }
    $r2 = $conn->query('SELECT id FROM blog_categories ORDER BY id ASC LIMIT 1');

    return $r2 ? (int) ($r2->fetch_assoc()['id'] ?? 0) : 0;
}

/** Onaylı cluster → Auto Blog ayarı (CE üretimi ayrı adımda). */
function sid_send_to_auto_blog(mysqli $conn, int $clusterRowId): array
{
    require_once __DIR__ . '/../admin/includes/auto_blog_functions.php';
    $row = sid_get_cluster_by_id($conn, $clusterRowId);
    if (!$row) {
        return ['ok' => false, 'message' => 'Cluster bulunamadı'];
    }
    if (($row['merge_recommendation'] ?? '') === 'merge') {
        return ['ok' => false, 'message' => 'Merge önerilen cluster doğrudan gönderilmez. Canonical cluster seçin.'];
    }
    $range = explode('-', (string) ($row['suggested_word_range'] ?? '1400-1800'));
    $minW = (int) ($range[0] ?? 1400);
    $maxW = (int) ($range[1] ?? 1800);
    $catId = sid_resolve_category_id($conn, (string) $row['service_group']);
    if ($catId <= 0) {
        $fb = $conn->query('SELECT id FROM blog_categories ORDER BY id ASC LIMIT 1');
        $catId = $fb ? (int) ($fb->fetch_assoc()['id'] ?? 0) : 0;
    }
    if ($catId <= 0) {
        return ['ok' => false, 'message' => 'Blog kategorisi bulunamadı. Admin → Blog Kategorileri\'nden en az bir kategori ekleyin.'];
    }
    $manual = '[ROADMAP] intent=' . $row['intent_type']
        . ' profile=' . $row['recommended_profile']
        . ' flow=' . $row['recommended_flow']
        . ' intro=' . $row['recommended_intro']
        . ' | ' . ($row['local_entity_opportunities'] ?? '');

    $data = [
        'category_id' => $catId,
        'keywords' => mb_substr((string) $row['query_text'], 0, 500),
        'manual_command' => $manual,
        'cover_image' => '',
        'min_words' => $minW,
        'max_words' => $maxW,
        'post_count_per_period' => 1,
        'period_type' => 'daily',
        'post_time' => '09:00',
        'active' => 0,
    ];
    if (!add_auto_blog_setting($data)) {
        $detail = function_exists('ab_last_setting_error') ? ab_last_setting_error() : '';

        return [
            'ok' => false,
            'message' => 'auto_blog_settings kaydı oluşturulamadı'
                . ($detail !== '' ? ': ' . $detail : ''),
        ];
    }
    $newId = (int) $conn->insert_id;
    sid_update_cluster_status($conn, $clusterRowId, 'sent_auto_blog');

    return [
        'ok' => true,
        'message' => 'Auto Blog ayarı oluşturuldu (pasif). CE Üret ile yazı üretin.',
        'auto_blog_setting_id' => $newId,
        'url' => '/admin/auto_blog_settings.php',
    ];
}

/** Bulk refresh öncelik listesine ekle (mevcut yazı eşleşmesi varsa post_id). */
function sid_set_bulk_priority(mysqli $conn, int $clusterRowId): array
{
    $row = sid_get_cluster_by_id($conn, $clusterRowId);
    if (!$row) {
        return ['ok' => false, 'message' => 'Cluster bulunamadı'];
    }
    $list = json_decode(br_setting_get($conn, SID_BULK_PRIORITY_SETTING, '[]'), true);
    if (!is_array($list)) {
        $list = [];
    }
    $entry = [
        'cluster_id' => $row['cluster_id'],
        'query' => $row['query_text'],
        'service_group' => $row['service_group'],
        'priority_score' => $row['priority_score'],
        'post_id' => (int) ($row['linked_post_id'] ?? 0),
        'recommended_profile' => $row['recommended_profile'],
    ];
    $list[$row['cluster_id']] = $entry;
    br_setting_set($conn, SID_BULK_PRIORITY_SETTING, json_encode(array_values($list), JSON_UNESCAPED_UNICODE));
    sid_update_cluster_status($conn, $clusterRowId, 'bulk_priority');

    return ['ok' => true, 'message' => 'Bulk refresh öncelik listesine eklendi.', 'post_id' => $entry['post_id']];
}

/**
 * GSC TSV/CSV parse → settings cache.
 *
 * @return array{ok: bool, count: int, message: string}
 */
function sid_import_gsc_text(string $raw): array
{
    $lines = preg_split('/\r\n|\r|\n/', trim($raw)) ?: [];
    if ($lines === []) {
        return ['ok' => false, 'count' => 0, 'message' => 'Boş veri'];
    }
    $header = null;
    $items = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $cols = str_getcsv($line, "\t");
        if (count($cols) < 2) {
            $cols = str_getcsv($line, ',');
        }
        if ($header === null) {
            $header = array_map(static fn ($h) => mb_strtolower(trim($h), 'UTF-8'), $cols);
            continue;
        }
        $row = [];
        foreach ($header as $i => $h) {
            $row[$h] = $cols[$i] ?? '';
        }
        $query = trim((string) ($row['query'] ?? $row['top queries'] ?? $row['sorgu'] ?? ''));
        if ($query === '') {
            continue;
        }
        $items[] = [
            'query' => $query,
            'clicks' => (int) ($row['clicks'] ?? $row['tıklamalar'] ?? 0),
            'impressions' => (int) ($row['impressions'] ?? $row['gösterimler'] ?? 0),
        ];
    }

    return ['ok' => true, 'count' => count($items), 'items' => $items, 'message' => count($items) . ' sorgu içe aktarıldı'];
}

/**
 * @return list<array<string, mixed>>
 */
function sid_list_clusters(mysqli $conn, ?string $status = null, int $limit = 500): array
{
    sid_ensure_roadmap_table($conn);
    if ($status !== null && $status !== '') {
        $stmt = $conn->prepare(
            'SELECT * FROM content_roadmap_clusters WHERE status = ? ORDER BY priority_score DESC LIMIT ?'
        );
        $stmt->bind_param('si', $status, $limit);
    } else {
        $stmt = $conn->prepare('SELECT * FROM content_roadmap_clusters ORDER BY priority_score DESC LIMIT ?');
        $stmt->bind_param('i', $limit);
    }
    $stmt->execute();
    $rows = [];
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }

    return $rows;
}
