<?php
/**
 * Blog toplu içerik yenileme (AI) — ortak kütüphane.
 * CLI: scripts/blog_bulk_content_refresh.php
 * Web: admin/blog_bulk_refresh.php
 *
 * Yayında (durum=3) yazıların gövdesini yeniler; slug sabit.
 * QC PASS → durum=1 (editor_review). QC FAIL → durum=2 (needs_revision); otomatik yayın yok.
 */
declare(strict_types=1);

require_once __DIR__ . '/mynak_local_seo_content_engine.php';
require_once __DIR__ . '/mynak_ce_production.php';

/**
 * @var string $logFile
 */
function br_log(string $msg): void
{
    global $logFile;
    $line = '[' . date('Y-m-d H:i:s') . "] $msg\n";
    if (PHP_SAPI === 'cli') {
        echo $line;
    }
    if (isset($GLOBALS['mynak_br_log_buffer']) && is_array($GLOBALS['mynak_br_log_buffer'])) {
        $GLOBALS['mynak_br_log_buffer'][] = rtrim($line);
    }
    if (isset($logFile) && $logFile !== '') {
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
}

function br_ensure_column(mysqli $conn): void
{
    $q = $conn->query("SHOW COLUMNS FROM blog_posts LIKE 'seo_content_upgrade_2026_at'");
    if ($q && $q->num_rows > 0) {
        return;
    }
    $conn->query("ALTER TABLE blog_posts ADD COLUMN seo_content_upgrade_2026_at DATETIME NULL DEFAULT NULL AFTER updated_at");
    br_log('[OK] Sütun eklendi: seo_content_upgrade_2026_at');
}

/** GSC / CE metadata için JSON kolonu (ileride editor_notes yerine). */
function br_ensure_content_engine_meta_column(mysqli $conn): void
{
    $q = $conn->query("SHOW COLUMNS FROM blog_posts LIKE 'content_engine_meta'");
    if ($q && $q->num_rows > 0) {
        return;
    }
    $sql = 'ALTER TABLE blog_posts ADD COLUMN content_engine_meta LONGTEXT NULL DEFAULT NULL AFTER editor_notes';
    if (!$conn->query($sql)) {
        br_log('[WARN] content_engine_meta eklenemedi: ' . $conn->error);

        return;
    }
    br_log('[OK] Sütun eklendi: content_engine_meta');
}

function br_ce_batch_version(mysqli $conn): string
{
    return mynak_ce_batch_version($conn);
}

function br_ce_batch_version_set(mysqli $conn, string $version): void
{
    $v = trim($version);
    if ($v === '') {
        $v = '1';
    }
    br_setting_set($conn, MYNAK_CE_BATCH_SETTING, $v);
}

/** batch_version +1 — eski motor / QC-fail re-run için. */
function br_ce_batch_version_bump(mysqli $conn): string
{
    $cur = (int) br_ce_batch_version($conn);
    $next = (string) max(1, $cur + 1);
    br_ce_batch_version_set($conn, $next);

    return $next;
}

/** QC FAIL veya eski CE (v1) — yeniden işleme adayı sayısı. */
function br_ce_rerun_candidate_count(mysqli $conn): int
{
    $q = $conn->query(
        "SELECT COUNT(*) AS c FROM blog_posts
         WHERE durum = 2
           AND (editor_notes LIKE '%[QC_GATE] FAIL%'
                OR editor_notes LIKE '%[QC_FAIL]%'
                OR (editor_notes LIKE '%[CE_META]%' AND editor_notes NOT LIKE '%\"qc_pass\":true%'))"
    );
    if (!$q) {
        return 0;
    }

    return (int) (($q->fetch_assoc()['c'] ?? 0));
}

function br_setting_get(mysqli $conn, string $name, string $default = ''): string
{
    $stmt = $conn->prepare('SELECT value FROM settings WHERE name = ? LIMIT 1');
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return $row && isset($row['value']) ? (string) $row['value'] : $default;
}

function br_setting_set(mysqli $conn, string $name, string $value): void
{
    $exists = $conn->query("SELECT id FROM settings WHERE name = '" . $conn->real_escape_string($name) . "' LIMIT 1");
    if ($exists && $exists->num_rows > 0) {
        $stmt = $conn->prepare('UPDATE settings SET value = ? WHERE name = ?');
        $stmt->bind_param('ss', $value, $name);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare('INSERT INTO settings (name, value) VALUES (?, ?)');
        $stmt->bind_param('ss', $name, $value);
        $stmt->execute();
    }
}

function br_daily_max(mysqli $conn): int
{
    $v = br_setting_get($conn, 'blog_bulk_refresh_daily_max', '3');
    $n = (int) $v;

    return $n >= 1 ? min($n, 50) : 3;
}

function br_check_and_consume_quota(mysqli $conn, int $need, bool $ignore): bool
{
    if ($ignore) {
        return true;
    }
    $today = date('Y-m-d');
    $day = br_setting_get($conn, 'blog_bulk_refresh_quota_day', '');
    $used = (int) br_setting_get($conn, 'blog_bulk_refresh_quota_used', '0');
    if ($day !== $today) {
        br_setting_set($conn, 'blog_bulk_refresh_quota_day', $today);
        br_setting_set($conn, 'blog_bulk_refresh_quota_used', '0');
        $used = 0;
    }
    $max = br_daily_max($conn);
    if ($used + $need > $max) {
        $remaining = max(0, $max - $used);
        br_log("[STOP] Günlük kota doldu veya yetersiz: kullanılan=$used, max=$max, kalan=$remaining.");

        return false;
    }

    return true;
}

function br_bump_quota(mysqli $conn, int $by, bool $ignore): void
{
    if ($ignore || $by <= 0) {
        return;
    }
    $today = date('Y-m-d');
    br_setting_set($conn, 'blog_bulk_refresh_quota_day', $today);
    $used = (int) br_setting_get($conn, 'blog_bulk_refresh_quota_used', '0');
    br_setting_set($conn, 'blog_bulk_refresh_quota_used', (string) ($used + $by));
}

function br_word_count_html(string $html): int
{
    $plain = strip_tags($html);

    return (int) preg_match_all('/[\w\p{L}]+/u', $plain);
}

/** QC word_range kritik eşiği (~%75 alt sınır). */
function br_ce_word_floor(array $pack): int
{
    $wMin = (int) ($pack['word_min'] ?? 1400);

    return max(400, (int) ($wMin * 0.75));
}

/**
 * Kısa CE çıktısı → tek genişletme turu (stabilizasyon; yeni prompt paketi değil).
 *
 * @param array<string, mixed> $pack
 * @param list<array{path?: string, url?: string}> $linkCatalog
 */
function br_ce_extend_if_short(
    string $apiKey,
    string $model,
    string $system,
    string $html,
    string $baslik,
    array $pack,
    array $linkCatalog,
    callable $logFn = null
): string {
    $floor = br_ce_word_floor($pack);
    $wMin = (int) ($pack['word_min'] ?? 1400);
    $wMax = (int) ($pack['word_max'] ?? 1900);
    $current = $html;

    $maxAttempts = 4;
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $wc = br_word_count_html($current);
        if ($wc >= $floor) {
            break;
        }
        $extendModel = $model;
        if ($wc < $floor && ($attempt >= 2 || str_contains($model, 'mini'))) {
            $extendModel = 'gpt-4o';
        }
        $extendPrompt = "GÖREV: Aşağıdaki Türkçe HTML makaleyi KORUYARAK genişlet (deneme {$attempt}/{$maxAttempts}).\n"
            . "- Hedef en az {$wMin} kelime (üst sınır ~{$wMax}); şu an ~{$wc} kelime.\n"
            . "- Konu/başlık aynı kalsın: {$baslik}\n"
            . "- h1 yok; mevcut H2 korunur, en az 2 yeni H2/H3 ve 3–5 SSS (h3) ekle.\n"
            . "- İç linkleri koru; anahtar kelimeyi doğal dağıt (stuffing yok).\n"
            . "- Kullanma: Günümüzde, Bu yazıda, Sonuç olarak, En iyi firma, lider firma.\n"
            . "- Sadece güncellenmiş HTML gövdesi döndür.\n\n"
            . $current;
        $r = br_openai_complete($apiKey, $extendModel, $system, $extendPrompt, 0.42);
        if (!($r['ok'] ?? false) || empty($r['content'])) {
            if ($logFn) {
                $logFn("[WORD-EXTEND] attempt={$attempt} fail wc=$wc floor=$floor model={$extendModel} err=" . ($r['error'] ?? ''));
            }
            break;
        }
        $parsed = br_parse_ai_response((string) $r['content']);
        $extended = trim((string) ($parsed['html'] ?? ''));
        if ($extended === '') {
            $extended = trim((string) $r['content']);
        }
        if ($extended !== '' && function_exists('br_sanitize_internal_anchors')) {
            $extended = br_sanitize_internal_anchors($extended, $linkCatalog);
        }
        if ($extended !== '' && function_exists('ab_ce_content_polish')) {
            $extended = ab_ce_content_polish($extended);
        }
        $wc2 = br_word_count_html($extended);
        if ($logFn) {
            $logFn("[WORD-EXTEND] attempt={$attempt} wc {$wc} -> {$wc2} floor={$floor} model={$extendModel}");
        }
        if ($wc2 > $wc && $extended !== '') {
            $current = $extended;
        } else {
            break;
        }
    }

    return $current;
}

/**
 * Kısa kaldıysa sıfırdan uzun makale (yalnızca gpt-4o).
 *
 * @param array<string, mixed> $pack
 * @param list<array{path?: string, url?: string}> $linkCatalog
 */
