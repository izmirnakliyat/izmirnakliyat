<?php
/**
 * Otomatik Blog — Content Engine Faz 1 (manuel üretim + QC gate).
 * Toplu yenileme ile aynı CE + QC + editör kuyruğu akışı.
 */
declare(strict_types=1);

require_once __DIR__ . '/blog_post_status.php';
require_once __DIR__ . '/mynak_local_seo_content_engine.php';
require_once __DIR__ . '/mynak_ce_production.php';
require_once __DIR__ . '/blog_bulk_content_refresh_lib.php';

function ab_ce_log(string $msg): void
{
    $logDir = realpath(__DIR__ . '/../logs');
    if ($logDir === false) {
        @mkdir(__DIR__ . '/../logs', 0777, true);
        $logDir = realpath(__DIR__ . '/../logs');
    }
    $logFile = ($logDir ?: __DIR__) . '/auto_blog_ce.log';
    @file_put_contents($logFile, date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
}

function ab_ce_daily_max(mysqli $conn): int
{
    $v = br_setting_get($conn, AB_CE_DAILY_MAX_SETTING, '5');
    $n = (int) $v;

    return max(3, min($n >= 1 ? $n : 5, 5));
}

function ab_ce_check_quota(mysqli $conn, bool $ignoreQuota = false): bool
{
    if ($ignoreQuota) {
        return true;
    }
    $today = date('Y-m-d');
    $day = br_setting_get($conn, AB_CE_QUOTA_DAY_SETTING, '');
    $used = (int) br_setting_get($conn, AB_CE_QUOTA_USED_SETTING, '0');
    if ($day !== $today) {
        br_setting_set($conn, AB_CE_QUOTA_DAY_SETTING, $today);
        br_setting_set($conn, AB_CE_QUOTA_USED_SETTING, '0');
        $used = 0;
    }
    $max = ab_ce_daily_max($conn);
    if ($used >= $max) {
        ab_ce_log("[STOP] Günlük kota: kullanılan=$used, max=$max");

        return false;
    }

    return true;
}

function ab_ce_bump_quota(mysqli $conn, bool $ignoreQuota = false): void
{
    if ($ignoreQuota) {
        return;
    }
    $today = date('Y-m-d');
    br_setting_set($conn, AB_CE_QUOTA_DAY_SETTING, $today);
    $used = (int) br_setting_get($conn, AB_CE_QUOTA_USED_SETTING, '0');
    br_setting_set($conn, AB_CE_QUOTA_USED_SETTING, (string) ($used + 1));
}

function ab_ce_quota_status(mysqli $conn): array
{
    $today = date('Y-m-d');
    $day = br_setting_get($conn, AB_CE_QUOTA_DAY_SETTING, '');
    $used = (int) br_setting_get($conn, AB_CE_QUOTA_USED_SETTING, '0');
    if ($day !== $today) {
        $used = 0;
    }
    $max = ab_ce_daily_max($conn);

    return ['used' => $used, 'max' => $max, 'remaining' => max(0, $max - $used)];
}

/**
 * Yeni yazı için staging post (CE seed + link kataloğu).
 *
 * @return array<string, mixed>
 */
function ab_ce_staging_post_from_setting(array $setting): array
{
    $settingId = (int) ($setting['id'] ?? 0);
    $keywords = array_map('trim', explode(',', (string) ($setting['keywords'] ?? '')));
    $keywords = array_values(array_filter($keywords, static fn ($k) => $k !== ''));
    $primary = $keywords[0] ?? 'izmir nakliyat';
    $stagingTitle = mb_convert_case(trim($primary), MB_CASE_TITLE, 'UTF-8');
    if (mb_strlen($stagingTitle) < 12) {
        $stagingTitle = 'İzmir ' . $stagingTitle . ' — rehber';
    }

    $slugBase = slug_olustur($stagingTitle);
    if (mb_strlen($slugBase) > 72) {
        $slugBase = mb_substr($slugBase, 0, 72);
        $slugBase = rtrim($slugBase, '-');
    }

    return [
        'id' => -1 * max(1, $settingId),
        'baslik' => $stagingTitle,
        'slug' => $slugBase !== '' ? $slugBase : 'yeni-yazi',
        'icerik' => '',
        'etiketler' => implode(', ', array_slice($keywords, 0, 8)),
        'kategori_id' => (int) ($setting['category_id'] ?? 0),
        '_ab_source' => 'auto_blog_faz1',
        '_ab_setting_id' => $settingId,
    ];
}

/**
 * @param array<string, mixed> $pack
 * @return array<string, mixed>
 */
function ab_ce_apply_setting_word_band(array $pack, array $setting): array
{
    $min = (int) ($setting['min_words'] ?? 0);
    $max = (int) ($setting['max_words'] ?? 0);
    if ($min > 0 && $max > 0 && $max >= $min) {
        $pack['word_min'] = $min;
        $pack['word_max'] = $max;
    }

    return $pack;
}

/** @return array<string, mixed>|null */
function ab_ce_load_setting_for_post(mysqli $conn, array $post): ?array
{
    $meta = mynak_ce_parse_post_meta($post);
    $sid = (int) ($meta['auto_blog_setting_id'] ?? $post['_ab_setting_id'] ?? 0);
    if ($sid <= 0) {
        return null;
    }
    $stmt = $conn->prepare('SELECT * FROM auto_blog_settings WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $sid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return is_array($row) ? $row : null;
}

/** Uzun makale hedefinde önce güçlü model. */
function ab_ce_model_chain_for_pack(array $pack): array
{
    $preferred = function_exists('get_openai_model') ? get_openai_model() : 'gpt-4o-mini';
    $wMin = (int) ($pack['word_min'] ?? 0);
    if ($wMin >= 1200) {
        return array_values(array_unique(array_filter(['gpt-4o', $preferred, 'gpt-4o-mini'])));
    }
    if ($wMin >= 1000) {
        return array_values(array_unique(array_filter([$preferred, 'gpt-4o', 'gpt-4o-mini'])));
    }

    return array_values(array_unique(array_filter([$preferred, 'gpt-4o-mini'])));
}

function ab_ce_build_user_prompt(array $post, array $pack, array $linkCatalog, array $setting, ?mysqli $conn = null): string
{
    $assembled = mynak_ce_assemble_prompt($post, $linkCatalog, $conn);
    $prompt = (string) ($assembled['prompt'] ?? '');

    $prompt = preg_replace(
        '/OUTPUT:.*$/m',
        'OUTPUT: YENİ makale. Kelime ' . (int) ($pack['word_min'] ?? 1400) . '–' . (int) ($pack['word_max'] ?? 1800)
            . ' (minimum ' . (int) ($pack['word_min'] ?? 1400) . ' kelime — kısa makale geçersiz).'
            . ' İlk satır YENİ_BAŞLIK. h1 yok; URL uydurma yok.',
        $prompt,
        1
    ) ?? $prompt;

    $manual = trim((string) ($setting['manual_command'] ?? ''));
    if ($manual !== '') {
        $manual = mb_strlen($manual) > 600 ? mb_substr($manual, 0, 600) . '…' : $manual;
        $prompt .= "\n\nEK BAĞLAM (düşük öncelik):\n" . $manual;
    }

    return $prompt;
}

/** QC yasak listesindeki klişeleri metinden temizle. */
function ab_ce_scrub_cliches(string $content): string
{
    $phrases = function_exists('mynak_ce_banned_phrases') ? mynak_ce_banned_phrases() : [];
    $extra = ['Bu yazıda', 'bu yazıda', 'Bu rehberde', 'bu rehberde', 'Merhaba', 'Pek çok kişi'];
    foreach (array_unique(array_merge($phrases, $extra)) as $phrase) {
        if ($phrase === '' || mb_strlen($phrase) < 3) {
            continue;
        }
        $pattern = '/' . preg_quote($phrase, '/') . '/iu';
        $content = preg_replace($pattern, '', $content) ?? $content;
    }
    $content = preg_replace('/\s{2,}/u', ' ', $content) ?? $content;
    $content = preg_replace('/<p>\s*<\/p>/iu', '', $content) ?? $content;

    return trim($content);
}

/** Yumuşak format — anahtar kelime bold zorlaması yok (CE ile uyumlu). */
function ab_ce_content_polish(string $content): string
{
    $content = ab_ce_scrub_cliches($content);
    $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);
    $content = preg_replace('/^(#{1})\s+(.+)$/m', '<h2>$2</h2>', $content);
    $content = preg_replace('/^(#{2})\s+(.+)$/m', '<h2>$2</h2>', $content);
    $content = preg_replace('/^(#{3})\s+(.+)$/m', '<h3>$2</h3>', $content);
    $content = preg_replace('/<h1(\b[^>]*)>/i', '<h2$1>', $content);
    $content = preg_replace('/<\/h1>/i', '</h2>', $content);

    if (!preg_match('/<(p|h2|h3|ul|ol|li)\b/i', $content)) {
        $paragraphs = preg_split("/\r?\n\r?\n/", trim($content)) ?: [];
        $formatted = '';
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }
            if (!preg_match('/^<(h[2-6]|ul|ol|li)/', $paragraph)) {
                $formatted .= '<p>' . $paragraph . '</p>' . "\n\n";
            } else {
                $formatted .= $paragraph . "\n\n";
            }
        }
        $content = trim($formatted);
    }

    return trim($content);
}

