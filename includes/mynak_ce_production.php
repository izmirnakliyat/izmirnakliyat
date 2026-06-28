<?php
/**
 * MY Nakliyat Content Engine — production layer.
 * Behavior profiles, semantic fatigue, link entropy, GSC metadata, editor fingerprint.
 */
declare(strict_types=1);

require_once __DIR__ . '/mynak_local_seo_content_engine.php';
require_once __DIR__ . '/mynak_ce_quality_model.php';

const MYNAK_CE_FATIGUE_WINDOW = 20;
const MYNAK_CE_FATIGUE_THRESHOLD = 4;
const MYNAK_CE_CRON_ENABLED_SETTING = 'auto_blog_cron_enabled';
const AB_CE_DAILY_MAX_SETTING = 'auto_blog_ce_daily_max';
const AB_CE_QUOTA_DAY_SETTING = 'auto_blog_ce_quota_day';
const AB_CE_QUOTA_USED_SETTING = 'auto_blog_ce_quota_used';

/** QC fail kuyruk etiketi (durum=2). */
const MYNAK_CE_QUEUE_DRAFT_INVALID = 'draft_invalid';

/**
 * @return array<string, array{label: string, tone: string, structure: string}>
 */
function mynak_ce_behavior_profiles(): array
{
    return [
        'calm_expert' => [
            'label' => 'Sakin uzman',
            'tone' => 'sakin, güven veren, abartısız',
            'structure' => 'net cevap → süreç → yumuşak kapanış',
        ],
        'operational' => [
            'label' => 'Operasyonel',
            'tone' => 'saha ve süreç odaklı, fiil ağırlıklı',
            'structure' => 'adım adım plan, risk noktaları',
        ],
        'practical' => [
            'label' => 'Pratik',
            'tone' => 'kısa cümle, doğrudan öneri',
            'structure' => 'sorun → çözüm → mini özet',
        ],
        'observational' => [
            'label' => 'Gözlemci',
            'tone' => '1 saha gözlemi + yorum',
            'structure' => 'gözlem → neden → ne yapılır',
        ],
        'checklist' => [
            'label' => 'Checklist',
            'tone' => 'madde madde, taranabilir',
            'structure' => 'giriş kısa; ortada 5–7 madde; FAQ',
        ],
        'analytical' => [
            'label' => 'Analitik',
            'tone' => 'karşılaştırma, kriter, ölçü',
            'structure' => 'kriter → değerlendirme → karar',
        ],
    ];
}

function mynak_ce_pick_behavior_profile(int $seed, array $fatigue = []): string
{
    $keys = array_keys(mynak_ce_behavior_profiles());
    $over = $fatigue['overused_profiles'] ?? [];
    $candidates = [];
    foreach ($keys as $i => $k) {
        if (($over[$k] ?? 0) >= MYNAK_CE_FATIGUE_THRESHOLD) {
            continue;
        }
        $candidates[] = $k;
    }
    if ($candidates === []) {
        $candidates = $keys;
    }

    return $candidates[mynak_ce_pick($seed >> 9, count($candidates))];
}

/**
 * Son N CE üretiminden yorgunluk sinyalleri.
 *
 * @return array{
 *   overused_micro_ids: array<int,int>,
 *   overused_intro: array<string,int>,
 *   overused_cta: array<string,int>,
 *   overused_flow: array<string,int>,
 *   overused_entities: array<string,int>,
 *   overused_link_patterns: array<string,int>,
 *   overused_profiles: array<string,int>
 * }
 */