function br_ce_longform_regenerate(
    string $apiKey,
    string $system,
    string $baslik,
    array $pack,
    array $linkCatalog,
    callable $logFn = null,
    int $pass = 1
): string {
    $wMin = (int) ($pack['word_min'] ?? 1400);
    $wMax = (int) ($pack['word_max'] ?? 1900);
    if ($pass > 1) {
        $wMin = (int) max($wMin, 1500);
    }
    $banned = function_exists('mynak_ce_banned_phrases')
        ? implode(', ', array_slice(mynak_ce_banned_phrases(), 0, 12))
        : 'Günümüzde, Bu yazıda, Müşteri memnuniyeti';
    $links = [];
    foreach (array_slice($linkCatalog, 0, 6) as $item) {
        $links[] = (string) ($item['path'] ?? $item['url'] ?? '');
    }
    $linkLine = $links !== [] ? implode(', ', array_filter($links)) : '/teklif-al, /blog';

    $prompt = "TAMAMEN YENİ Türkçe HTML makale yaz (tur {$pass}).\n"
        . "Konu başlığı: {$baslik}\n"
        . "ZORUNLU uzunluk: EN AZ {$wMin} kelime — 700 kelimelik kısa metin KABUL EDİLMEZ (üst ~{$wMax}).\n"
        . "Yapı: en az 8 H2, en az 4 SSS (h3), en az 12 paragraf (p), 2 liste (ul/li); h1 YOK.\n"
        . "İç link (doğal): {$linkLine}\n"
        . "YASAK ifadeler (hiç kullanma): {$banned}, Merhaba, Bu rehberde.\n"
        . "Çıktı formatı: ilk satır YENİ_BAŞLIK: … sonra boş satır, sonra HTML gövde.\n";

    $r = br_openai_complete($apiKey, 'gpt-4o', $system, $prompt, 0.52);
    if (!($r['ok'] ?? false) || empty($r['content'])) {
        if ($logFn) {
            $logFn('[LONGFORM] fail err=' . ($r['error'] ?? 'empty'));
        }

        return '';
    }
    $parsed = br_parse_ai_response((string) $r['content']);
    $html = br_sanitize_internal_anchors($parsed['html'], $linkCatalog);
    if (function_exists('ab_ce_content_polish')) {
        $html = ab_ce_content_polish($html);
    }
    $wc = br_word_count_html($html);
    if ($logFn) {
        $logFn("[LONGFORM] wc={$wc} target={$wMin}");
    }

    return br_ce_extend_if_short($apiKey, 'gpt-4o', $system, $html, $baslik, $pack, $linkCatalog, $logFn);
}

/**
 * Kuyruk yeniden yazma — doğrudan longform (2 tur gerekirse).
 *
 * @param array<string, mixed> $pack
 * @param list<array{path?: string, url?: string}> $linkCatalog
 * @return array{html: string, model: string, kelime: int}
 */
function br_ce_rewrite_longform_only(
    string $apiKey,
    string $system,
    string $baslik,
    array $pack,
    array $linkCatalog,
    callable $logFn = null
): array {
    $floor = br_ce_word_floor($pack);
    $html = br_ce_longform_regenerate($apiKey, $system, $baslik, $pack, $linkCatalog, $logFn, 1);
    $wc = $html !== '' ? br_word_count_html($html) : 0;
    if ($html !== '' && $wc < $floor) {
        if ($logFn) {
            $logFn("[REWRITE-V2] pass2 needed wc={$wc} floor={$floor}");
        }
        $html2 = br_ce_longform_regenerate($apiKey, $system, $baslik, $pack, $linkCatalog, $logFn, 2);
        $wc2 = $html2 !== '' ? br_word_count_html($html2) : 0;
        if ($wc2 > $wc) {
            $html = $html2;
            $wc = $wc2;
        }
    }

    return [
        'html' => $html,
        'model' => 'gpt-4o-rewrite-v2',
        'kelime' => $wc,
    ];
}

/**
 * CE üretim + extend + gerekirse longform (ortak boru hattı).
 *
 * @param list<string> $models
 * @param array<string, mixed> $pack
 * @param list<array{path?: string, url?: string}> $linkCatalog
 * @return array{html: string, title: ?string, model: string, kelime: int}
 */
function br_ce_run_generation_pipeline(
    string $apiKey,
    array $models,
    string $system,
    string $userPrompt,
    string $baslikFallback,
    array $pack,
    array $linkCatalog,
    ?callable $acceptTitleFn = null,
    callable $logFn = null
): array {
    $floor = br_ce_word_floor($pack);
    $html = '';
    $newTitle = null;
    $modelUsed = '';
    $lastWc = 0;

    foreach ($models as $model) {
        $r = br_openai_complete($apiKey, $model, $system, $userPrompt, (float) ($pack['temperature'] ?? 0.58));
        if (!($r['ok'] ?? false) || empty($r['content'])) {
            if ($logFn) {
                $logFn('[PIPE] model=' . $model . ' err=' . ($r['error'] ?? 'empty'));
            }
            continue;
        }
        $modelUsed = $model;
        $parsed = br_parse_ai_response((string) $r['content']);
        $candidateHtml = br_sanitize_internal_anchors($parsed['html'], $linkCatalog);
        if (function_exists('ab_ce_content_polish')) {
            $candidateHtml = ab_ce_content_polish($candidateHtml);
        }
        $titleCandidate = $parsed['title'];
        if ($acceptTitleFn && $titleCandidate !== null) {
            $accepted = $acceptTitleFn($titleCandidate);
            if ($accepted !== null) {
                $newTitle = $accepted;
            }
        }
        $titleForExtend = $newTitle ?? $baslikFallback;
        $candidateHtml = br_ce_extend_if_short($apiKey, $modelUsed, $system, $candidateHtml, $titleForExtend, $pack, $linkCatalog, $logFn);
        $lastWc = br_word_count_html($candidateHtml);
        $html = $candidateHtml;
        if ($logFn) {
            $logFn("[PIPE] model={$modelUsed} wc={$lastWc} floor={$floor}");
        }
        if ($lastWc >= $floor) {
            break;
        }
    }

    if ($html !== '' && $lastWc < $floor) {
        if ($logFn) {
            $logFn("[PIPE] longform fallback wc={$lastWc}");
        }
        $long = br_ce_longform_regenerate($apiKey, $system, $newTitle ?? $baslikFallback, $pack, $linkCatalog, $logFn);
        if ($long !== '') {
            $html = $long;
            $modelUsed = 'gpt-4o-longform';
            $lastWc = br_word_count_html($html);
        }
    }

    return [
        'html' => $html,
        'title' => $newTitle,
        'model' => $modelUsed,
        'kelime' => $lastWc,
    ];
}

/**
 * CLI ve bazı giriş noktalarında mynak_public_path yoksa site_url_define + seo.php yüklenir.
 */
function br_ensure_seo_public_path(): void
{
    if (function_exists('mynak_public_path')) {
        return;
    }
    if (!defined('SITE_URL')) {
        require_once dirname(__DIR__) . '/config/site_url_define.php';
        if (!defined('SITE_URL')) {
            return;
        }
    }
    require_once dirname(__DIR__) . '/config/seo.php';
}

function br_link_public_path_key(string $publicPath): string
{
    $p = str_replace('\\', '/', rawurldecode(trim($publicPath)));
    if ($p === '') {
        return '/';
    }
    if ($p[0] !== '/') {
        $p = '/' . $p;
    }

    return rtrim($p, '/') ?: '/';
}

function br_hosts_same_site(string $hrefHost, string $siteHost): bool
{
    $hrefHost = strtolower(preg_replace('/:\d+$/', '', $hrefHost));
    $siteHost = strtolower(preg_replace('/:\d+$/', '', $siteHost));
    if ($hrefHost === '' || $siteHost === '') {
        return false;
    }
    if ($hrefHost === $siteHost) {
        return true;
    }
    $hn = preg_replace('/^www\./i', '', $hrefHost);
    $sn = preg_replace('/^www\./i', '', $siteHost);

    return $hn === $sn;
}

/**
 * href → site içi path anahtarı (whitelist ile eşleştirme için).
 */