function ab_ce_finalize_slug(string $title): string
{
    $slug = slug_olustur($title);
    if ($slug === '' || $slug === 'n-a') {
        $slug = 'blog-yazi';
    }
    $parts = array_values(array_filter(explode('-', $slug)));
    $seen = [];
    $clean = [];
    foreach ($parts as $p) {
        if ($p === '' || isset($seen[$p])) {
            continue;
        }
        $seen[$p] = true;
        $clean[] = $p;
    }
    $slug = implode('-', $clean);
    if (mb_strlen($slug) > 72) {
        $slug = rtrim(mb_substr($slug, 0, 72), '-');
    }

    return $slug !== '' ? $slug : 'blog-yazi';
}

function ab_ce_unique_slug(mysqli $conn, string $slug): string
{
    $stmt = $conn->prepare('SELECT id FROM blog_posts WHERE slug = ?');
    if (!$stmt) {
        return $slug;
    }
    $base = $slug;
    $suffix = 1;
    $current = $slug;
    $stmt->bind_param('s', $current);
    while (true) {
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            break;
        }
        $current = $base . '-' . $suffix++;
        $stmt->bind_param('s', $current);
    }
    $stmt->close();

    return $current;
}

function ab_ce_accept_new_title(string $stagingTitle, ?string $candidate): ?string
{
    if ($candidate === null) {
        return null;
    }
    $candidate = trim($candidate);
    if ($candidate === '' || mb_strlen($candidate) < 12 || mb_strlen($candidate) > 110) {
        return null;
    }
    $spam = mynak_ce_title_spam_check($candidate, $stagingTitle);

    return $spam['ok'] ? $candidate : null;
}