function mynak_ce_fatigue_load(?mysqli $conn, int $window = MYNAK_CE_FATIGUE_WINDOW): array
{
    $empty = [
        'overused_micro_ids' => [],
        'overused_intro' => [],
        'overused_cta' => [],
        'overused_flow' => [],
        'overused_entities' => [],
        'overused_link_patterns' => [],
        'overused_profiles' => [],
    ];
    if (!$conn instanceof mysqli) {
        return $empty;
    }

    br_ensure_content_engine_meta_column($conn);
    $sql = "SELECT content_engine_meta, editor_notes, baslik, icerik
            FROM blog_posts
            WHERE (content_engine_meta IS NOT NULL AND content_engine_meta != '')
               OR editor_notes LIKE '%[CE_META]%'
            ORDER BY COALESCE(updated_at, created_at) DESC
            LIMIT " . max(5, min(50, $window));
    $q = $conn->query($sql);
    if (!$q) {
        return $empty;
    }

    $acc = $empty;
    while ($row = $q->fetch_assoc()) {
        $meta = mynak_ce_extract_meta_from_row($row);
        if ($meta === []) {
            continue;
        }
        foreach ((array) ($meta['micro_detail_ids'] ?? []) as $mid) {
            $mid = (int) $mid;
            $acc['overused_micro_ids'][$mid] = ($acc['overused_micro_ids'][$mid] ?? 0) + 1;
        }
        foreach (['intro_type', 'cta_style', 'flow_type', 'profile_type', 'internal_link_pattern'] as $k) {
            $v = (string) ($meta[$k] ?? '');
            if ($v === '') {
                continue;
            }
            $key = match ($k) {
                'intro_type' => 'overused_intro',
                'cta_style' => 'overused_cta',
                'flow_type' => 'overused_flow',
                'profile_type' => 'overused_profiles',
                'internal_link_pattern' => 'overused_link_patterns',
                default => null,
            };
            if ($key !== null) {
                $acc[$key][$v] = ($acc[$key][$v] ?? 0) + 1;
            }
        }
        $entity = (string) ($meta['local_entity'] ?? '');
        if ($entity !== '') {
            $acc['overused_entities'][$entity] = ($acc['overused_entities'][$entity] ?? 0) + 1;
        }
    }

    return $acc;
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function mynak_ce_extract_meta_from_row(array $row): array
{
    $raw = trim((string) ($row['content_engine_meta'] ?? ''));
    if ($raw !== '') {
        $j = json_decode($raw, true);

        return is_array($j) ? $j : [];
    }
    $notes = (string) ($row['editor_notes'] ?? '');
    if (preg_match('/\[CE_META\](\{.+)$/s', $notes, $m)) {
        $j = json_decode(trim($m[1]), true);

        return is_array($j) ? $j : [];
    }

    return [];
}

/**
 * @param array<string, mixed> $pack
 * @param array<string, mixed> $fatigue
 * @return array<string, mixed>
 */
function mynak_ce_apply_fatigue_to_pack(array $pack, array $fatigue, int $seed): array
{
    $pool = mynak_ce_micro_detail_pool();
    $n = count($pool);
    $overMicro = $fatigue['overused_micro_ids'] ?? [];
    $count = (int) ($pack['micro_count'] ?? 3);
    $ids = [];
    $hints = [];
    $offset = mynak_ce_pick($seed >> 7, $n);
    $tries = 0;
    for ($i = 0; $i < $count && $tries < $n * 2; $i++, $tries++) {
        $idx = ($offset + $i * 11 + $tries) % $n;
        if (($overMicro[$idx] ?? 0) >= MYNAK_CE_FATIGUE_THRESHOLD) {
            continue;
        }
        if (in_array($idx, $ids, true)) {
            continue;
        }
        $ids[] = $idx;
        $hints[] = $pool[$idx];
    }
    if ($ids === []) {
        $picked = mynak_ce_pick_micro_details($seed, $count);
        $pack['micro_detail_ids'] = $picked['ids'];
        $pack['micro_hint_line'] = $picked['hint_line'];
    } else {
        $pack['micro_detail_ids'] = $ids;
        $pack['micro_hint_line'] = implode('; ', $hints);
    }

    $introOver = $fatigue['overused_intro'] ?? [];
    $intros = [
        ['key' => 'direct_answer', 'instruction' => 'Giriş: 40–70 kelime net cevap.'],
        ['key' => 'user_question', 'instruction' => 'Giriş: tek somut soru.'],
        ['key' => 'mini_scenario', 'instruction' => 'Giriş: 2 cümle senaryo.'],
        ['key' => 'field_observation', 'instruction' => 'Giriş: kısa saha gözlemi.'],
        ['key' => 'myth_bust', 'instruction' => 'Giriş: yanlış bilinen + düzeltme.'],
        ['key' => 'checklist', 'instruction' => 'Giriş: 5–7 maddelik mini liste.'],
        ['key' => 'price_worry', 'instruction' => 'Giriş: fiyat kaygısı; rakam yok.'],
        ['key' => 'planning_stress', 'instruction' => 'Giriş: zaman baskısı; sakin.'],
    ];
    $introCandidates = [];
    foreach ($intros as $t) {
        if (($introOver[$t['key']] ?? 0) < MYNAK_CE_FATIGUE_THRESHOLD) {
            $introCandidates[] = $t;
        }
    }
    if ($introCandidates !== []) {
        $t = $introCandidates[mynak_ce_pick($seed, count($introCandidates))];
        $pack['intro_type'] = $t['key'];
        $pack['intro_instruction'] = $t['instruction'];
    }

    $ctaOver = $fatigue['overused_cta'] ?? [];
    $ctas = [
        'sakin planlama + teklif',
        'soru kapanış + teklif',
        '3 madde + teklif',
        'yazılı teklif vurgusu',
        'tek cümle + link',
        'kriter özeti + teklif',
        'yoğun sezon + keşif',
        'checklist kapanış + teklif',
    ];
    $ctaCandidates = [];
    foreach ($ctas as $c) {
        if (($ctaOver[$c] ?? 0) < MYNAK_CE_FATIGUE_THRESHOLD) {
            $ctaCandidates[] = $c;
        }
    }
    if ($ctaCandidates !== []) {
        $pack['cta_style'] = $ctaCandidates[mynak_ce_pick($seed >> 5, count($ctaCandidates))];
    }

    $local = $pack['local_memory'] ?? null;
    if (is_array($local)) {
        $place = (string) ($local['place'] ?? '');
        if (($fatigue['overused_entities'][$place] ?? 0) >= MYNAK_CE_FATIGUE_THRESHOLD) {
            $alt = [
                ['place' => 'Narlıdere', 'note' => 'yokuşlu sokak'],
                ['place' => 'Karabağlar', 'note' => 'site girişi'],
                ['place' => 'Foça', 'note' => 'mesafe planı'],
                ['place' => 'Torbalı', 'note' => 'çıkış hattı'],
            ];
            foreach ($alt as $a) {
                if (($fatigue['overused_entities'][$a['place']] ?? 0) < MYNAK_CE_FATIGUE_THRESHOLD) {
                    $pack['local_memory'] = $a;
                    $pack['local_entity'] = $a['place'];
                    break;
                }
            }
        } else {
            $pack['local_entity'] = $place;
        }
    }

    $linkOver = $fatigue['overused_link_patterns'] ?? [];
    $hints = [
        'blog→hub→teklif',
        'tanım→teklif→blog',
        'yerel→hub→teklif',
        'hub→hizmet→blog',
        'hub→teklif→blog',
        'blog→belgeler→teklif',
    ];
    $linkCandidates = [];
    foreach ($hints as $h) {
        if (($linkOver[$h] ?? 0) < MYNAK_CE_FATIGUE_THRESHOLD) {
            $linkCandidates[] = $h;
        }
    }
    if ($linkCandidates !== []) {
        $pack['link_entropy'] = $linkCandidates[mynak_ce_pick($seed, count($linkCandidates))];
    }

    $pack['fatigue_applied'] = true;

    return $pack;
}

/**
 * @param array<string, mixed> $pack
 */
function mynak_ce_block_behavior_profile(array $pack): string
{
    $key = (string) ($pack['profile_type'] ?? 'calm_expert');
    $profiles = mynak_ce_behavior_profiles();
    $p = $profiles[$key] ?? $profiles['calm_expert'];

    return 'PROFILE ' . $key . ': ' . $p['tone'] . ' | ' . $p['structure'];
}

/**
 * Kısa modüler prompt — yalnızca aktif bloklar.
 */
function mynak_ce_build_modular_user_prompt(array $post, array $pack, string $linkBlock, string $htmlRule): string
{
    $baslik = (string) ($post['baslik'] ?? '');
    $tags = trim((string) ($post['etiketler'] ?? ''));
    $konu = $baslik . ($tags !== '' ? ' | ' . $tags : '');

    $blocks = ['KONU: ' . $konu];
    $blocks[] = mynak_ce_block_core_quality();
    $blocks[] = mynak_ce_block_behavior_profile($pack);
    $blocks[] = mynak_ce_block_service_intent($pack);
    $blocks[] = mynak_ce_block_flow($pack);

    $local = mynak_ce_block_local($pack);
    if ($local !== '') {
        $blocks[] = $local;
    }
    $micro = mynak_ce_block_micro($pack);
    if ($micro !== '') {
        $blocks[] = $micro;
    }
    $blocks[] = mynak_ce_block_internal_links($linkBlock, $pack);
    $blocks[] = mynak_ce_block_output($pack, $htmlRule);

    $eski = strip_tags((string) ($post['icerik'] ?? ''));
    $eski = trim(preg_replace('/\s+/u', ' ', $eski) ?? '');
    if (mb_strlen($eski) > 120) {
        $eskiOz = mb_strlen($eski) > 900 ? mb_substr($eski, 0, 900) . '…' : $eski;
        $blocks[] = 'ESKİ özet: ' . $eskiOz;
    }

    return implode("\n\n", $blocks);
}

/**
 * Production pack: seed + fatigue + behavior profile.
 *
 * @return array<string, mixed>
 */
function mynak_ce_build_production_pack(array $post, ?mysqli $conn = null): array
{
    $pack = mynak_ce_build_pack($post, $conn);
    $seed = (int) ($pack['seed'] ?? mynak_ce_seed($post));
    $fatigue = mynak_ce_fatigue_load($conn, MYNAK_CE_FATIGUE_WINDOW);
    $pack = mynak_ce_apply_fatigue_to_pack($pack, $fatigue, $seed);
    $pack['profile_type'] = mynak_ce_pick_behavior_profile($seed, $fatigue);
    $pack = mynak_ce_apply_editor_fingerprint($pack, $post);

    return $pack;
}

/**
 * @param list<array{path?: string, title?: string, type?: string}> $catalog
 * @return list<array{path?: string, title?: string, type?: string}>
 */
function mynak_ce_shuffle_link_catalog(array $catalog, int $seed): array
{
    if (count($catalog) < 2) {
        return $catalog;
    }
    $items = $catalog;
    $n = count($items);
    for ($i = $n - 1; $i > 0; $i--) {
        $j = mynak_ce_pick($seed + $i * 13, $i + 1);
        $tmp = $items[$i];
        $items[$i] = $items[$j];
        $items[$j] = $tmp;
    }

    return $items;
}

/**
 * @param list<array{path?: string, title?: string, type?: string}> $catalog
 */
function mynak_ce_link_prompt_section(array $catalog, int $seed = 0): string
{
    if ($catalog === []) {
        return '';
    }
    $catalog = mynak_ce_shuffle_link_catalog($catalog, $seed);
    $lines = [];
    $anchors = [];
    foreach ($catalog as $item) {
        $path = (string) ($item['path'] ?? '');
        $title = (string) ($item['title'] ?? '');
        $type = (string) ($item['type'] ?? '');
        if ($path === '') {
            continue;
        }
        $short = mb_strlen($title) > 42 ? mb_substr($title, 0, 39) . '…' : $title;
        $lines[] = '- ' . $path . ' | ' . $short . ' (' . $type . ')';
        $anchors[] = $short;
    }
    if ($lines === []) {
        return '';
    }
    $orderHint = 'Sıra seed-' . ($seed % 97) . '; anchor çeşitlendir; aynı cümle kalıbı tekrar etme.';

    return "Whitelist (4–7; href aynen):\n" . implode("\n", $lines) . "\n" . $orderHint;
}

/**
 * Tek giriş: pack + link + prompt.
 */
function mynak_ce_assemble_prompt(array $post, array $linkCatalog, ?mysqli $conn = null): array
{
    $pack = mynak_ce_build_production_pack($post, $conn);
    $seed = (int) ($pack['seed'] ?? 0);
    $linkBlock = mynak_ce_link_prompt_section($linkCatalog, $seed);
    $htmlRule = $linkBlock !== ''
        ? 'HTML: h2,h3,p,ul,li,strong,a — a yalnız whitelist'
        : 'HTML: h2,h3,p,ul,li,strong — a yok';
    $prompt = mynak_ce_build_modular_user_prompt($post, $pack, $linkBlock, $htmlRule);

    return ['pack' => $pack, 'prompt' => $prompt, 'link_catalog' => $linkCatalog];
}

/**
 * @param array<string, mixed> $post
 * @param array<string, mixed> $pack
 * @return array<string, mixed>
 */
function mynak_ce_apply_editor_fingerprint(array $pack, array $post): array
{
    $fp = $post['_editor_fingerprint'] ?? null;
    if (!is_array($fp)) {
        return $pack;
    }
    foreach (['intro_type', 'cta_style', 'faq_count', 'flow_type', 'profile_type'] as $k) {
        if (isset($fp[$k]) && $fp[$k] !== '') {
            $pack[$k] = $fp[$k];
        }
    }
    if (!empty($fp['local_entity']) && is_array($pack['local_memory'] ?? null)) {
        $pack['local_memory']['place'] = (string) $fp['local_entity'];
        $pack['use_local'] = true;
    }
    if (!empty($fp['micro_detail_ids']) && is_array($fp['micro_detail_ids'])) {
        $pool = mynak_ce_micro_detail_pool();
        $ids = array_map('intval', $fp['micro_detail_ids']);
        $hints = [];
        foreach ($ids as $id) {
            if (isset($pool[$id])) {
                $hints[] = $pool[$id];
            }
        }
        if ($hints !== []) {
            $pack['micro_detail_ids'] = $ids;
            $pack['micro_hint_line'] = implode('; ', $hints);
        }
    }
    $pack['editor_fingerprint_applied'] = true;

    return $pack;
}

/**
 * @param array<string, mixed> $fingerprint
 */
function mynak_ce_merge_fingerprint_into_meta(string $ceMetaJson, array $fingerprint): string
{
    $meta = json_decode($ceMetaJson, true);
    if (!is_array($meta)) {
        $meta = [];
    }
    $meta['editor_fingerprint'] = $fingerprint;
    $meta['editor_touched_at'] = date('c');

    return json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/** @return array{ok: bool, reasons: list<string>} */
function mynak_ce_qc_keyword_stuffing_check(string $html, array $post): array
{
    $plain = mb_strtolower(strip_tags($html), 'UTF-8');
    $reasons = [];
    $wc = function_exists('br_word_count_html') ? br_word_count_html($html) : 0;
    if ($wc > 0 && $wc < 900) {
        return ['ok' => true, 'reasons' => []];
    }
    $hay = mynak_ce_topic_hay($post);
    $targets = [];
    if (preg_match_all('/nakliyat|evden|izmir|taşıma|tasima/u', $hay, $m)) {
        $targets = array_unique($m[0]);
    }
    $baseLimit = 14;
    $limit = $wc >= 1400
        ? $baseLimit
        : (int) max($baseLimit, ceil($baseLimit * (1400 / max(500, $wc))));
    foreach ($targets as $word) {
        if ($word === '' || mb_strlen($word) < 4) {
            continue;
        }
        $cnt = mb_substr_count($plain, $word);
        if ($cnt >= $limit) {
            $reasons[] = 'stuffing:' . $word . '(' . $cnt . ')';
        }
    }
    preg_match_all('/<strong>([^<]+)<\/strong>/iu', $html, $sm);
    $strongs = $sm[1] ?? [];
    if (count($strongs) >= 8) {
        $reasons[] = 'asiri_strong';
    }

    return ['ok' => $reasons === [], 'reasons' => $reasons];
}

/** @return array{ok: bool, reasons: list<string>} */
function mynak_ce_qc_paragraph_repetition_check(string $html): array
{
    $plain = strip_tags($html);
    $parts = preg_split('/\n{2,}|<\/p>/iu', $plain) ?: [];
    $norm = [];
    foreach ($parts as $p) {
        $p = trim(preg_replace('/\s+/u', ' ', $p) ?? '');
        if (mb_strlen($p) < 40) {
            continue;
        }
        $key = mb_substr($p, 0, 80);
        $norm[$key] = ($norm[$key] ?? 0) + 1;
    }
    $reasons = [];
    foreach ($norm as $k => $c) {
        if ($c >= 2) {
            $reasons[] = 'tekrar_paragraf';
            break;
        }
    }
    $openers = [];
    foreach ($norm as $k => $_) {
        $first = mb_substr($k, 0, 24);
        $openers[$first] = ($openers[$first] ?? 0) + 1;
    }
    foreach ($openers as $op => $c) {
        if ($c >= 4) {
            $reasons[] = 'tekrar_acilis';
            break;
        }
    }

    return ['ok' => $reasons === [], 'reasons' => $reasons];
}

function mynak_ce_cron_enabled(mysqli $conn): bool
{
    return br_setting_get($conn, MYNAK_CE_CRON_ENABLED_SETTING, '0') === '1';
}

/** Varsayılan production ayarlarını oluştur. */
function mynak_ce_ensure_settings(mysqli $conn): void
{
    $defaults = [
        MYNAK_CE_CRON_ENABLED_SETTING => '0',
        AB_CE_DAILY_MAX_SETTING => '5',
    ];
    foreach ($defaults as $name => $value) {
        if (br_setting_get($conn, $name, '') === '') {
            br_setting_set($conn, $name, $value);
        }
    }
}

/**
 * @param array<string, mixed> $row blog_posts satırı veya alt kümesi
 * @return array<string, mixed>
 */
function mynak_ce_parse_post_meta(array $row): array
{
    return mynak_ce_extract_meta_from_row($row);
}

/**
 * Editör fingerprint → content_engine_meta + editor_notes.
 *
 * @param array<string, mixed> $fingerprint
 * @return array{ok: bool, error?: string}
 */
function mynak_ce_save_editor_fingerprint(mysqli $conn, int $postId, array $fingerprint): array
{
    br_ensure_content_engine_meta_column($conn);
    $stmt = $conn->prepare('SELECT content_engine_meta, editor_notes FROM blog_posts WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return ['ok' => false, 'error' => $conn->error];
    }
    $stmt->bind_param('i', $postId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        return ['ok' => false, 'error' => 'Yazı bulunamadı'];
    }

    $meta = mynak_ce_parse_post_meta($row);
    $meta['editor_fingerprint'] = $fingerprint;
    $meta['editor_touched_at'] = date('c');
    $json = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $noteLine = '[EDITOR_FP] ' . date('Y-m-d H:i')
        . ' intro=' . ($fingerprint['intro_type'] ?? '-')
        . ' profile=' . ($fingerprint['profile_type'] ?? '-')
        . ' faq=' . ($fingerprint['faq_count'] ?? '-')
        . ' cta=' . mb_substr((string) ($fingerprint['cta_style'] ?? '-'), 0, 40);

    $stmt2 = $conn->prepare(
        "UPDATE blog_posts SET
            content_engine_meta = ?,
            editor_notes = CONCAT(IFNULL(editor_notes,''), IF(IFNULL(editor_notes,'')='','','\n'), ?)
         WHERE id = ?"
    );
    if (!$stmt2) {
        return ['ok' => false, 'error' => $conn->error];
    }
    $stmt2->bind_param('ssi', $json, $noteLine, $postId);
    if (!$stmt2->execute()) {
        return ['ok' => false, 'error' => $stmt2->error];
    }

    return ['ok' => true, 'meta' => $meta];
}

/**
 * GSC-ready metadata v3.
 *
 * @param array<string, mixed> $pack
 */
function mynak_ce_production_metadata_json(array $post, array $pack, string $model = '', float $temperature = 0.0): string
{
    $meta = [
        'engine' => 'mynak_local_seo_content_engine',
        'v' => 3,
        'gsc_ready' => true,
        'generated_at' => date('c'),
        'post_id' => (int) ($post['id'] ?? 0),
        'slug' => (string) ($post['slug'] ?? ''),
        'seed' => (int) ($pack['seed'] ?? 0),
        'batch_version' => (string) ($pack['batch_version'] ?? '1'),
        'profile_type' => (string) ($pack['profile_type'] ?? ''),
        'service_intent' => (string) ($pack['service_intent'] ?? ''),
        'flow_type' => (string) ($pack['flow_type'] ?? ''),
        'intro_type' => (string) ($pack['intro_type'] ?? ''),
        'word_range' => [(int) ($pack['word_min'] ?? 0), (int) ($pack['word_max'] ?? 0)],
        'faq_count' => (int) ($pack['faq_count'] ?? 0),
        'h2_count' => (int) ($pack['h2_count'] ?? 0),
        'cta_style' => (string) ($pack['cta_style'] ?? ''),
        'syntax_key' => (string) ($pack['syntax_key'] ?? ''),
        'local_density' => !empty($pack['use_local']) ? 'izmir' : 'none',
        'local_entity' => (string) ($pack['local_entity'] ?? ''),
        'micro_detail_ids' => $pack['micro_detail_ids'] ?? [],
        'internal_link_pattern' => (string) ($pack['link_entropy'] ?? ''),
        'title_style' => 'yeni_baslik_line',
        'depth_tier' => (string) ($pack['depth_tier'] ?? ''),
        'fatigue_applied' => (bool) ($pack['fatigue_applied'] ?? false),
        'editor_fingerprint_applied' => (bool) ($pack['editor_fingerprint_applied'] ?? false),
        'model' => $model,
        'temperature' => round($temperature, 2),
        'qc_pass' => (bool) ($pack['_qc_pass'] ?? false),
        'qc_status' => (string) ($pack['_qc_status'] ?? ''),
        'qc_queue' => (string) ($pack['_qc_queue'] ?? ''),
    ];
    if (!empty($post['_editor_fingerprint']) && is_array($post['_editor_fingerprint'])) {
        $meta['editor_fingerprint'] = $post['_editor_fingerprint'];
    }

    return json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