function br_href_resolve_to_public_path_key(string $href): string
{
    $href = trim(html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($href === '' || $href[0] === '#' || str_starts_with($href, '#')) {
        return '';
    }
    if (preg_match('#^(mailto:|tel:|javascript:)#i', $href)) {
        return '';
    }
    if (str_starts_with($href, '//')) {
        return '';
    }

    $path = '';
    if (preg_match('#^https?://#i', $href)) {
        $p = parse_url($href);
        if (!is_array($p) || empty($p['host'])) {
            return '';
        }
        $linkHost = (string) $p['host'];
        $siteHost = '';
        if (defined('SITE_URL')) {
            $sh = parse_url((string) SITE_URL, PHP_URL_HOST);
            $siteHost = is_string($sh) ? $sh : '';
        }
        if ($siteHost !== '' && !br_hosts_same_site($linkHost, $siteHost)) {
            return '';
        }
        $path = isset($p['path']) ? (string) $p['path'] : '/';
    } else {
        $path = $href;
    }

    $path = str_replace('\\', '/', rawurldecode($path));
    if (str_contains($path, '?')) {
        $path = explode('?', $path, 2)[0];
    }
    if (str_contains($path, '#')) {
        $path = explode('#', $path, 2)[0];
    }
    if ($path === '') {
        return '/';
    }
    if ($path[0] !== '/') {
        $path = '/' . $path;
    }

    return br_link_public_path_key($path);
}

function br_ensure_seo_cluster(): void
{
    if (function_exists('seo_rt_pillar_cluster_definitions')) {
        return;
    }
    br_ensure_seo_public_path();
    $base = dirname(__DIR__) . '/includes/seo_runtime/';
    if (is_file($base . 'paths.php')) {
        require_once $base . 'paths.php';
    }
    if (is_file($base . 'internal_linking.php')) {
        require_once $base . 'internal_linking.php';
    }
}

/** @return list<string> */
function br_izmir_district_slug_tokens(): array
{
    return [
        'aliaga', 'balcova', 'bayindir', 'bayrakli', 'bergama', 'beydag', 'bornova', 'buca',
        'cesme', 'cigli', 'dikili', 'foca', 'gaziemir', 'guzelbahce', 'karabaglar', 'karaburun',
        'karsiyaka', 'kemalpasa', 'kinik', 'kiraz', 'konak', 'menderes', 'menemen', 'narlidere',
        'odemis', 'seferihisar', 'selcuk', 'tire', 'torbali', 'urla',
    ];
}

/**
 * @return array{hay: string, slug: string}
 */
function br_post_topic_signals(array $post): array
{
    $slug = mb_strtolower(trim((string) ($post['slug'] ?? '')), 'UTF-8');
    $title = mb_strtolower(trim((string) ($post['baslik'] ?? '')), 'UTF-8');
    $tags = mb_strtolower(trim((string) ($post['etiketler'] ?? '')), 'UTF-8');

    return [
        'hay' => $slug . ' ' . $title . ' ' . $tags,
        'slug' => $slug,
    ];
}

/**
 * SEO/AI öncelik skoru — yüksek skor önce işlenir (hub-first strateji).
 */
function br_post_priority_score(array $post): int
{
    $sig = br_post_topic_signals($post);
    $hay = $sig['hay'];
    $score = 0;

    if (str_contains($hay, 'izmir-evden-eve') || str_contains($hay, 'evden-eve-nakliyat')) {
        $score += 100;
    } elseif (str_contains($hay, 'evden-eve')) {
        $score += 80;
    }
    if (str_contains($hay, 'izmir') && str_contains($hay, 'nakliyat')) {
        $score += 40;
    }
    foreach (br_izmir_district_slug_tokens() as $district) {
        if (str_contains($hay, $district)) {
            $score += 35;
            break;
        }
    }
    foreach (['fiyat', 'ucret', 'maliyet', 'tavsiye', 'oneri', 'yorum', 'guvenilir', 'en-iyi'] as $kw) {
        if (str_contains($hay, $kw)) {
            $score += 25;
        }
    }
    if (str_contains($hay, 'nakliyat')) {
        $score += 10;
    }

    return $score;
}

function br_detect_graph_cluster_slug(array $post): ?string
{
    br_ensure_seo_cluster();
    if (!function_exists('seo_rt_pillar_cluster_definitions')) {
        return null;
    }

    $slug = br_post_topic_signals($post)['slug'];
    $defs = seo_rt_pillar_cluster_definitions();
    $keys = array_keys($defs);
    usort($keys, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

    foreach ($keys as $graphSlug) {
        if ($graphSlug === 'blog' || $graphSlug === '') {
            continue;
        }
        if (str_contains($slug, $graphSlug)) {
            return $graphSlug;
        }
        $short = preg_replace('#^izmir-#', '', $graphSlug) ?? $graphSlug;
        if ($short !== $graphSlug && $short !== '' && str_contains($slug, $short)) {
            return $graphSlug;
        }
    }

    if (str_contains($slug, 'evden') && str_contains($slug, 'nakliyat')) {
        return function_exists('seo_rt_money_page_pillar_slug')
            ? seo_rt_money_page_pillar_slug()
            : 'izmir-evden-eve-nakliyat';
    }

    return null;
}

/**
 * @param array<string, bool> $seen
 */
function br_catalog_push(array &$catalog, array &$seen, ?array $item): void
{
    if ($item === null) {
        return;
    }
    $path = br_link_public_path_key((string) ($item['path'] ?? ''));
    if ($path === '' || isset($seen[$path])) {
        return;
    }
    $seen[$path] = true;
    $catalog[] = [
        'path' => $path,
        'title' => (string) ($item['title'] ?? ''),
        'type' => (string) ($item['type'] ?? ''),
    ];
}

/**
 * Küme / sayfa / hizmet slug → whitelist satırı (yayında değilse null).
 *
 * @return ?array{path: string, title: string, type: string}
 */
function br_catalog_resolve_graph_slug(mysqli $conn, string $graphSlug, string $typeLabel): ?array
{
    br_ensure_seo_public_path();
    if (!function_exists('mynak_public_path')) {
        return null;
    }

    br_ensure_seo_cluster();
    $defs = function_exists('seo_rt_pillar_cluster_definitions')
        ? seo_rt_pillar_cluster_definitions()
        : [];
    if ($graphSlug === 'blog' && isset($defs['blog']['path'])) {
        $path = (string) $defs['blog']['path'];
        $title = (string) ($defs['blog']['nav_title'] ?? 'Blog');

        return ['path' => $path, 'title' => $title, 'type' => 'liste'];
    }

    $liveSlug = function_exists('seo_rt_public_url_slug_for_graph_slug')
        ? seo_rt_public_url_slug_for_graph_slug($graphSlug)
        : $graphSlug;
    $title = '';

    $st = $conn->prepare('SELECT title FROM pages WHERE status = 1 AND slug = ? LIMIT 1');
    if ($st) {
        $st->bind_param('s', $liveSlug);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        if ($row) {
            $title = (string) ($row['title'] ?? '');
        }
    }
    if ($title === '') {
        $st = $conn->prepare('SELECT ana_baslik FROM services WHERE status = 1 AND slug = ? LIMIT 1');
        if ($st) {
            $st->bind_param('s', $liveSlug);
            $st->execute();
            $row = $st->get_result()->fetch_assoc();
            $st->close();
            if ($row) {
                $title = (string) ($row['ana_baslik'] ?? '');
            }
        }
    }
    if ($title === '' && isset($defs[$graphSlug]['nav_title'])) {
        $title = (string) $defs[$graphSlug]['nav_title'];
    }
    if ($title === '') {
        return null;
    }

    return [
        'path' => mynak_public_path($liveSlug),
        'title' => $title,
        'type' => $typeLabel,
    ];
}

/**
 * Konuya yakın yayın blogları (RAND yok — slug/başlık uyumu).
 *
 * @return list<array{baslik: string, slug: string}>
 */
function br_fetch_topic_matched_blogs(mysqli $conn, array $post, int $limit): array
{
    $id = (int) ($post['id'] ?? 0);
    $sig = br_post_topic_signals($post);
    $hay = $sig['hay'];
    $patterns = [];

    if (str_contains($hay, 'evden-eve') || (str_contains($hay, 'evden') && str_contains($hay, 'eve'))) {
        $patterns[] = '%evden%eve%';
    }
    if (str_contains($hay, 'izmir')) {
        $patterns[] = '%izmir%';
    }
    foreach (br_izmir_district_slug_tokens() as $d) {
        if (str_contains($hay, $d)) {
            $patterns[] = '%' . $d . '%';
            break;
        }
    }
    if ($patterns === []) {
        $patterns[] = '%nakliyat%';
    }
    $patterns = array_values(array_unique($patterns));
    $p1 = $patterns[0] ?? '%nakliyat%';
    $p2 = $patterns[1] ?? $p1;

    $sql = "SELECT baslik, slug FROM blog_posts
        WHERE durum = 3 AND id != ?
          AND (slug LIKE ? OR baslik LIKE ? OR slug LIKE ? OR baslik LIKE ?)
        ORDER BY
          CASE WHEN slug LIKE '%izmir%evden%' THEN 0
               WHEN slug LIKE '%evden-eve%' THEN 1
               ELSE 2 END,
          id DESC
        LIMIT ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param('issssi', $id, $p1, $p1, $p2, $p2, $limit);
    $stmt->execute();
    $out = [];
    $res = $stmt->get_result();
    while ($res && ($row = $res->fetch_assoc())) {
        $out[] = $row;
    }
    $stmt->close();

    return $out;
}

/**
 * Hub-first + konu eşleşmeli iç link kataloğu (SEO/AI SSOT ile uyumlu).
 *
 * @return list<array{path: string, title: string, type: string}>
 */
function br_internal_link_catalog(mysqli $conn, array $post): array
{
    br_ensure_seo_public_path();
    if (!function_exists('mynak_public_path') || !function_exists('mynak_blog_href_path')) {
        return [];
    }

    $catalog = [];
    $seen = [];
    $maxItems = 18;

    $pillarSlug = function_exists('seo_rt_money_page_pillar_slug')
        ? seo_rt_money_page_pillar_slug()
        : 'izmir-evden-eve-nakliyat';

    $hubSlugs = [
        [$pillarSlug, 'hub'],
        ['teklif-alin', 'cta'],
        ['belgelerimiz', 'guven'],
        ['hakkimizda', 'guven'],
    ];
    foreach ($hubSlugs as [$g, $type]) {
        br_catalog_push($catalog, $seen, br_catalog_resolve_graph_slug($conn, $g, $type));
        if (count($catalog) >= $maxItems) {
            return $catalog;
        }
    }

    $blogList = mynak_blog_href_path('');
    if ($blogList !== '') {
        br_catalog_push($catalog, $seen, [
            'path' => rtrim($blogList, '/') ?: '/blog',
            'title' => 'Blog yazıları',
            'type' => 'liste',
        ]);
    }

    $clusterKey = br_detect_graph_cluster_slug($post);
    if ($clusterKey !== null && function_exists('seo_rt_pillar_cluster_definitions')) {
        $defs = seo_rt_pillar_cluster_definitions();
        $related = $defs[$clusterKey]['related'] ?? [];
        foreach ($related as $graphSlug) {
            if ($graphSlug === 'blog') {
                continue;
            }
            br_catalog_push($catalog, $seen, br_catalog_resolve_graph_slug($conn, (string) $graphSlug, 'kume'));
            if (count($catalog) >= $maxItems) {
                return $catalog;
            }
        }
    }

    foreach (br_fetch_topic_matched_blogs($conn, $post, 6) as $row) {
        $slug = trim((string) ($row['slug'] ?? ''));
        if ($slug === '') {
            continue;
        }
        br_catalog_push($catalog, $seen, [
            'path' => mynak_public_path($slug),
            'title' => (string) ($row['baslik'] ?? ''),
            'type' => 'blog',
        ]);
        if (count($catalog) >= $maxItems) {
            return $catalog;
        }
    }

    $svc = $conn->query(
        "SELECT ana_baslik, slug FROM services WHERE status = 1
         ORDER BY CASE WHEN slug LIKE '%nakliyat%' THEN 0 ELSE 1 END, id ASC LIMIT 2"
    );
    if ($svc) {
        while ($row = $svc->fetch_assoc()) {
            $slug = trim((string) ($row['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }
            br_catalog_push($catalog, $seen, [
                'path' => mynak_public_path($slug),
                'title' => (string) ($row['ana_baslik'] ?? ''),
                'type' => 'hizmet',
            ]);
            if (count($catalog) >= $maxItems) {
                return $catalog;
            }
        }
    }

    return $catalog;
}

/**
 * Öncelik skoruna göre yenilenecek yayın yazıları (yüksek skor önce).
 *
 * @return list<array<string, mixed>>
 */
function br_select_posts_for_refresh(
    mysqli $conn,
    ?int $singleId,
    bool $useDateCutoff,
    string $cutoffDate,
    int $limit
): array {
    if ($singleId !== null && $singleId > 0) {
        $sql = "SELECT id, baslik, slug, icerik, etiketler, kategori_id, created_at
            FROM blog_posts
            WHERE id = ? AND durum = 3
            LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $singleId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    if ($useDateCutoff) {
        $sql = "SELECT id, baslik, slug, icerik, etiketler, kategori_id, created_at
            FROM blog_posts
            WHERE durum = 3
              AND created_at < ?
              AND seo_content_upgrade_2026_at IS NULL";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $cutoffDate);
    } else {
        $sql = "SELECT id, baslik, slug, icerik, etiketler, kategori_id, created_at
            FROM blog_posts
            WHERE durum = 3
              AND seo_content_upgrade_2026_at IS NULL";
        $stmt = $conn->prepare($sql);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if ($rows === []) {
        return [];
    }

    usort($rows, static function (array $a, array $b): int {
        $sa = br_post_priority_score($a);
        $sb = br_post_priority_score($b);
        if ($sa !== $sb) {
            return $sb <=> $sa;
        }
        $ca = (string) ($a['created_at'] ?? '');
        $cb = (string) ($b['created_at'] ?? '');
        if ($ca !== $cb) {
            return $ca <=> $cb;
        }

        return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
    });

    $picked = array_slice($rows, 0, $limit);
    foreach ($picked as $row) {
        $sc = br_post_priority_score($row);
        br_log('[ÖNCELİK] skor=' . $sc . ' id=' . (int) $row['id'] . ' slug=' . ($row['slug'] ?? ''));
    }

    return $picked;
}

/**
 * Production test — 10 yazılık kova planı.
 *
 * @return list<array<string, mixed>>
 */
/**
 * @return array{rows: list<array<string, mixed>>, gaps: array<string, array{needed: int, found: int}>, ready: bool}
 */
function br_select_posts_for_production_test(mysqli $conn): array
{
    $audit = mynak_ce_production_test_audit($conn);
    foreach ($audit['gaps'] as $bucket => $gap) {
        br_log('[CE-TEST-GAP] bucket=' . $bucket . ' needed=' . $gap['needed'] . ' found=' . $gap['found']);
    }
    if (!$audit['ready']) {
        br_log('[CE-TEST-GAP] Plan eksik — bazı kovalar için yayında (durum=3) yazı yok.');
    }

    $rows = [];
    foreach ($audit['items'] as $item) {
        $id = (int) ($item['id'] ?? 0);
        if ($id <= 0) {
            continue;
        }
        $stmt = $conn->prepare(
            'SELECT id, baslik, slug, icerik, etiketler, kategori_id, created_at
             FROM blog_posts WHERE id = ? AND durum = 3 LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!is_array($row)) {
            br_log('[CE-TEST] Eksik aday id=' . $id . ' bucket=' . ($item['bucket'] ?? '?'));
            continue;
        }
        $row['_ce_test_bucket'] = (string) ($item['bucket'] ?? '');
        br_log('[CE-TEST] bucket=' . $row['_ce_test_bucket'] . ' id=' . $id . ' slug=' . ($row['slug'] ?? ''));
        $rows[] = $row;
    }

    return [
        'rows' => $rows,
        'gaps' => $audit['gaps'],
        'ready' => $audit['ready'],
    ];
}

/**
 * @param list<array{path: string, title: string, type: string}> $catalog
 */
function br_internal_link_prompt_section(array $catalog, int $seed = 0): string
{
    if ($catalog === []) {
        return '';
    }
    $lines = [];
    foreach ($catalog as $item) {
        $path = (string) ($item['path'] ?? '');
        $title = (string) ($item['title'] ?? '');
        $type = (string) ($item['type'] ?? '');
        if ($path === '') {
            continue;
        }
        $lines[] = '- href="'
            . htmlspecialchars($path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '" — ' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . ' (' . htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ')';
    }
    if ($lines === []) {
        return '';
    }
    $list = implode("\n", $lines);

    return <<<SECTION
Whitelist (4–7 link, href aynen; anchor kısa/doğal, tekrar yok; uydurma URL yok):
{$list}
SECTION;
}

function br_system_prompt_short(): string
{
    return mynak_ce_system_prompt();
}

/**
 * @return array{title: ?string, html: string}
 */
function br_parse_ai_response(string $raw): array
{
    $raw = trim($raw);
    $title = null;
    if (preg_match('/^YENİ_BAŞLIK:\s*(.+)$/imu', $raw, $m)) {
        $title = trim((string) $m[1]);
        $title = preg_replace('/\s+/u', ' ', $title) ?? $title;
        $raw = trim((string) preg_replace('/^YENİ_BAŞLIK:\s*.+$/imu', '', $raw, 1));
    }

    return ['title' => $title, 'html' => br_clean_ai_html($raw)];
}

/** @return list<string> */
function br_title_intent_tokens(string $text): array
{
    $t = mb_strtolower(trim($text), 'UTF-8');
    $stop = [
        've', 'ile', 'icin', 'için', 'bir', 'bu', 'şu', 'mi', 'mı', 'mu', 'mü', 'da', 'de',
        'na', 'ne', 'the', 'hakkında', 'hakkinda', 'olan', 'için', 'nedir', 'nasıl',
    ];
    $words = preg_split('/[\s\-–—\/]+/u', $t) ?: [];
    $out = [];
    foreach ($words as $w) {
        $w = trim($w);
        if ($w === '' || mb_strlen($w) < 3) {
            continue;
        }
        if (in_array($w, $stop, true)) {
            continue;
        }
        $out[$w] = true;
    }

    return array_keys($out);
}

function br_validate_new_title(string $oldTitle, string $newTitle, string $slug): bool
{
    $newTitle = trim($newTitle);
    if ($newTitle === '' || mb_strlen($newTitle) < 12 || mb_strlen($newTitle) > 110) {
        return false;
    }
    $oldTokens = br_title_intent_tokens($oldTitle . ' ' . str_replace('-', ' ', $slug));
    if ($oldTokens === []) {
        return true;
    }
    $newHay = mb_strtolower($newTitle, 'UTF-8');
    $hit = 0;
    foreach ($oldTokens as $tok) {
        if (str_contains($newHay, $tok)) {
            ++$hit;
        }
    }

    return ($hit / count($oldTokens)) >= 0.35;
}

/**
 * Konuya göre kelime bandı (varyant tabanı + intent ayarı).
 *
 * @return array{word_min: int, word_max: int}
 */
function br_dynamic_word_band(array $post, array $stylePack): array
{
    return [
        'word_min' => (int) $stylePack['word_min'],
        'word_max' => (int) $stylePack['word_max'],
    ];
}

function br_service_intent_engine_block(array $post): string
{
    $hay = br_post_topic_signals($post)['hay'];
    $block = '';

    if (preg_match('/sehirler.?arasi|şehirler.?arasi/', $hay)) {
        $block = 'Şehirlerarası: rota, teslim süresi, uzun yol güvenliği, yük sabitleme, sigorta, planlama.';
    } elseif (preg_match('/asansor.*kiralama|asansör.*kiralama/', $hay)) {
        $block = 'Asansör kiralama: kat yüksekliği, bina önü uygunluğu, kurulum alanı, güvenlik, operatör, teknik plan.';
    } elseif (preg_match('/vinç|vinc|sepetli/', $hay)) {
        $block = 'Sepetli vinç: erişim, güvenlik mesafesi, operatör tecrübesi, bina çevresi, teknik planlama.';
    } elseif (preg_match('/asansorlu|asansörlü/', $hay)) {
        $block = 'Asansörlü taşıma: kat, bina önü, kurulum süresi, eşya güvenliği, park/izin gerçekleri.';
    } elseif (preg_match('/depo|depolama/', $hay)) {
        $block = 'Eşya depolama: nem, güvenlik, envanter, erişim süresi, paketleme, depo koşulları.';
    } elseif (preg_match('/parca|parça/', $hay)) {
        $block = 'Parça eşya: az hacim, esnek tarih, ortak rota mantığı, maliyet optimizasyonu.';
    } elseif (preg_match('/ofis|kurumsal/', $hay)) {
        $block = 'Ofis taşıma: iş sürekliliği, cihaz/dosya güvenliği, hafta sonu veya mesai dışı plan.';
    } elseif (preg_match('/izmir.*evden|evden.eve.*izmir/', $hay) || (str_contains($hay, 'izmir') && str_contains($hay, 'evden'))) {
        $block = 'İzmir evden eve: ilçe/trafik, bina tipi, park, asansör kurulumu, site yönetimi izinleri — doğal 1–2 ilçe örneği, liste spam yok.';
    } elseif (str_contains($hay, 'evden') || str_contains($hay, 'ev-tasima')) {
        $block = 'Evden eve: güven, paketleme, stres, planlama, zaman; operasyon disiplini hissi.';
    } else {
        $block = br_topic_angle_from_post($post);
    }

    return <<<INTENT

# SERVICE INTENT ENGINE (bu yazının omurgası)
{$block}
INTENT;
}

function br_human_reality_block(): string
{
    return <<<'REAL'

# İNSANİ GERÇEKLİK — saha detayı (zorunlu)

En az **4** gerçek operasyon detayı kullan; liste değil, doğal anlatım içinde.
Örnek havuz (aynısını kopyalama — bağlama uygun türet):
Alsancak park zorluğu; Karşıyaka eski apartman merdivenleri; Bayraklı/Mavişehir yüksek kat residence;
Bornova/Buca değişken bina girişleri; site yönetiminden asansör/taşıma izni; yaz yoğunluğu;
şehirlerarası rota ve teslim saati; asansör aracının binaya yakınlığı; depoda nem ve envanter düzeni.

AI kokusu verme: "günümüzde", "bu noktada", "sonuç olarak", boş kurumsal övgü yok.

REAL;
}

function br_local_operations_block(array $post): string
{
    $hay = br_post_topic_signals($post)['hay'];
    if (!str_contains($hay, 'izmir') && !preg_match('/buca|bornova|karsiyaka|konak|cigli|bayrakli/', $hay)) {
        return '';
    }

    return <<<'LOCAL'

# LOCAL OPERASYON — İzmir

İzmir gerçeklerini 1–2 paragrafta işle: trafik penceresi, otopark, dar sokak, site kuralları, asansör kurulum günü.
30 ilçe listesi veya ilçe spam yok. Sadece konuyla ilgili yerler.

LOCAL;
}

function br_premium_tone_block(): string
{
    return <<<'TONE'

# MARKA TONU — MY Nakliyat (premium, abartısız)

"En iyi firma", "lider", "tek tercih", "en kaliteli hizmet" YOK.
MY Nakliyat: **işini bilen**, planlı, sahadan anlayan, güven veren firma hissi — abartılı reklam değil.
Güç: operasyon kalitesi, planlama disiplini, ekipman doğru kullanımı, müşteri kaygısını okuma.
Google/AI için: doğrulanabilir süreç dili; boş övgü yok.

TONE;
}

function br_operational_friction_block(): string
{
    return <<<'FRIC'

# SAHA ZORLUKLARI (en az 1 bölümde doğal geçsin)

Konuya uygunsa gerçekçi belirt (dramatize etme):
- taşınma günü park / araç yaklaştırma zorluğu
- yaz/ay sonu yoğun sezon ve slot bulma
- bina/siteden erişim ve izin penceresi
- dar merdiven / asansör kurulum süresi
- teslim saati ve rota gecikme riski

FRIC;
}

function br_banned_phrases_block(): string
{
    return <<<'BAN'

# YASAKLI KALIPLAR
Günümüzde, Bu noktada, Sonuç olarak, Profesyonel hizmet anlayışı, Müşteri memnuniyeti,
Kesintisiz hizmet, Kapsamlı çözümler, Sorunsuz deneyim, Kullanıcı deneyimi, Öne çıkan,
Alanında uzman ekip, Kaliteli hizmet anlayışı, Lider firma, Kurumsal çözümler, En doğru yöntem, En iyi firma, En iyi nakliyat.
Aynı paragraf yapısı ve aynı bağlaç zinciri tekrarı yok. Keyword stuffing yok.

BAN;
}

/**
 * İzinli olmayan veya harici <a> etiketlerini metne çevirir; geçerli href'leri kanonik path ile yeniler.
 *
 * @param list<array{path: string, title: string, type: string}> $catalog
 */
function br_sanitize_internal_anchors(string $html, array $catalog): string
{
    if ($html === '') {
        return '';
    }
    $map = [];
    foreach ($catalog as $item) {
        $path = (string) ($item['path'] ?? '');
        if ($path === '') {
            continue;
        }
        $key = br_link_public_path_key($path);
        if (!isset($map[$key])) {
            $map[$key] = $path;
        }
    }

    if ($map !== [] && function_exists('mynak_url_path_prefix')) {
        $pref = trim(str_replace('\\', '/', (string) mynak_url_path_prefix()), '/');
        if ($pref !== '') {
            $needle = '/' . $pref;
            $aliases = [];
            foreach ($map as $k => $canon) {
                if ($k === $needle) {
                    continue;
                }
                if (!str_starts_with($k, $needle . '/')) {
                    continue;
                }
                $rest = br_link_public_path_key(substr($k, strlen($needle)));
                if ($rest !== '' && $rest !== '/' && !isset($map[$rest])) {
                    $aliases[$rest] = $canon;
                }
            }
            if ($aliases !== []) {
                $map = array_merge($aliases, $map);
            }
        }
    }

    $prev = libxml_use_internal_errors(true);
    $dom = new DOMDocument('1.0', 'UTF-8');
    $wrapped = '<div>' . $html . '</div>';
    $dom->loadHTML('<?xml encoding="UTF-8">' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $div = $dom->getElementsByTagName('div')->item(0);
    if (!$div) {
        libxml_use_internal_errors($prev);

        return $html;
    }

    $stripped = 0;
    $kept = 0;
    foreach (iterator_to_array($dom->getElementsByTagName('a')) as $a) {
        if (!$a instanceof DOMElement) {
            continue;
        }
        $href = $a->getAttribute('href');
        $key = br_href_resolve_to_public_path_key($href);
        if ($key === '' || $map === [] || !isset($map[$key])) {
            $parent = $a->parentNode;
            if ($parent) {
                while ($a->firstChild) {
                    $parent->insertBefore($a->firstChild, $a);
                }
                $parent->removeChild($a);
                ++$stripped;
            }
            continue;
        }
        $canon = $map[$key];
        while ($a->attributes->length > 0) {
            $a->removeAttributeNode($a->attributes->item(0));
        }
        $a->setAttribute('href', $canon);
        ++$kept;
    }

    $out = '';
    foreach ($div->childNodes as $child) {
        $out .= $dom->saveHTML($child);
    }
    libxml_use_internal_errors($prev);

    if ($stripped > 0) {
        br_log("[INFO] İç link: izin verilmeyen {$stripped} adet <a> çıkarıldı, {$kept} geçerli.");
    }

    return $out;
}

/**
 * Slug/başlıktan makaleye özel açı (diğer yazılardan ayrışma).
 */
function br_topic_angle_from_post(array $post): string
{
    $hay = br_post_topic_signals($post)['hay'];

    if (preg_match('/fiyat|ucret|maliyet|ucuz|pahali/', $hay)) {
        return 'Fiyat ve teklif şeffaflığı: rakam uydurma; değişkenleri ve keşif sürecini öne çıkar.';
    }
    if (preg_match('/guvenilir|guven|yorum|referans/', $hay)) {
        return 'Güven ve kanıt: sözleşme, sigorta, yazılı teklif, doğrulanmış yorum kriterleri.';
    }
    if (preg_match('/tavsiye|oneri|en-iyi|karsilastir/', $hay)) {
        return 'Seçim rehberi: objektif kriter listesi; tek firma sıralaması yapma.';
    }
    foreach (br_izmir_district_slug_tokens() as $d) {
        if (str_contains($hay, $d)) {
            return 'İlçe/yerel bağlam: o bölgede taşıma pratiği, erişim ve planlama (spam ilçe listesi yok).';
        }
    }
    if (str_contains($hay, 'ofis') || str_contains($hay, 'kurumsal')) {
        return 'Kurumsal/ofis taşıma: iş sürekliliği, ekipman ve zamanlama.';
    }
    if (str_contains($hay, 'depo')) {
        return 'Depolama süresi, envanter ve sigorta maddeleri.';
    }
    if (str_contains($hay, 'asansor')) {
        return 'Asansörlü taşıma: bina izni, kat yükü, süre planı.';
    }

    return 'Genel bilgilendirme: okuyucunun somut problemine göre net, kısa cevaplar.';
}

/** H2 sayısı — yapı çeşitliliği (FAQ ayrı motor). */
function br_structure_h2_count(int $postId, array $post): int
{
    $h2map = [4, 5, 6, 7, 8, 5, 4, 6];
    $h2 = $h2map[$postId % 8];
    $hay = br_post_topic_signals($post)['hay'];
    if (preg_match('/rehber|nasil|nedir/', $hay)) {
        $h2 = min(8, $h2 + 1);
    }
    if (preg_match('/kiralama|vinç|vinc|parca/', $hay)) {
        $h2 = max(4, $h2 - 1);
    }

    return $h2;
}

/**
 * FAQ Randomization Engine — 0, 3, 4, 5, 6, 7 (yazı başına sabit).
 *
 * @return array{count: int, rule: string}
 */
function br_faq_randomization_engine(int $postId): array
{
    $counts = [0, 3, 3, 4, 5, 6, 7, 0, 4, 7, 3, 5];
    $c = $counts[$postId % count($counts)];
    if ($c === 0) {
        $rule = 'FAQ/SSS bölümü YOK (0 adet). Soruları gövde paragrafları içinde doğal yanıtla; ayrı SSS H2 açma.';
    } elseif ($c >= 6) {
        $rule = "Tam {$c} adet özgün SSS (her biri H3 + 2–4 cümle); gerçek kullanıcı araması gibi sorular.";
    } else {
        $rule = "Tam {$c} adet SSS (H3); kısa net cevap.";
    }

    return ['count' => $c, 'rule' => $rule];
}

/**
 * Dynamic Length Engine — 1500+ zorunluluğu yok.
 *
 * @return array{label: string, min: int, max: int}
 */
function br_dynamic_length_engine(array $post, int $postId): array
{
    $tiers = [
        ['label' => 'mikro', 'min' => 650, 'max' => 880],
        ['label' => 'kısa', 'min' => 880, 'max' => 1120],
        ['label' => 'orta', 'min' => 1120, 'max' => 1350],
        ['label' => 'derin', 'min' => 1350, 'max' => 1580],
        ['label' => 'seçici-uzun', 'min' => 1500, 'max' => 1720],
    ];
    $t = $tiers[($postId + (int) strlen((string) ($post['slug'] ?? ''))) % 5];
    $hay = br_post_topic_signals($post)['hay'];
    if (preg_match('/kiralama|vinç|vinc|sepetli|parca-esya/', $hay) && $t['min'] > 900) {
        $t = $tiers[1];
    }
    if (preg_match('/rehber|karsilastir|tavsiye|fiyat/', $hay) && $t['max'] < 1300) {
        $t = $tiers[2];
    }

    return $t;
}

function br_opening_hook_instruction(int $postId): string
{
    $hooks = [
        'Giriş: Tek somut kullanıcı sorusu (1–2 paragraf, H2 öncesi).',
        'Giriş: İki cümle mini senaryo (dramatize etme, isim yok).',
        'Giriş: Yanlış bilinen + tek cümle düzeltme.',
        'Giriş: 5–7 maddelik mini checklist; sonra konuya gir.',
        'Giriş: 40–70 kelime net cevap kutusu; sonra genişlet.',
        'Giriş: Tek saha gözlemi (park / site izni / asansör mesafesi).',
        'Giriş: İki kısa paragraf — önce durum, sonra sorun (farklı cümle uzunlukları).',
        'Giriş: Doğrudan "şu üç şeyi bilmek gerekir" listesi (3 madde, H2 öncesi).',
    ];

    return $hooks[$postId % 8];
}

/** Intro Randomization — AI opening pattern kırıcı. */
function br_intro_randomization_engine(int $postId): string
{
    $hook = br_opening_hook_instruction($postId);
    $starters = [
        'İlk cümle fiil ile başlasın (soru ile değil).',
        'İlk cümle soru ile başlasın (fiil ile değil).',
        'İlk cümle yer/zaman ifadesi ile başlasın (İzmir, sabah, taşınma günü vb.).',
        'İlk cümle sayı veya ölçü içersin (kat, metrekare, km — uydurma fiyat değil).',
    ];

    return $hook . "\n" . $starters[$postId % 4] . <<<'INTRO'

YASAK giriş: "Merhaba", "Bu yazıda", "Bu rehberde", "Günümüzde", "Pek çok kişi", "Nakliyat sektöründe",
"İnternetin yaygınlaşması", "Öncelikle şunu belirtelim", art arda iki retorik soru.
INTRO;
}

/**
 * Syntax Entropy — üslup modu.
 *
 * @return array{mode: string, instruction: string, temperature: float}
 */
function br_syntax_entropy_engine(int $postId): array
{
    $modes = [
        [
            'mode' => 'kısa-paragraf',
            'instruction' => 'Üslup: kısa-orta cümleler; az sıfat; az liste; net ve sade operasyon dili.',
            'temperature' => 0.62,
        ],
        [
            'mode' => 'teknik-operasyonel',
            'instruction' => 'Üslup: daha teknik — süreç adımları, izin, ekipman, rota, sigorta maddeleri; madde listeleri kullanılabilir.',
            'temperature' => 0.68,
        ],
        [
            'mode' => 'gözlemsel-saha',
            'instruction' => 'Üslup: gözlem ağırlıklı — saha detayı, zamanlama, park/asansör; 1–2 paragraf “sahada görülen” ton (abartısız).',
            'temperature' => 0.75,
        ],
    ];

    return $modes[$postId % 3];
}

/** CTA Chaos — kapanış tonu yazı başına değişir. */
function br_cta_chaos_engine(int $postId): string
{
    $ctas = [
        'Kapanış: Sakin — keşif olmadan fiyat yanıltıcı; teklif linki tek cümlede.',
        'Kapanış: Soru ile bitir — "Hacim ve tarih netleşince teklif anlamlı olur" + teklif.',
        'Kapanış: 3 maddelik "şimdi ne yapmalı" + teklif; satış kokusu yok.',
        'Kapanış: İşini bilen ekip vurgusu (en iyi yok) + yazılı teklif + teklif linki.',
        'Kapanış: Tek kısa cümle; link var, ünlem yok.',
        'Kapanış: Kriterleri özetle (madde) + isteğe bağlı teklif.',
        'Kapanış: Saha hatırlatması (yoğun sezon/park) + keşif + teklif.',
        'Kapanış: Sadece web teklif formu; telefon/WhatsApp metni yazma.',
        'Kapanış: Karar erteleme tonu — "acele seçim riski" + teklif.',
        'Kapanış: Mini özet (2 cümle) + yumuşak teklif.',
    ];

    return $ctas[$postId % count($ctas)];
}

/** Dynamic Structure Engine — modül sırası ve H2 sayısı esnek. */
function br_dynamic_structure_engine(int $postId, int $h2, string $faqRule): string
{
    $flows = [
        'Akış A: giriş → 2 ara H2 → fiyat → kapanış (SSS yoksa gövdede soru yanıtla).',
        'Akış B: giriş → süreç → saha zorluğu → local → kapanış.',
        'Akış C: giriş → yanlış bilinen → checklist gövdesi → teklif.',
        'Akış D: giriş → tanım → risk → firma seçimi → SSS (varsa) → CTA.',
        'Akış E: giriş → parça parça derinleşen 3 blok → SSS (varsa) → kısa CTA.',
        'Akış F: giriş → operasyon detayı → maliyet faktörleri → kapanış.',
    ];
    $flow = $flows[$postId % count($flows)];

    return <<<STRUCT

# DYNAMIC STRUCTURE ENGINE
- Bu yazı **{$flow}** mantığında ilerlesin; diğer yazılarla aynı sıra olmasın.
- **{$h2} adet H2**; başlıklar özgün (şablon H2 yok). Modül sırasını konuya göre **yeniden düzenle**.
- HTML table yok.
- {$faqRule}

Modül havuzundan seç (hepsi şart değil): tanım · yanlış bilinen · süreç · saha zorluğu · fiyat faktörleri · işini bilen firma kriterleri · local · checklist · SSS · CTA

STRUCT;
}

/**
 * @return array{
 *   variant_id: int,
 *   format_key: string,
 *   voice: string,
 *   architecture: string,
 *   cta: string,
 *   syntax_mode: string,
 *   word_min: int,
 *   word_max: int,
 *   h2_count: int,
 *   faq_count: int,
 *   temperature: float,
 *   angle: string
 * }
 */
function br_content_style_pack(array $post, ?mysqli $conn = null): array
{
    return mynak_ce_build_production_pack($post, $conn);
}

function br_save_refreshed_post(
    mysqli $conn,
    int $id,
    string $slug,
    string $baslik,
    string $html,
    string $metaDesc,
    int $quality,
    string $note,
    bool $updateTitle,
    string $ceMetaJson,
    bool $qcPass
): bool {
    $hasMetaCol = false;
    $q = $conn->query("SHOW COLUMNS FROM blog_posts LIKE 'content_engine_meta'");
    if ($q && $q->num_rows > 0) {
        $hasMetaCol = true;
    }

    $targetDurum = $qcPass ? MYNAK_CE_DURUM_EDITOR_REVIEW : MYNAK_CE_DURUM_NEEDS_REVISION;
    $upgradeAt = $qcPass ? 'NOW()' : 'NULL';

    if ($updateTitle && $hasMetaCol) {
        $sql = "UPDATE blog_posts SET
                baslik = ?, icerik = ?, meta_description = ?, durum = ?,
                is_ai_generated = 1, ai_quality_score = ?,
                seo_content_upgrade_2026_at = {$upgradeAt},
                content_engine_meta = ?,
                editor_notes = CONCAT(IFNULL(editor_notes,''), IF(IFNULL(editor_notes,'')='','','\n'), ?),
                updated_at = NOW()
             WHERE id = ? AND slug = ? AND durum = 3";
        $stmtUp = $conn->prepare($sql);
        $stmtUp->bind_param('sssiissis', $baslik, $html, $metaDesc, $targetDurum, $quality, $ceMetaJson, $note, $id, $slug);
    } elseif ($updateTitle) {
        $sql = "UPDATE blog_posts SET
                baslik = ?, icerik = ?, meta_description = ?, durum = ?,
                is_ai_generated = 1, ai_quality_score = ?,
                seo_content_upgrade_2026_at = {$upgradeAt},
                editor_notes = CONCAT(IFNULL(editor_notes,''), IF(IFNULL(editor_notes,'')='','','\n'), ?),
                updated_at = NOW()
             WHERE id = ? AND slug = ? AND durum = 3";
        $stmtUp = $conn->prepare($sql);
        $stmtUp->bind_param('sssiisis', $baslik, $html, $metaDesc, $targetDurum, $quality, $note, $id, $slug);
    } elseif ($hasMetaCol) {
        $sql = "UPDATE blog_posts SET
                icerik = ?, meta_description = ?, durum = ?,
                is_ai_generated = 1, ai_quality_score = ?,
                seo_content_upgrade_2026_at = {$upgradeAt},
                content_engine_meta = ?,
                editor_notes = CONCAT(IFNULL(editor_notes,''), IF(IFNULL(editor_notes,'')='','','\n'), ?),
                updated_at = NOW()
             WHERE id = ? AND slug = ? AND durum = 3";
        $stmtUp = $conn->prepare($sql);
        $stmtUp->bind_param('ssiissis', $html, $metaDesc, $targetDurum, $quality, $ceMetaJson, $note, $id, $slug);
    } else {
        $sql = "UPDATE blog_posts SET
                icerik = ?, meta_description = ?, durum = ?,
                is_ai_generated = 1, ai_quality_score = ?,
                seo_content_upgrade_2026_at = {$upgradeAt},
                editor_notes = CONCAT(IFNULL(editor_notes,''), IF(IFNULL(editor_notes,'')='','','\n'), ?),
                updated_at = NOW()
             WHERE id = ? AND slug = ? AND durum = 3";
        $stmtUp = $conn->prepare($sql);
        $stmtUp->bind_param('ssiisis', $html, $metaDesc, $targetDurum, $quality, $note, $id, $slug);
    }

        if (!$stmtUp) {
            br_log('[SQL] prepare failed: ' . $conn->error);

            return false;
        }
        if (!$stmtUp->execute()) {
            br_log('[SQL] execute failed: ' . $stmtUp->error);

            return false;
        }
        if ($stmtUp->affected_rows === 0) {
            return false;
        }

    return true;
}

/**
 * Kuyruk / revize yazısı CE yenileme (durum 1 veya 2) — slug sabit.
 */
function br_save_ce_queue_rewrite(
    mysqli $conn,
    int $id,
    string $slug,
    string $baslik,
    string $html,
    string $metaDesc,
    int $quality,
    string $note,
    bool $updateTitle,
    string $ceMetaJson,
    bool $qcPass
): bool {
    $hasMetaCol = false;
    $q = $conn->query("SHOW COLUMNS FROM blog_posts LIKE 'content_engine_meta'");
    if ($q && $q->num_rows > 0) {
        $hasMetaCol = true;
    }

    $targetDurum = $qcPass ? MYNAK_CE_DURUM_EDITOR_REVIEW : MYNAK_CE_DURUM_NEEDS_REVISION;

    if ($updateTitle && $hasMetaCol) {
        $sql = "UPDATE blog_posts SET
                baslik = ?, icerik = ?, meta_description = ?, durum = ?,
                is_ai_generated = 1, ai_quality_score = ?,
                content_engine_meta = ?,
                editor_notes = CONCAT(IFNULL(editor_notes,''), IF(IFNULL(editor_notes,'')='','','\n'), ?),
                updated_at = NOW()
             WHERE id = ? AND slug = ? AND durum IN (1, 2)";
        $stmtUp = $conn->prepare($sql);
        if (!$stmtUp) {
            return false;
        }
        $stmtUp->bind_param('sssiissis', $baslik, $html, $metaDesc, $targetDurum, $quality, $ceMetaJson, $note, $id, $slug);
    } elseif ($updateTitle) {
        $sql = "UPDATE blog_posts SET
                baslik = ?, icerik = ?, meta_description = ?, durum = ?,
                is_ai_generated = 1, ai_quality_score = ?,
                editor_notes = CONCAT(IFNULL(editor_notes,''), IF(IFNULL(editor_notes,'')='','','\n'), ?),
                updated_at = NOW()
             WHERE id = ? AND slug = ? AND durum IN (1, 2)";
        $stmtUp = $conn->prepare($sql);
        if (!$stmtUp) {
            return false;
        }
        $stmtUp->bind_param('sssiisis', $baslik, $html, $metaDesc, $targetDurum, $quality, $note, $id, $slug);
    } elseif ($hasMetaCol) {
        $sql = "UPDATE blog_posts SET
                icerik = ?, meta_description = ?, durum = ?,
                is_ai_generated = 1, ai_quality_score = ?,
                content_engine_meta = ?,
                editor_notes = CONCAT(IFNULL(editor_notes,''), IF(IFNULL(editor_notes,'')='','','\n'), ?),
                updated_at = NOW()
             WHERE id = ? AND slug = ? AND durum IN (1, 2)";
        $stmtUp = $conn->prepare($sql);
        if (!$stmtUp) {
            return false;
        }
        $stmtUp->bind_param('ssiissis', $html, $metaDesc, $targetDurum, $quality, $ceMetaJson, $note, $id, $slug);
    } else {
        $sql = "UPDATE blog_posts SET
                icerik = ?, meta_description = ?, durum = ?,
                is_ai_generated = 1, ai_quality_score = ?,
                editor_notes = CONCAT(IFNULL(editor_notes,''), IF(IFNULL(editor_notes,'')='','','\n'), ?),
                updated_at = NOW()
             WHERE id = ? AND slug = ? AND durum IN (1, 2)";
        $stmtUp = $conn->prepare($sql);
        if (!$stmtUp) {
            return false;
        }
        $stmtUp->bind_param('ssiisis', $html, $metaDesc, $targetDurum, $quality, $note, $id, $slug);
    }

    if (!$stmtUp->execute()) {
        return false;
    }

    return $stmtUp->affected_rows > 0;
}

function br_build_user_prompt(array $post, array $linkCatalog = [], ?mysqli $conn = null): string
{
    $assembled = mynak_ce_assemble_prompt($post, $linkCatalog, $conn);

    return (string) ($assembled['prompt'] ?? '');
}

function br_openai_complete(string $apiKey, string $model, string $system, string $user, float $temperature = 0.55): array
{
    $temperature = max(0.45, min(0.85, $temperature));
    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ],
        'max_tokens' => 8192,
        'temperature' => $temperature,
    ];
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
        CURLOPT_TIMEOUT => 300,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err || $code !== 200) {
        $apiMsg = '';
        $j = json_decode((string) $resp, true);
        if (is_array($j) && isset($j['error']['message'])) {
            $apiMsg = $j['error']['message'];
        }

        return ['ok' => false, 'error' => $err ?: $apiMsg ?: 'HTTP ' . $code];
    }
    $data = json_decode((string) $resp, true);
    $text = trim((string) ($data['choices'][0]['message']['content'] ?? ''));

    return ['ok' => true, 'content' => $text];
}

function br_clean_ai_html(string $content): string
{
    $content = preg_replace('/^```[a-zA-Z0-9_\-]*[ \t]*\r?\n/m', '', $content);
    $content = preg_replace('/\r?\n?[ \t]*```[ \t]*$/m', '', $content);
    $content = str_replace('```', '', $content);
    $content = trim($content);
    $content = preg_replace('/<h1(\b[^>]*)>/i', '<h2$1>', $content);
    $content = preg_replace('/<\/h1>/i', '</h2>', $content);

    return $content;
}

/**
 * @param array{
 *   dry_run?: bool,
 *   ignore_quota?: bool,
 *   limit?: int,
 *   before?: string,
 *   post_id?: int,
 *   date_cutoff?: bool
 * } $opts
 * @return array{
 *   success: bool,
 *   fatal: ?string,
 *   exit_code: int,
 *   processed: int,
 *   candidates: int,
 *   dry_run: bool,
 *   logs: list<string>
 * }
 */
function blog_bulk_content_refresh_run(mysqli $conn, array $opts): array
{
    global $logFile;

    $GLOBALS['mynak_br_log_buffer'] = [];
    $logDir = realpath(__DIR__ . '/../logs') ?: __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }
    $logFile = $logDir . '/blog_bulk_refresh.log';

    $dryRun = !empty($opts['dry_run']);
    $ignoreQuota = !empty($opts['ignore_quota']);
    $limitRun = isset($opts['limit']) ? max(1, min(50, (int) $opts['limit'])) : 3;
    $cutoffDate = isset($opts['before']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $opts['before'])
        ? (string) $opts['before']
        : '2025-01-01';
    $singleId = isset($opts['post_id']) && (int) $opts['post_id'] > 0 ? (int) $opts['post_id'] : null;
    // true = yalnızca created_at < before (eski makaleler). false = tüm yayınlar (tarih yok).
    $useDateCutoff = array_key_exists('date_cutoff', $opts)
        ? (bool) $opts['date_cutoff']
        : true;

    $empty = [
        'success' => false,
        'fatal' => null,
        'exit_code' => 1,
        'processed' => 0,
        'candidates' => 0,
        'dry_run' => $dryRun,
        'logs' => [],
        'qc_passed' => 0,
        'qc_failed' => 0,
        'qc_reports' => [],
        'bucket_gaps' => [],
        'test_plan_ready' => true,
        'production_test' => !empty($opts['production_test']),
    ];

    br_log('=== Blog bulk content refresh — MY Nakliyat Local SEO Content Engine (modüler) ===');
    br_ensure_column($conn);
    br_ensure_content_engine_meta_column($conn);
    $batchVer = br_ce_batch_version($conn);
    br_log('[CE] batch_version=' . $batchVer);

    $apiKey = '';
    if (!$dryRun) {
        $apiKey = (string) get_openai_api_key();
        if ($apiKey === '') {
            br_log('[FATAL] openai_api_key ayarı boş.');
            $empty['fatal'] = 'OpenAI API anahtarı ayarlarda tanımlı değil.';
            $empty['logs'] = $GLOBALS['mynak_br_log_buffer'];

            return $empty;
        }
    }

    $preferred = get_openai_model();
    $modelsDefault = array_values(array_unique(array_filter(['gpt-4o', $preferred, 'gpt-4o-mini'])));

    $dailyMax = br_daily_max($conn);
    $toProcess = $singleId ? 1 : min($limitRun, $dailyMax);

    if (!$ignoreQuota) {
        $today = date('Y-m-d');
        $day = br_setting_get($conn, 'blog_bulk_refresh_quota_day', '');
        $used = (int) br_setting_get($conn, 'blog_bulk_refresh_quota_used', '0');
        if ($day !== $today) {
            $used = 0;
        }
        $remaining = max(0, $dailyMax - $used);
        $toProcess = min($toProcess, $remaining);
        if ($toProcess <= 0) {
            br_log("[STOP] Günlük kota: kullanılan=$used / max=$dailyMax. Yarın tekrar veya kotayı aş seçeneğini kullanın.");
            $logs = $GLOBALS['mynak_br_log_buffer'];

            return [
                'success' => true,
                'fatal' => null,
                'exit_code' => 0,
                'processed' => 0,
                'candidates' => 0,
                'dry_run' => $dryRun,
                'logs' => $logs,
            ];
        }
    }

    if (!$dryRun && !br_check_and_consume_quota($conn, $toProcess, $ignoreQuota)) {
        $logs = $GLOBALS['mynak_br_log_buffer'];

        return [
            'success' => true,
            'fatal' => null,
            'exit_code' => 0,
            'processed' => 0,
            'candidates' => 0,
            'dry_run' => false,
            'logs' => $logs,
        ];
    }

    $system = br_system_prompt_short();

    $productionTest = !empty($opts['production_test']);
    $bucketGaps = [];
    $testReady = true;
    if ($productionTest) {
        $testPick = br_select_posts_for_production_test($conn);
        $rows = $testPick['rows'];
        $bucketGaps = $testPick['gaps'];
        $testReady = (bool) $testPick['ready'];
        $toProcess = count($rows);
        br_log('[CE-TEST] Production test modu: ' . $toProcess . ' yazı planlandı.');
    } else {
        $rows = br_select_posts_for_refresh(
            $conn,
            $singleId,
            $useDateCutoff,
            $cutoffDate,
            $toProcess
        );
    }

    if ($rows === []) {
        br_log('[INFO] İşlenecek kayıt yok (filtre veya hepsi tamamlanmış).');
        $cPub = $conn->query("SELECT COUNT(*) AS c FROM blog_posts WHERE durum = 3");
        $nPub = $cPub ? (int) $cPub->fetch_assoc()['c'] : 0;
        br_log("[TEŞHİS-BİLGİ] Yayında sayısı (durum=3): {$nPub}");
        $cPend = $conn->query("SELECT COUNT(*) AS c FROM blog_posts WHERE durum = 3 AND seo_content_upgrade_2026_at IS NULL");
        $nPend = $cPend ? (int) $cPend->fetch_assoc()['c'] : 0;
        br_log("[TEŞHİS-BİLGİ] Yayında ve henüz bu araçla işaretlenmemiş: {$nPend}");
        if ($singleId) {
            br_log('[TEŞHİS] Tek yazı: ID ' . (int) $singleId . ' yok veya yayında (durum=3) değil. Blog listesinde ID ve durumu kontrol edin.');
        } elseif ($useDateCutoff) {
            $st = $conn->prepare(
                'SELECT COUNT(*) AS c FROM blog_posts WHERE durum = 3 AND created_at < ? AND seo_content_upgrade_2026_at IS NULL'
            );
            $st->bind_param('s', $cutoffDate);
            $st->execute();
            $nOld = (int) ($st->get_result()->fetch_assoc()['c'] ?? 0);
            br_log('[TEŞHİS] Tarih filtresi açık: created_at < ' . $cutoffDate . " ile uygun yazı: {$nOld}");
            br_log('[TEŞHİS] İpucu: Tarih filtresini kapatın veya “önce” tarihini ileri alın (ör. yarının tarihi = hemen hepsi seçilir).');
        } else {
            br_log('[TEŞHİS] Tarih filtresi kapalı; yine de aday yok → tüm yayınlar zaten seo_content_upgrade_2026_at ile işaretli olabilir.');
        }
        $cLegacy = $conn->query("SELECT COUNT(*) AS c FROM blog_posts WHERE durum = 1");
        $nLeg = $cLegacy ? (int) $cLegacy->fetch_assoc()['c'] : 0;
        if ($nLeg > 0 && $nPub === 0) {
            br_log('[TEŞHİS-BİLGİ] durum=1 (editör kuyruğu) yazı sayısı: ' . $nLeg . ' — v2 şemada yayın durum=3 olmalı; DB taşıması yapıldı mı?');
        }
        $logs = $GLOBALS['mynak_br_log_buffer'];

        return [
            'success' => true,
            'fatal' => null,
            'exit_code' => 0,
            'processed' => 0,
            'candidates' => 0,
            'dry_run' => $dryRun,
            'logs' => $logs,
        ];
    }

    br_log('[INFO] Bu koşuda işlenecek: ' . count($rows) . ' yazı.');

    $processed = 0;
    $qcPassed = 0;
    $qcFailed = 0;
    $qcReports = [];
    foreach ($rows as $post) {
        $id = (int) $post['id'];
        $metaRow = mynak_ce_parse_post_meta($post);
        if (!empty($metaRow['editor_fingerprint']) && is_array($metaRow['editor_fingerprint'])) {
            $post['_editor_fingerprint'] = $metaRow['editor_fingerprint'];
        }
        br_log("[POST] id=$id slug={$post['slug']} başlık=" . mb_substr($post['baslik'], 0, 60));

        if ($dryRun) {
            if ($productionTest) {
                $linkCatalog = br_internal_link_catalog($conn, $post);
                $stylePack = br_content_style_pack($post, $conn);
                $userPrompt = br_build_user_prompt($post, $linkCatalog, $conn);
                $qcReports[] = [
                    'post_id' => $id,
                    'bucket' => (string) ($post['_ce_test_bucket'] ?? ''),
                    'seed' => (int) ($stylePack['seed'] ?? 0),
                    'flow_type' => (string) ($stylePack['flow_type'] ?? ''),
                    'intro_type' => (string) ($stylePack['intro_type'] ?? ''),
                    'word_range' => [(int) ($stylePack['word_min'] ?? 0), (int) ($stylePack['word_max'] ?? 0)],
                    'qc_pass' => null,
                    'queue' => 'dry_run',
                    'editor_note' => 'Önizleme — API/QC yok. prompt_chars=' . mb_strlen($userPrompt),
                ];
                br_log('[CE-TEST-DRY] id=' . $id
                    . ' bucket=' . ($post['_ce_test_bucket'] ?? '?')
                    . ' seed=' . ($stylePack['seed'] ?? 0)
                    . ' flow=' . ($stylePack['flow_type'] ?? '?')
                    . ' intro=' . ($stylePack['intro_type'] ?? '?')
                    . ' kelime=' . ($stylePack['word_min'] ?? 0) . '-' . ($stylePack['word_max'] ?? 0)
                    . ' prompt_chars=' . mb_strlen($userPrompt));
            }
            continue;
        }

        $linkCatalog = br_internal_link_catalog($conn, $post);
        $stylePack = br_content_style_pack($post, $conn);
        $userPrompt = br_build_user_prompt($post, $linkCatalog, $conn);
        $wordBand = br_dynamic_word_band($post, $stylePack);
        $promptLen = mb_strlen($userPrompt);
        br_log('[CE] id=' . $id
            . ' seed=' . ($stylePack['seed'] ?? 0)
            . ' batch=' . ($stylePack['batch_version'] ?? '?')
            . ' depth=' . ($stylePack['depth_tier'] ?? '?')
            . ' kelime=' . $wordBand['word_min'] . '-' . $wordBand['word_max']
            . ' intro=' . ($stylePack['intro_type'] ?? '?')
            . ' flow=' . ($stylePack['flow_type'] ?? '?')
            . ' intent=' . ($stylePack['service_intent'] ?? '?')
            . ' FAQ=' . ($stylePack['faq_count'] ?? 0)
            . ' prompt_chars=' . $promptLen);
        $models = function_exists('ab_ce_model_chain_for_pack')
            ? ab_ce_model_chain_for_pack($stylePack)
            : $modelsDefault;
        $pipe = br_ce_run_generation_pipeline(
            $apiKey,
            $models,
            $system,
            $userPrompt,
            (string) $post['baslik'],
            $stylePack,
            $linkCatalog,
            static function (?string $candidateTitle) use ($post, $id): ?string {
                if ($candidateTitle === null) {
                    return null;
                }
                if (!br_validate_new_title((string) $post['baslik'], $candidateTitle, (string) $post['slug'])) {
                    br_log('[WARN] id=' . $id . ' YENİ_BAŞLIK niyet uyumsuz: ' . mb_substr($candidateTitle, 0, 80));

                    return null;
                }
                $titleSpam = mynak_ce_title_spam_check($candidateTitle, (string) $post['baslik']);
                if (!$titleSpam['ok']) {
                    br_log('[WARN] id=' . $id . ' başlık spam: ' . implode(',', $titleSpam['reasons']));

                    return null;
                }

                return $candidateTitle;
            },
            'br_log'
        );
        $html = (string) ($pipe['html'] ?? '');
        $newTitle = $pipe['title'] ?? null;
        $modelUsed = (string) ($pipe['model'] ?? '');
        $pipeWc = (int) ($pipe['kelime'] ?? 0);
        br_log('[PIPE] id=' . $id . ' wc=' . $pipeWc . ' model=' . $modelUsed);

        if ($html === '') {
            br_log("[FAIL] id=$id içerik alınamadı.");
            continue;
        }

        if (!preg_match('/<(p|h2|h3|ul|ol|li)\b/i', $html)) {
            $html = icerik_donustur($html);
        }
        if (function_exists('ab_ce_content_polish')) {
            $html = ab_ce_content_polish($html);
        }
        $metaDesc = mb_substr(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '', 0, 160);
        $slug = (string) $post['slug'];
        $baslik = (string) $post['baslik'];
        $titleNote = '';
        if ($newTitle !== null) {
            $titleNote = ' | Yeni başlık: ' . $newTitle;
            $baslik = $newTitle;
        }
        $qc = mynak_ce_quality_report($post, $html, $stylePack, $linkCatalog, $newTitle, $modelUsed);
        $qcPass = (bool) ($qc['pass'] ?? false);
        $stylePack['_qc_pass'] = $qcPass;
        $stylePack['_qc_queue'] = (string) ($qc['queue'] ?? '');
        $stylePack['_qc_status'] = (string) ($qc['qc_status'] ?? '');

        $scores = mynak_ce_compute_score_bundle($html, $post, $stylePack, $qc);
        $quality = (int) ($scores['structure_score'] ?? 0);

        br_log('[QC-GATE] id=' . $id . ' status=' . ($stylePack['_qc_status'] ?? '?')
            . ' yapı=' . $quality
            . ' ed=' . (int) ($scores['editorial_score'] ?? 0)
            . ' critical=' . implode(',', $qc['critical_fail_keys'] ?? []));

        $ceMeta = mynak_ce_production_metadata_json(
            $post,
            $stylePack,
            $modelUsed,
            (float) ($stylePack['temperature'] ?? 0.68)
        );
        $ceMeta = mynak_ce_merge_scores_into_meta($ceMeta, $scores, $stylePack, $qc);
        $prefix = $productionTest ? '[CE-TEST] ' : '';
        $note = $prefix . '[Otomatik 2026 içerik upgrade ' . date('Y-m-d H:i') . '] Slug sabit' . $titleNote . ".\n"
            . (string) ($qc['editor_note'] ?? '')
            . ($qcPass ? '' : "\n[QC_FAIL] Yayın güvenliği filtresi — otomatik onay yok.")
            . "\n[CE_META]" . $ceMeta;

        $saved = br_save_refreshed_post($conn, $id, $slug, $baslik, $html, $metaDesc, $quality, $note, $newTitle !== null, $ceMeta, $qcPass);
        if (!$saved) {
            br_log("[FAIL] id=$id güncellenemedi (race?).");
            continue;
        }

        $wc = br_word_count_html($html);
        ++$processed;
        if ($qcPass) {
            ++$qcPassed;
        } else {
            ++$qcFailed;
        }

        $qcReports[] = [
            'post_id' => $id,
            'slug' => $slug,
            'bucket' => (string) ($post['_ce_test_bucket'] ?? ''),
            'seed' => (int) ($stylePack['seed'] ?? 0),
            'flow_type' => (string) ($stylePack['flow_type'] ?? ''),
            'intro_type' => (string) ($stylePack['intro_type'] ?? ''),
            'word_range' => [(int) ($stylePack['word_min'] ?? 0), (int) ($stylePack['word_max'] ?? 0)],
            'qc_pass' => $qcPass,
            'queue' => (string) ($qc['queue'] ?? ''),
            'critical_fail_keys' => $qc['critical_fail_keys'] ?? [],
            'editor_note' => (string) ($qc['editor_note'] ?? ''),
        ];

        $durumLabel = $qcPass ? 'durum=1 editor_review' : 'durum=2 needs_revision';
        br_log("[OK] id=$id {$durumLabel}, kelime≈$wc, kalite=$quality" . ($newTitle ? ', başlık güncellendi' : ''));
        br_bump_quota($conn, 1, $ignoreQuota);
    }

    if ($dryRun) {
        br_log('[DRY-RUN] API çağrısı yapılmadı.');
    } else {
        br_log("=== Bitti. İşlenen: $processed | QC pass: $qcPassed | QC fail: $qcFailed ===");
    }

    $logs = $GLOBALS['mynak_br_log_buffer'];

    return [
        'success' => true,
        'fatal' => null,
        'exit_code' => 0,
        'processed' => $processed,
        'candidates' => count($rows),
        'dry_run' => $dryRun,
        'logs' => $logs,
        'qc_passed' => $qcPassed,
        'qc_failed' => $qcFailed,
        'qc_reports' => $qcReports,
        'bucket_gaps' => $bucketGaps,
        'test_plan_ready' => $testReady,
        'production_test' => $productionTest,
    ];
}