function ab_ce_generate_seo_tags(array $keywords, string $title): string
{
    $tags = [];
    foreach ($keywords as $keyword) {
        $keyword = trim($keyword);
        if ($keyword !== '') {
            $tags[] = $keyword;
        }
    }
    $titleWords = explode(' ', mb_strtolower($title, 'UTF-8'));
    $common = ['ve', 'ile', 'için', 'den', 'dan', 'in', 'un', 'ün', 'a', 'e', 'i', 'o', 'u', 'ı', 'ü', 'ö'];
    foreach ($titleWords as $word) {
        $word = trim($word, '.,!?;:');
        if (mb_strlen($word) > 3 && !in_array($word, $common, true) && !in_array($word, $tags, true)) {
            $tags[] = $word;
        }
    }

    return implode(', ', array_slice($tags, 0, 10));
}

function ab_ce_copy_cover_image(array $setting): string
{
    if (empty($setting['cover_image'])) {
        return '';
    }
    $src_path = __DIR__ . '/../' . ltrim((string) $setting['cover_image'], '/');
    $ext = strtolower(pathinfo($src_path, PATHINFO_EXTENSION));
    $new_filename = uniqid('ab_', true) . '.' . $ext;
    $dest_dir = __DIR__ . '/../uploads/blog';
    if (!is_dir($dest_dir)) {
        @mkdir($dest_dir, 0777, true);
    }
    $dest_path = $dest_dir . '/' . $new_filename;
    if (is_file($src_path)) {
        @copy($src_path, $dest_path);

        return $new_filename;
    }

    return '';
}

/**
 * @return array{ok: bool, blog_id?: int, error?: string}
 */
function ab_ce_insert_post(
    mysqli $conn,
    string $baslik,
    string $icerik,
    string $slug,
    int $kategoriId,
    string $etiketler,
    string $kapakFoto,
    int $durum,
    int $authorId,
    int $quality,
    string $metaDesc,
    string $editorNotes,
    string $ceMetaJson
): array {
    br_ensure_content_engine_meta_column($conn);

    $hasMeta = false;
    $hasNotes = false;
    $hasMetaDesc = false;
    $q = $conn->query("SHOW COLUMNS FROM blog_posts LIKE 'content_engine_meta'");
    if ($q && $q->num_rows > 0) {
        $hasMeta = true;
    }
    $q2 = $conn->query("SHOW COLUMNS FROM blog_posts LIKE 'editor_notes'");
    if ($q2 && $q2->num_rows > 0) {
        $hasNotes = true;
    }
    $q3 = $conn->query("SHOW COLUMNS FROM blog_posts LIKE 'meta_description'");
    if ($q3 && $q3->num_rows > 0) {
        $hasMetaDesc = true;
    }

    $isAi = 1;

    if ($hasMeta && $hasNotes && $hasMetaDesc) {
        $sql = 'INSERT INTO blog_posts
            (baslik, icerik, kapak_foto, kategori_id, etiketler, durum, slug,
             author_id, is_ai_generated, ai_quality_score, meta_description,
             content_engine_meta, editor_notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return ['ok' => false, 'error' => $conn->error];
        }
        $stmt->bind_param(
            'sssisisiiisss',
            $baslik,
            $icerik,
            $kapakFoto,
            $kategoriId,
            $etiketler,
            $durum,
            $slug,
            $authorId,
            $isAi,
            $quality,
            $metaDesc,
            $ceMetaJson,
            $editorNotes
        );
    } elseif ($hasNotes && $hasMetaDesc) {
        $sql = 'INSERT INTO blog_posts
            (baslik, icerik, kapak_foto, kategori_id, etiketler, durum, slug,
             author_id, is_ai_generated, ai_quality_score, meta_description, editor_notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return ['ok' => false, 'error' => $conn->error];
        }
        $stmt->bind_param(
            'sssisisiiiss',
            $baslik,
            $icerik,
            $kapakFoto,
            $kategoriId,
            $etiketler,
            $durum,
            $slug,
            $authorId,
            $isAi,
            $quality,
            $metaDesc,
            $editorNotes
        );
    } else {
        $sql = 'INSERT INTO blog_posts
            (baslik, icerik, kapak_foto, kategori_id, etiketler, durum, slug,
             author_id, is_ai_generated, ai_quality_score, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return ['ok' => false, 'error' => $conn->error];
        }
        $stmt->bind_param(
            'sssisisiii',
            $baslik,
            $icerik,
            $kapakFoto,
            $kategoriId,
            $etiketler,
            $durum,
            $slug,
            $authorId,
            $isAi,
            $quality
        );
    }

    if (!$stmt->execute()) {
        return ['ok' => false, 'error' => $stmt->error];
    }

    return ['ok' => true, 'blog_id' => (int) $conn->insert_id];
}

/**
 * Faz 1 ana üretim akışı.
 *
 * @param array<string, mixed> $options preview_mode, ignore_quota
 * @return array<string, mixed>
 */
function ab_ce_generate_article(mysqli $conn, array $setting, array $options = []): array
{
    $previewMode = !empty($options['preview_mode']);
    $ignoreQuota = !empty($options['ignore_quota']);

    if (!$previewMode && !ab_ce_check_quota($conn, $ignoreQuota)) {
        $st = ab_ce_quota_status($conn);

        return [
            'success' => false,
            'message' => 'Günlük Otomatik Blog CE kotası doldu (' . $st['used'] . '/' . $st['max'] . '). Yarın veya blog_bulk_refresh ile devam edin.',
        ];
    }

    $apiKey = get_openai_api_key();
    if ($apiKey === '') {
        return ['success' => false, 'message' => 'OpenAI API anahtarı girilmemiş!'];
    }

    br_ensure_seo_public_path();

    $post = ab_ce_staging_post_from_setting($setting);
    $linkCatalog = br_internal_link_catalog($conn, $post);
    $assembled = mynak_ce_assemble_prompt($post, $linkCatalog, $conn);
    $pack = ab_ce_apply_setting_word_band((array) ($assembled['pack'] ?? []), $setting);
    $pack['source'] = 'auto_blog_ce_faz1';

    $userPrompt = ab_ce_build_user_prompt($post, $pack, $linkCatalog, $setting, $conn);
    $system = mynak_ce_system_prompt();

    $models = ab_ce_model_chain_for_pack($pack);
    $pipe = br_ce_run_generation_pipeline(
        $apiKey,
        $models,
        $system,
        $userPrompt,
        (string) $post['baslik'],
        $pack,
        $linkCatalog,
        static fn (?string $t) => $t !== null ? ab_ce_accept_new_title((string) $post['baslik'], $t) : null,
        'ab_ce_log'
    );
    $html = (string) ($pipe['html'] ?? '');
    $newTitle = $pipe['title'] ?? null;
    $modelUsed = (string) ($pipe['model'] ?? '');

    if ($html === '') {
        return ['success' => false, 'message' => 'OpenAI içerik üretemedi (boş yanıt)'];
    }

    $baslik = $newTitle ?? (string) $post['baslik'];
    $slug = ab_ce_unique_slug($conn, ab_ce_finalize_slug($baslik));
    $post['slug'] = $slug;
    $post['baslik'] = $baslik;

    $html = icerik_donustur($html);
    $html = ab_ce_content_polish($html);
    $metaDesc = mb_substr(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '', 0, 160);
    $qc = mynak_ce_quality_report($post, $html, $pack, $linkCatalog, $newTitle, $modelUsed);
    $qcPass = (bool) ($qc['pass'] ?? false);
    $pack['_qc_pass'] = $qcPass;
    $pack['_qc_queue'] = (string) ($qc['queue'] ?? '');
    $pack['_qc_status'] = (string) ($qc['qc_status'] ?? mynak_ce_qc_gate_status($qc));

    $scores = mynak_ce_compute_score_bundle($html, $post, $pack, $qc);
    $quality = (int) ($scores['structure_score'] ?? mynak_ce_structure_score($html));

    $durum = $qcPass ? MYNAK_CE_DURUM_EDITOR_REVIEW : MYNAK_CE_DURUM_NEEDS_REVISION;

    ab_ce_log('[QC-GATE] setting=' . (int) ($setting['id'] ?? 0)
        . ' status=' . ($pack['_qc_status'] ?? '?')
        . ' durum=' . $durum
        . ' yapı=' . $quality
        . ' ed=' . (int) ($scores['editorial_score'] ?? 0)
        . ' critical=' . implode(',', $qc['critical_fail_keys'] ?? []));

    $ceMeta = mynak_ce_production_metadata_json($post, $pack, $modelUsed, (float) ($pack['temperature'] ?? 0.58));
    $ceMeta = mynak_ce_merge_scores_into_meta($ceMeta, $scores, $pack, $qc);
    $ceMetaArr = json_decode($ceMeta, true);
    if (is_array($ceMetaArr)) {
        $ceMetaArr['pipeline'] = 'auto_blog_ce_faz1';
        $ceMetaArr['auto_blog_setting_id'] = (int) ($setting['id'] ?? 0);
        $ceMeta = json_encode($ceMetaArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    $editorNotes = '[Otomatik Blog CE Faz1 ' . date('Y-m-d H:i') . "]\n"
        . (string) ($qc['editor_note'] ?? '')
        . ($qcPass ? '' : "\n[QC_FAIL] Otomatik yayın yok — düzeltme gerekli.")
        . "\n[CE_META]" . $ceMeta;

    $keywords = array_map('trim', explode(',', (string) ($setting['keywords'] ?? '')));
    $etiketler = ab_ce_generate_seo_tags($keywords, $baslik);
    $kategoriId = (int) ($setting['category_id'] ?? 0);
    $kapakFoto = ab_ce_copy_cover_image($setting);

    $kategoriAdi = '';
    $catRes = $conn->query('SELECT ad FROM blog_categories WHERE id = ' . max(0, $kategoriId));
    if ($catRes && ($catRow = $catRes->fetch_assoc())) {
        $kategoriAdi = (string) $catRow['ad'];
    }

    $kelimeSayisi = br_word_count_html($html);
    $quota = ab_ce_quota_status($conn);

    $qcSummary = [
        'pass' => $qcPass,
        'qc_status' => (string) ($qc['qc_status'] ?? $pack['_qc_status'] ?? ''),
        'queue' => $qc['queue'] ?? '',
        'target_durum' => $durum,
        'critical_fail_keys' => $qc['critical_fail_keys'] ?? [],
        'warning_keys' => $qc['warning_keys'] ?? [],
        'structure_score' => $quality,
        'editorial_score' => (int) ($scores['editorial_score'] ?? 0),
        'coverage_score' => (int) ($scores['coverage_score'] ?? 0),
        'word_count' => $kelimeSayisi,
        'word_target' => [(int) ($pack['word_min'] ?? 0), (int) ($pack['word_max'] ?? 0)],
    ];

    if ($previewMode) {
        return [
            'success' => true,
            'preview' => [
                'baslik' => $baslik,
                'icerik' => $html,
                'icerik_raw' => $html,
                'slug' => $slug,
                'kategori_id' => $kategoriId,
                'kategori_adi' => $kategoriAdi,
                'etiketler' => $etiketler,
                'kapak_foto' => $kapakFoto,
                'kelime_sayisi' => $kelimeSayisi,
                'durum' => $durum,
                'qc' => $qcSummary,
                'ce_meta' => json_decode($ceMeta, true),
                'editor_notes_preview' => $editorNotes,
            ],
            'qc' => $qcSummary,
            'quota' => $quota,
        ];
    }

    $authorId = auto_blog_pick_author_id($kategoriId);
    $insert = ab_ce_insert_post(
        $conn,
        $baslik,
        $html,
        $slug,
        $kategoriId,
        $etiketler,
        $kapakFoto,
        $durum,
        $authorId,
        $quality,
        $metaDesc,
        $editorNotes,
        $ceMeta
    );

    if (!($insert['ok'] ?? false)) {
        return ['success' => false, 'message' => 'Veritabanı hatası: ' . ($insert['error'] ?? 'kayıt başarısız')];
    }

    ab_ce_bump_quota($conn, $ignoreQuota);

    $blogId = (int) ($insert['blog_id'] ?? 0);
    $msg = $qcPass
        ? 'Yazı üretildi → QC PASS → Editör kuyruğu (durum=1). Otomatik yayın yok.'
        : 'Yazı üretildi → QC FAIL → Revize kuyruğu (durum=2). Otomatik yayın yok.';

    return [
        'success' => true,
        'message' => $msg,
        'blog_id' => $blogId,
        'slug' => $slug,
        'durum' => $durum,
        'qc_pass' => $qcPass,
        'qc' => $qcSummary,
        'in_review' => $durum === MYNAK_BLOG_STATUS_EDITOR_QUEUE,
        'needs_revision' => $durum === MYNAK_BLOG_STATUS_REVISION,
        'review_url' => '/admin/blog_review.php?focus=' . $blogId . ($qcPass ? '' : '&status=2'),
        'quota' => ab_ce_quota_status($conn),
    ];
}

/**
 * Basit akış: Tekrar yaz → QC → hazırsa editör kuyruğu (1), değilse düzeltme (2).
 * Yayında (3) → toplu yenileme motoru.
 *
 * @return array<string, mixed>
 */
function ab_ce_rewrite_and_send(mysqli $conn, int $postId): array
{
    $stmt = $conn->prepare('SELECT * FROM blog_posts WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return ['success' => false, 'message' => 'Veritabanı hatası'];
    }
    $stmt->bind_param('i', $postId);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    if (!$post) {
        return ['success' => false, 'message' => 'Yazı bulunamadı'];
    }

    $durum = (int) ($post['durum'] ?? 0);
    if ($durum === 3) {
        $result = blog_bulk_content_refresh_run($conn, [
            'post_id' => $postId,
            'limit' => 1,
            'ignore_quota' => true,
        ]);
        $processed = (int) ($result['processed'] ?? 0);
        $qcPassed = (int) ($result['qc_passed'] ?? 0);
        $qcFailed = (int) ($result['qc_failed'] ?? 0);

        return [
            'success' => $processed > 0,
            'message' => $processed > 0
                ? ($qcPassed > 0
                    ? 'Yeniden yazıldı → Editör kuyruğuna alındı. İnceleyip yayınlayabilirsiniz.'
                    : 'Yeniden yazıldı → Hâlâ düzeltme gerekiyor. Metni kontrol edin veya tekrar deneyin.')
                : ($result['message'] ?? 'İşlem yapılamadı'),
            'qc_pass' => $qcPassed > 0,
            'review_url' => '/admin/blog_review.php?' . ($qcPassed > 0 ? 'status=1&focus=' : 'status=2&focus=') . $postId,
        ];
    }

    if (!in_array($durum, [1, 2], true)) {
        return ['success' => false, 'message' => 'Bu yazı durumunda otomatik yeniden yazma yok (taslak/yayın dışı).'];
    }

    $meta = mynak_ce_parse_post_meta($post);
    if (!empty($meta['editor_fingerprint']) && is_array($meta['editor_fingerprint'])) {
        $post['_editor_fingerprint'] = $meta['editor_fingerprint'];
    }

    $apiKey = get_openai_api_key();
    if ($apiKey === '') {
        return ['success' => false, 'message' => 'OpenAI API anahtarı girilmemiş'];
    }

    br_ensure_seo_public_path();
    $linkCatalog = br_internal_link_catalog($conn, $post);
    $pack = mynak_ce_build_production_pack($post, $conn);
    $abSetting = ab_ce_load_setting_for_post($conn, $post);
    if ($abSetting !== null) {
        $pack = ab_ce_apply_setting_word_band($pack, $abSetting);
    }
    if ($abSetting !== null) {
        $pack['word_min'] = max(1400, (int) ($abSetting['min_words'] ?? $pack['word_min'] ?? 1400));
        $pack['word_max'] = max(1600, (int) ($abSetting['max_words'] ?? $pack['word_max'] ?? 1900));
    } else {
        $pack['word_min'] = max(1400, (int) ($pack['word_min'] ?? 1400));
        $pack['word_max'] = max(1900, (int) ($pack['word_max'] ?? 1900));
    }
    $system = mynak_ce_system_prompt();
    $baslikForGen = (string) $post['baslik'];
    $lf = br_ce_rewrite_longform_only($apiKey, $system, $baslikForGen, $pack, $linkCatalog, 'ab_ce_log');
    $html = (string) ($lf['html'] ?? '');
    $modelUsed = (string) ($lf['model'] ?? 'gpt-4o-rewrite-v2');
    $newTitle = null;

    if ($html === '') {
        return ['success' => false, 'message' => 'AI uzun metin üretemedi — API/model kontrol edin (gpt-4o).'];
    }

    $wcBeforeConvert = br_word_count_html($html);
    ab_ce_log('[REWRITE-V2] wc_before_convert=' . $wcBeforeConvert);
    if (!preg_match('/<(p|h2|h3|ul|ol|li)\b/i', $html)) {
        $html = icerik_donustur($html);
    }
    $html = ab_ce_content_polish($html);
    ab_ce_log('[REWRITE-V2] wc_after_polish=' . br_word_count_html($html));
    $slug = (string) $post['slug'];
    $baslik = $newTitle ?? (string) $post['baslik'];
    $post['baslik'] = $baslik;
    $qc = mynak_ce_quality_report($post, $html, $pack, $linkCatalog, $newTitle, $modelUsed);
    $qcPass = (bool) ($qc['pass'] ?? false);
    $scores = mynak_ce_compute_score_bundle($html, $post, $pack, $qc);
    $quality = (int) ($scores['structure_score'] ?? mynak_ce_structure_score($html));
    $metaDesc = mb_substr(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '', 0, 160);
    $pack['_qc_pass'] = $qcPass;
    $pack['_qc_status'] = (string) ($qc['qc_status'] ?? mynak_ce_qc_gate_status($qc));
    $ceMeta = mynak_ce_production_metadata_json($post, $pack, $modelUsed, (float) ($pack['temperature'] ?? 0.58));
    $ceMeta = mynak_ce_merge_scores_into_meta($ceMeta, $scores, $pack, $qc);
    $note = '[Tekrar yaz ' . date('Y-m-d H:i') . " CE-REWRITE-V2 model={$modelUsed}]\n" . (string) ($qc['editor_note'] ?? '');

    $saved = br_save_ce_queue_rewrite(
        $conn,
        $postId,
        $slug,
        $baslik,
        $html,
        $metaDesc,
        $quality,
        $note,
        $newTitle !== null,
        $ceMeta,
        $qcPass
    );

    if (!$saved) {
        return ['success' => false, 'message' => 'Kayıt güncellenemedi'];
    }

    $kelime = br_word_count_html($html);
    $simple = $qcPass
        ? 'Tamam — metin yenilendi (' . $kelime . ' kelime). Editör kuyruğunda.'
        : 'Metin yenilendi (' . $kelime . ' kelime, hedef ' . (int) $pack['word_min'] . '+) — otomatik kontrol tam geçmedi. Tekrar deneyin veya elle düzeltin. Model: ' . $modelUsed;

    return [
        'success' => true,
        'message' => $simple,
        'qc_pass' => $qcPass,
        'kelime' => $kelime,
        'model' => $modelUsed,
        'review_url' => '/admin/blog_review.php?' . ($qcPass ? 'status=1&focus=' : 'status=2&focus=') . $postId,
    ];
}
